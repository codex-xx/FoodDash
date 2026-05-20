#!/bin/sh
set -e

# If AIVEN_CA_CERT env var is provided, write it to the system CA store
if [ -n "\${AIVEN_CA_CERT:-}" ]; then
  echo "Writing AIVEN CA cert to /usr/local/share/ca-certificates/aiven-ca.crt"
  mkdir -p /usr/local/share/ca-certificates
  printf '%s' "\${AIVEN_CA_CERT}" > /usr/local/share/ca-certificates/aiven-ca.crt
  update-ca-certificates || true
fi

# Exec the container default command
exec "$@"
