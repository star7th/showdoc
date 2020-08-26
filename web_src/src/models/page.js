// 处理页面数据相关的逻辑

// 渲染来自runapi的文档
const rederPageContent = page_content => {
  let obj;
  // 先定义一个html反转义的函数
  const unescapeHTML = str =>
    str.replace(
      /&amp;|&lt;|&gt;|&#39;|&quot;/g,
      tag =>
        ({
          "&amp;": "&",
          "&lt;": "<",
          "&gt;": ">",
          "&#39;": "'",
          "&quot;": '"'
        }[tag] || tag)
    );
  page_content = unescapeHTML(page_content);
  try {
    obj = JSON.parse(page_content);
  } catch (e) {
    // console.log(`不支持解析的页面内容：${page_content}`);
  }
  if (!obj || !obj.info || !obj.info.url) {
    return page_content;
  }
  console.log(obj);
  let newContent = `
[TOC]

##### 简要描述
  - ${obj.info.description ? obj.info.description : "无"}

##### 请求URL

  - \` ${obj.info.url} \`

##### 请求方式
  - ${obj.info.method}
 `;

  if (
    obj.request.headers &&
    obj.request.headers[0] &&
    obj.request.headers[0].name
  ) {
    newContent += `
##### Header

|header|必选|类型|说明|
|:-----  |:-----|-----|
`;
    const headers = obj.request.headers;
    headers.map(one => {
      newContent += `|${one.name} |${one.require > 0 ? "是" : "否"} |${
        one.type
      } |${one.remark ? one.remark : "无"}   |
`;
    });
  }
  const params = obj.request.params[obj.request.params.mode];

  if (params && params[0] && params[0].name) {
    newContent += `
##### 请求参数

|参数名|必选|类型|说明|
|:-----  |:-----|-----|
`;
    params.map(one => {
      newContent += `|${one.name} |${one.require > 0 ? "是" : "否"} |${
        one.type
      } |${one.remark ? one.remark : "无"}   |
`;
    });
  }

  if (obj.request.params.mode == "json" && params) {
    newContent += `
##### 请求参数示例
\`\`\`
${params}
\`\`\`

`;
  }

  const jsonDesc = obj.request.params.jsonDesc;

  if ( obj.request.params.mode == "json" && jsonDesc && jsonDesc[0] && jsonDesc[0].name) {
    newContent += `
##### json字段说明

|字段名|必选|类型|说明|
|:-----  |:-----|-----|
`;
    jsonDesc.map(one => {
      newContent += `|${one.name} |${one.require > 0 ? "是" : "否"} |${
        one.type
      } |${one.remark ? one.remark : "无"}   |
`;
    });
  }

  if (obj.response.responseExample) {
    newContent += `
##### 返回示例 
\`\`\`
${obj.response.responseExample}
   \`\`\`
   `;
  }

  if (
    obj.response.responseParamsDesc &&
    obj.response.responseParamsDesc[0] &&
    obj.response.responseParamsDesc[0].name
  ) {
    newContent += `
##### 返回参数说明

|参数名|类型|说明|
|:-----  |:-----|-----|
`;
    const returnParams = obj.response.responseParamsDesc;
    returnParams.map(one => {
      newContent += `|${one.name} |${one.type} |${
        one.remark ? one.remark : "无"
      }   |
`;
    });
  }

  newContent += `
##### 备注

  ${obj.info.remark}

`;

  return newContent;
};

export { rederPageContent };
