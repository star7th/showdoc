/**
 *
 */

import axios from '@/http'
import router from '@/router/index'
import { MessageBox } from 'element-ui'

const request = (
  path,
  data = {},
  method = 'post',
  msgAlert = true,
  contentType = 'form'
) => {
  let url = DocConfig.server + path

  const userinfostr = localStorage.getItem('userinfo')
  if (userinfostr) {
    const userinfo = JSON.parse(userinfostr)
    if (userinfo && userinfo.user_token) {
      data.user_token = userinfo.user_token
    }
  }

  let axiosConfig = {
    url: url,
    method: method,
    data: new URLSearchParams(data),
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    }
  }

  if (contentType == 'json') {
    axiosConfig.data = data // 这里使用原始data，不经过URLSearchParams
    axiosConfig.headers['Content-Type'] = 'application/json'
  }

  return new Promise((resolve, reject) => {
    axios(axiosConfig)
      .then(
        response => {
          //超时登录
          if (
            response.data.error_code === 10102 &&
            response.config.data.indexOf('redirect_login=false') === -1
          ) {
            var redirect = router.currentRoute.fullPath.repeat(1)
            if (redirect.indexOf('redirect=') > -1) {
              return false
            }
            router.replace({
              path: '/user/login',
              query: { redirect: redirect }
            })
            reject(new Error('登录态无效'))
          }

          if (msgAlert && response.data && response.data.error_code !== 0) {
            MessageBox.alert(response.data.error_message)
            return reject(new Error('业务级别的错误'))
          }
          //上面没有return的话，最后返回这个
          resolve(response.data)
        },
        err => {
          if (err.Cancel) {
            console.log(err)
          } else {
            reject(err)
          }
        }
      )
      .catch(err => {
        reject(err)
      })
  })
}

export default request
