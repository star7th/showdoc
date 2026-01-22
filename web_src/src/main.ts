import { createApp } from 'vue'
import { createPinia } from 'pinia'
import mitt from 'mitt'
import App from './App.vue'
import router from './router'
import i18n from './i18n'
import { detectBrowserLanguage } from './utils/language'

// Ant Design Vue
import Antd from 'ant-design-vue'
import 'ant-design-vue/dist/antd.css'

// 全局样式
import './styles/index.scss'
import './styles/runapi-base.scss'
import './styles/antdv.scss'
import './styles/vue-json-pretty.scss'

// ===== 主题初始化（必须最先执行，防止闪烁） =====
const savedTheme = localStorage.getItem('theme') as 'light' | 'dark' | null
const theme = savedTheme || 'light'
document.documentElement.setAttribute('data-theme', theme)
// ================================================

// ===== 语言初始化（自动判断浏览器语言） =====
const savedLocale = localStorage.getItem('locale')
if (savedLocale) {
  document.documentElement.lang = savedLocale
} else {
  // 根据浏览器语言自动判断
  const detectedLocale = detectBrowserLanguage()
  localStorage.setItem('locale', detectedLocale)
  document.documentElement.lang = detectedLocale
}
// ================================================

// 创建应用实例
const app = createApp(App)

// 注册插件
app.use(router)
app.use(i18n)
app.use(Antd)

// Pinia 状态管理
const pinia = createPinia()
app.use(pinia)

// 将 pinia 挂载到 window，供弹窗工厂使用
;(window as any).__MAIN_APP_STORE__ = pinia

// 将 router 挂载到 window，供弹窗工厂使用
;(window as any).__MAIN_APP_ROUTER__ = router

// 事件总线
const emitter = mitt()
app.config.globalProperties.emitter = emitter
;(window as any).__MAIN_EMITTER__ = emitter

// 挂载应用
app.mount('#app')

