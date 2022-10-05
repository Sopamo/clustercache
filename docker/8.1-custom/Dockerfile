FROM php:8.1-fpm-buster

# This is based on https://gitlab.com/ric_harvey/nginx-php-fpm

ENV php_conf /usr/local/etc/php-fpm.conf
ENV fpm_conf /usr/local/etc/php-fpm.d/www.conf
ENV php_vars /usr/local/etc/php/conf.d/docker-vars.ini

ENV DEBIAN_FRONTEND=noninteractive

ENV NGINX_VERSION 1.21.6

# resolves #166
#ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php
#RUN apk add --no-cache --repository http://dl-3.alpinelinux.org/alpine/edge/community gnu-libiconv

RUN adduser --system --home /var/cache/nginx --shell /bin/false --disabled-login --disabled-password --group nginx \
  && sed -i "s#deb http://deb.debian.org/debian buster main#deb http://deb.debian.org/debian buster main contrib#g" /etc/apt/sources.list \
  && apt-get update \
  && apt-get install -y -q --no-install-recommends --no-install-suggests \
      wget \
      nano \
      host \
      net-tools \
      tzdata \
      ca-certificates \
      supervisor \
      fonts-liberation \
      nginx \
      libmcrypt-dev \
      libfreetype6-dev \
      libjpeg62-turbo-dev \
      libmcrypt-dev \
      libpng-dev \
      libcurl4-openssl-dev \
      libmagickwand-dev \
      libmagickcore-dev \
      libssl-dev \
      librabbitmq-dev \
      zlib1g-dev \
      libicu-dev \
      g++ \
      localepurge \
      make \
      unzip \
      locales \
      pkg-config \
      git \
      gcc \
      curl \
      gnupg \
      libxslt1-dev \
      libgd-dev \
      qtbase5-dev \
      qt5-default \
      ttf-dejavu \
      libgeoip-dev \
      libssl1.1 \
      gettext \
      gnupg

RUN GPG_KEYS=B0F4253373F8F6F510D42178520A9993A1C052F8 \
  && CONFIG="\
    --prefix=/etc/nginx \
    --sbin-path=/usr/sbin/nginx \
    --modules-path=/usr/lib/nginx/modules \
    --conf-path=/etc/nginx/nginx.conf \
    --error-log-path=/var/log/nginx/error.log \
    --http-log-path=/var/log/nginx/access.log \
    --pid-path=/var/run/nginx.pid \
    --lock-path=/var/run/nginx.lock \
    --http-client-body-temp-path=/var/cache/nginx/client_temp \
    --http-proxy-temp-path=/var/cache/nginx/proxy_temp \
    --http-fastcgi-temp-path=/var/cache/nginx/fastcgi_temp \
    --http-uwsgi-temp-path=/var/cache/nginx/uwsgi_temp \
    --http-scgi-temp-path=/var/cache/nginx/scgi_temp \
    --user=nginx \
    --group=nginx \
    --with-http_ssl_module \
    --with-http_realip_module \
    --with-http_addition_module \
    --with-http_sub_module \
    --with-http_dav_module \
    --with-http_flv_module \
    --with-http_mp4_module \
    --with-http_gunzip_module \
    --with-http_gzip_static_module \
    --with-http_random_index_module \
    --with-http_secure_link_module \
    --with-http_stub_status_module \
    --with-http_auth_request_module \
    --with-http_xslt_module=dynamic \
    --with-http_image_filter_module=dynamic \
    --with-http_geoip_module=dynamic \
    --with-threads \
    --with-stream \
    --with-stream_ssl_module \
    --with-stream_ssl_preread_module \
    --with-stream_realip_module \
    --with-stream_geoip_module=dynamic \
    --with-http_slice_module \
    --with-mail \
    --with-mail_ssl_module \
    --with-compat \
    --with-file-aio \
    --with-http_v2_module \
  " \
    && curl -fSL http://nginx.org/download/nginx-$NGINX_VERSION.tar.gz -o nginx.tar.gz \
    && curl -fSL http://nginx.org/download/nginx-$NGINX_VERSION.tar.gz.asc  -o nginx.tar.gz.asc \
    && export GNUPGHOME="$(mktemp -d)" \
    && found=''; \
    for server in \
      ha.pool.sks-keyservers.net \
      hkp://keyserver.ubuntu.com:80 \
      hkp://p80.pool.sks-keyservers.net:80 \
      pgp.mit.edu \
    ; do \
      echo "Fetching GPG key $GPG_KEYS from $server"; \
      gpg --keyserver "$server" --keyserver-options timeout=10 --recv-keys "$GPG_KEYS" && found=yes && break; \
    done; \
    test -z "$found" && echo >&2 "error: failed to fetch GPG key $GPG_KEYS" && exit 1; \
    gpg --batch --verify nginx.tar.gz.asc nginx.tar.gz \
    #&& rm -r "$GNUPGHOME" nginx.tar.gz.asc \
    && mkdir -p /usr/src \
    && tar -zxC /usr/src -f nginx.tar.gz \
    && rm nginx.tar.gz \
    && cd /usr/src/nginx-$NGINX_VERSION \
    && ./configure $CONFIG --with-debug \
    && make -j$(getconf _NPROCESSORS_ONLN) \
    && mv objs/nginx objs/nginx-debug \
    && mv objs/ngx_http_xslt_filter_module.so objs/ngx_http_xslt_filter_module-debug.so \
    && mv objs/ngx_http_image_filter_module.so objs/ngx_http_image_filter_module-debug.so \
    && mv objs/ngx_http_geoip_module.so objs/ngx_http_geoip_module-debug.so \
    && mv objs/ngx_stream_geoip_module.so objs/ngx_stream_geoip_module-debug.so \
    && ./configure $CONFIG \
    && make -j$(getconf _NPROCESSORS_ONLN) \
    && make install \
    && rm -rf /etc/nginx/html/ \
    && mkdir -p /usr/share/nginx/html/ \
    && install -m644 html/index.html /usr/share/nginx/html/ \
    && install -m644 html/50x.html /usr/share/nginx/html/ \
    && install -m755 objs/nginx-debug /usr/sbin/nginx-debug \
    && install -m755 objs/ngx_http_xslt_filter_module-debug.so /usr/lib/nginx/modules/ngx_http_xslt_filter_module-debug.so \
    && install -m755 objs/ngx_http_image_filter_module-debug.so /usr/lib/nginx/modules/ngx_http_image_filter_module-debug.so \
    && install -m755 objs/ngx_http_geoip_module-debug.so /usr/lib/nginx/modules/ngx_http_geoip_module-debug.so \
    && install -m755 objs/ngx_stream_geoip_module-debug.so /usr/lib/nginx/modules/ngx_stream_geoip_module-debug.so \
    && ln -s ../../usr/lib/nginx/modules /etc/nginx/modules \
    && strip /usr/sbin/nginx* \
    && strip /usr/lib/nginx/modules/*.so \
    && rm -rf /usr/src/nginx-$NGINX_VERSION \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log
RUN curl -sL https://deb.nodesource.com/setup_14.x | bash - \
  && apt-get install -y -q --no-install-recommends --no-install-suggests nodejs && node --version && npm --version
RUN apt-get install -y -q --no-install-recommends --no-install-suggests \
      bash \
      wget \
      supervisor \
      nano \
      curl \
      openssh-client \
      libcurl4 \
      libzip-dev \
      libxrender1 \
      fontconfig \
      bzip2 \
      unzip \
      ca-certificates \
      dialog \
      autoconf \
      make \
      gcc \
      git \
      libpng-dev \
      libicu-dev \
      libxslt1-dev \
      libffi-dev \
      libfreetype6-dev \
      libjpeg62-turbo-dev \
      wkhtmltopdf \
    && pecl install zip imagick && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) bcmath gd pcntl iconv pdo_mysql exif opcache && \
    docker-php-ext-enable imagick zip && \
    pecl install -o -f redis mongodb && \
    echo "extension=redis.so" > /usr/local/etc/php/conf.d/redis.ini && \
    echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongodb.ini && \
    docker-php-source delete && \
    mkdir -p /run/php && \
    mkdir -p /etc/nginx && \
    mkdir -p /run/nginx && \
    mkdir -p /var/log/supervisor
    RUN wget -O composer-setup.php https://getcomposer.org/installer && \
    php composer-setup.php --quiet --install-dir=/usr/bin --filename=composer && \
    rm composer-setup.php && \
    git config --global url."https://github.com/".insteadOf ssh://git@github.com/ && \
    mkdir -p /root/.ssh && \
    echo "github.com ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAq2A7hRGmdnm9tUDbO9IDSwBK6TbQa+PXYPCPy6rbTrTtw7PHkccKrpp0yVhp5HdEIcKr6pLlVDBfOLX9QUsyCOV0wzfjIJNlGEYsdlLJizHhbn2mUjvSAHQqZETYP81eFzLQNnPHt4EVVUh7VfDESU84KezmD5QlWpXLmvU31/yMf+Se8xhHTvKSCZIFImWwoG6mbUoWf9nzpIoaSjB+weqqUUmpaaasXVal72J+UX2B+2RPW3RcT0eOzQgqlJL3RKrTJvdsjE3JEAvGq3lGHSZXy28G3skua2SmVi/w4yCE6gbODqnTWlg7+wC604ydGXA8VJiS5ap43JXiUFFAaQ==" >> "/root/.ssh/known_hosts" && \
    npm config set unsafe-perm true && \
    npm install -g gulp && \
    dpkg-reconfigure fontconfig-config && \
    fc-cache -f && \
    apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/* && \
    mkdir -p /var/www/html && \
    fc-cache -fv && \
    # Copy our nginx config
    rm -Rf /etc/nginx/nginx.conf

COPY ./nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf
COPY ./nginx/serversettings.conf /etc/nginx/conf.d/serversettings.conf

# Set the php config
COPY ./conf/php.ini /usr/local/etc/php/
# This is needed to see the env variables in php-fpm
RUN sed -i 's/;clear_env = no/clear_env = no/g' /usr/local/etc/php-fpm.d/www.conf

# Copy supervisor config
COPY ./conf/supervisord.conf /etc/supervisord.conf

# Copy php-fpm config
COPY ./conf/www.conf /usr/local/etc/php-fpm.d/www.conf

# Add Scripts
COPY ./scripts/start.sh /start.sh
RUN chmod 755 /start.sh

WORKDIR "/var/www/html"

CMD ["/start.sh"]
