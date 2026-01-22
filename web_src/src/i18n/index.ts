import { createI18n } from 'vue-i18n'
import zhCN from './zh-CN/main'
import enUS from './en-US/main'

const i18n = createI18n({
  legacy: false, // 使用 Composition API 模式
  locale: localStorage.getItem('locale') || 'zh-CN',
  fallbackLocale: 'zh-CN',
  messages: {
    'zh-CN': zhCN,
    'en-US': enUS,
  },
})

export default i18n
