#!/bin/bash

# **********************************************
# @version 0.0.1
# @author Ralf Zimmermann <hello@waldhacker.dev>
# **********************************************

if [ "$BASH" = "" ]; then echo "Error: you are not running this script within the bash."; exit 1; fi
if [ ! -x "$(command -v rsync)" ]; then echo "Error: rsync is not installed."; exit 1; fi
if [ ! -x "$(command -v tar)" ]; then echo "Error: rsync is not installed."; exit 1; fi

_THIS_SCRIPT_REAL_PATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
_ROOT_DIRECTORY=${_THIS_SCRIPT_REAL_PATH}/..

function prepare
{
    echo "[BUILD]: prepare"

    rm -rf "$_ROOT_DIRECTORY/.build/install-package/"
    mkdir -p "$_ROOT_DIRECTORY/.build/install-package/"
}

function build
{
    echo "[BUILD]: build"

    rsync -raq "$_ROOT_DIRECTORY/docker-compose.yml"                 "$_ROOT_DIRECTORY/.build/install-package/"
    rsync -raq "$_ROOT_DIRECTORY/docker-compose.database.yml"        "$_ROOT_DIRECTORY/.build/install-package/"
    rsync -raq "$_ROOT_DIRECTORY/docker-compose.llm-addon.yml"       "$_ROOT_DIRECTORY/.build/install-package/"
    rsync -raq --exclude="/userdata/var" "$_ROOT_DIRECTORY/userdata" "$_ROOT_DIRECTORY/.build/install-package/"
    rsync -raq "$_ROOT_DIRECTORY/LICENSE"                            "$_ROOT_DIRECTORY/.build/install-package/"
    rsync -raq "$_ROOT_DIRECTORY/README.md"                          "$_ROOT_DIRECTORY/.build/install-package/"
    mkdir -p                                                         "$_ROOT_DIRECTORY/.build/install-package/database-data/"
    rsync -raq "$_ROOT_DIRECTORY/build/install-package/example.sql"  "$_ROOT_DIRECTORY/.build/install-package/database-data/"

    tar -czf "$_ROOT_DIRECTORY/.build/install-package.tar.gz" -C "$_ROOT_DIRECTORY/.build/install-package/" .
}

function cleanup
{
    echo "[BUILD]: cleanup"

    rm -rf "$_ROOT_DIRECTORY/.build/install-package/"
}

function controller
{
    prepare
    build
    cleanup
}

controller

unset _THIS_SCRIPT_REAL_PATH
unset _ROOT_DIRECTORY
