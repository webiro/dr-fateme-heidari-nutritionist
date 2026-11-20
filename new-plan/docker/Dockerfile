# NOTE: Docker Hub can be blocked in some networks — pull via the AWS ECR
# public mirror of Docker Library images instead.
FROM public.ecr.aws/docker/library/php:8.5-apache

# Extensions needed by the app
RUN apt-get update && apt-get install -y --no-install-recommends libzip-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql zip \
    && a2enmod rewrite headers expires deflate

# Apache: docroot = /var/www/html/public (the project mounts its public/ here)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow bigger uploads for reels (local testing; production uses .user.ini / cPanel)
RUN { \
    echo 'upload_max_filesize = 512M'; \
    echo 'post_max_size = 512M'; \
    echo 'max_execution_time = 300'; \
    echo 'memory_limit = 256M'; \
  } > /usr/local/etc/php/conf.d/fh-uploads.ini

WORKDIR /var/www/html/public
EXPOSE 80
