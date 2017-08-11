#!/bin/bash

./bin/behat --suite=installation -n -f progress -p "$TEST_SUITE"

shopt -s dotglob
shopt -s nullglob

find features/* -prune -type d | while read -r d; do
    if [[ "$d" == "features/installation" ]]; then
        continue
    fi

    SUITE=$(echo "$d"| cut -d'/' -f 2)

    find "$d" -name "*.feature" -prune -type f | while read -r t; do
        echo "Running feature $t with suite \"$SUITE\" and profile \"$TEST_SUITE\""
        ./bin/behat -s "$SUITE" -n -f progress -p "$TEST_SUITE" "$t" --strict

        if [ "$?" != 0 ]; then
            exit 1
        fi
    done
done

#./bin/behat --suite=installation -n -f progress -p "$TEST_SUITE"
#./bin/behat --suite=login -n -f progress -p "$TEST_SUITE"

#./bin/behat --suite=api -n -f progress -p "$TEST_SUITE"
#./bin/behat --suite=api -n -f progress -p "$TEST_SUITE" --tags=client
#./bin/behat --suite=api -n -f progress -p "$TEST_SUITE" --tags=contact