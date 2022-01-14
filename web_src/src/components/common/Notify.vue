<template>
  <div></div>
</template>
<script>
export default {
  props: {},
  data() {
    return {
      intervalId: 0,
      nObj: ''
    }
  },
  methods: {
    // 获取用户未读的提醒
    getUnreadRemind() {
      this.request('/api/message/getUnreadRemind', {}, 'post', false).then(
        data => {
          const json = data.data
          if (json && json.id) {
            let msg =
              json.from_name +
              ' ' +
              this.$t('update_the_page') +
              ' ' +
              json.page_data.page_title +
              ' , ' +
              this.$t('click_to_view')
            this.brownNotify(msg, json.message_content_id)
          }
        }
      )
    },
    // 浏览器通知
    brownNotify(msg, message_content_id) {
      const title = 'showdoc通知'
      const options = {
        dir: 'auto', // 文字方向
        body: msg, // 通知主体
        requireInteraction: true // 不自动关闭通知
      }
      // 先检查浏览器是否支持
      if (!window.Notification) {
        console.log('浏览器不支持通知')
        this.webNotify(msg, message_content_id) // 如果不能浏览器通知，则使用web本身的弹窗通知
      } else {
        let notification = {}
        // 检查用户曾经是否同意接受通知
        if (Notification.permission === 'granted') {
          notification = new Notification(title, options) // 显示通知
          notification.onclick = e => {
            this.toMessIndex()
            this.setRead(message_content_id)
            // 可直接打开通知notification相关联的tab窗
            window.focus()
            notification.close()
          }
        } else if (Notification.permission === 'default') {
          this.webNotify(msg, message_content_id) // 如果不能浏览器通知，则使用web本身的弹窗通知

          // 用户还未选择，可以询问用户是否同意发送通知
          Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
              console.log('用户同意授权')
              if (this.nObj && this.nObj.close) this.nObj.close()
              notification = new Notification(title, options) // 显示通知
              notification.onclick = e => {
                this.toMessIndex()
                this.setRead(message_content_id)
                // 可直接打开通知notification相关联的tab窗
                window.focus()
                notification.close()
              }
            } else if (permission === 'default') {
              console.warn('用户关闭授权 未刷新页面之前 可以再次请求授权')
            } else {
              // denied
              console.log('用户拒绝授权 不能显示通知')
            }
          })
        } else {
          // denied 用户拒绝
          console.log('用户曾经拒绝显示通知')
          this.webNotify(msg, message_content_id) // 如果不能浏览器通知，则使用web本身的弹窗通知
        }
      }
    },
    // web弹窗通知
    webNotify(msg, message_content_id) {
      this.nObj = this.$notify({
        message: msg,
        duration: 30000,
        type: 'info',
        customClass: 'cursor-click',
        onClick: data => {
          this.toMessIndex()
          this.nObj.close()
        },
        onClose: () => {
          // 设置已读
          this.setRead(message_content_id)
        }
        // dangerouslyUseHTMLString: true
      })
    },

    toMessIndex() {
      let routeUrl = this.$router.resolve({
        path: '/message/index',
        query: {
          dtab: 'remindList'
        }
      })
      window.open(routeUrl.href, '_blank')
    },
    // 设置已读
    setRead(message_content_id) {
      setTimeout(() => {
        this.request('/api/message/setRead', {
          message_content_id: message_content_id
        })
      }, 2000)
    }
  },
  mounted() {
    setTimeout(() => {
      this.getUnreadRemind()
    }, 2000)
    // 5分钟重复获取未读提醒
    this.intervalId = setInterval(() => {
      this.getUnreadRemind()
    }, 5 * 60 * 1000)
  },

  destroyed() {
    clearInterval(this.intervalId)
  }
}
</script>
<style scoped></style>
