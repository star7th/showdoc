FROM  webdevops/php-nginx:7.4-alpine

## docker build --build-arg=IN_CHINA=true .
ARG IN_CHINA=false

# 环境变量
ENV SHOWDOC_DOCKER_VERSION 2.4
ENV IN_CHINA=${IN_CHINA}

WORKDIR /showdoc_data/html
COPY . .

RUN bash docker.run.sh --build

CMD ["bash", "docker.run.sh"]
