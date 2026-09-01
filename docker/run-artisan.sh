#!/bin/sh
set -eu
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
export HOME="${HOME:-/home/wagateway}"
cd /var/www/html
echo "artisan: php artisan $*"
exec /usr/local/bin/php /var/www/html/artisan "$@"
