#!/bin/bash

./bin/behat --suite=installation -n -f progress
./bin/behat --suite=login -n -f progress