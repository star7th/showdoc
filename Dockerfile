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
FROM ${BASE_IMAGE}

# ===== 构建参数 =====
# 注意：FROM 之前的 ARG 作用域仅限 FROM 行，此处需重新声明
ARG BASE_IMAGE=webdevops/php-nginx:8.3-alpine
ARG IN_CHINA=false
ARG TARGETVARIANT

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
