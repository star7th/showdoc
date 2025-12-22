#### Technical stack description

Frontend: Vue + ElementUI

Backend: PHP + Slim 4 + Illuminate/Database
- Architecture: Slim 4 micro-framework + Illuminate/Database ORM
- Core features: PSR-7 HTTP messages, PSR-11 dependency injection, PSR-15 middleware
- PHP requirement: PHP 7.4+ (recommended 8.0+)
- Compatibility: Fully compatible with existing API interfaces and URL formats (including `/server/index.php?s=...`)

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

Server/app/Api/Controller/ //API controllers directory, all background APIs are placed here

Server/app/Common/Helper/ //Helper classes directory (Security, HttpHelper, FileHelper, etc.)

Server/app/Model/ //Model classes directory (User, Item, Page, etc.)

Server/app/Runtime/Logs/ //Error logs directory, errors will be printed to browser or logged here

Public/Uploads/ //Uploaded images are placed here

Server/app/Api/Lang/ //Backend language pack

```


#### other instructions

Please respect the open-source agreement after the second development, retain the copyright-logo and link
If you have developed useful features, you may wish to contribute to the official GitHub code repository for sharing with everyone.

The upgrade of ShowDoc may overwrite your original secondary development. If you want to be compatible, it is best to submit it to the official warehouse to become an official function.
