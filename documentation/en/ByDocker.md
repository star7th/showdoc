### Basic installation

Make sure your environment has Docker installed before installation. The Docker installation tutorial is more online, you can search for it. Here is a highlight of ShowDoc.

```
# The original official image installation command (Chinese mainland users do not recommend direct use of the original image, you can use the following accelerated image)
Docker pull star7th/showdoc

# Chinese Image Installation Command (Remember to execute the Docker tag command after installation to rename)
Docker pull registry.docker-cn.com/star7th/showdoc
Docker tag registry.docker-cn.com/star7th/showdoc:latest star7th/showdoc:latest

## Follow-up commands need to be executed whether you use official image or accelerated image

# New directory to store ShowDoc data
Mkdir /showdoc_data
Mkdir /showdoc_data/html
Chmod 777 -R /showdoc_data

# Start the ShowDoc container. Don't forget to follow the steps to transfer data after booting.
docker run -d --name showdoc -p 4999:80 -v /showdoc_data/html:/var/www/html/ star7th/showdoc

# Move data When you execute here, pay attention to the error message that the command line interface has permission to prohibit.
# If there is, check the permissions, or security restrictions (for example, selinux may prohibit the Docker process from writing files)
docker exec showdoc \cp -fr /showdoc_data/html/ /var/www/
# permission
Chmod 777 -R /showdoc_data

```

According to the above command, the data of ShowDoc will be stored in the /showdoc_data/html directory.
You can access ShowDoc by opening http://localhost:4999 (localhost can be changed to your server domain name or IP). The default admin account is Username: showdoc Password: 123456 . After logging in, you can see the management background entry in the upper right. It is recommended to change the password after login.
For issues or suggestions on ShowDoc, please go to https://github.com/star7th/showdoc to issue an issue. If you think that ShowDoc is easy to use, please star it.

### How to upgrade
The upgrade here is an upgrade to the above Docker installation. If you used a non-Docker installation (such as PHP installation), please skip this section and go directly to the next section.
```
/ / Stop the container
docker stop showdoc

/ / Download the latest code package
Wget https://github.com/star7th/showdoc/archive/master.tar.gz
//Unzip
Tar -zxvf master.tar.gz -C /showdoc_data/

Rm -rf /showdoc_data/html_bak
//Backup. If possible, the html_bak in the command can also be date-suffixed to keep multiple backups of different dates.
Mv /showdoc_data/html /showdoc_data/html_bak
Mv /showdoc_data/showdoc-master /showdoc_data/html ##// */

/ / Grant permissions
Chmod 777 -R /showdoc_data/html

/ / Start the container
docker start showdoc

// Perform the installation. The Chinese version is installed by default. If you want to install the English version, change the zh in the following parameters to en
Curl http://localhost:4999/install/non_interactive.php?lang=en

/ / Transfer the old database
\cp -f /showdoc_data/html_bak/Sqlite/showdoc.db.php /showdoc_data/html/Sqlite/showdoc.db.php

/ / Transfer old attachment data
\cp -r -f /showdoc_data/html_bak/Public/Uploads /showdoc_data/html/Public/Uploads

/ / Perform a database upgrade, see the word "OK" to prove success
Curl http://localhost:4999?s=/home/update/db

//If there is an error in the middle, please rename the original /showdoc_data/html_bak file to /showdoc_data/html and restart the container to recover.

```


### How to upgrade non-Docker installation method to Docker installation method

First refer to the previous article, use Docker way to install a new ShowDoc, and do data persistence.
Next, assuming that the old ShowDoc you originally installed has been uploaded to the /tmp/showdoc directory of the server, then
```
/ / Transfer the old database
\cp -r -f /tmp/showdoc/Sqlite/showdoc.db.php /showdoc_data/html/Sqlite/showdoc.db.php

/ / Transfer old attachment data
\cp -r -f /tmp/showdoc/Public/Uploads /showdoc_data/html/Public/Uploads

/ / Perform a database upgrade, see the word "OK" to prove success
Curl http://localhost:4999?s=/home/update/db
```

### data backup
Just back up the /showdoc_data/html directory. For example, execute the following command to compress and store
```
Zip -r /showdoc_data/showdoc_bak.zip /showdoc_data/html
//where showdoc_bak.zip can be named with a date suffix for multiple backups. You can also use timed tasks to implement scheduled backups.
```
### Other reference commands
```
 docker stop showdoc //stop the container
 docker restart showdoc // restart the showdoc container
 docker rm showdoc //delete showdoc container
 docker rmi star7th/showdoc //delete showdoc image
 docker stop $(docker ps -a -q) ;docker rm $(docker ps -a -q) ;//Stop and delete all containers. Dangerous orders, do not know how to use.
```
