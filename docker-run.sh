#!/bin/bash

assure_link () {
  [[ -L "${2}" ]] \
    || ln -s "${1}" "${2}" \
    || exit 1
}

assure_folder () {
  mkdir -p "${1}" \
    && chown www-data:www-data "${1}" \
    || exit 1
}

dir=$(dirname "$(realpath "${0}")")
basedir=$(basename "${dir}")
port="${basedir##*-}"
[[ "${port}" =~ ^[0-9]+$ ]] \
  || port=80

log_dir=/var/log/${basedir}
build_dir=build
assure_folder "${build_dir}"
chown -R www-data:www-data "${build_dir}"
assure_folder "${log_dir}"
assure_link "${log_dir}" logs

PORT="${port}" docker-compose -f "${dir}/docker-compose.yml" up \
  -d --build --force-recreate

phpcont="$(docker ps | grep -o "${basedir//./}[_-]php[_-]1$")"
docker exec -t "${phpcont}" composer update -o

chmod 0644 logs/*
