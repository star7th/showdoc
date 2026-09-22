import { defineStore } from 'pinia'
import { getUserInfo } from '@/models/user'
import request from '@/utils/request'

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
  is_sso?: number
}

export const useUserStore = defineStore('user', {
  state: () => ({
    /** 用户信息 */
    userInfo: null as UserInfo | null,
    /** 新消息数量 */
    newMsg: 0,
    /** 新消息类型：'remind' | 'announcement' | '' */
    newMsgType: '' as 'remind' | 'announcement' | '',
    /** 是否已创建过 AI 令牌（用于 AI 接入入口红点） */
    hasAiToken: false,
    /** 是否已检查过 AI 令牌状态 */
    aiTokenChecked: false,
    /** AI 令牌状态检查中 */
    aiTokenChecking: false,
    /** 是否曾经创建过令牌（含已删光，持久化到 localStorage，按账号维度） */
    aiTokenEverCreated: false,
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
    /** 是否通过 SSO（OAuth2/LDAP/CAS/SecretKey）登录 */
    isSso: (state) => !!(state.userInfo && Number(state.userInfo.is_sso)),
    /** AI 接入入口是否显示红点（已检查、无令牌且从未创建过令牌） */
    showAiDot: (state) =>
      state.aiTokenChecked && !state.hasAiToken && !state.aiTokenEverCreated,
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
      this.hasAiToken = false
      this.aiTokenChecked = false
      this.aiTokenChecking = false
      this.aiTokenEverCreated = false
    },

    /** 设置新消息数量 */
    setNewMsg(count: number, type?: 'remind' | 'announcement') {
      this.newMsg = count
      if (type) {
        this.newMsgType = type
      }
    },

    /** 清除新消息标记 */
    clearNewMsg() {
      this.newMsg = 0
      this.newMsgType = ''
    },

    /** 同步 AI 令牌状态（弹窗内增删令牌后调用） */
    setAiTokenStatus(has: boolean) {
      this.hasAiToken = has
      this.aiTokenChecked = true
      if (has) this.markAiTokenEverCreated()
    },

    /** 标记当前账号曾经创建过 AI 令牌（持久化，按账号维度） */
    markAiTokenEverCreated() {
      this.aiTokenEverCreated = true
      const name = this.getAiTokenAccountKey()
      if (name !== '') {
        try {
          localStorage.setItem(`ai_token_ever_created_${name}`, '1')
        } catch (e) {
          // 忽略存储异常
        }
      }
    },

    /** 从 localStorage 恢复当前账号的「曾经创建过令牌」标记 */
    loadAiTokenEverCreated() {
      const name = this.getAiTokenAccountKey()
      if (name === '') {
        this.aiTokenEverCreated = false
        return
      }
      try {
        this.aiTokenEverCreated = localStorage.getItem(`ai_token_ever_created_${name}`) === '1'
      } catch (e) {
        this.aiTokenEverCreated = false
      }
    },

    /** 生成按账号维度的存储 key 后缀（优先 username，回退 uid；与 isLoggedIn 一致地从 localStorage 兜底） */
    getAiTokenAccountKey(): string {
      let name = this.userInfo?.username || this.userInfo?.uid || ''
      if (name === '') {
        try {
          const savedInfo = localStorage.getItem('userinfo')
          if (savedInfo) {
            const info = JSON.parse(savedInfo)
            name = info?.username || info?.uid || ''
          }
        } catch (e) {
          // 解析失败则返回空
        }
      }
      return name !== '' ? String(name) : ''
    },

    /** 检查当前用户是否创建过 AI 令牌（静默，仅登录用户，带防重） */
    async fetchAiTokenStatus(force = false) {
      if (!this.isLoggedIn) return
      if (this.aiTokenChecking) return
      if (this.aiTokenChecked && !force) return
      this.aiTokenChecking = true
      try {
        const data = await request('/api/ai_token/list', {}, 'post', false)
        if (data && data.error_code === 0 && data.data) {
          this.hasAiToken =
            Number(data.data.total) > 0 ||
            (Array.isArray(data.data.tokens) && data.data.tokens.length > 0)
          this.aiTokenChecked = true
          if (this.hasAiToken) {
            // 当前有令牌即视为曾创建过，写入持久标记（兼容旧数据）
            this.markAiTokenEverCreated()
          } else {
            // 无令牌时从 localStorage 恢复「曾创建过」标记，决定红点是否显示
            this.loadAiTokenEverCreated()
          }
        }
      } catch (e) {
        // 静默失败，不影响主流程
      } finally {
        this.aiTokenChecking = false
      }
    },
  },
})

