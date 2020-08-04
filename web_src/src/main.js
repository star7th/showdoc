// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import App from './App'
import router from './router'
import ElementUI from 'element-ui'
import 'element-ui/lib/theme-chalk/index.css'
import Header from '@/components/common/Header'
import Footer from '@/components/common/Footer'
import util from '@/util.js'
import axios from '@/http'
import request from '@/request.js'
import VueI18n from 'vue-i18n'
import enLocale from 'element-ui/lib/locale/lang/en'
import zhLocale from 'element-ui/lib/locale/lang/zh-CN'
import myZhLocale from '../static/lang/zh-CN'
import myEnLocale from '../static/lang/en'
import 'url-search-params-polyfill'
import 'babel-polyfill'
import VueClipboard from 'vue-clipboard2'
import store from './store/'

Vue.use(util)
Vue.config.productionTip = false
Vue.component('Header', Header)
Vue.component('Footer', Footer)
Vue.use(ElementUI)
Vue.use(VueI18n)
Vue.use(VueClipboard)

// 多语言相关
var allZhLocale = Object.assign(zhLocale, myZhLocale)
var allEnLocale = Object.assign(enLocale, myEnLocale)
Vue.config.lang = DocConfig.lang
Vue.locale('zh-cn', allZhLocale)
Vue.locale('en', allEnLocale)

// 将axios挂载到prototype上，在组件中可以直接使用this.axios访问
Vue.prototype.axios = axios
Vue.prototype.request = request

/* eslint-disable no-new */

new Vue({
  el: '#app',
  router,
  store,
  template: '<App/>',
  components: { App }
})
