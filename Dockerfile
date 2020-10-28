FROM hyperf/hyperf:7.2-alpine-base

LABEL maintainer="ethananony <ethananony@aliyun.com>" version="1.0"

##
# ---------- env settings ----------
##
ENV \
    #  install and remove building packages
    PHPIZE_DEPS="autoconf dpkg-dev dpkg file g++ gcc libc-dev make php7-dev php7-pear pkgconf re2c pcre-dev zlib-dev libtool automake"

COPY ./resource/ /tmp/

# update
RUN set -ex \
    && sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories \
    && apk update \
    # for swoole extension libaio linux-headers
    && apk add --no-cache libstdc++ openssl git bash librdkafka-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS libaio-dev openssl-dev \
    # php extension:swoole
    && cd /tmp \
    && mkdir -p swoole \
    && tar -xf swoole-4.5.5.tgz -C swoole --strip-components=1 \
    && ( \
        cd swoole \
        && phpize \
        && ./configure --enable-mysqlnd --enable-openssl \
        && make -s -j$(nproc) && make install \
    ) \
    && echo "extension=swoole.so" > /etc/php7/conf.d/swoole.ini \
    && echo "swoole.use_shortname = 'Off'" >> /etc/php7/conf.d/swoole.ini \
    # php extension:rdkafka
    && cd /tmp \
    && mkdir -p rdkafka \
    && tar -xf rdkafka-4.0.4.tgz -C rdkafka --strip-components=1 \
    && ( \
        cd rdkafka \
        && phpize \
        && ./configure \
        && make -s -j$(nproc) && make install \
    ) \
    && echo "extension=rdkafka.so" > /etc/php7/conf.d/rdkafka.ini \
    # install composer
    && cd /tmp \
    && chmod a+x composer \
    && mv composer /usr/local/bin \
    && composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/ \
    # - config PHP
    && { \
        echo "upload_max_filesize=100M"; \
        echo "post_max_size=108M"; \
        echo "memory_limit=1024M"; \
        echo "date.timezone=${TIMEZONE}"; \
    } | tee /etc/php7/conf.d/99_overrides.ini \
    # - config timezone
    && ln -sf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone \
    # clear
    && php -v \
    && php -m \
    && php --ri swoole \
    && php --ri rdkafka \
    # ---------- clear works ----------
    && apk del .build-deps \
    && rm -rf /var/cache/apk/* /tmp/* /usr/share/man \
    && echo -e "\033[42;37m Build Completed :).\033[0m\n" 

WORKDIR /opt/www

COPY . /opt/www

RUN composer install -o && php bin/hyperf.php

EXPOSE 9501

ENTRYPOINT ["php", "/opt/www/bin/hyperf.php", "start"]