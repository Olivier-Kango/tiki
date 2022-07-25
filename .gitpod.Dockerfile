FROM gitpod/workspace-base:latest

RUN sudo apt-get update &&  \
    sudo apt-get -y install \
    apache2 \
    mariadb-server \
    php7.4 \
    libapache2-mod-php \
    php-mysql \
    php-curl \
    php-gd \
    php-mbstring \
    php-xml \
    php-xmlrpc \
    php-intl \
    php-bcmath \
    php-bz2 \
    php-zip

COPY .gitpod/mysql/mysql.cnf /etc/mysql/mariadb.conf.d/60-server_custom.cnf
COPY .gitpod/apache/apache.conf /etc/apache2/apache2.conf
COPY .gitpod/apache/envvars /etc/apache2/envvars


RUN sudo mkdir -p /var/run/mysqld && \
    sudo chown gitpod:gitpod /var/run/apache2 /var/lock/apache2 /var/run/mysqld && \
    sudo sed -i 's/^bind-address.*/#&/' /etc/mysql/mariadb.conf.d/50-server.cnf && \
    sudo sed -i 's/^user.*/#&/' /etc/mysql/mariadb.conf.d/50-server.cnf && \
    sudo addgroup gitpod www-data && \
    mkdir -p /workspace/mysql/data \
    mkdir -p /workspace/logs/mysql 

