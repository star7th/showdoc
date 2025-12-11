// 处理页面数据相关的逻辑

// 定义一个html反转义的函数
const unescapeHTML = str =>
  str.replace(
    /&amp;|&lt;|&gt;|&#39;|&#039;|&quot;/g,
    tag =>
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

// HTTP API 转换函数（原 rederPageContent 逻辑）
const renderHttpApiPageContent = (obj, globalParams = {}) => {
  // 判断有没有全局参数，有的话加上。
  if (globalParams) {
    // 全局query
    if (
      obj.info.method == 'get' &&
      globalParams.query &&
      globalParams.query[0] &&
      globalParams.query[0].name
    ) {
      globalParams.query.map(element => {
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
      globalParams.body.map(element => {
        obj.request.params[obj.request.params.mode].unshift(element)
      })
    }
    // 全局header
    if (
      globalParams.header &&
      globalParams.header[0] &&
      globalParams.header[0].name
    ) {
      globalParams.header.map(element => {
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
[TOC]

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
 - ${statusText}`
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

  - \` ${obj.info.url} \`

##### 请求方式
  - ${obj.info.method}
 `
  const pathVariable = obj.request.pathVariable
  if (pathVariable && pathVariable[0] && pathVariable[0].name) {
    newContent += `
##### 路径变量

|变量名|示例值|必选|类型|说明|
|:-----  |:-----|-----|
`
    pathVariable.map(one => {
      // 如果名字为空，或者存在禁用的key且禁用状态生效中，则终止本条参数
      if (!one.name || (one.disable && one.disable >= 1)) return
      newContent += `|${one.name}|${one.value} |${
        one.require > 0 ? '是' : '否'
      } |${one.type} |${one.remark ? one.remark : '无'}   |
`
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
|:-----  |:-----|-----|
`
    const headers = obj.request.headers
    headers.map(one => {
      // 如果名字为空，或者存在禁用的key且禁用状态生效中，则终止本条参数
      if (!one.name || (one.disable && one.disable >= 1)) return
      newContent += `|${one.name}|${one.value} |${
        one.require > 0 ? '是' : '否'
      } |${one.type} | ${one.remark ? one.remark : '无'}   |
`
    })
  }

  const query = obj.request.query

  if (query && query[0] && query[0].name) {
    newContent += `
##### 请求Query参数

|参数名|示例值|必选|类型|说明|
|:-----  |:-----|-----|
`
    query.map(one => {
      // 如果名字为空，或者存在禁用的key且禁用状态生效中，则终止本条参数
      if (!one.name || (one.disable && one.disable >= 1)) return
      newContent += `|${one.name}|${one.value} |${
        one.require > 0 ? '是' : '否'
      } |${one.type} |${one.remark ? one.remark : '无'}   |
`
    })
  }

  const params = obj.request.params[obj.request.params.mode]

  if (params && params[0] && params[0].name) {
    newContent += `
##### 请求Body参数

|参数名|示例值|必选|类型|说明|
|:-----  |:-----|-----|
`
    params.map(one => {
      // 如果名字为空，或者存在禁用的key且禁用状态生效中，则终止本条参数
      if (!one.name || (one.disable && one.disable >= 1)) return
      newContent += `|${one.name}|${one.value} |${
        one.require > 0 ? '是' : '否'
      } |${one.type} |${one.remark ? one.remark : '无'}   |
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

  if (
    obj.request.params.mode == 'json' &&
    jsonDesc &&
    jsonDesc[0] &&
    jsonDesc[0].name
  ) {
    newContent += `
##### 请求json字段说明

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
|:-----  |:-----|-----|
`
    const returnParams = obj.response.responseFailParamsDesc
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

// WebSocket 转换函数
const renderWebSocketPageContent = (obj, globalParams = {}) => {
  let newContent = `
[TOC]

##### 简要描述
  - ${obj.info.description ? obj.info.description : '无'}`

  // 接口状态
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
    if (statusText) {
      newContent += `

##### 接口状态
 - ${statusText} `
    }
  }

  newContent += `

##### 协议类型
  - WebSocket

##### 连接URL
  - \` ${obj.info.url} \`
`

  // 子协议
  if (
    obj.protocolConfig &&
    obj.protocolConfig.websocket &&
    obj.protocolConfig.websocket.subProtocols &&
    Array.isArray(obj.protocolConfig.websocket.subProtocols) &&
    obj.protocolConfig.websocket.subProtocols.length > 0
  ) {
    const protocols = obj.protocolConfig.websocket.subProtocols.join(', ')
    newContent += `
##### 子协议
  - ${protocols}
`
  }

  // Headers（握手阶段）
  const headers = (obj.request && obj.request.headers) || []
  if (headers.length > 0 && headers[0] && headers[0].name) {
    newContent += `
##### 连接Headers

|字段名|示例值|必选|类型|说明|
|:-----  |:-----|:-----|:-----|:-----|
`
    headers.map(one => {
      if (!one.name || (one.disable && one.disable >= 1)) return
      newContent += `|${one.name}|${one.value || ''} |${
        one.require > 0 ? '是' : '否'
      } |${one.type || 'string'} |${one.remark ? one.remark : '无'}   |
`
    })
  }

  // Query 参数
  const query = (obj.request && obj.request.query) || []
  if (query.length > 0 && query[0] && query[0].name) {
    newContent += `
##### 连接Query参数

|参数名|示例值|必选|类型|说明|
|:-----  |:-----|:-----|:-----|:-----|
`
    query.map(one => {
      if (!one.name || (one.disable && one.disable >= 1)) return
      newContent += `|${one.name}|${one.value || ''} |${
        one.require > 0 ? '是' : '否'
      } |${one.type || 'string'} |${one.remark ? one.remark : '无'}   |
`
    })
  }

  // 认证配置
  if (
    obj.request &&
    obj.request.auth &&
    obj.request.auth.type &&
    (!obj.request.auth.disabled || obj.request.auth.disabled !== '1')
  ) {
    let authTypeText = ''
    switch (obj.request.auth.type) {
      case 'bearer':
        authTypeText = 'Bearer Token'
        break
      case 'basic':
        authTypeText = 'Basic Auth'
        break
      default:
        authTypeText = obj.request.auth.type
        break
    }
    newContent += `
##### 认证方式
  - ${authTypeText}
`
  }

  // 重连配置
  if (
    obj.protocolConfig &&
    obj.protocolConfig.websocket &&
    obj.protocolConfig.websocket.autoReconnect
  ) {
    const interval = obj.protocolConfig.websocket.reconnectInterval || 3000
    const maxTimes = obj.protocolConfig.websocket.reconnectMaxTimes || 5
    const maxTimesText = maxTimes > 0 ? `${maxTimes}次` : '无限'
    newContent += `
##### 重连配置
  - 自动重连：是
  - 重连间隔：${interval}毫秒
  - 最大重连次数：${maxTimesText}
`
  }

  // 心跳配置
  if (
    obj.protocolConfig &&
    obj.protocolConfig.websocket &&
    obj.protocolConfig.websocket.heartbeat &&
    obj.protocolConfig.websocket.heartbeat.enabled
  ) {
    const interval = obj.protocolConfig.websocket.heartbeat.interval || 30000
    const pingMsg = obj.protocolConfig.websocket.heartbeat.pingMessage || 'ping'
    newContent += `
##### 心跳配置
  - 心跳间隔：${interval}毫秒
  - Ping 消息：\`${pingMsg}\`
`
  }

  // 消息模板
  if (
    obj.messaging &&
    obj.messaging.templates &&
    Array.isArray(obj.messaging.templates) &&
    obj.messaging.templates.length > 0
  ) {
    newContent += `
##### 消息模板

`
    obj.messaging.templates.map(msg => {
      if (msg.enabled === false) return
      const msgType = msg.type || 'text'
      const msgName = msg.name || '未命名'
      let msgContent = msg.payload || ''

      // 格式化 JSON
      if (msgType === 'json') {
        try {
          const parsed = JSON.parse(msgContent)
          msgContent = JSON.stringify(parsed, null, 2)
        } catch (e) {
          // 如果不是有效的 JSON，保持原样
        }
      }

      newContent += `**${msgName}** (${msgType})
\`\`\`
${msgContent}
\`\`\`

`
    })
  }

  // 消息示例（支持多示例）
  if (
    obj.response &&
    obj.response.examples &&
    Array.isArray(obj.response.examples) &&
    obj.response.examples.length > 0
  ) {
    newContent += `
##### 消息示例

`
    obj.response.examples.map(example => {
      if (!example.data) return

      const exampleName = example.name || '示例'
      let exampleData = example.data

      // 格式化 JSON
      try {
        const parsed = JSON.parse(exampleData)
        exampleData = JSON.stringify(parsed, null, 2)
      } catch (e) {
        // 如果不是有效的 JSON，保持原样
      }

      newContent += `**${exampleName}**
\`\`\`
${exampleData}
\`\`\`

`

      // 字段说明
      if (
        example.param &&
        Array.isArray(example.param) &&
        example.param.length > 0 &&
        example.param[0].name
      ) {
        newContent += `|字段名|类型|说明|
|:-----  |:-----|:-----|
`
        example.param.map(param => {
          if (!param.name) return
          const paramType = param.type || 'string'
          const paramRemark = param.remark ? param.remark : '无'
          newContent += `|${param.name} |${paramType} |${paramRemark}   |
`
        })
        newContent += `
`
      }
    })
  }

  // 备注
  const remark = (obj.response && obj.response.remark) || (obj.info && obj.info.remark) || ''
  if (remark) {
    newContent += `
##### 备注

  ${remark}

`
  }

  return newContent
}

// SSE 转换函数
const renderSSEPageContent = (obj, globalParams = {}) => {
  let newContent = `
[TOC]

##### 简要描述
  - ${obj.info.description ? obj.info.description : '无'}`

  // 接口状态
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
    if (statusText) {
      newContent += `

##### 接口状态
 - ${statusText} `
    }
  }

  newContent += `

##### 协议类型
  - SSE (Server-Sent Events)

##### 请求方法
  - ${(obj.info.method || 'POST').toUpperCase()}

##### 请求URL
  - \` ${obj.info.url} \`
`

  // Headers
  const headers = (obj.request && obj.request.headers) || []
  if (headers.length > 0 && headers[0] && headers[0].name) {
    newContent += `
##### 请求Headers

|字段名|示例值|必选|类型|说明|
|:-----  |:-----|:-----|:-----|:-----|
`
    headers.map(one => {
      if (!one.name || (one.disable && one.disable >= 1)) return
      newContent += `|${one.name}|${one.value || ''} |${
        one.require > 0 ? '是' : '否'
      } |${one.type || 'string'} |${one.remark ? one.remark : '无'}   |
`
    })
  }

  // Query 参数
  const query = (obj.request && obj.request.query) || []
  if (query.length > 0 && query[0] && query[0].name) {
    newContent += `
##### 请求Query参数

|参数名|示例值|必选|类型|说明|
|:-----  |:-----|:-----|:-----|:-----|
`
    query.map(one => {
      if (!one.name || (one.disable && one.disable >= 1)) return
      newContent += `|${one.name}|${one.value || ''} |${
        one.require > 0 ? '是' : '否'
      } |${one.type || 'string'} |${one.remark ? one.remark : '无'}   |
`
    })
  }

  // Body 参数（POST 等方法）
  if (
    obj.request &&
    obj.request.params &&
    obj.request.params.mode &&
    obj.request.params.mode !== 'none'
  ) {
    const mode = obj.request.params.mode
    let modeText = ''
    switch (mode) {
      case 'json':
        modeText = 'JSON'
        break
      case 'urlencoded':
        modeText = 'URL Encoded'
        break
      case 'formdata':
        modeText = 'Form Data'
        break
      default:
        modeText = mode
        break
    }

    if (mode === 'json' && obj.request.params.json) {
      let jsonBody = obj.request.params.json
      try {
        const parsed = JSON.parse(jsonBody)
        jsonBody = JSON.stringify(parsed, null, 2)
      } catch (e) {
        // 如果不是有效的 JSON，保持原样
      }
      newContent += `
##### 请求Body (${modeText})

\`\`\`
${jsonBody}
\`\`\`
`
    } else if (
      mode === 'urlencoded' &&
      obj.request.params.urlencoded &&
      Array.isArray(obj.request.params.urlencoded) &&
      obj.request.params.urlencoded.length > 0
    ) {
      newContent += `
##### 请求Body (${modeText})

|参数名|示例值|必选|类型|说明|
|:-----  |:-----|:-----|:-----|:-----|
`
      obj.request.params.urlencoded.map(one => {
        if (!one.name || (one.disable && one.disable >= 1)) return
        newContent += `|${one.name}|${one.value || ''} |${
          one.require > 0 ? '是' : '否'
        } |${one.type || 'string'} |${one.remark ? one.remark : '无'}   |
`
      })
    } else if (
      mode === 'formdata' &&
      obj.request.params.formdata &&
      Array.isArray(obj.request.params.formdata) &&
      obj.request.params.formdata.length > 0
    ) {
      newContent += `
##### 请求Body (${modeText})

|参数名|示例值|必选|类型|说明|
|:-----  |:-----|:-----|:-----|:-----|
`
      obj.request.params.formdata.map(one => {
        if (!one.name || (one.disable && one.disable >= 1)) return
        newContent += `|${one.name}|${one.value || ''} |${
          one.require > 0 ? '是' : '否'
        } |${one.type || 'string'} |${one.remark ? one.remark : '无'}   |
`
      })
    }
  }

  // 认证配置
  if (
    obj.request &&
    obj.request.auth &&
    obj.request.auth.type &&
    (!obj.request.auth.disabled || obj.request.auth.disabled !== '1')
  ) {
    let authTypeText = ''
    switch (obj.request.auth.type) {
      case 'bearer':
        authTypeText = 'Bearer Token'
        break
      case 'basic':
        authTypeText = 'Basic Auth'
        break
      default:
        authTypeText = obj.request.auth.type
        break
    }
    newContent += `
##### 认证方式
  - ${authTypeText}
`
  }

  // 消息示例（支持多示例）
  if (
    obj.response &&
    obj.response.examples &&
    Array.isArray(obj.response.examples) &&
    obj.response.examples.length > 0
  ) {
    newContent += `
##### 返回示例

`
    obj.response.examples.map(example => {
      if (!example.data) return

      const exampleName = example.name || '示例'
      let exampleData = example.data

      // 格式化 JSON
      try {
        const parsed = JSON.parse(exampleData)
        exampleData = JSON.stringify(parsed, null, 2)
      } catch (e) {
        // 如果不是有效的 JSON，保持原样
      }

      newContent += `**${exampleName}**
\`\`\`
${exampleData}
\`\`\`

`

      // 字段说明
      if (
        example.param &&
        Array.isArray(example.param) &&
        example.param.length > 0 &&
        example.param[0].name
      ) {
        newContent += `|字段名|类型|说明|
|:-----  |:-----|:-----|
`
        example.param.map(param => {
          if (!param.name) return
          const paramType = param.type || 'string'
          const paramRemark = param.remark ? param.remark : '无'
          newContent += `|${param.name} |${paramType} |${paramRemark}   |
`
        })
        newContent += `

`
      }
    })
  }

  // 备注
  const remark =
    (obj.response && obj.response.remark) || (obj.info && obj.info.remark) || ''
  if (remark) {
    newContent += `
##### 备注

  ${remark}

`
  }

  return newContent
}

export { rederPageContent, unescapeHTML }
