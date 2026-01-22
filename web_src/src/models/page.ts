// 处理页面数据相关的逻辑

// 定义一个html反转义的函数
const unescapeHTML = (str: string): string =>
  str.replace(
    /&amp;|&lt;|&gt;|&#39;|&#039;|&quot;/g,
    (tag: string) =>
      ({
        '&amp;': '&',
        '&lt;': '<',
        '&gt;': '>',
        '&#39;': "'",
        '&#039;': "'",
        '&quot;': '"'
      }[tag] || tag)
  )

// 渲染来自runapi的文档
export const renderPageContent = (page_content: string, globalParams: any = {}): string => {
  let obj: any

  page_content = unescapeHTML(page_content)
  try {
    obj = JSON.parse(page_content)
  } catch (e) {
    // console.log(`不支持解析的页面内容：${page_content}`);
    return page_content
  }
  
  if (!obj || !obj.info || !obj.info.url) {
    return page_content
  }

  // 判断类型（兼容旧数据）
  const type = obj.info.type || 'api'

  if (type === 'websocket') {
    return renderWebSocketPageContent(obj, globalParams)
  } else if (type === 'sse') {
    return renderSSEPageContent(obj, globalParams)
  } else {
    return renderHttpApiPageContent(obj, globalParams)
  }
}

// HTTP API 转换函数
const renderHttpApiPageContent = (obj: any, globalParams: any = {}): string => {
  // 判断有没有全局参数，有的话加上。
  if (globalParams) {
    // 全局query
    if (
      obj.info.method == 'get' &&
      globalParams.query &&
      globalParams.query[0] &&
      globalParams.query[0].name
    ) {
      globalParams.query.forEach((element: any) => {
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
      globalParams.body.forEach((element: any) => {
        obj.request.params[obj.request.params.mode].unshift(element)
      })
    }
    // 全局header
    if (
      globalParams.header &&
      globalParams.header[0] &&
      globalParams.header[0].name
    ) {
      globalParams.header.forEach((element: any) => {
        obj.request.headers.unshift(element)
      })
    }
  } // 全局参数处理完毕

  // 兼容query
  if (obj.info.method == 'get') {
    if (!obj.request.query) {
      obj.request.query = obj.request.params[obj.request.params.mode]
    }
    obj.request.params[obj.request.params.mode] = []
  }

  let newContent = `
##### 简要描述
  - ${obj.info.description ? obj.info.description : '无'}`

  if (obj.info.apiStatus > 0) {
    let statusText = ''
    switch (obj.info.apiStatus) {
      case '1':
        statusText = '开发中'
        break
      case '2':
        statusText = '测试中'
        break
      case '3':
        statusText = '已完成'
        break
      case '4':
        statusText = '需修改'
        break
      case '5':
        statusText = '已废弃'
        break
      default:
        break
    }

    newContent += `

##### 接口状态
 - ${statusText} `
  }

  // 如果有query参数组，则把url中的参数去掉
  if (
    obj.request.query &&
    obj.request.query[0] &&
    obj.request.query[0]['name']
  ) {
    const words = obj.info.url.split('?')
    obj.info.url = words[0]
  }

  newContent += `

##### 请求URL

  - \`${obj.info.url}\`

##### 请求方式
  - ${obj.info.method}
`
  const pathVariable = obj.request.pathVariable
  if (pathVariable && pathVariable[0] && pathVariable[0].name) {
    newContent += `
##### 路径变量

|变量名|示例值|必选|类型|说明|
|:-----|:-----|:-----|:-----|:-----|
`
    pathVariable.forEach((one: any) => {
      if (!one.name || (one.disable && one.disable >= 1)) return
      newContent += `|${one.name}|${one.value}|${
        one.require > 0 ? '是' : '否'
      }|${one.type}|${one.remark ? one.remark : '无'}|\n`
    })
  }

  if (
    obj.request.headers &&
    obj.request.headers[0] &&
    obj.request.headers[0].name
  ) {
    newContent += `
##### Header

|字段名|示例值|必选|类型|说明|
|:-----|:-----|:-----|:-----|:-----|
`
    const headers = obj.request.headers
    headers.forEach((one: any) => {
      if (!one.name || (one.disable && one.disable >= 1)) return
      newContent += `|${one.name}|${one.value}|${
        one.require > 0 ? '是' : '否'
      }|${one.type}|${one.remark ? one.remark : '无'}|\n`
    })
  }

  const query = obj.request.query
  if (query && query[0] && query[0].name) {
    newContent += `
##### 请求Query参数

|参数名|示例值|必选|类型|说明|
|:-----|:-----|:-----|:-----|:-----|
`
    query.forEach((one: any) => {
      if (!one.name || (one.disable && one.disable >= 1)) return
      newContent += `|${one.name}|${one.value}|${
        one.require > 0 ? '是' : '否'
      }|${one.type}|${one.remark ? one.remark : '无'}|\n`
    })
  }

  const params = obj.request.params[obj.request.params.mode]

  if (params && params[0] && params[0].name) {
    newContent += `
##### 请求Body参数

|参数名|示例值|必选|类型|说明|
|:-----|:-----|:-----|:-----|:-----|
`
    params.forEach((one: any) => {
      if (!one.name || (one.disable && one.disable >= 1)) return
      newContent += `|${one.name}|${one.value}|${
        one.require > 0 ? '是' : '否'
      }|${one.type}|${one.remark ? one.remark : '无'}|\n`
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

  if (
    obj.request.params.mode == 'json' &&
    jsonDesc &&
    jsonDesc[0] &&
    jsonDesc[0].name
  ) {
    newContent += `
##### 请求json字段说明

|字段名|必选|类型|说明|
|:-----|:-----|:-----|:-----|
`
    jsonDesc.forEach((one: any) => {
      if (!one.name) return
      newContent += `|${one.name}|${one.require > 0 ? '是' : '否'}|${
        one.type
      }|${one.remark ? one.remark : '无'}|\n`
    })
  }

  if (obj.response.responseExample) {
    newContent += `
##### 成功返回示例
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
##### 成功返回示例的参数说明

|参数名|类型|说明|
|:-----|:-----|:-----|
`
    const returnParams = obj.response.responseParamsDesc
    returnParams.forEach((one: any) => {
      if (!one.name) return
      newContent += `|${one.name}|${one.type}|${
        one.remark ? one.remark : '无'
      }|\n`
    })
  }

  if (obj.response.responseFailExample) {
    newContent += `
##### 失败返回示例
\`\`\`
${obj.response.responseFailExample}
   \`\`\`
   `
  }

  if (
    obj.response.responseFailParamsDesc &&
    obj.response.responseFailParamsDesc[0] &&
    obj.response.responseFailParamsDesc[0].name
  ) {
    newContent += `
##### 失败返回示例的参数说明

|参数名|类型|说明|
|:-----|:-----|:-----|
`
    const returnParams = obj.response.responseFailParamsDesc
    returnParams.forEach((one: any) => {
      if (!one.name) return
      newContent += `|${one.name}|${one.type}|${
        one.remark ? one.remark : '无'
      }|\n`
    })
  }

  newContent += `
##### 备注

  ${obj.info.remark || ''}

`

  return newContent
}

// WebSocket 转换函数（简化版，后续可扩展）
const renderWebSocketPageContent = (obj: any, globalParams: any = {}): string => {
  let newContent = `
##### 简要描述
  - ${obj.info.description ? obj.info.description : '无'}

##### 协议类型
  - WebSocket

##### 连接URL
  - \`${obj.info.url}\`

##### 备注
  ${obj.info.remark || ''}

`
  return newContent
}

// SSE 转换函数（简化版，后续可扩展）
const renderSSEPageContent = (obj: any, globalParams: any = {}): string => {
  let newContent = `
##### 简要描述
  - ${obj.info.description ? obj.info.description : '无'}

##### 协议类型
  - SSE (Server-Sent Events)

##### 请求方法
  - ${(obj.info.method || 'POST').toUpperCase()}

##### 请求URL
  - \`${obj.info.url}\`

##### 备注
  ${obj.info.remark || ''}

`
  return newContent
}

export { unescapeHTML }

