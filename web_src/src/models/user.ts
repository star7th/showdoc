import request from '@/utils/request'

/**
 * 从 localStorage 获取用户信息
 */
export function getUserInfoFromStorage() {
  try {
    const userinfostr = localStorage.getItem('userinfo')
    if (userinfostr) {
      return JSON.parse(userinfostr)
    }
  } catch (error) {
    console.error('解析用户信息失败:', error)
  }
  return null
}

/**
 * 获取 user_token
 */
export function getUserToken() {
  const userinfo = getUserInfoFromStorage()
  return userinfo?.user_token || ''
}

/**
 * 保存用户信息到 localStorage
 */
export function saveUserInfoToStorage(userinfo: any) {
  try {
    localStorage.setItem('userinfo', JSON.stringify(userinfo))
  } catch (error) {
    console.error('保存用户信息失败:', error)
  }
}

/**
 * 清除用户信息
 */
export function clearUserInfo() {
  localStorage.removeItem('userinfo')
  // 清空所有cookies
  const keys = document.cookie.match(/[^ =;]+(?==)/g)
  if (keys) {
    for (let i = keys.length; i--; ) {
      document.cookie = keys[i] + '=0;expires=' + new Date(0).toUTCString()
    }
  }
  localStorage.clear()
}

// 用户信息类型
export interface UserInfo {
  uid: number
  username: string
  name?: string
  email?: string
  mobile?: string
  groupid: number
  reg_time?: string
  last_login_time?: string
  user_token: string
}

/**
 * 获取当前用户信息
 */
export async function getUserInfo(): Promise<UserInfo | null> {
  try {
    const userInfo = getUserInfoFromStorage()
    if (userInfo) {
      return userInfo as UserInfo
    }
  } catch (error) {
    console.error('获取用户信息失败:', error)
  }
  return null
}

/**
 * 获取所有用户列表（用于添加团队成员）
 */
export function getAllUser(params?: { username?: string }) {
  return request('/api/user/allUser', params || {}, 'post', true, 'form')
}