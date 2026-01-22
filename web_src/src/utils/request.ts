/**
 * HTTP 请求封装
 * 基于 axios，适配 ShowDoc 后端接口
 * 参考 web_src/src/request.js（ShowDoc 旧版）
 */
import axios from 'axios'
import router from '@/router'
import { getServerHost } from './system'
import AlertModal from '@/components/AlertModal'

/**
 * 发起 HTTP 请求
 * @param url - 请求路径
 * @param data - 请求数据
 * @param method - 请求方法
 * @param msgAlert - 是否显示错误提示
 * @param contentType - 内容类型
 */
const request = (
  url: string = '',
  data: any = {},
  method: 'get' | 'post' = 'post',
  msgAlert = true,
  contentType: 'form' | 'json' = 'form'
): Promise<any> => {
  const serverHost = getServerHost()
  let finalUrl = serverHost + url
  // 获取用户信息
  let userinfo: any = null
  try {
    const userinfostr = localStorage.getItem('userinfo')
    if (userinfostr) {
      userinfo = JSON.parse(userinfostr)
    }
  } catch (error) {
    console.warn('解析 userinfo 失败:', error)
    userinfo = null
  }

  // 添加 user_token
  if (data instanceof FormData) {
    if (userinfo && userinfo.user_token) {
      data.append('user_token', userinfo.user_token)
    }
  } else if (typeof data === 'object') {
    if (userinfo && userinfo.user_token) {
      data.user_token = userinfo.user_token
    }
  }

  // _item_pwd 参数的作用：跨域请求的时候无法携带 cookies，自然无法记住 session
  // 用这个参数使记住用户输入过的项目密码
  if (data.item_id > 0 || data.item_id != '' || data.page_id > 0) {
    let _item_pwd = sessionStorage.getItem('_item_pwd')
    data._item_pwd = _item_pwd
  }

  // 构建请求配置
  let axiosConfig: any = {
    url: finalUrl,
    method: method,
  }

  // 根据数据类型设置不同的 data 和 headers
  if (data instanceof FormData) {
    // 如果是 FormData，直接使用，不转换
    axiosConfig.data = data
    axiosConfig.headers = {
      'Content-Type': 'multipart/form-data',
    }
    // 为文件上传设置 5 分钟超时时间
    axiosConfig.timeout = 300000
  } else if (contentType === 'json') {
    // JSON 类型
    axiosConfig.data = data
    axiosConfig.headers = {
      'Content-Type': 'application/json',
    }
  } else {
    // 默认：表单类型
    axiosConfig.data = new URLSearchParams(data)
    axiosConfig.headers = {
      'Content-Type': 'application/x-www-form-urlencoded',
    }
  }

  return new Promise((resolve, reject) => {
    axios(axiosConfig)
      .then(
        (response) => {
          // 业务错误处理
          if (msgAlert && response.data && response.data.error_code !== 0) {
            // 超时登录
            if (response.data.error_code === 10102) {
              const currentPath = router.currentRoute.value?.fullPath || '/'
              var redirect = currentPath.repeat(1)
              if (redirect.indexOf('redirect=') > -1) {
                // 防止重复 redirect
                return false
              }
              router.replace({
                path: '/user/login',
                query: { redirect: redirect }
              })
            } else {
              // 使用 AlertModal 替换 Modal.error
              AlertModal(response.data.error_message || '操作失败')
            }
            reject(new Error('业务级别的错误'))
            return
          }

          // 上面没有 return 的话，最后返回这个
          resolve(response.data)
        },
        (err) => {
          if (err.__CANCEL__) {
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
