### Foreword
 The automatic script script uses Docker to install the runtime environment for Linux servers. If your server does not have a Docker service, the script will try to install it. The process of installing Docker may be a bit slow. If you have already installed docker, the script will omit some of the steps to speed up the ShowDoc installation.
 
When the script fails to install Docker, you can manually install Docker before executing the script. If you still have Docker installed, you can (un/re-)install and debug it step by step according to this tutorial: [ByDocker.md](https://github.com/star7th/showdoc/blob/master/documentation/en/ByDocker.md)

If the server system itself does not support Docker, you can only run ShowDoc by manually installing the PHP environment:  [DeployManual.md](https://github.com/star7th/showdoc/blob/master/documentation/en/DeployManual.md)


### Instructions


 
 ```
  # Download the script and give permission
 Wget https://www.showdoc.cc/script/showdoc;chmod +x showdoc;
  
  # Default installation is the Chinese version. If you want to install the English version, please add the en parameter, such as ./showdoc en
  ./showdoc en
 
 ```


### Post-installation instructions

Once installed, the ShowDoc data will be stored in the /showdoc_data/html directory. The ./showdoc script can be placed in any directory for later use. You can also re-download it from the official address.

You can open ShowDoc by opening http://your-domain.com:4999 (replace your-domain.com with your server domain name or IP-address). The default admin account is Username: showdoc Password: 123456 . After logging in, you can see the management background entry in the upper right. It is recommended to change the password after login.

For issues or suggestions on ShowDoc, please go to https://github.com/star7th/showdoc for an issue.

### Development & Contribution

Please refer to: [Development&Contribution.md](https://github.com/star7th/showdoc/blob/master/documentation/en/Development&Contribution.md)


### Upgrade from manual mode to automatic script mode
If you have previously installed ShowDoc manually, consider upgrading to this automatic scripting method. After upgrading to the script mode, you can use the automation features of the script, such as upgrading to the latest version, restarting, uninstalling, and so on.
Upgrade method:

1, First refer to the previous section (Post-installation instructions), then install ShowDoc on the server

2, The original ShowDoc directory Sqlite / showdoc.db.php override /showdoc_data/html/Sqlite/showdoc.db.php, Public / Uploads override /showdoc_data/html/Public/Uploads

3, Execute the command

 ```
 Chmod 777 -R /showdoc_data/html
 ./showdoc update

 ```



 
### Other commands

 ```
 
 # Attach the script to other commands, so you can use it when managing ShowDoc.

 # Stop ShowDoc
 ./showdoc stop
 
 # Restart ShowDoc
 ./showdoc restart

 # Update ShowDoc to the latest version
 ./showdoc update
  
 # Uninstall ShowDoc
 ./showdoc uninstall
 
 ```
