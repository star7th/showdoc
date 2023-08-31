### Foreword

The automatic script uses Docker to install the runtime environment for Linux servers. If your server does't have a Docker service, the script will try to install it. The process of installing Docker maybe a bit slow. If you have already installed Docker, the script will omit some of the steps to speed up the ShowDoc installation.

When the script fails to install Docker, you should manually install Docker before executing the script. If you still have problem in installing Docker, you can (un/re-)install and debug it step by step according to this tutorial: [ByDocker.md](https://github.com/star7th/showdoc/blob/master/documentation/en/ByDocker.md)

If the server does't support Docker, you can only run ShowDoc by manually installing the PHP environment:  [DeployManual.md](https://github.com/star7th/showdoc/blob/master/documentation/en/DeployManual.md)

### Instructions

```bash
# Download the script and run
curl -fL https://www.showdoc.cc/script/showdoc | bash

# Default is the Chinese version. If you want to install the English version, please add the en parameter
curl -fL https://www.showdoc.cc/script/showdoc | bash -s en
```


### Post-installation instructions

Once installed, the ShowDoc data will be stored in the /showdoc_data/html directory.

You can open ShowDoc by opening http://your-domain.com:4999 (replace your-domain.com with your server domain name or IP-address).

The default admin account is: `showdoc`.
The default admin password is: `123456`.

After logging in, you can see the management background entry in the upper right. It is recommended to change the password.

For issues or suggestions on ShowDoc, please go to https://github.com/star7th/showdoc for an issue.

### Development & Contribution

Please refer to: [Development&Contribution.md](https://github.com/star7th/showdoc/blob/master/documentation/en/Development&Contribution.md)

### Upgrade from manual mode to automatic script mode

If you have previously installed ShowDoc manually, consider upgrading to this automatic scripting method. After upgrading to the script mode, you can use the automation features of the script, such as upgrading to the latest version, restart, uninstall, etc.

Upgrade method:

1. First refer to the previous section (Post-installation instructions), then install ShowDoc on the server

2. The original ShowDoc directory Sqlite/showdoc.db.php override /showdoc_data/html/Sqlite/showdoc.db.php, Public/Uploads override /showdoc_data/html/Public/Uploads

3. Execute the command

```bash
chmod 777 -R /showdoc_data/html
curl -fL https://www.showdoc.cc/script/showdoc | bash -s update
```


### Other commands

```bash
# Attach the script to other commands, so you can use it when managing ShowDoc.
# Stop ShowDoc
curl -fL https://www.showdoc.cc/script/showdoc | bash -s stop

# Restart ShowDoc
curl -fL https://www.showdoc.cc/script/showdoc | bash -s restart

# Update ShowDoc to the latest version
curl -fL https://www.showdoc.cc/script/showdoc | bash -s update

# Uninstall ShowDoc
curl -fL https://www.showdoc.cc/script/showdoc | bash -s uninstall
```
