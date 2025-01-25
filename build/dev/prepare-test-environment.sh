#!/bin/bash

if [ "$BASH" = "" ]; then echo "Error: you are not running this script within the bash."; exit 1; fi
if [ ! -x "$(command -v docker)" ]; then echo "Error: docker is not installed."; exit 1; fi

docker exec -it pseudify bash -c "
    bin/simple-phpunit --version \
    && export PHPUNIT_VERSION=\$(bin/simple-phpunit --version | grep -Eo '([0-9]{1,}\\.)+[0-9]{1,}') \
    && cd vendor/bin/.phpunit/phpunit \
    && COMPOSER_ROOT_VERSION=\$PHPUNIT_VERSION composer require --update-no-dev --no-progress phpspec/prophecy-phpunit:^2.0 nimut/phpunit-merger --ignore-platform-req php --with-all-dependencies \
"
