#!/bin/sh
set -eu

repository_url="${WALLOS_REPOSITORY_URL:-https://raw.githubusercontent.com/HuNiu-rgp/wallos/main}"

if ! command -v curl >/dev/null 2>&1; then
    printf 'curl is required to install Wallos.\n' >&2
    exit 1
fi

curl --fail --location --silent --show-error \
    --output docker-compose.yml \
    "$repository_url/docker-compose.yml"

if [ ! -f .env.docker ]; then
    curl --fail --location --silent --show-error \
        --output .env.docker \
        "$repository_url/.env.docker"
else
    printf 'Keeping the existing .env.docker configuration.\n'
fi

printf '\nWallos Docker files have been downloaded.\n'
printf 'Review .env.docker if needed, then start Wallos with:\n\n'
printf '  docker compose --env-file .env.docker up -d\n\n'
