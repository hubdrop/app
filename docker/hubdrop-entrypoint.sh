#!/usr/bin/env bash

set -e

echo "HD || Running composer install ..."
cd /var/hubdrop/app && composer install

echo "HD || Running setfacl -dR -m u:www-data:rwX -m u:hubdrop:rwX /var/hubdrop/app/app/cache /var/hubdrop/app/app/logs ..."
setfacl -dR -m u:www-data:rwX -m u:hubdrop:rwX /var/hubdrop/app/app/cache /var/hubdrop/app/app/logs

# Launch apache2-foreground in a new process
echo "HD || Running apache2-foreground& ..."
sudo apache2-foreground&

# @TODO: Run the hubdrop:queue command
echo "HD || Running tail -f /var/hubdrop/app/app/logs/apache.log ..."
tail -f /var/hubdrop/app/app/logs/apache.log