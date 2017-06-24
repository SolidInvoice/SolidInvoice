#!/bin/bash

./bin/behat --suite=installation -n -f progress -p "$TEST_SUITE" -vvv
./bin/behat --suite=login -n -f progress -p "$TEST_SUITE" -vvv
./bin/behat --suite=api -n -f progress -p "$TEST_SUITE" -vvv