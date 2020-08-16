FROM debian:stable-slim
RUN apt upgrade -y && apt update -y
RUN apt install -y apt-utils
RUN apt install -y php7.3 php-xml php-curl php-ctype php-tokenizer php-sqlite3 php-pdo php-dom php-bcmath php-zip
RUN apt install -y php-simplexml
RUN apt install -y sqlite
RUN apt install -y composer git --fix-missing

WORKDIR /home
RUN git clone https://github.com/Mediashare/CloudFile-API cloudfile-api
WORKDIR /home/cloudfile-api

RUN composer install
RUN bin/console doctrine:database:create
RUN bin/console doctrine:schema:update --force
RUN chmod -R 777 var
EXPOSE 8080

RUN sed -i '/^ *memory_limit/s/=.*/= -1/' /etc/php/7.3/cli/php.ini
RUN sed -i '/^ *post_max_size/s/=.*/= 10000M/' /etc/php/7.3/cli/php.ini
RUN sed -i '/^ *upload_max_filesize/s/=.*/= 10000M/' /etc/php/7.3/cli/php.ini
RUN sed -i '/^ *max_file_uploads/s/=.*/= 10000/' /etc/php/7.3/cli/php.ini
RUN sed -i '/^ *max_execution_time/s/=.*/= 360/' /etc/php/7.3/cli/php.ini
RUN sed -i '/^ *max_input_time/s/=.*/= 360/' /etc/php/7.3/cli/php.ini
# RUN service apache2 restart

# RUN echo "nohup php -S 0.0.0.0:8080 -t public >/dev/null 2>&1 &" >> ~/.bashrc
RUN echo "nohup bin/robots >/dev/null 2>&1 &" >> ~/.bashrc
ENTRYPOINT ["php", "-S", "0.0.0.0:8080", "-t", "public" ]

# docker build -t cloudfile .
# docker run -it -p 127.0.0.1:8080:8080 cloudfile