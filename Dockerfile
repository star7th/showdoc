FROM  webdevops/php-nginx:7.4-alpine

## docker build --build-arg=IN_CHINA=true .
ARG IN_CHINA=false

# 环境变量
ENV SHOWDOC_DOCKER_VERSION 2.4

WORKDIR /showdoc_data/html
COPY . .

RUN bash docker.build.sh

CMD ["bash", "docker.run.sh"]
