#!/bin/bash

if [ "$BASH" = "" ]; then echo "Error: you are not running this script within the bash."; exit 1; fi
if [ ! -x "$(command -v docker)" ]; then echo "Error: docker is not installed."; exit 1; fi

docker exec -it pseudify composer install --optimize-autoloader --classmap-authoritative --no-interaction
docker exec --user 0:0 -it pseudify supervisorctl restart php-fpm
docker exec -it pseudify_ollama bash -c 'ollama pull $OLLAMA_MODEL'
