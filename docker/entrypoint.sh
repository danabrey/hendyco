#!/bin/sh
# Host-mounted storage (e.g. receipts on a Dokku volume) is owned by root, so hand it to the web user at startup.
mkdir -p /app/storage/app/private/receipts
chown -R www-data:www-data /app/storage/app/private 2>/dev/null || true

exec /usr/bin/supervisord -c /etc/supervisord.conf
