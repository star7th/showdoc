// 处理页面数据相关的逻辑

// 定义一个html反转义的函数
const unescapeHTML = str =>
  str.replace(
    /&amp;|&lt;|&gt;|&#39;|&quot;/g,
    tag =>
      ({
        '&amp;': '&',
        '&lt;': '<',
        '&gt;': '>',
        '&#39;': "'",
        '&quot;': '"'
      }[tag] || tag)
  )

// 渲染来自runapi的文档
const rederPageContent = (page_content, globalParams = {}) => {
  let obj

  page_content = unescapeHTML(page_content)
  try {
    obj = JSON.parse(page_content)
  } catch (e) {
    // console.log(`不支持解析的页面内容：${page_content}`);
  }
  if (!obj || !obj.info || !obj.info.url) {
    return page_content
  }
  // console.log(obj)

  // 判断有没有全局参数，有的话加上。
  if (globalParams) {
    // 全局query
    if (
      obj.info.method == 'get' &&
      globalParams.query &&
      globalParams.query[0] &&
      globalParams.query[0].name
    ) {
      globalParams.query.map((element) => {
        obj.request.params[obj.request.params.mode].unshift(element)
      })
    }
    // 全局body
    if (
      obj.info.method != 'get' &&
      obj.request.params.mode != 'json' &&
      globalParams.body &&
      globalParams.body[0] &&
      globalParams.body[0].name
    ) {
      globalParams.body.map((element) => {
        obj.request.params[obj.request.params.mode].unshift(element)
      })
    }
    // 全局header
    if (globalParams.header && globalParams.header[0] && globalParams.header[0].name) {
      globalParams.header.map((element) => {
        obj.request.headers.unshift(element)
      })
    }
  } // 全局参数处理完毕
  let newContent = `
[TOC]

##### 简要描述
  - ${obj.info.description ? obj.info.description : '无'}

##### 请求URL

  - \` ${obj.info.url} \`

##### 请求方式
  - ${obj.info.method}
 `

  if (
    obj.request.headers &&
    obj.request.headers[0] &&
    obj.request.headers[0].name
  ) {
    newContent += `
##### Header

|header|必选|类型|说明|
|:-----  |:-----|-----|
`
    const headers = obj.request.headers
    headers.map(one => {
      // 如果名字为空，或者存在禁用的key且禁用状态生效中，则终止本条参数
      if (!one.name || (one.disable && one.disable >= 1)) return
      newContent += `|${one.name} |${one.require > 0 ? '是' : '否'} |${
        one.type
        } |${one.remark ? one.remark : '无'}   |
`
    })
  }
  const params = obj.request.params[obj.request.params.mode]

  if (params && params[0] && params[0].name) {
    newContent += `
##### 请求参数

|参数名|必选|类型|说明|
|:-----  |:-----|-----|
`
    params.map(one => {
      // 如果名字为空，或者存在禁用的key且禁用状态生效中，则终止本条参数
      if (!one.name || (one.disable && one.disable >= 1)) return
      newContent += `|${one.name} |${one.require > 0 ? '是' : '否'} |${
        one.type
        } |${one.remark ? one.remark : '无'}   |
`
    })
  }

  if (obj.request.params.mode == 'json' && params) {
    newContent += `
##### 请求参数示例
\`\`\`
${params}
\`\`\`

`
  }

  const jsonDesc = obj.request.params.jsonDesc

  if (obj.request.params.mode == 'json' && jsonDesc && jsonDesc[0] && jsonDesc[0].name) {
    newContent += `
##### json字段说明

|字段名|必选|类型|说明|
|:-----  |:-----|-----|
`
    jsonDesc.map(one => {
      if (!one.name) return
      newContent += `|${one.name} |${one.require > 0 ? '是' : '否'} |${
        one.type
        } |${one.remark ? one.remark : '无'}   |
`
    })
  }

  if (obj.response.responseExample) {
    newContent += `
##### 返回示例
\`\`\`
${obj.response.responseExample}
   \`\`\`
   `
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
`
    const returnParams = obj.response.responseParamsDesc
    returnParams.map(one => {
      if (!one.name) return
      newContent += `|${one.name} |${one.type} |${
        one.remark ? one.remark : '无'
        }   |
`
    })
  }

  newContent += `
##### 备注

  ${obj.info.remark}

`

  return newContent
}

export { rederPageContent, unescapeHTML }
