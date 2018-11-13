#!/usr/bin/env bash

docker-compose run wordpress bash -c "cd /opt/project && phpunit --configuration phpunit-integration.xml --coverage-text --colors=never"

docker-compose run wordpress bash -c "cd /opt/project && phpunit --configuration phpunit-unit.xml --coverage-text --colors=never"
