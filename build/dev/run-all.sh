#!/bin/bash

if [ "$BASH" = "" ]; then echo "Error: you are not running this script within the bash."; exit 1; fi
if [ ! -x "$(command -v docker)" ]; then echo "Error: docker is not installed."; exit 1; fi

_THIS_SCRIPT_REAL_PATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

. "$_THIS_SCRIPT_REAL_PATH/install.sh"
. "$_THIS_SCRIPT_REAL_PATH/prepare-test-environment.sh"
. "$_THIS_SCRIPT_REAL_PATH/../../tests/import.sh"
. "$_THIS_SCRIPT_REAL_PATH/compile-assets.sh"
. "$_THIS_SCRIPT_REAL_PATH/analyze-code.sh"
. "$_THIS_SCRIPT_REAL_PATH/run-unit-tests.sh"
. "$_THIS_SCRIPT_REAL_PATH/run-functional-tests.sh"
