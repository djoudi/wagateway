#!/bin/sh
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
export HOME="${HOME:-/root}"
export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"
cd /var/www/html || exit 1
echo "run-artisan: /usr/local/bin/php artisan $*"
exec /usr/local/bin/php /var/www/html/artisan "$@"
