## docker image build
version=1.5.1
```
sudo docker build -t star7th/showdoc:latest -t star7th/showdoc:${version} .
```
## docker-compose op
### create docker container and start
change the dir to showdoc root dir and execute followings:
```
sudo docker-compose up
# sudo docker-compose up -f docker-compose.yml
```

### start
```
sudo docker-compose start
```

## backup
SHOWDOC_HOME="/var/www/html"

backup_dir="~/showdoc"

backup the showdoc db and uploads with docker cmd
```
docker cp star7th-showdoc:${SHOWDOC_HOME}/Public/Uploads ${backup_dir}

docker cp star7th-showdoc:${SHOWDOC_HOME}/Sqlite ${backup_dir}
```

## cron backup shell script
`auto-backup-showdoc.sh`
```
#!/bin/bash
set -e

SHOWDOC_HOME="/var/www/html"

container_name="star7th-showdoc"
backup_file_prefix=`date +%s_%Y_%m_%d_1.5.2_showdoc_backup`
backup_filename="${backup_file_prefix}.tar"

backup_dir="~/showdoc"
mkdir -p ${backup_dir}

container_sqlite_dir="${SHOWDOC_HOME}/Sqlite"
container_uploads_dir="${SHOWDOC_HOME}/Public/Uploads"

showdoc_ps_count=`docker ps|grep star7th-showdoc|wc -l`
if [[ ${showdoc_ps_count} -le 0 ]]; then
    exit 0;
fi

cd ${backup_dir}
# copy files from docker
docker cp ${container_name}:${container_sqlite_dir} .
docker cp ${container_name}:${container_uploads_dir}  .

# tar files
tar -cf ${backup_filename}   Sqlite/ Uploads/
# tar -tvf test.tar
rm -rf Sqlite/ Uploads/
```

### cron expression
```
0 *   *  *    *    sudo bash ${shell-script-dir}/auto-backup-showdoc.sh
```
