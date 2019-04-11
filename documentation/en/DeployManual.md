### Introduction 

 About ShowDoc , Please refer to [/README.md](README.md)
 
### Environment dependence

 - Required environment
  
  `PHP5.3 or  above version ` ,`php-gd`,`php-pdo`
 
### Install and configure

- #### New installation
 
 - Manually download the code from https://github.com/star7th/showdoc/
 
 - Make these writable

  Example: `sudo chmod -R 777 server/showdoc/install`
  
  `/install`, `/Application/Runtime ` ,`/Public/Uploads`,`/Sqlite`,`/Sqlite/showdoc.db.php`
   
 - Windows sever
    
   Please entable `extension=php_sqlite.dll` ，`extension=php_pdo_sqlite.dll`and `php_mbstring.dll` in `php.ini` . Ignore it if you are on Linux.
 
 - Run installation
 
     `http://your-domain.com/install/`
   
 -  Default Admin 

   Username : showdoc
   Password : 123456
   
- #### Upgrade 

 - Manually download the code from https://github.com/star7th/showdoc/
 - Backup your codes via: `mv showdoc  showdoc_backup`
 - Dowload new codes and decompression it to a new directory.Copy `/Sqlite/*` and `/Public/Uploads/*` from old directory to new directory
 
 - Open `http://your-domain.com/index.php?s=/home/update/db`  in your brown to update database 
 
 
