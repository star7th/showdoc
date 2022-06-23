
import request from '@/request.js'

const getUserInfo = (callback = () => {}) => {
  request('/api/user/info', {
    redirect_login: false
  }, 'post', false).then((data) => {
    if (callback) { callback({ data }) };
  })
}

export { getUserInfo }
