import { defineStore } from 'pinia'

export const useAppStore = defineStore('app', {
  state: () => ({
    /** 主题模式 */
    theme: 'light' as 'light' | 'dark',
    /** 语言设置 */
    locale: 'zh-CN' as 'zh-CN' | 'en-US',
    /** 侧边栏折叠状态 */
    sidebarCollapsed: false,
    /** 新消息数量 */
    newMsg: 0,
  }),

  getters: {
    isDarkMode: (state) => state.theme === 'dark',
  },

  actions: {
    /** 切换主题 */
    toggleTheme() {
      this.theme = this.theme === 'light' ? 'dark' : 'light'
      localStorage.setItem('theme', this.theme)
      document.documentElement.setAttribute('data-theme', this.theme)
    },

    /** 设置主题 */
    setTheme(theme: 'light' | 'dark') {
      this.theme = theme
      localStorage.setItem('theme', theme)
      document.documentElement.setAttribute('data-theme', theme)
    },

    /** 设置语言 */
    setLocale(locale: 'zh-CN' | 'en-US') {
      this.locale = locale
      localStorage.setItem('locale', locale)
    },

    /** 初始化主题（从本地存储读取，同步到 store） */
    initTheme() {
      const savedTheme = localStorage.getItem('theme') as 'light' | 'dark' | null
      if (savedTheme) {
        this.theme = savedTheme
      }
      // DOM 属性已在 main.ts 中设置，这里不再重复设置
    },

    /** 初始化语言 */
    initLocale() {
      const savedLocale = localStorage.getItem('locale') as 'zh-CN' | 'en-US' | null
      if (savedLocale) {
        this.locale = savedLocale
      }
    },

    /** 切换侧边栏折叠状态 */
    toggleSidebar() {
      this.sidebarCollapsed = !this.sidebarCollapsed
    },

    /** 设置新消息数量 */
    setNewMsg(count: number) {
      this.newMsg = count
    },
  },
})

