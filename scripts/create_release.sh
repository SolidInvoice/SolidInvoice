#!/bin/bash

set -euo pipefail

# Extract version components
VERSION="$1"    # e.g. "2.3.14"
REPOSITORY="$2" # e.g. "SolidInvoice/SolidInvoice"
MAJOR="$(echo "$VERSION" | cut -d '.' -f1)"
MINOR="$(echo "$VERSION" | cut -d '.' -f2)"
PATCH="$(echo "$VERSION" | cut -d '.' -f3)"

gh repo set-default "${REPOSITORY}"

# ---------------------------------------------------------------------------
# create_merge_up_pr FROM TARGET BRANCH VERSION
#
# Creates a merge-up branch from TARGET (not FROM), merges FROM into it via
# the GitHub API, then opens a PR. This means:
#   - The PR diff is minimal (just the merge commit + new commits from FROM)
#   - GitHub can merge it with a simple "Create a merge commit" — no rebase needed
#   - Old commits already present in TARGET are never re-introduced
#
# Conflict handling: if the GitHub API reports a conflict (HTTP 409), the
# empty branch is deleted and clear local-resolution instructions are printed.
# ---------------------------------------------------------------------------
create_merge_up_pr() {
  local FROM="$1"    # source branch, e.g. "2.3.x"
  local TARGET="$2"  # target branch, e.g. "2.4.x"
  local BRANCH="$3"  # name for the merge-up branch
  local RELEASE="$4" # release version, used in PR body

  echo ""
  echo "==> Merge-up: ${FROM} → ${TARGET}"
  echo "==> Checking for commits not yet in ${TARGET}..."

  local AHEAD
  AHEAD=$(gh api "repos/${REPOSITORY}/compare/${TARGET}...${FROM}" --jq '.ahead_by')

  if [ "${AHEAD}" -eq 0 ]; then
    echo "==> Nothing to do: ${FROM} is already fully merged into ${TARGET}"
    return 0
  fi

  echo "==> ${AHEAD} commit(s) to merge from ${FROM} into ${TARGET}"

  # Bail out if the merge-up branch already exists — avoids accidentally
  # overwriting an in-progress resolution from a previous run.
  if gh api "repos/${REPOSITORY}/git/refs/heads/${BRANCH}" >/dev/null 2>&1; then
    echo ""
    echo "⚠️  Branch '${BRANCH}' already exists on the remote."
    echo "   If you want to recreate it, delete it first:"
    echo "     git push origin --delete ${BRANCH}"
    return 1
  fi

  # Create the merge-up branch from TARGET tip — this is the key difference
  # from the old approach. The branch starts identical to TARGET, so the PR
  # will only show what the merge brings in, not the entire source history.
  local TARGET_SHA
  TARGET_SHA=$(gh api "repos/${REPOSITORY}/git/refs/heads/${TARGET}" --jq '.object.sha')

  echo "==> Creating '${BRANCH}' from ${TARGET} (${TARGET_SHA:0:7})..."
  gh api --method POST "repos/${REPOSITORY}/git/refs" \
    -f "ref=refs/heads/${BRANCH}" \
    -f "sha=${TARGET_SHA}"

  # Ask GitHub to merge FROM into the merge-up branch. This is a server-side
  # merge, so no local checkout is required. On conflict (HTTP 409) gh exits
  # non-zero; we catch that and give the developer local-resolution steps.
  echo "==> Merging ${FROM} into ${BRANCH} via GitHub API..."
  if gh api --method POST "repos/${REPOSITORY}/merges" \
      -f "base=${BRANCH}" \
      -f "head=${FROM}" \
      -f "commit_message=Merge branch '${FROM}' into ${TARGET}" \
      >/dev/null; then
    echo "==> Merge successful"
  else
    echo ""
    echo "⚠️  Merge conflict: ${FROM} cannot be automatically merged into ${TARGET}."
    echo "   The remote branch '${BRANCH}' has been removed."
    echo ""
    echo "   Resolve locally, then push:"
    echo ""
    echo "     git fetch origin"
    echo "     git checkout -b ${BRANCH} origin/${TARGET}"
    echo "     git merge --no-ff origin/${FROM}"
    echo "     # Fix conflicts, stage, commit, then:"
    echo "     git push origin ${BRANCH}"
    echo ""
    echo "   Once pushed, create the PR with:"
    echo "     gh pr create \\"
    echo "       --title 'Merge ${FROM} to ${TARGET}' \\"
    echo "       --base '${TARGET}' \\"
    echo "       --head '${BRANCH}'"
    echo ""
    echo "   When merging the PR, use 'Create a merge commit' — NOT squash or rebase."
    echo ""
    # Clean up the empty branch we created so it doesn't cause confusion
    gh api --method DELETE "repos/${REPOSITORY}/git/refs/heads/${BRANCH}" || true
    return 1
  fi

  # Build a commit summary for the PR body (capped at 30 entries)
  local COMMITS_LIST
  COMMITS_LIST=$(gh api "repos/${REPOSITORY}/compare/${TARGET}...${FROM}" \
    --jq '[.commits[] | "- `\(.sha[:7])` \(.commit.message | split("\n")[0])"] | .[0:30] | join("\n")' \
    2>/dev/null || echo "_Commit list unavailable_")

  # Check whether a PR for this branch already exists
  local EXISTING_PR
  EXISTING_PR=$(gh pr list \
    --head "${BRANCH}" \
    --base "${TARGET}" \
    --json number \
    --jq '.[0].number // empty' 2>/dev/null || true)

  if [ -n "${EXISTING_PR}" ]; then
    echo "==> PR #${EXISTING_PR} already exists for ${BRANCH} → ${TARGET}"
    return 0
  fi

  echo "==> Opening PR: ${BRANCH} → ${TARGET}..."
  gh pr create \
    --title "Merge ${FROM} to ${TARGET}" \
    --label "merge-up" \
    --body "$(cat <<EOF
Merge-up for release **${RELEASE}**: brings ${AHEAD} commit(s) from \`${FROM}\` into \`${TARGET}\`.

## Commits included

${COMMITS_LIST}

---

> **Merge instructions:** use **"Create a merge commit"** when merging this PR.
> Squash and rebase are disabled for merge-up PRs — squashing loses the individual
> commit history from \`${FROM}\`, and rebasing fails due to branch divergence.
EOF
)" \
    --base "${TARGET}" \
    --head "${BRANCH}"
}

# ---------------------------------------------------------------------------

if [ "$PATCH" != "0" ]; then
  # PATCH RELEASE — e.g. 2.3.14
  FROM_BRANCH="${MAJOR}.${MINOR}.x"
  TAG="$VERSION"
  NEXT_MINOR_BRANCH="${MAJOR}.$((MINOR+1)).x"
  MERGE_UP_BRANCH="merge-up/${VERSION}-to-${NEXT_MINOR_BRANCH}"
  NEXT_PATCH_RELEASE="${MAJOR}.${MINOR}.$((PATCH+1))"

  echo "==> PATCH release: tagging ${TAG} from ${FROM_BRANCH}, merging up to ${NEXT_MINOR_BRANCH}"

  gh release create "$TAG" \
    -t "Release $TAG" \
    --discussion-category "Releases" \
    --target "$FROM_BRANCH" \
    --generate-notes

  create_merge_up_pr "${FROM_BRANCH}" "${NEXT_MINOR_BRANCH}" "${MERGE_UP_BRANCH}" "${VERSION}"

  # Open a milestone for the next patch so issues can be triaged immediately
  gh api \
    --method POST \
    "repos/${REPOSITORY}/milestones" \
    -f "title=${NEXT_PATCH_RELEASE}" \
    -f "state=open"

elif [ "$PATCH" = "0" ] && [ "$MINOR" != "0" ]; then
  # MINOR RELEASE — e.g. 2.4.0
  TAG="$VERSION"
  CURRENT_BRANCH="${MAJOR}.${MINOR}.x"
  NEW_BRANCH="${MAJOR}.$((MINOR+1)).x"
  NEXT_MINOR_RELEASE="${MAJOR}.$((MINOR+1)).0"
  NEXT_PATCH_RELEASE="${MAJOR}.${MINOR}.1"

  echo "==> MINOR release: tagging ${TAG} from ${CURRENT_BRANCH}, creating ${NEW_BRANCH}, setting as default"

  gh release create "${TAG}" \
    -t "Release ${TAG}" \
    --discussion-category "Releases" \
    --target "${CURRENT_BRANCH}" \
    --generate-notes

  gh api --method POST "repos/${REPOSITORY}/git/refs" \
    -f "ref=refs/heads/${NEW_BRANCH}" \
    -f "sha=$(gh api "repos/${REPOSITORY}/git/refs/heads/${CURRENT_BRANCH}" --jq '.object.sha')"

  gh repo edit "${REPOSITORY}" --default-branch "${NEW_BRANCH}"

  gh api \
    --method POST \
    "repos/${REPOSITORY}/milestones" \
    -f "title=${NEXT_MINOR_RELEASE}" \
    -f "state=open"

  gh api \
    --method POST \
    "repos/${REPOSITORY}/milestones" \
    -f "title=${NEXT_PATCH_RELEASE}" \
    -f "state=open"

else
  # MAJOR RELEASE — e.g. 3.0.0
  TAG="$VERSION"
  CUR_MAJOR_BRANCH="${MAJOR}.${MINOR}.x"
  NEXT_MINOR_BRANCH="${MAJOR}.$((MINOR+1)).x"
  NEXT_MINOR_RELEASE="${MAJOR}.$((MINOR+1)).0"
  NEXT_PATCH_RELEASE="${MAJOR}.${MINOR}.1"

  echo "==> MAJOR release: tagging ${TAG} from ${CUR_MAJOR_BRANCH}, creating ${NEXT_MINOR_BRANCH}, setting as default"

  gh release create "${TAG}" \
    -t "Release ${TAG}" \
    --discussion-category "Releases" \
    --target "${CUR_MAJOR_BRANCH}" \
    --generate-notes

  gh api --method POST "repos/${REPOSITORY}/git/refs" \
    -f "ref=refs/heads/${NEXT_MINOR_BRANCH}" \
    -f "sha=$(gh api "repos/${REPOSITORY}/git/refs/heads/${CUR_MAJOR_BRANCH}" --jq '.object.sha')"

  gh repo edit "${REPOSITORY}" --default-branch "${NEXT_MINOR_BRANCH}"

  gh api \
    --method POST \
    "repos/${REPOSITORY}/milestones" \
    -f "title=${NEXT_MINOR_RELEASE}" \
    -f "state=open"

  gh api \
    --method POST \
    "repos/${REPOSITORY}/milestones" \
    -f "title=${NEXT_PATCH_RELEASE}" \
    -f "state=open"
fi
