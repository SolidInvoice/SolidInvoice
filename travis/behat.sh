#!/bin/bash

set -e
stty cols 120
shopt -s dotglob
shopt -s nullglob

# nanoseconds and tfold functions has been adapted from Symfony Travis config
nanoseconds () {
  local cmd="date"
  local format="+%s%N"
  local os=$(uname)
  if hash gdate > /dev/null 2>&1; then
    cmd="gdate"
  elif [[ "$os" = Darwin ]]; then
    format="+%s000000000"
  fi
  $cmd -u $format
}
export -f nanoseconds

tfold () {
  local title="$1"
  local fold=$(echo $title | sed -r 's/[^-_A-Za-z0-9]+/./g')
  shift
  local id=$(printf %08x $(( RANDOM * RANDOM )))
  local start=$(nanoseconds)
  echo -e "travis_fold:start:$fold"
  echo -e "travis_time:start:$id"
  echo -e "\\e[1;34m$title\\e[0m"
  bash -xc "$*" 2>&1
  local ok=$?
  local end=$(nanoseconds)
  echo -e "\\ntravis_time:end:$id:start=$start,finish=$end,duration=$(($end-$start))"
  (exit $ok) &&
      echo -e "\\e[32mOK\\e[0m $title\\n\\ntravis_fold:end:$fold" ||
      echo -e "\\e[41mKO\\e[0m $title\\n"
  (exit $ok)
}
export -f tfold

tfold 'Installation' bin/behat -s installation -n -f progress -p ${TEST_SUITE} --strict

find features/* -prune -type d | while read -r d; do
    if [[ "$d" == "features/installation" ]]; then
        continue
    fi

    SUITE=$(echo "$d" | cut -d'/' -f 2)

    find "$d" -name "*.feature" -prune -type f | while read -r t; do
        tfold "$SUITE ($t)" bin/behat -s "$SUITE" -n -f progress -p "$TEST_SUITE" "$t" --strict
    done
done
