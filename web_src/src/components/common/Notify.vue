<template>
  <div></div>
</template>
<script>
export default {
  props: {
    popup: {
      type: Boolean,
      required: false,
      default: true
    }
  },
  data() {
    return {
      intervalId: 0,
      nObj: ''
    }
  },
  methods: {
    // 获取用户未读的提醒
    getUnread() {
      this.request('/api/message/getUnread', {}, 'post', false).then(data => {
        const json = data.data
        // 提醒类消息
        if (json['remind'] && json['remind'].id) {
          if (json['remind'].object_type == 'page') {
            this.$store.dispatch('changeNewMsg', 1) // 设置全局new标识
            let msg =
              json['remind'].from_name +
              ' ' +
              this.$t('update_the_page') +
              ' ' +
              json['remind'].page_data.page_title +
              ' , ' +
              this.$t('click_to_view')

            let routeUrl = this.$router.resolve({
              path: '/message/index',
              query: {
                dtab: 'remindList'
              }
            })
            const toUrl = routeUrl.href
            this.brownNotify(
              msg,
              json['remind'].from_uid,
              json['remind'].message_content_id,
              toUrl
            )
          }
          if (json['remind'].object_type == 'vip') {
            let msg =
              '你在showdoc购买的付费版资格很快过期了，你可以点此进入用户中心进行续费(如已续费请忽略该通知)'

            let routeUrl = this.$router.resolve({
              path: '/user/setting'
            })
            const toUrl = routeUrl.href
            this.brownNotify(
              msg,
              json['remind'].from_uid,
              json['remind'].message_content_id,
              toUrl
            )
          }
        }
        // 公告类消息
        if (json['announce'] && json['announce'].id) {
          this.$store.dispatch('changeNewMsg', 1) // 设置全局new标识
          const msg = '你有未读的公告消息，点此查看'
          let routeUrl = this.$router.resolve({
            path: '/message/index',
            query: {
              dtab: 'announcementList'
            }
          })
          const toUrl = routeUrl.href
          this.brownNotify(
            msg,
            json['announce'].from_uid,
            json['announce'].message_content_id,
            toUrl
          )
        }
      })
    },
    // 浏览器通知
    brownNotify(msg, from_uid, message_content_id, toUrl) {
      if (!this.popup) return false
      const title = 'showdoc通知'
      const options = {
        dir: 'auto', // 文字方向
        body: msg, // 通知主体
        requireInteraction: true, // 不自动关闭通知
        tag: from_uid + '_' + message_content_id
      }
      // 先检查浏览器是否支持
      if (!window.Notification) {
        console.log('浏览器不支持通知')
        this.webNotify(msg, from_uid, message_content_id, toUrl) // 如果不能浏览器通知，则使用web本身的弹窗通知
      } else {
        let notification = {}
        // 检查用户曾经是否同意接受通知
        if (Notification.permission === 'granted') {
          notification = new Notification(title, options) // 显示通知
          notification.onclick = e => {
            window.open(toUrl, '_blank')
            this.setRead(from_uid, message_content_id)
            // 可直接打开通知notification相关联的tab窗
            window.focus()
            notification.close()
          }
        } else if (Notification.permission === 'default') {
          this.webNotify(msg, from_uid, message_content_id, toUrl) // 如果不能浏览器通知，则使用web本身的弹窗通知

          // 用户还未选择，可以询问用户是否同意发送通知
          Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
              console.log('用户同意授权')
              if (this.nObj && this.nObj.close) this.nObj.close()
              notification = new Notification(title, options) // 显示通知
              notification.onclick = e => {
                window.open(toUrl, '_blank')
                this.setRead(from_uid, message_content_id)
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
          this.webNotify(msg, from_uid, message_content_id, toUrl) // 如果不能浏览器通知，则使用web本身的弹窗通知
        }
      }
    },
    // web弹窗通知
    webNotify(msg, from_uid, message_content_id, toUrl) {
      this.nObj = this.$notify({
        message: msg,
        duration: 30000,
        type: 'info',
        customClass: 'cursor-click',
        onClick: data => {
          window.open(toUrl, '_blank')
          this.nObj.close()
        },
        onClose: () => {
          // 设置已读
          this.setRead(from_uid, message_content_id)
        }
        // dangerouslyUseHTMLString: true
      })
    },

    // 设置已读
    setRead(from_uid, message_content_id) {
      this.$store.dispatch('changeNewMsg', 0)
      setTimeout(() => {
        this.request('/api/message/setRead', {
          from_uid: from_uid,
          message_content_id: message_content_id
        })
      }, 2000)
    }
  },
  mounted() {
    setTimeout(() => {
      this.getUnread()
    }, 2000)
    // 60分钟重复获取未读提醒
    this.intervalId = setInterval(() => {
      this.getUnread()
    }, 5 * 60 * 1000)
  },

  destroyed() {
    clearInterval(this.intervalId)
  }
}
</script>
<style scoped></style>
