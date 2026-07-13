#!/usr/bin/env bash
set -e

mysql_cmd=(mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" --default-character-set=utf8mb4)

"${mysql_cmd[@]}" <<SQL
CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE}\`.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;
SQL

for file in /docker-entrypoint-initdb.d/migrations/*.sql; do
    echo "Applying migration: ${file}"
    "${mysql_cmd[@]}" "${MYSQL_DATABASE}" < "${file}"
done

for file in /docker-entrypoint-initdb.d/seeders/*.sql; do
    echo "Applying seeder: ${file}"
    "${mysql_cmd[@]}" "${MYSQL_DATABASE}" < "${file}"
done
