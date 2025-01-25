#!/bin/bash

if [ "$BASH" = "" ]; then echo "Error: you are not running this script within the bash."; exit 1; fi
if [ ! -x "$(command -v docker)" ]; then echo "Error: docker is not installed."; exit 1; fi

_THIS_SCRIPT_REAL_PATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

rm -rf "$_THIS_SCRIPT_REAL_PATH/../../src/public/assets/"
docker exec -it pseudify bin/pseudify importmap:install
docker exec -it pseudify bin/pseudify asset-map:compile
