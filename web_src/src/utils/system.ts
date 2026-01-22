/**
 * 系统级配置和工具函数
 */

/**
 * DocConfig - 系统配置对象
 */
export const DocConfig = {
  server: (() => {
    return '../server/index.php?s='
  })(),
  originalServer: (() => {
    return '../server/index.php?s='
  })(),
}

/**
 * 获取后端服务地址
 * 优先使用 HTML 中定义的全局变量 window.DocConfig.server
 * 如果没有则使用默认值
 */
export const getServerHost = (): string => {
  if (typeof window !== 'undefined' && window.DocConfig?.server) {
    return window.DocConfig.server
  }
  return DocConfig.server
}

// 获取原始的后端地址 originalServer 
export const getOriginalServerHost = (): string => {
  if (typeof window !== 'undefined' && window.DocConfig?.originalServer) {
    return window.DocConfig.originalServer
  }
  return DocConfig.originalServer
}

/**
 * 获取服务器基础路径（用于构建完整的 URL）
 * 返回 '../server/'，适用于需要域名 + 完整路径的场景（如支付跳转、下载等）
 * @returns 服务器基础路径，例如 '../server/'
 */
export const getServerBasePath = (): string => {
  // 从 serverHost 中提取 ../server/
  const serverHost = getServerHost()
  return (
    serverHost?.replace('/index.php?s=', '')?.replace('/?s=', '') || '../server/'
  )
}

/**
 * 获取静态资源路径
 * 从 window.DocConfig.staticPath 中获取，用于加载外部静态资源（如 xspreadsheet、whiteboard 等）
 * @returns 静态资源路径，例如 './'
 */
export const getStaticPath = (): string => {
  if (typeof window !== 'undefined' && window.DocConfig?.staticPath) {
    return window.DocConfig.staticPath
  }
  return './'
}

/**
 * 从 serverHost 中提取基础 URL（去掉 /server/index.php?s= 等后缀）
 * @param serverHost serverHost 值，例如 "../server/index.php?s="
 * @returns 基础 URL，例如 "../"
 */
export const getBaseUrl = (serverHost?: string): string => {
  if (!serverHost) {
    serverHost = getServerHost()
  }
  let url = serverHost
  url = url?.replace('/server/index.php?s=', '')
  url = url?.replace('/server/?s=', '')
  // 去掉末尾的斜杠
  url = url?.replace(/\/$/, '')
  return url || serverHost
}

/**
 * 向 URL 追加参数，自动判断使用 ? 还是 &
 * @param url 基础URL
 * @param param 参数名
 * @param value 参数值
 * @returns 拼接后的完整URL
 */
export const appendUrlParam = (
  url: string,
  param: string,
  value: string
): string => {
  if (!url || !param) return url
  const separator = url.indexOf('?') > -1 ? '&' : '?'
  return `${url}${separator}${param}=${encodeURIComponent(value)}`
}

/**
 * 向 URL 追加多个参数，自动判断使用 ? 还是 &
 * @param url 基础URL
 * @param params 参数对象，例如 { item_id: '123', cat_id: '456' }
 * @returns 拼接后的完整URL
 */
export const appendUrlParams = (
  url: string,
  params: Record<string, string | number>
): string => {
  if (!url || !params || Object.keys(params).length === 0) return url

  let resultUrl = url
  const isFirst = url.indexOf('?') === -1

  Object.keys(params).forEach((key, index) => {
    const value = params[key]
    const prefix = isFirst && index === 0 ? '?' : '&'
    resultUrl += `${prefix}${key}=${encodeURIComponent(String(value))}`
  })

  return resultUrl
}

/**
 * 将任意类型转换为字符串 '0' 或 '1'
 * 兼容后端返回的各种数据类型：数字(1/0)、字符串('1'/'0')、布尔值(true/false)
 * @param value 任意类型的值
 * @returns '0' 或 '1'
 *
 * @example
 * normalizeToBinaryString(1) // '1'
 * normalizeToBinaryString('1') // '1'
 * normalizeToBinaryString(true) // '1'
 * normalizeToBinaryString(0) // '0'
 * normalizeToBinaryString('0') // '0'
 * normalizeToBinaryString(false) // '0'
 */
export const normalizeToBinaryString = (value: any): string => {
  return value == 1 ? '1' : '0'
}

/**
 * 将布尔值或字符串 '0'/'1' 转换为数字 0/1
 * @param value 布尔值、字符串或数字
 * @returns 0 或 1
 *
 * @example
 * normalizeBooleanToNumber(true) // 1
 * normalizeBooleanToNumber(false) // 0
 * normalizeBooleanToNumber('1') // 1
 * normalizeBooleanToNumber('0') // 0
 * normalizeBooleanToNumber(1) // 1
 * normalizeBooleanToNumber(0) // 0
 */
export const normalizeBooleanToNumber = (
  value: boolean | string | number
): number => {
  return value == 1 ? 1 : 0
}

/**
 * 将字符串 '0'/'1' 或布尔值转换为布尔类型
 * @param value 字符串、布尔值或数字
 * @returns true 或 false
 *
 * @example
 * normalizeToBoolean('1') // true
 * normalizeToBoolean('0') // false
 * normalizeToBoolean(true) // true
 * normalizeToBoolean(false) // false
 * normalizeToBoolean(1) // true
 * normalizeToBoolean(0) // false
 */
export const normalizeToBoolean = (value: any): boolean => {
  return value == 1 ? true : false
}
