# =============================================================================
# ShowDoc 统一 Docker 镜像（amd64 / arm64 / arm/v7）
#
# 默认基础镜像 webdevops/php-nginx:8.3-alpine 支持 amd64 + arm64。
# 需要 32 位 ARM(arm/v7) 等平台时，注入旧版基础镜像：
#   docker build --build-arg BASE_IMAGE=wordpress:php8.1 \
#                --build-arg IN_CHINA=true .
#
# 国内镜像：docker build --build-arg=IN_CHINA=true .
# =============================================================================

ARG BASE_IMAGE=webdevops/php-nginx:8.3-alpine

# =============================================================================
# PHP 补丁构建阶段：源码编译 PHP 8.3.36 覆盖基础镜像内的 8.3.35
# （修复 CVE-2026-109618 / CVE-2026-109619，php2shell 类漏洞；
#   上游 webdevops 镜像尚未跟进时先行自救。方案实证记录 2026-10-21）
# 说明：
#  - 源码包预下载后放在 docker/php-src/，构建期不外联下载
#  - 编译参数与基础镜像 8.3.35 完全一致（configure 逐字复刻），
#    同分支小版本 Zend API 不变，基础镜像自带共享扩展 .so 原样兼容
#  - gnu libiconv 1.18：musl 内置 iconv 缺 GBK 等中文编码，且 1.17
#    在 gcc15/musl 下编译失败，必须 ≥1.18
#  - 仅对 webdevops 8.3 基础镜像生效；旧版 wordpress:php8.1（arm/v7）路径跳过
# =============================================================================
FROM ${BASE_IMAGE} AS php-patch-builder
ARG BASE_IMAGE=webdevops/php-nginx:8.3-alpine
ARG MIN_PHP_VERSION=8.3.36
ARG PHP_PATCH_VERSION=8.3.36
ARG SKIP_PHP_PATCH=false

# 注意：RUN 默认 /bin/sh（POSIX），须用 case 而非 [[ ]]
RUN case "$BASE_IMAGE" in \
        webdevops/php-nginx:8.3-*) \
            PHP_NOW=$(php -r 'echo PHP_VERSION;') && \
            if [ "$SKIP_PHP_PATCH" = "true" ] || \
               [ "$(printf '%s\n' "$MIN_PHP_VERSION" "$PHP_NOW" | sort -V | head -1)" != "$PHP_NOW" ]; then \
                echo "[php-patch] skip: base PHP $PHP_NOW >= $MIN_PHP_VERSION (or SKIP_PHP_PATCH=true)"; \
                exit 0; \
            fi \
            && apk add --no-cache --virtual .php-build-deps \
                gcc g++ make autoconf pkgconfig re2c bison \
                openssl-dev curl-dev zlib-dev libxml2-dev sqlite-dev oniguruma-dev \
                readline-dev argon2-dev libsodium-dev \
            && cd /usr/src \
            && curl -fsSL -o php.tar.gz "https://www.php.net/distributions/php-${PHP_PATCH_VERSION}.tar.gz" \
            && curl -fsSL -o libiconv.tar.gz "https://ftp.gnu.org/pub/gnu/libiconv/libiconv-1.18.tar.gz" \
            && tar xzf php.tar.gz && rm php.tar.gz \
            && tar xzf libiconv.tar.gz && rm libiconv.tar.gz \
            && cd /usr/src/libiconv-1.18 \
            && ./configure --prefix=/usr --disable-nls > /dev/null \
            && make -j"$(nproc)" > /dev/null 2>&1 && make install > /dev/null 2>&1 && make clean \
            && cd /usr/src/php-8.3.36 \
            && ./configure \
                --sysconfdir=/usr/local/etc \
                --with-config-file-path=/usr/local/etc/php \
                --with-config-file-scan-dir=/usr/local/etc/php/conf.d \
                --enable-option-checking=fatal \
                --with-mhash \
                --with-pic \
                --enable-mbstring \
                --enable-mysqlnd \
                --with-password-argon2 \
                --with-sodium=shared \
                --with-pdo-sqlite=/usr \
                --with-sqlite3=/usr \
                --with-curl \
                --with-iconv=/usr \
                --with-openssl \
                --with-readline \
                --with-zlib \
                --disable-phpdbg \
                --with-pear \
                --disable-cgi \
                --enable-fpm \
                --with-fpm-user=www-data \
                --with-fpm-group=www-data \
            && make -j"$(nproc)" \
            && make install \
            && make clean \
        ;; \
        *) echo "[php-patch] skip: $BASE_IMAGE not a webdevops 8.3 image" \
           && mkdir -p /usr/local/sbin \
           && { command -v php-fpm || cp /usr/local/bin/php /usr/local/sbin/php-fpm; } ;; \
    esac

# =============================================================================
# 主镜像
# =============================================================================
FROM ${BASE_IMAGE}

# ===== 构建参数 =====
# 注意：FROM 之前的 ARG 作用域仅限 FROM 行，此处需重新声明
ARG BASE_IMAGE=webdevops/php-nginx:8.3-alpine
ARG IN_CHINA=false
ARG TARGETVARIANT

# PHP 补丁二进制覆盖（基础镜像共享扩展 .so 跨补丁版本兼容，无需重装）
COPY --from=php-patch-builder /usr/local/bin/php /usr/local/bin/php
COPY --from=php-patch-builder /usr/local/sbin/php-fpm /usr/local/sbin/php-fpm
RUN case "$BASE_IMAGE" in \
        webdevops/php-nginx:8.3-*) \
            php -v | head -1 && php-fpm -v | head -1 \
            && php -r 'var_dump(iconv("UTF-8","GBK","测试"));' > /dev/null \
            && php --ri sodium > /dev/null && echo PHP_PATCH_OK \
        ;; \
    esac

# 环境变量
ENV SHOWDOC_DOCKER_VERSION=3.4.2
ENV IN_CHINA=${IN_CHINA}
ENV BASE_IMAGE=${BASE_IMAGE}
ENV TARGETVARIANT=${TARGETVARIANT}

WORKDIR /showdoc_data/html
COPY . .

# ---------- Office 导入转换器 anydoc（musl 静态，见 server/bin/VERSION） ----------
# 注意：.dockerignore 已排除 server/bin/*（防二进制被整包拷进 web 根），
# 但用 !server/bin/anydoc 反排除了本文件，所以这里仍可 COPY
COPY --chmod=755 server/bin/anydoc /usr/local/bin/anydoc

# 旧版基础镜像（wordpress:*，如 arm/v7）：代码需放入 apache 网站根目录 /var/www/html，
# 并提供 PHP 渲染 web/index.html 的入口，同时放宽权限以兼容历史行为。
# 注意：RUN 默认 /bin/sh（POSIX），须用 case 而非 [[ ]]
RUN case "$BASE_IMAGE" in \
        wordpress:*) \
            cp -R /showdoc_data/html/. /var/www/html/ && \
            rm -f /var/www/html/server/bin/anydoc && \
            echo "<?php echo file_get_contents('index.html'); ?>" > /var/www/html/web/index.php && \
            chmod -R 777 /var/www/html/ \
        ;; \
    esac

RUN bash entrypoint.sh --build

CMD ["bash", "entrypoint.sh"]
