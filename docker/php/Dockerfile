FROM php:7.4-fpm

RUN docker-php-ext-install pdo_mysql

RUN pecl install apcu

RUN apt-get update && \
    apt-get install -y \
    libzip-dev

RUN docker-php-ext-install zip
RUN docker-php-ext-enable apcu

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('sha384', 'composer-setup.php') === '572cb359b56ad9ae52f9c23d29d4b19a040af10d6635642e646a7caa7b96de717ce683bd797a92ce99e5929cc51e7d5f') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');"

WORKDIR /var/www/project

# COPY --chown=1000:1000 ./ /var/www/project

RUN PATH=$PATH:/var/www/project/vendor/bin:bin