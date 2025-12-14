FROM php:8.2-apache

# --------------------------------------------------
# Environment
# --------------------------------------------------
ENV TZ=Asia/Tehran
ENV DEBIAN_FRONTEND=noninteractive

# --------------------------------------------------
# Apache config
# --------------------------------------------------
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite headers

# --------------------------------------------------
# System dependencies
# --------------------------------------------------
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    nano \
    wget \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    pkg-config \
    --no-install-recommends

# --------------------------------------------------
# PHP extensions
# --------------------------------------------------
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        zip \
        intl \
        gd \
        mbstring \
        exif \
        pcntl \
        bcmath \
        opcache

# Install Redis extension
RUN pecl install redis

# Enable redis extension
RUN docker-php-ext-enable redis

# --------------------------------------------------
# Composer
# --------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# --------------------------------------------------
# PHP.ini Copy PHP configuration
# --------------------------------------------------

COPY php.ini /usr/local/etc/php/php.ini


# --------------------------------------------------
# Cleanup
# --------------------------------------------------
RUN apt-get purge -y pkg-config \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/* /tmp/*

# --------------------------------------------------
# Workdir & permissions
# --------------------------------------------------
WORKDIR /var/www/html

# --------------------------------------------------
# Run Apache
# --------------------------------------------------
CMD ["apache2-foreground"]
