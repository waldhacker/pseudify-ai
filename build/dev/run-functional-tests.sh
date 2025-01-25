#!/bin/bash

if [ "$BASH" = "" ]; then echo "Error: you are not running this script within the bash."; exit 1; fi
if [ ! -x "$(command -v docker)" ]; then echo "Error: docker is not installed."; exit 1; fi

_THIS_SCRIPT_REAL_PATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

mkdir -p "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test"
rm -rf "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/"

# ---

echo -e '\n[TEST]: mysql:5.5:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/5.5/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mysql:5.6:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/5.6/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mysql:5.7:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/5.7/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mysql:8.0:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/8.0/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mysql:8.0:utf8\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/8.0/.env.utf8" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mysql:8.0:iso8859\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/8.0/.env.iso8859" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mysql:8.0:cp1252\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/8.0/.env.cp1252" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mysql:8.1:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/8.1/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mysql:8.1:utf8\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/8.1/.env.utf8" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mysql:8.1:iso8859\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/8.1/.env.iso8859" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mysql:8.1:cp1252\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/8.1/.env.cp1252" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mysql:8.2:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/8.2/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mysql:8.2:utf8\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/8.2/.env.utf8" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mysql:8.2:iso8859\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/8.2/.env.iso8859" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mysql:8.2:cp1252\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/8.2/.env.cp1252" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mysql:8.3:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/8.3/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mysql:8.3:utf8\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/8.3/.env.utf8" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mysql:8.3:iso8859\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/8.3/.env.iso8859" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mysql:8.3:cp1252\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mysql/8.3/.env.cp1252" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

# ---

echo -e '\n[TEST]: mariadb:10.2:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mariadb/10.2/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mariadb:10.3:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mariadb/10.3/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mariadb:10.4:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mariadb/10.4/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mariadb:10.5:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mariadb/10.5/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mariadb:10.6:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mariadb/10.6/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mariadb:10.7:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mariadb/10.7/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mariadb:10.8:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mariadb/10.8/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mariadb:10.9:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mariadb/10.9/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mariadb:10.10:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mariadb/10.10/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mariadb:10.11:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mariadb/10.11/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mariadb:11.0:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mariadb/11.0/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mariadb:11.1:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mariadb/11.1/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mariadb:11.2:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mariadb/11.2/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mariadb:11.3:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mariadb/11.3/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mariadb:11.4:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mariadb/11.4/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mariadb:11.5:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mariadb/11.5/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mariadb:11.6:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mariadb/11.6/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mariadb:11.7:utf8mb4\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mariadb/11.7/.env.utf8mb4" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

# ---

echo -e '\n[TEST]: postgres:9:utf8\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/postgres/9/.env.utf8" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: postgres:10:utf8\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/postgres/10/.env.utf8" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: postgres:11:utf8\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/postgres/11/.env.utf8" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: postgres:12:utf8\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/postgres/12/.env.utf8" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: postgres:13:utf8\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/postgres/13/.env.utf8" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: postgres:14:utf8\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/postgres/14/.env.utf8" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: postgres:15:utf8\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/postgres/15/.env.utf8" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: postgres:16:utf8\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/postgres/16/.env.utf8" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: postgres:17:utf8\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/postgres/17/.env.utf8" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

# ---

#echo -e '\n[TEST]: mssql:2017:utf8\n'
#cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mssql/2017/.env.utf8" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
#docker exec -it pseudify bin/simple-phpunit --testsuite application
#rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mssql:2019:utf8\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mssql/2019/.env.utf8" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

echo -e '\n[TEST]: mssql:2022:utf8\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/mssql/2022/.env.utf8" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

# ---

echo -e '\n[TEST]: sqlite:3:utf8\n'
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/sqlite/.env.utf8" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/.env"
cp "$_THIS_SCRIPT_REAL_PATH/../../tests/sqlite/pseudify_utf8.sqlite3" "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test/pseudify_utf8.sqlite3"
docker exec -it pseudify bin/simple-phpunit --testsuite application
rm -f "$_THIS_SCRIPT_REAL_PATH/../../userdata/var/log/pseudify_dry_run.log"

# ---

rm -rf "$_THIS_SCRIPT_REAL_PATH/../../src/tests/.test"
