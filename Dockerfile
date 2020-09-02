FROM alpine:3.12
# Dependencies
RUN echo http://dl-2.alpinelinux.org/alpine/edge/community/ >> /etc/apk/repositories
RUN apk upgrade && apk update
RUN apk --update add shadow
RUN apk --update add nano && \
    apk --update add wget bash composer git sqlite mysql && \
    apk --update add php php-fpm php-xml php-curl php-ctype php-tokenizer php-sqlite3 php-pdo php-dom php-bcmath php-zip php-simplexml php-session php-pdo_sqlite php-pdo_mysql
# Symfony
RUN wget https://get.symfony.com/cli/installer -O - | bash && \
    mv /root/.symfony/bin/symfony /usr/local/bin/symfony
# Php configuration
RUN sed -i '/^ *memory_limit/s/=.*/= -1/' /etc/php7/php.ini
RUN sed -i '/^ *post_max_size/s/=.*/= 10000M/' /etc/php7/php.ini
RUN sed -i '/^ *upload_max_filesize/s/=.*/= 10000M/' /etc/php7/php.ini
RUN sed -i '/^ *max_file_uploads/s/=.*/= 10000/' /etc/php7/php.ini
RUN sed -i '/^ *max_execution_time/s/=.*/= 360/' /etc/php7/php.ini
RUN sed -i '/^ *max_input_time/s/=.*/= 360/' /etc/php7/php.ini
# Certificat & Permissions
RUN symfony server:ca:install
RUN mkdir -p /home/www-data
WORKDIR /home/www-data
RUN chown -R 1000:1000 /home/www-data
# Project
RUN git clone https://github.com/Mediashare/CloudFile-API /home/www-data/cloudfile-api
WORKDIR /home/www-data/cloudfile-api
# Installation
RUN composer install
RUN chmod -R 777 var
RUN bin/console cloudfile:install
RUN chmod -R 777 var
# Jobs
RUN echo "nohup bin/robots >/dev/null 2>&1 &" >> ~/.bashrc
ENTRYPOINT ["symfony", "server:start", "--port=80", "--allow-http"]

# docker build -t cloudfile/api . && docker run -it -p '8080:80' cloudfile/api
