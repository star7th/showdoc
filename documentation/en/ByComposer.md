Composer is a tool for dependency management in PHP. It allows you to declare the libraries your project depends on and it will manage (install/update) them for you.https://getcomposer.org/
You can install ShowDoc by Composer automatically.

- New installation

 Change to web directory（e.g：/var/www/html/）and execute:

 ```
 composer create-project  showdoc/showdoc
 ```
There will be a folder name "showdoc".Open your browser and visit 
http://your-domain.com/showdoc/install/ (change to your servser's address) to initialize ShowDoc.

- Upgrade

 Stop apache or nginx , and move showdoc folder to bakup
 ```
 mv showdoc  showdoc_backup 
 ```
Then flollow the "New installation" guide to install ShowDoc .After that ,move db file and pictures from old folder to new folder.
```
rm showdoc/Sqlite/showdoc.db.php
cp showdoc_backup/Sqlite/showdoc.db.php  showdoc/Sqlite/showdoc.db.php
rm -r showdoc/Public/Uploads/
cp -r showdoc_backup/Public/Uploads/ showdoc/Public/
```
Open your browser and visit 
http://your-domain.com/showdoc/index.php?s=/home/update/db (change to your servser's address) to update db.


