#!/bin/bash

# GitHub Actions setup for Git
if [ -n "$GITHUB_ACTIONS" ]; then
  git config --local user.email "action@github.com"
  git config --local user.name "GitHub Action"
  git remote set-url origin https://"${GITHUB_ACTOR}":"${GITHUB_TOKEN}"@github.com/"${GITHUB_REPOSITORY}".git
fi

# File path
FILE="./src/CoreBundle/SolidInvoiceCoreBundle.php"
PACKAGE_JSON="./package.json"

# Function to bump semver
bump_version() {
  local version=$1
  local field=$2
  local increment=$3

  IFS='.' read -ra parts <<< "$version"
  ((parts[field]+=increment))

  if [ "$field" -lt 2 ]; then
    for i in $(seq $((field+1)) 2); do
      parts[i]=0
    done
  fi

  echo "${parts[0]}.${parts[1]}.${parts[2]}"
}

# Read current version
current_version=$(awk -F\' '/public const VERSION/ {print $2}' $FILE)

# Remove '-dev' suffix if exists
clean_version="${current_version%-dev}"

# Bump to next dev version
dev_version=$(bump_version "$clean_version" 2 1)-dev

# Update file with next dev version
sed -i "s/public const VERSION = '.*';/public const VERSION = '$dev_version';/" $FILE
jq --arg version "$dev_version" '.version=$version' --indent 2 $PACKAGE_JSON > tmp.json && mv tmp.json $PACKAGE_JSON

git add $FILE $PACKAGE_JSON
git commit -m "Bump to dev version $dev_version"
git push
