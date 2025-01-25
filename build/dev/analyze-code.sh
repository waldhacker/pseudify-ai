#!/bin/bash

if [ "$BASH" = "" ]; then echo "Error: you are not running this script within the bash."; exit 1; fi
if [ ! -x "$(command -v docker)" ]; then echo "Error: docker is not installed."; exit 1; fi

docker exec -it pseudify composer update --lock
docker exec -it pseudify composer composer:normalize:fix
docker exec -it pseudify composer cgl:fix
docker exec -it pseudify composer rector
docker exec -it pseudify composer stan
docker exec -it pseudify composer psalm
