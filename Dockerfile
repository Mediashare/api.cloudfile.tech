FROM php:7.4-fpm-alpine

# Dependencies
RUN apk upgrade && apk --update add \
    wget bash composer git sqlite mysql \
    php php-fpm php-xml php-curl php-ctype php-tokenizer php-sqlite3 php-pdo php-dom php-bcmath php-zip php-simplexml php-session
# Symfony
RUN wget https://get.symfony.com/cli/installer -O - | bash && \
    mv /root/.symfony/bin/symfony /usr/local/bin/symfony
RUN symfony server:ca:install
# Project
RUN git clone https://github.com/Mediashare/CloudFile-API /home/cloudfile-api
WORKDIR /home/cloudfile-api
# Installation
RUN composer install
RUN bin/console cache:clear
RUN bin/console doctrine:database:create
RUN bin/console doctrine:schema:update --force
RUN chmod -R 777 var
# Php configuration
RUN sed -i '/^ *memory_limit/s/=.*/= -1/' /etc/php7/php.ini
RUN sed -i '/^ *post_max_size/s/=.*/= 10000M/' /etc/php7/php.ini
RUN sed -i '/^ *upload_max_filesize/s/=.*/= 10000M/' /etc/php7/php.ini
RUN sed -i '/^ *max_file_uploads/s/=.*/= 10000/' /etc/php7/php.ini
RUN sed -i '/^ *max_execution_time/s/=.*/= 360/' /etc/php7/php.ini
RUN sed -i '/^ *max_input_time/s/=.*/= 360/' /etc/php7/php.ini
# Jobs
RUN echo "nohup bin/robots >/dev/null 2>&1 &" >> ~/.bashrc
ENTRYPOINT ["symfony", "server:start", "--port=80", "--allow-http"]

# docker build -t cloudfile/api . && docker run -it -p '8080:8080' cloudfile/api
