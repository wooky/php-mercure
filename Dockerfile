FROM php:7.4-apache

RUN \
  apt update && \
  (curl -sS https://getcomposer.org/installer | php) && mv composer.phar /usr/local/bin/composer && rm -f composer-setup.php && \
  apt install -y git unzip && \
  pecl install xdebug-3.0.4 && \
  docker-php-ext-enable xdebug &&
  docker-php-ext-install sockets && \
  a2enmod rewrite && \
  rm -f /var/log/apache2/*
