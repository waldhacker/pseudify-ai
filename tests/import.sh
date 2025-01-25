#!/bin/bash

if [ "$BASH" = "" ]; then echo "Error: you are not running this script within the bash."; exit 1; fi
if [ ! -x "$(command -v docker)" ]; then echo "Error: docker is not installed."; exit 1; fi

# cd .. && docker compose -f docker-compose.yml -f docker-compose.llm-addon.yml -f docker-compose.dev.yml up

docker exec -it pseudify_mysql_5_5 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mysql_5_5 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mysql_5_6 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mysql_5_6 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mysql_5_7 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mysql_5_7 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mysql_8_0 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mysql_8_0 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'
docker exec -it pseudify_mysql_8_0 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8 < /data/data_pseudify_utf8.sql'
docker exec -it pseudify_mysql_8_0 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_iso8859_2 < /data/data_pseudify_iso8859_2.sql'
docker exec -it pseudify_mysql_8_0 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_cp1252 < /data/data_pseudify_cp1252.sql'

docker exec -it pseudify_mysql_8_1 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mysql_8_1 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'
docker exec -it pseudify_mysql_8_1 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8 < /data/data_pseudify_utf8.sql'
docker exec -it pseudify_mysql_8_1 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_iso8859_2 < /data/data_pseudify_iso8859_2.sql'
docker exec -it pseudify_mysql_8_1 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_cp1252 < /data/data_pseudify_cp1252.sql'

docker exec -it pseudify_mysql_8_2 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mysql_8_2 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'
docker exec -it pseudify_mysql_8_2 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8 < /data/data_pseudify_utf8.sql'
docker exec -it pseudify_mysql_8_2 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_iso8859_2 < /data/data_pseudify_iso8859_2.sql'
docker exec -it pseudify_mysql_8_2 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_cp1252 < /data/data_pseudify_cp1252.sql'

docker exec -it pseudify_mysql_8_3 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mysql_8_3 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'
docker exec -it pseudify_mysql_8_3 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8 < /data/data_pseudify_utf8.sql'
docker exec -it pseudify_mysql_8_3 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_iso8859_2 < /data/data_pseudify_iso8859_2.sql'
docker exec -it pseudify_mysql_8_3 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_cp1252 < /data/data_pseudify_cp1252.sql'

docker exec -it pseudify_mysql_8_4 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mysql_8_4 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'
docker exec -it pseudify_mysql_8_4 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8 < /data/data_pseudify_utf8.sql'
docker exec -it pseudify_mysql_8_4 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_iso8859_2 < /data/data_pseudify_iso8859_2.sql'
docker exec -it pseudify_mysql_8_4 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_cp1252 < /data/data_pseudify_cp1252.sql'

docker exec -it pseudify_mysql_9_0 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mysql_9_0 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'
docker exec -it pseudify_mysql_9_0 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8 < /data/data_pseudify_utf8.sql'
docker exec -it pseudify_mysql_9_0 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_iso8859_2 < /data/data_pseudify_iso8859_2.sql'
docker exec -it pseudify_mysql_9_0 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_cp1252 < /data/data_pseudify_cp1252.sql'

docker exec -it pseudify_mysql_9_1 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mysql_9_1 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'
docker exec -it pseudify_mysql_9_1 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8 < /data/data_pseudify_utf8.sql'
docker exec -it pseudify_mysql_9_1 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_iso8859_2 < /data/data_pseudify_iso8859_2.sql'
docker exec -it pseudify_mysql_9_1 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_cp1252 < /data/data_pseudify_cp1252.sql'

# ---

docker exec -it pseudify_mariadb_10_2 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mariadb_10_2 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mariadb_10_3 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mariadb_10_3 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mariadb_10_4 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mariadb_10_4 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mariadb_10_5 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mariadb_10_5 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mariadb_10_6 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mariadb_10_6 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mariadb_10_7 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mariadb_10_7 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mariadb_10_8 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mariadb_10_8 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mariadb_10_9 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mariadb_10_9 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mariadb_10_10 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mariadb_10_10 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mariadb_10_11 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mariadb_10_11 bash -c 'mysql -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mariadb_11_0 bash -c 'mariadb -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mariadb_11_0 bash -c 'mariadb -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mariadb_11_1 bash -c 'mariadb -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mariadb_11_1 bash -c 'mariadb -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mariadb_11_2 bash -c 'mariadb -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mariadb_11_2 bash -c 'mariadb -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mariadb_11_3 bash -c 'mariadb -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mariadb_11_3 bash -c 'mariadb -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mariadb_11_4 bash -c 'mariadb -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mariadb_11_4 bash -c 'mariadb -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mariadb_11_5 bash -c 'mariadb -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mariadb_11_5 bash -c 'mariadb -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mariadb_11_6 bash -c 'mariadb -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mariadb_11_6 bash -c 'mariadb -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

docker exec -it pseudify_mariadb_11_7 bash -c 'mariadb -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" < /data/init.sql'
docker exec -it pseudify_mariadb_11_7 bash -c 'mariadb -h localhost -uroot -p"P53ud1fy(!)w4ldh4ck3r" pseudify_utf8mb4 < /data/data_pseudify_utf8mb4.sql'

# ---

docker exec -it pseudify_postgres_9 bash -c 'psql -h localhost -U postgres < /data/init.sql'
docker exec -it pseudify_postgres_9 bash -c 'psql -h localhost -U pseudify -d pseudify_utf8 < /data/data_pseudify_utf8.sql'

docker exec -it pseudify_postgres_10 bash -c 'psql -h localhost -U postgres < /data/init.sql'
docker exec -it pseudify_postgres_10 bash -c 'psql -h localhost -U pseudify -d pseudify_utf8 < /data/data_pseudify_utf8.sql'

docker exec -it pseudify_postgres_11 bash -c 'psql -h localhost -U postgres < /data/init.sql'
docker exec -it pseudify_postgres_11 bash -c 'psql -h localhost -U pseudify -d pseudify_utf8 < /data/data_pseudify_utf8.sql'

docker exec -it pseudify_postgres_12 bash -c 'psql -h localhost -U postgres < /data/init.sql'
docker exec -it pseudify_postgres_12 bash -c 'psql -h localhost -U pseudify -d pseudify_utf8 < /data/data_pseudify_utf8.sql'

docker exec -it pseudify_postgres_13 bash -c 'psql -h localhost -U postgres < /data/init.sql'
docker exec -it pseudify_postgres_13 bash -c 'psql -h localhost -U pseudify -d pseudify_utf8 < /data/data_pseudify_utf8.sql'

docker exec -it pseudify_postgres_14 bash -c 'psql -h localhost -U postgres < /data/init.sql'
docker exec -it pseudify_postgres_14 bash -c 'psql -h localhost -U pseudify -d pseudify_utf8 < /data/data_pseudify_utf8.sql'

docker exec -it pseudify_postgres_15 bash -c 'psql -h localhost -U postgres < /data/init.sql'
docker exec -it pseudify_postgres_15 bash -c 'psql -h localhost -U pseudify -d pseudify_utf8 < /data/data_pseudify_utf8.sql'

docker exec -it pseudify_postgres_16 bash -c 'psql -h localhost -U postgres < /data/init.sql'
docker exec -it pseudify_postgres_16 bash -c 'psql -h localhost -U pseudify -d pseudify_utf8 < /data/data_pseudify_utf8.sql'

docker exec -it pseudify_postgres_17 bash -c 'psql -h localhost -U postgres < /data/init.sql'
docker exec -it pseudify_postgres_17 bash -c 'psql -h localhost -U pseudify -d pseudify_utf8 < /data/data_pseudify_utf8.sql'

# ---

#docker exec -it pseudify_mssql_2017 bash -c '/opt/mssql-tools18/bin/sqlcmd -C -S localhost -U sa -P "P53ud1fy(!)w4ldh4ck3r" -i /data/init.sql'
#docker exec -it pseudify_mssql_2017 bash -c '/opt/mssql-tools18/bin/sqlcmd -C -S localhost -U pseudify -P "P53ud1fy(!)w4ldh4ck3r" -d pseudify -i /data/data_pseudify.sql'

docker exec -it pseudify_mssql_2019 bash -c '/opt/mssql-tools18/bin/sqlcmd -C -S localhost -U sa -P "P53ud1fy(!)w4ldh4ck3r" -i /data/init.sql'
docker exec -it pseudify_mssql_2019 bash -c '/opt/mssql-tools18/bin/sqlcmd -C -S localhost -U pseudify -P "P53ud1fy(!)w4ldh4ck3r" -d pseudify -i /data/data_pseudify.sql'

docker exec -it pseudify_mssql_2022 bash -c '/opt/mssql-tools18/bin/sqlcmd -C -S localhost -U sa -P "P53ud1fy(!)w4ldh4ck3r" -i /data/init.sql'
docker exec -it pseudify_mssql_2022 bash -c '/opt/mssql-tools18/bin/sqlcmd -C -S localhost -U pseudify -P "P53ud1fy(!)w4ldh4ck3r" -d pseudify -i /data/data_pseudify.sql'
