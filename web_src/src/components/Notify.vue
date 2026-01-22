<template>
  <div></div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/store/user'
import { getUnread, setRead } from '@/models/message'
import { notification } from 'ant-design-vue'

const { t } = useI18n()
const router = useRouter()
const userStore = useUserStore()

const props = defineProps<{
  popup?: boolean
}>()

// 默认 popup 为 true
const popup = ref(props.popup !== undefined ? props.popup : true)
const intervalId = ref<number | null>(null)
let webNotificationKey = ref<string | null>(null)

// 获取用户未读的提醒
const getUnreadMessage = async () => {
  try {
    const response = await getUnread()
    const json = response.data

    // 提醒类消息
    if (json['remind'] && json['remind'].id) {
      userStore.setNewMsg(1) // 设置全局 new 标识

      // 如果启用弹窗通知
      if (popup.value) {
        let msg = ''

        // VIP 会员到期提醒
        if (json['remind'].object_type == 'vip') {
          msg = t('message.vip_expiring_notice')
          let routeUrl = router.resolve({
            path: '/user/setting'
          })
          const toUrl = routeUrl.href
          browserNotify(
            msg,
            json['remind'].from_uid,
            json['remind'].message_content_id,
            toUrl
          )
          return
        }

        // 根据action_type显示不同的消息
        if (
          json['remind'].action_type == 'comment' ||
          json['remind'].action_type == 'comment_reply'
        ) {
          // 评论类型的消息，直接使用message_content（已包含简化后的文案）
          msg =
            json['remind'].message_content ||
            json['remind'].from_name + ' ' + t('message.publish_comment')
        } else {
          // 其他类型的消息（如update）
          msg =
            json['remind'].from_name +
            ' ' +
            t('message.update_the_page') +
            ' ' +
            json['remind'].page_data.page_title +
            ' , ' +
            t('message.click_to_view')
        }

        let routeUrl = router.resolve({
          path: '/message/index',
          query: {
            dtab: 'remindList'
          }
        })
        const toUrl = routeUrl.href
        browserNotify(
          msg,
          json['remind'].from_uid,
          json['remind'].message_content_id,
          toUrl
        )
      }
    }

    // 公告类消息
    if (json['announce'] && json['announce'].id) {
      userStore.setNewMsg(1) // 设置全局 new 标识

      // 如果启用弹窗通知
      if (popup.value) {
        const msg = t('message.you_have_unread_announcement')
        let routeUrl = router.resolve({
          path: '/message/index',
          query: {
            dtab: 'announcementList'
          }
        })
        const toUrl = routeUrl.href
        browserNotify(
          msg,
          json['announce'].from_uid,
          json['announce'].message_content_id,
          toUrl
        )
      }
    }
  } catch (error) {
    console.error('[Notify] 获取未读消息失败:', error)
  }
}

// Web 弹窗通知
const webNotify = (msg: string, fromUid: number, messageContentId: number, toUrl: string) => {
  const key = fromUid + '_' + messageContentId
  webNotificationKey.value = key

  notification.open({
    message: 'ShowDoc ' + t('message.notification'),
    description: msg,
    duration: 0, // 不自动关闭
    key: key,
    placement: 'topRight',
    onClick: () => {
      window.open(toUrl, '_blank')
      notification.close(key)
      // 关闭会触发 onClose，这里不需要再次调用 setReadMessage
    },
    onClose: () => {
      // 关闭时设置已读
      setReadMessage(fromUid, messageContentId)
    }
  })
}

// 浏览器通知
const browserNotify = (
  msg: string,
  fromUid: number,
  messageContentId: number,
  toUrl: string
) => {
  if (!popup.value) return false

  const title = 'ShowDoc ' + t('message.notification')
  const options: NotificationOptions = {
    dir: 'auto', // 文字方向
    body: msg, // 通知主体
    requireInteraction: true, // 不自动关闭通知
    tag: fromUid + '_' + messageContentId
  }

  // 先检查浏览器是否支持
  if (!('Notification' in window)) {
    console.log('浏览器不支持系统通知，使用 web 弹窗通知')
    webNotify(msg, fromUid, messageContentId, toUrl)
    return
  }

  let browserNotification: any = null

  // 检查用户曾经是否同意接受通知
  if (Notification.permission === 'granted') {
    // 显示通知
    browserNotification = new Notification(title, options)
    browserNotification.onclick = () => {
      window.open(toUrl, '_blank')
      setReadMessage(fromUid, messageContentId)
      window.focus()
      browserNotification.close()
    }
  } else if (Notification.permission === 'default') {
    // 用户还未选择，先使用 web 弹窗通知，并询问用户是否同意发送通知
    webNotify(msg, fromUid, messageContentId, toUrl)

    Notification.requestPermission().then((permission) => {
      if (permission === 'granted') {
        console.log('用户同意授权')
        // 关闭 web 弹窗通知
        const key = fromUid + '_' + messageContentId
        notification.close(key)

        // 显示系统通知
        browserNotification = new Notification(title, options)
        browserNotification.onclick = () => {
          window.open(toUrl, '_blank')
          setReadMessage(fromUid, messageContentId)
          window.focus()
          browserNotification.close()
        }
      } else if (permission === 'default') {
        console.warn('用户关闭授权 未刷新页面之前 可以再次请求授权')
      } else {
        // denied
        console.log('用户拒绝授权，继续使用 web 弹窗通知')
      }
    })
  } else {
    // denied 用户拒绝授权，使用 web 弹窗通知
    console.log('用户曾经拒绝显示通知，使用 web 弹窗通知')
    webNotify(msg, fromUid, messageContentId, toUrl)
  }
}

// 设置消息已读
const setReadMessage = async (fromUid: number, messageContentId: number) => {
  userStore.setNewMsg(0)
  setTimeout(async () => {
    try {
      await setRead({
        from_uid: fromUid,
        message_content_id: messageContentId
      })
    } catch (error) {
      console.error('设置已读失败:', error)
    }
  }, 2000)
}

onMounted(() => {
  // 延迟 2 秒后首次获取，避免页面刚加载时就弹出通知
  setTimeout(() => {
    getUnreadMessage()
  }, 2000)

  // 60分钟重复获取未读提醒
  intervalId.value = window.setInterval(() => {
    getUnreadMessage()
  }, 60 * 60 * 1000)
})

onUnmounted(() => {
  if (intervalId.value) {
    clearInterval(intervalId.value)
  }
})
</script>

<style scoped lang="scss"></style>

