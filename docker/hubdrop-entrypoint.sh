#!/usr/bin/env bash

set -e

if [ ! -d /var/hubdrop/app ]; then
  echo "HD || Running 'git clone https://github.com/hubdrop/app.git /var/hubdrop/app' ..."
  git clone https://github.com/hubdrop/app.git /var/hubdrop/app
fi

echo "HD || Writing log files so they are owned by $USER"
if [ ! -f /var/hubdrop/app/app/logs/apache.log ]; then
  touch /var/hubdrop/app/app/logs/apache.log
  touch /var/hubdrop/app/app/logs/apache.error.log
  touch /var/hubdrop/app/app/logs/dev.log
  touch /var/hubdrop/app/app/logs/test.log
  touch /var/hubdrop/app/app/logs/prod.log
fi

echo "HD || Running composer install ..."
cd /var/hubdrop/app && composer install

echo "HD || Running setfacl -dR -m u:www-data:rwX -m u:hubdrop:rwX /var/hubdrop/app/app/cache /var/hubdrop/app/app/logs ..."
setfacl -dR -m u:www-data:rwX -m u:hubdrop:rwX /var/hubdrop/app/app/cache /var/hubdrop/app/app/logs

# Launch apache2-foreground in a new process
echo "HD || Saving SYMFONY_ENV to /etc/apache2/envvars from environment variables ..."
echo "export SYMFONY_ENV=$SYMFONY_ENV"
echo "export SYMFONY_ENV=$SYMFONY_ENV" >> /etc/apache2/envvars
echo "export JENKINS_URL=$JENKINS_URL" >> /etc/apache2/envvars

echo "HD || Running apache2-foreground& ..."
sudo apache2-foreground&

if [ ! -f /var/hubdrop/.ssh/id_rsa ]; then
    echo "HD || Generating SSH Key ..."
    ssh-keygen -t rsa -N "" -f /var/hubdrop/.ssh/id_rsa
fi

if [ ! -d /var/hubdrop/repos ]; then
    echo "HD || Creating repos folder ..."
  mkdir /var/hubdrop/repos
fi

if [ ! -d /var/hubdrop/users ]; then
    echo "HD || Creating users folder ..."
    mkdir /var/hubdrop/users
    chgrp www-data /var/hubdrop/users
    chmod 775 /var/hubdrop/users
fi

echo "HD || Public Key:"
cat ~/.ssh/id_rsa.pub

# @TODO: Run the hubdrop:queue command
echo "HD || Running tail -f /var/hubdrop/app/app/logs/apache.log ..."
tail -f /var/hubdrop/app/app/logs/apache.log