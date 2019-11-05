#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

composer --no-dev --no-interaction --quiet --optimize-autoloader --no-scripts

cp $DIR/parameters.yml.env.env $DIR/../app/config/parameters.yml

bunzip2 < $DIR/dump.sql.bz2 | mysql -h $DB_HOST -P $DB_PORT -p=$DB_PASSWORD $DB_NAME
