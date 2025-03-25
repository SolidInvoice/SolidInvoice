#!/bin/bash

set -euxo pipefail

# Extract version from milestone title
VERSION="$1"  # e.g. "2.3.1"
REPOSITORY="$2"  # e.g. "2.3.1"
MAJOR="$(echo "$VERSION" | cut -d '.' -f1)"    # e.g. "2"
MINOR="$(echo "$VERSION" | cut -d '.' -f2)"    # e.g. "3"
PATCH="$(echo "$VERSION" | cut -d '.' -f3)"    # e.g. "1"

# Identify release type
if [ "$PATCH" != "0" ]; then
  # PATCH RELEASE
  FROM_BRANCH="${MAJOR}.${MINOR}.x"
  TAG="$VERSION"
  NEXT_MINOR_BRANCH="${MAJOR}.$((MINOR+1)).x"
  MERGE_UP_BRANCH="merge-up/${VERSION}-to-${NEXT_MINOR_BRANCH}"

  echo "==> PATCH release: Creating tag $TAG from $FROM_BRANCH, merging up to $NEXT_MINOR_BRANCH"
  gh release create "$TAG" --generate-notes -t "Release $TAG" --discussion-category releases --target "$FROM_BRANCH"

  # Create dedicated merge-up branch
  gh api --method POST repos/"${REPOSITORY}"/git/refs \
    -f ref="refs/heads/${MERGE_UP_BRANCH}" \
    -f sha="$(gh api repos/"${REPOSITORY}"/git/refs/heads/"${FROM_BRANCH}" --jq '.object.sha')"

  # Create merge-up PR
  gh pr create \
    --title "Merge $FROM_BRANCH to $NEXT_MINOR_BRANCH" \
    --body "Merge up for patch release" \
    --base "$NEXT_MINOR_BRANCH" \
    --head "$MERGE_UP_BRANCH"

elif [ "$PATCH" = "0" ] && [ "$MINOR" != "0" ]; then
  # MINOR RELEASE
  TAG="$VERSION"
  #CURRENT_BRANCH="${MAJOR}.${MINOR}.x"
  CURRENT_BRANCH="frankenphp" # @TODO: Change to the correct branch after testing
  NEW_BRANCH="${MAJOR}.$((MINOR+1)).x"
  NEXT_MINOR_RELEASE="${MAJOR}.$((MINOR+1)).0" # "3.1.x"

  echo "==> MINOR release: Creating tag ${TAG} from $CURRENT_BRANCH, new branch $NEW_BRANCH, set default"

  gh release create "${TAG}" --generate-notes -t "Release ${TAG}" --discussion-category releases --target "${CURRENT_BRANCH}"

  gh api --method POST repos/"${REPOSITORY}"/git/refs \
    -f ref="refs/heads/${NEW_BRANCH}" \
    -f sha="$(gh api repos/"${REPOSITORY}"/git/refs/heads/${CURRENT_BRANCH} --jq '.object.sha')"

  gh repo edit "${REPOSITORY}" --default-branch "${NEW_BRANCH}"

  gh api \
    --method POST \
    repos/"${REPOSITORY}"/milestones \
    -f title="${NEXT_MINOR_RELEASE}" \
    -f state='open' \

  ./scripts/bump_version_dev.sh "${NEXT_MINOR_RELEASE}"-dev

else
  # MAJOR RELEASE
  # e.g. version "3.0.0" => Current major branch "3.0.x"
  CUR_MAJOR_BRANCH="${MAJOR}.${MINOR}.x" # "3.0.x"
  NEXT_MINOR_BRANCH="${MAJOR}.$((MINOR+1)).x" # "3.1.x"
  NEXT_MINOR_RELEASE="${MAJOR}.$((MINOR+1)).0" # "3.1.x"

  echo "==> MAJOR release: Tag from $CUR_MAJOR_BRANCH, create $NEXT_MINOR_BRANCH, set default to $NEXT_MINOR_BRANCH"

  gh release create "${TAG}" --generate-notes -t "Release ${TAG}" --discussion-category releases --target "${CUR_MAJOR_BRANCH}"

  gh api --method POST repos/"${REPOSITORY}"/git/refs \
    -f ref="refs/heads/${NEXT_MINOR_BRANCH}" \
    -f sha="$(gh api repos/"${REPOSITORY}"/git/refs/heads/"${CUR_MAJOR_BRANCH}" --jq '.object.sha')"

  gh repo edit "${REPOSITORY}" --default-branch "${NEXT_MINOR_BRANCH}"

  gh api \
    --method POST \
    repos/"${REPOSITORY}"/milestones \
    -f title="${NEXT_MINOR_RELEASE}" \
    -f state='open' \

  ./scripts/bump_version_dev.sh "${NEXT_MINOR_RELEASE}"-dev
fi
