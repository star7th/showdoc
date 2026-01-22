import { defineStore } from 'pinia'
import { getUserInfo } from '@/models/user'

export interface UserInfo {
  uid?: number
  username?: string
  name?: string
  email?: string
  user_token?: string
  groupType?: number
  groupid?: number
  email_verify?: number
  reg_time?: string
  avatar?: string
}

export const useUserStore = defineStore('user', {
  state: () => ({
    /** 用户信息 */
    userInfo: null as UserInfo | null,
    /** 新消息数量 */
    newMsg: 0,
    /** 加载状态 */
    loading: false,
  }),

  getters: {
    /** 是否已登录 */
    isLoggedIn: (state) => {
      // 如果 state 中有 userInfo，直接判断
      if (state.userInfo?.user_token) {
        return true
      }
      // 否则尝试从 localStorage 读取
      try {
        const savedInfo = localStorage.getItem('userinfo')
        if (savedInfo) {
          const info = JSON.parse(savedInfo)
          return !!info?.user_token
        }
      } catch (e) {
        console.warn('解析用户信息失败:', e)
      }
      return false
    },
    /** 用户名 */
    username: (state) => state.userInfo?.username || '',
    /** 用户 Token */
    userToken: (state) => {
      // 如果 state 中有 user_token，直接返回
      if (state.userInfo?.user_token) {
        return state.userInfo.user_token
      }
      // 否则尝试从 localStorage 读取
      try {
        const savedInfo = localStorage.getItem('userinfo')
        if (savedInfo) {
          const info = JSON.parse(savedInfo)
          return info?.user_token || ''
        }
      } catch (e) {
        console.warn('解析用户信息失败:', e)
      }
      return ''
    },
    /** 是否是管理员 */
    isAdmin: (state) => state.userInfo && Number(state.userInfo.groupid) === 1,
  },

  actions: {
    /** 设置用户信息 */
    setUserInfo(info: UserInfo | null) {
      this.userInfo = info
      if (info) {
        localStorage.setItem('userinfo', JSON.stringify(info))
      } else {
        localStorage.removeItem('userinfo')
      }
    },

    /** 从本地存储加载用户信息 */
    loadUserInfo() {
      try {
        const savedInfo = localStorage.getItem('userinfo')
        if (savedInfo) {
          this.userInfo = JSON.parse(savedInfo)
        }
      } catch (e) {
        console.warn('解析用户信息失败:', e)
        this.userInfo = null
      }
    },

    /** 获取用户信息 */
    async fetchUserInfo() {
      if (this.loading) return
      this.loading = true
      try {
        const data = await getUserInfo()
        this.userInfo = data
        return data
      } catch (error) {
        console.error('获取用户信息失败:', error)
        this.userInfo = null
        throw error
      } finally {
        this.loading = false
      }
    },

    /** 退出登录 */
    logout() {
      this.userInfo = null
      localStorage.removeItem('userinfo')
    },

    /** 设置新消息数量 */
    setNewMsg(count: number) {
      this.newMsg = count
    },
  },
})

