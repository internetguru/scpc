FROM php:7-fpm
WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y git python3 python3-pip
RUN curl -sS https://getcomposer.org/installer | php -- \
  --install-dir=/usr/bin --filename=composer && chmod +x /usr/bin/composer
RUN pip3 install jupyter

RUN git config --global --add safe.directory /var/www/html
