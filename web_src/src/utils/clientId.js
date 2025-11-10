// 生成或获取客户端ID（用于游客反馈）

// 生成UUID
function generateUUID() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
    const r = (Math.random() * 16) | 0
    const v = c === 'x' ? r : (r & 0x3) | 0x8
    return v.toString(16)
  })
}

// 获取Cookie
function getCookie(name) {
  const value = `; ${document.cookie}`
  const parts = value.split(`; ${name}=`)
  if (parts.length === 2) return parts.pop().split(';').shift()
  return null
}

// 设置Cookie
function setCookie(name, value, days) {
  const expires = new Date(Date.now() + days * 864e5).toUTCString()
  document.cookie = `${name}=${value}; expires=${expires}; path=/`
}

// 生成或获取客户端ID
export function getClientId() {
  // 优先从 LocalStorage 读取
  let clientId = localStorage.getItem('showdoc_client_id')

  // 如果不存在，从 Cookie 读取
  if (!clientId) {
    clientId = getCookie('showdoc_client_id')
  }

  // 如果都不存在，生成新的
  if (!clientId) {
    clientId = generateUUID()
  }

  // 同时保存到 LocalStorage 和 Cookie
  localStorage.setItem('showdoc_client_id', clientId)
  setCookie('showdoc_client_id', clientId, 7) // 7天有效期

  return clientId
}

