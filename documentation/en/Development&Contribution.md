#### Technical stack description

Frontend: Vue + ElementUI

Backend: In order to be compatible with the low-level php runtime environment (compatible with 5.3), the conservative ThinkPHP 3.2.3 framework is used.

Database: ShowDoc comes with a file database (/Sqlite/showdoc.db.php), no need to manually install the database

#### Preparation before development

Developing a machine requires first installing the PHP environment and the NodeJS environment.
Download the code and place it in the www directory under the PHP environment

First access in the browser through the address, in order to complete the initial installation of ShowDoc (if it has been installed, ignore it)

Go to ShowDoc's web_src directory on the command line and execute npm install to install the dependencies. (If there is no npm, you must first install the NodeJS environment)


#### Front-end development

Execute npm run dev to enable the mode, and you can see the effect of the changes in real time by accessing localhost:8080. Please use a proxy to proxy to the PHP server when requesting the backend API.

The npm run build needs to be executed before the final package takes effect. The packaged static files will be in the /web directory.

Mainly related to the directories and files:

```
Web_src/src/components //page components are basically placed here

Web_src/src/router // page routing. Can target components based on url

Web_src/static // static resource directory

Web_src/static/lang // front-end language pack

```

#### Backend development

Mainly related directories and files

```

Server/Application/Api/ //Application directory, basically all background apis are placed here

Server/Application/Runtime/Logs //If there is an error log, it will print out the browser directly or print it here.

Public/Uploads //The uploaded image is placed here

Server/Application/Api/Lang //Backend language pack

```


#### other instructions

Please respect the open-source agreement after the second development, retain the copyright-logo and link
If you have developed useful features, you may wish to contribute to the official GitHub code repository for sharing with everyone.

The upgrade of ShowDoc may overwrite your original secondary development. If you want to be compatible, it is best to submit it to the official warehouse to become an official function.
