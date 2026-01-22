<template>
  <div class="wechat-modal">
    <CommonModal
      :class="{ show }"
      :title="isBound ? $t('user.modify_wechat') : $t('user.bind_wechat')"
      @close="handleClose"
    >
      <div class="modal-content">
        <!-- 直接显示二维码，无论是否已绑定 -->
        <div class="wechat-bind">
          <div class="qrcode-container">
            <img v-if="qrcodeUrl" :src="qrcodeUrl" alt="WeChat QR Code" class="qrcode" />
            <div v-else class="qrcode-loading">{{ $t('common.loading') }}</div>
          </div>
          <div class="bind-tips">
            <p v-if="isBound">{{ $t('user.wechat_modify_tips1') }}</p>
            <p v-else>{{ $t('user.wechat_scan_tips1') }}</p>
            <p>{{ $t('user.wechat_scan_tips2') }}</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <CommonButton @click="handleClose">
          {{ $t('common.close') }}
        </CommonButton>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import request from '@/utils/request'
import AlertModal from '@/components/AlertModal'

const { t } = useI18n()

const props = defineProps<{
  isBound: boolean
  onClose: () => void
}>()

const show = ref(false)
const qrcodeUrl = ref('')
const qrscene = ref('')

let checkBindTimer: number | null = null

const getQrcode = async () => {
  try {
    const data = await request('/api/wechat/getQrcodeUrl', {})
    if (data && data.data) {
      qrscene.value = data.data.qrscene
      qrcodeUrl.value = data.data.url
      startCheckBind()
    }
  } catch (error) {
    console.error('获取微信二维码失败:', error)
  }
}

const startCheckBind = () => {
  checkBindTimer = window.setInterval(async () => {
    try {
      const data = await request('/api/wechat/checkOrcodeStatus', {
        qrscene: qrscene.value
      })
      if (data && data.data && data.data.status >= 1) {
        stopCheckBind()
        const successMsg = props.isBound ? t('user.wechat_modify_success') : t('user.bind_wechat_success')
        // 使用弹窗提醒用户
        AlertModal(successMsg, {
          callback: () => {
            props.onClose()
          }
        })
      }
    } catch (error) {
      console.error('检查微信绑定状态失败:', error)
    }
  }, 2000) // 每2秒检查一次
}

const stopCheckBind = () => {
  if (checkBindTimer) {
    clearInterval(checkBindTimer)
    checkBindTimer = null
  }
}

const handleClose = () => {
  stopCheckBind()
  show.value = false
  setTimeout(() => {
    props.onClose()
  }, 300)
}

onMounted(() => {
  setTimeout(() => {
    show.value = true
    getQrcode()
  })
})

onUnmounted(() => {
  stopCheckBind()
})
</script>

<style lang="scss" scoped>
.modal-content {
  padding: 30px 50px;
  border-bottom: 1px solid var(--color-interval);
  min-height: 200px;
}

.wechat-bind {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.qrcode-container {
  width: 200px;
  height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--color-bg-secondary);
  border-radius: 8px;
  margin-bottom: 20px;

  .qrcode {
    width: 180px;
    height: 180px;
    object-fit: contain;
  }

  .qrcode-loading {
    color: var(--color-grey);
  }
}

.bind-tips {
  font-size: 13px;
  color: var(--color-grey);
  line-height: 1.8;
  text-align: center;

  p {
    margin: 4px 0;
  }
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 90px;

  .primary-button {
    width: 160px;
  }
}
</style>
