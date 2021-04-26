// 模版相关的逻辑

const apiTemplateZh = `

    
##### 简要描述

- 用户注册接口

##### 请求URL
- \` http://xx.com/api/user/register \`
  
##### 请求方式
- POST 

##### 参数

|参数名|必选|类型|说明|
|:----    |:---|:----- |-----   |
|username |是  |string |用户名   |
|password |是  |string | 密码    |
|name     |否  |string | 昵称    |

##### 返回示例 

\`\`\` 
  {
    "error_code": 0,
    "data": {
      "uid": "1",
      "username": "12154545",
      "name": "吴系挂",
      "groupid": 2 ,
      "reg_time": "1436864169",
      "last_login_time": "0",
    }
  }
\`\`\`

##### 返回参数说明 

|参数名|类型|说明|
|:-----  |:-----|-----                           |
|groupid |int   |用户组id，1：超级管理员；2：普通用户  |

##### 备注 

- 更多返回错误代码请看首页的错误代码描述



`;

const databaseTemplateZh = `

    
-  用户表，储存用户信息

|字段|类型|空|默认|注释|
|:----    |:-------    |:--- |---|------      |
|uid    |int(10)     |否 |  |             |
|username |varchar(20) |否 |    |   用户名  |
|password |varchar(50) |否   |    |   密码    |
|name     |varchar(15) |是   |    |    昵称     |
|reg_time |int(11)     |否   | 0  |   注册时间  |

- 备注：无

`;

const apiTemplateEn = `
    
##### Brief description

- User Registration Interface


##### Request URL
- \` http://xx.com/api/user/register \`
  
##### Method
- POST 

##### Parameter

|Parameter name|Required|Type|Explain|
|:----    |:---|:----- |-----   |
|username |Yes  |string |Your username   |
|password |Yes  |string | Your password    |
|name     |No  |string | Your name    |

##### Return example 

\`\`\` 
  {
    "error_code": 0,
    "data": {
      "uid": "1",
      "username": "12154545",
      "name": "harry",
      "groupid": 2 ,
      "reg_time": "1436864169",
      "last_login_time": "0",
    }
  }
\`\`\`

##### Return parameter description 

|Parameter name|Type|Explain|
|:-----  |:-----|-----                           |
|groupid |int   |  .|

##### Remark 

- For more error code returns, see the error code description on the home page


`;
const databaseTemplateEn = `
    
-  User table , to store user information


|Field|Type|Empty|Default|Explain|
|:----    |:-------    |:--- |-- -|------      |
|uid    |int(10)     |No |  |             |
|username |varchar(20) |No |    |     |
|password |varchar(50) |No   |    |       |
|name     |varchar(15) |No   |    |         |
|reg_time |int(11)     |No   | 0  |    . |

- Remark : none


`;
export { apiTemplateZh, databaseTemplateZh, apiTemplateEn, databaseTemplateEn };
