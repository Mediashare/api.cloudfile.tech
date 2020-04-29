FROM debian:buster-slim
RUN apt upgrade && apt update
RUN apt install -y php7.3 php-xml php-curl php-ctype php-tokenizer php-sqlite3 php-pdo php-dom
RUN apt install -y php-simplexml
RUN apt install -y apache2 sqlite
RUN apt install -y composer git

WORKDIR /home
RUN git clone https://github.com/Mediashare/CloudFile-API
WORKDIR /home/CloudFile-API

RUN composer install
RUN bin/console doctrine:database:create
RUN bin/console doctrine:schema:update --force
RUN chmod -R 777 var
EXPOSE 8080

RUN sed -i '/^ *cloudfile_password/s/=.*/= ""/' .env
RUN sed -i '/^ *memory_limit/s/=.*/= -1/' /etc/php/7.3/cli/php.ini
RUN sed -i '/^ *post_max_size/s/=.*/= 10000M/' /etc/php/7.3/cli/php.ini
RUN sed -i '/^ *upload_max_filesize/s/=.*/= 10000M/' /etc/php/7.3/cli/php.ini
RUN sed -i '/^ *max_file_uploads/s/=.*/= 10000/' /etc/php/7.3/cli/php.ini

CMD [ "bin/console", "server:run", "0.0.0.0:8080" ] 