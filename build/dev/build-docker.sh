#!/bin/bash

# Build the pseudify docker container locally.
# If you change something here, you will probably want to change it there too: `../.github/workflows/build-and-release.yml`

# Example: `docker run --user 0:0 --entrypoint /bin/bash -it ghcr.io/waldhacker/pseudify-ai:latest`
# Example: `docker run -v "$(pwd)/userdata":/opt/pseudify/userdata -it ghcr.io/waldhacker/pseudify-ai:latest`
# Example: `docker run -v "$(pwd)/userdata":/opt/pseudify/userdata -v "$(pwd)/build/docker/entrypoint:/usr/local/bin/entrypoint" -it ghcr.io/waldhacker/pseudify-ai:latest`

if [ "$BASH" = "" ]; then echo "Error: you are not running this script within the bash."; exit 1; fi
if [ ! -x "$(command -v docker)" ]; then echo "Error: docker is not installed."; exit 1; fi

_THIS_SCRIPT_REAL_PATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
_ROOT_DIRECTORY="${_THIS_SCRIPT_REAL_PATH}/../../"
_IMAGE_NAMESPACE=${IMAGE_NAMESPACE:-ghcr.io/waldhacker/pseudify-ai}

function prepare
{
    echo "[BUILD]: prepare"

    mkdir -p "$_ROOT_DIRECTORY/.build/docker/context/core/src/bin/"
    mkdir -p "$_ROOT_DIRECTORY/.build/docker/context/userdata/"
}

function build
{
    # https://github.com/docker-library/docs/blob/master/php/README.md#supported-tags-and-respective-dockerfile-links
    local PHP_VERSION=8.3
    # https://learn.microsoft.com/de-de/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-ver15
    local MSODBC_SQL_APK_URI=https://download.microsoft.com/download/7/6/d/76de322a-d860-4894-9945-f0cc5d6a45f8/msodbcsql18_18.4.1.1-1_amd64.apk
    local MSSQL_TOOLS_APK_URI=https://download.microsoft.com/download/7/6/d/76de322a-d860-4894-9945-f0cc5d6a45f8/mssql-tools18_18.4.1.1-1_amd64.apk
    local MSODBC_SQL_SIG_URI=https://download.microsoft.com/download/7/6/d/76de322a-d860-4894-9945-f0cc5d6a45f8/msodbcsql18_18.4.1.1-1_amd64.sig
    local MSSQL_TOOLS_SIG_URI=https://download.microsoft.com/download/7/6/d/76de322a-d860-4894-9945-f0cc5d6a45f8/mssql-tools18_18.4.1.1-1_amd64.sig

    local BUILD_TAG=$(git rev-parse --short HEAD)

    echo "[BUILD]: build $BUILD_TAG"
    echo "[BUILD]: use PHP $PHP_VERSION"
    echo "[BUILD]: use Microsoft ODBC Driver $(basename $MSODBC_SQL_APK_URI .apk)"
    echo "[BUILD]: use Microsoft SQL Server Tools $(basename $MSSQL_TOOLS_APK_URI .apk)"

    rsync -raq "$_ROOT_DIRECTORY/build/docker/"         "$_ROOT_DIRECTORY/.build/docker/context/"

    rsync -raq "$_ROOT_DIRECTORY/src/assets/"           "$_ROOT_DIRECTORY/.build/docker/context/core/src/assets/"
    rsync -raq "$_ROOT_DIRECTORY/src/bin/pseudify"      "$_ROOT_DIRECTORY/.build/docker/context/core/src/bin/pseudify"
    rsync -raq "$_ROOT_DIRECTORY/src/config/"           "$_ROOT_DIRECTORY/.build/docker/context/core/src/config/"
    rsync -raq "$_ROOT_DIRECTORY/src/public/"           "$_ROOT_DIRECTORY/.build/docker/context/core/src/public/"
    rsync -raq "$_ROOT_DIRECTORY/src/src/"              "$_ROOT_DIRECTORY/.build/docker/context/core/src/src/"
    rsync -raq "$_ROOT_DIRECTORY/src/templates/"        "$_ROOT_DIRECTORY/.build/docker/context/core/src/templates/"
    rsync -raq "$_ROOT_DIRECTORY/src/translations/"     "$_ROOT_DIRECTORY/.build/docker/context/core/src/translations/"
    rsync -raq "$_ROOT_DIRECTORY/src/composer.json"     "$_ROOT_DIRECTORY/.build/docker/context/core/src/composer.json"
    rsync -raq "$_ROOT_DIRECTORY/src/composer.lock"     "$_ROOT_DIRECTORY/.build/docker/context/core/src/composer.lock"
    rsync -raq "$_ROOT_DIRECTORY/src/importmap.php"     "$_ROOT_DIRECTORY/.build/docker/context/core/src/importmap.php"
    rsync -raq "$_ROOT_DIRECTORY/src/symfony.lock"      "$_ROOT_DIRECTORY/.build/docker/context/core/src/symfony.lock"

    rsync -raq "$_ROOT_DIRECTORY/userdata/config/"      "$_ROOT_DIRECTORY/.build/docker/context/userdata/config/"
    rsync -raq "$_ROOT_DIRECTORY/userdata/src/"         "$_ROOT_DIRECTORY/.build/docker/context/userdata/src/"
    rsync -raq "$_ROOT_DIRECTORY/userdata/.env.example" "$_ROOT_DIRECTORY/.build/docker/context/userdata/.env.example"
    rsync -raq "$_ROOT_DIRECTORY/userdata/.env.example" "$_ROOT_DIRECTORY/.build/docker/context/userdata/.env"

    docker build \
        --pull \
        --no-cache \
        --build-arg PHP_VERSION=$PHP_VERSION \
        --build-arg MSODBC_SQL_APK_URI=$MSODBC_SQL_APK_URI \
        --build-arg MSSQL_TOOLS_APK_URI=$MSSQL_TOOLS_APK_URI \
        --build-arg MSODBC_SQL_SIG_URI=$MSODBC_SQL_SIG_URI \
        --build-arg MSSQL_TOOLS_SIG_URI=$MSSQL_TOOLS_SIG_URI \
        --build-arg BUILD_TAG=$BUILD_TAG \
        -t ${_IMAGE_NAMESPACE}:$BUILD_TAG \
        -t ${_IMAGE_NAMESPACE}:2.0 \
        -t ${_IMAGE_NAMESPACE}:latest \
        --file "$_ROOT_DIRECTORY/build/Dockerfile" \
        "$_ROOT_DIRECTORY/.build/docker/context"
}

function cleanup
{
    echo "[BUILD]: cleanup"

    rm -rf "$_ROOT_DIRECTORY/.build/docker/"
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
unset _IMAGE_NAMESPACE
