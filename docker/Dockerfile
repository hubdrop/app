FROM php:5-apache

RUN apt-get update -qq && apt-get install -y -qq \
  sudo

# Change this in docker-compose for each installation.
ENV SYMFONY_ENV dev
ENV APACHE_RUN_USER hubdrop

# add our user and group first to make sure their IDs get assigned consistently, regardless of whatever dependencies get added
ARG HUBDROP_UID=12345
RUN echo "Creating user and group 'hubdrop' with UID and GID $HUBDROP_UID..."
RUN groupadd -r hubdrop --gid $HUBDROP_UID && useradd -r --uid $HUBDROP_UID --gid $HUBDROP_UID --system --home /var/hubdrop hubdrop
RUN adduser hubdrop www-data
RUN mkdir /var/hubdrop && chown hubdrop:hubdrop /var/hubdrop
WORKDIR /var/hubdrop

# Allow our user to run apache2-foreground
COPY sudoers /etc/sudoers.d/hubdrop
RUN chmod 0440 /etc/sudoers.d/hubdrop

# Enable mod_rewrite
RUN a2enmod rewrite

# Set ownership to our user
RUN chown hubdrop:hubdrop /var/lock/apache2
RUN chown hubdrop:hubdrop /var/run/apache2


# Add our own PHP configuration
COPY php.ini /usr/local/etc/php

# Add our own Apache configuration
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Add our own entrypoint script
COPY hubdrop-entrypoint.sh /usr/local/bin/hubdrop-entrypoint.sh
RUN chmod +x /usr/local/bin/hubdrop-entrypoint.sh

# Add composer CLI
# Instructions from from https://getcomposer.org/download/
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '669656bab3166a7aff8a7506b8cb2d1c292f042046c5a994c43155c0be6190fa0355160742ab2e1c88d40d5be660b410') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"  && \
    php composer-setup.php  && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/local/bin/composer && \
    chmod +x /usr/local/bin/composer && \
    composer

USER hubdrop

# Set the container's command to our script.
CMD ["hubdrop-entrypoint.sh"]