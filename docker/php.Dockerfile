FROM php:8.1-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libcurl4-openssl-dev \
    libz-dev \
    libmemcached-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libc-client-dev \
    libkrb5-dev \
    zip \
    unzip\
    libzip-dev \
    libldap2-dev \
    wget

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install php extensions
RUN docker-php-ext-install gd
# RUN docker-php-ext-install curl
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl && docker-php-ext-install imap
# RUN docker-php-ext-install mbstring
# RUN docker-php-ext-install xml
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install zip
RUN docker-php-ext-install soap
RUN docker-php-ext-install intl
RUN docker-php-ext-install ldap

RUN pecl install -o -f redis \
&&  rm -rf /tmp/pear \
&&  docker-php-ext-enable redis

# Install xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install mhsendmail
RUN wget https://github.com/mailhog/mhsendmail/releases/download/v0.2.0/mhsendmail_linux_amd64 \
    && chmod +x mhsendmail_linux_amd64 \
    && mv mhsendmail_linux_amd64 /usr/local/bin/mhsendmail \
    && echo "sendmail_path = '/usr/local/bin/mhsendmail --smtp-addr=mailhog:1025'" >> /usr/local/etc/php/conf.d/php-sendmail.ini

# Enable error reporting
RUN echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/php-error-reporting.ini


# create dev user and group
ARG UID
ARG GID
ENV UID=${UID}
ENV GID=${GID}
RUN groupadd dev  -g ${GID}
RUN useradd dev -m -u 1000 -g ${UID}


# Create composer cache dir for www-data user
RUN mkdir /var/www/.composer \
    && chmod -R ugo+rw /var/www/.composer


RUN mkdir /app \
    && chown -R www-data:www-data /app

USER dev