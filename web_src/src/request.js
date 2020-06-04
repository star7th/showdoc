/**
 *
 */

import axios from 'axios'
import router from '@/router/index'
import { MessageBox } from 'element-ui'

// axios 配置
axios.defaults.timeout = 20000

// http request 拦截器
axios.interceptors.request.use(
  config => {
    // if (store.state.token) {
    // config.headers.Authorization = `token ${store.state.token}`;
    // }
    return config
  },
  err => {
    return Promise.reject(err)
  })

// http response 拦截器
axios.interceptors.response.use(
  response => {
    if (response.config.data && response.config.data.indexOf('redirect_login=false') > -1) {
      // 不跳转到登录
    } else if (response.data.error_code === 10102) {
      router.replace({
        path: '/user/login',
        query: { redirect: router.currentRoute.fullPath }
      })
    } else if (response.data.error_code !== 0) {
      MessageBox.alert(response.data.error_message)
      return Promise.reject(new Error('something bad happened'))
    }

    return response
  },
  error => {
    // console.log(JSON.stringify(error));//console : Error: Request failed with status code 402
    return Promise.reject(error.response.data)
  })

// 这里是后来追加的，懒得去钻研拦截器语法了，就直接这么来吧
const request = (path, data, method = 'post') => {
  var params = new URLSearchParams(data)
  let url = DocConfig.server + path
  return axios(
    {
      url: url,
      method: method,
      data: params,
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      }
    }
  )
}

export default request
