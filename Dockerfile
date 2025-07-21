FROM  webdevops/php-nginx:8.3-alpine

## 构建时环境变量 docker build --build-arg=IN_CHINA=true .
ARG IN_CHINA=false

# 环境变量
ENV SHOWDOC_DOCKER_VERSION=3.4.1
ENV IN_CHINA=${IN_CHINA}

WORKDIR /showdoc_data/html
COPY . .

RUN bash docker.run.sh --build

CMD ["bash", "docker.run.sh"]
