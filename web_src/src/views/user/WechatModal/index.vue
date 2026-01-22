<template>
  <CommonModal :class="{ show }" :title="$t('user.wechat_login_tip')" @close="handleClose">
    <div class="modal-content">
      <div class="wechat-modal-content">
        <p>{{ $t('wechat.modal_desc_1') }}</p>
        <p>{{ $t('wechat.modal_desc_2') }}</p>
        <p class="qrcode-container">
          <img
            v-if="qrcodeUrl"
            :src="qrcodeUrl"
            alt="Wechat QR Code"
            class="qrcode-img"
            @click="getQrcodeUrl"
          />
          <span v-else class="loading">
            {{ $t('common.loading') }}
          </span>
        </p>
      </div>
    </div>
    <div class="modal-footer">
      <div class="primary-button" @click="handleClose">{{ $t('common.close') }}</div>
    </div>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import CommonModal from '@/components/CommonModal.vue'
import request from '@/utils/request'

const props = defineProps<{
  callback?: (qrscene: string) => void
  onClose: () => void
}>()

const show = ref(false)
const qrscene = ref('')
const qrcodeUrl = ref('')
let intervalId: number = 0

// 获取微信二维码
const getQrcodeUrl = async () => {
  try {
    const data = await request('/api/wechat/getQrcodeUrl', {}, 'post', false)
    if (data.error_code === 0) {
      qrscene.value = data.data.qrscene
      qrcodeUrl.value = data.data.url
      // 每2秒重复获取二维码扫描情况
      clearInterval(intervalId)
      intervalId = window.setInterval(() => {
        checkOrcodeStatus()
      }, 2 * 1000)
    }
  } catch (error) {
    console.error('获取二维码失败:', error)
  }
}

// 检查二维码的扫描情况
const checkOrcodeStatus = async () => {
  try {
    const data = await request(
      '/api/wechat/checkOrcodeStatus',
      { qrscene: qrscene.value },
      'post',
      false
    )
    if (data.data && data.data.status >= 1) {
      clearInterval(intervalId)
      // status=1是扫描成功了
      if (props.callback) {
        props.callback(qrscene.value)
      }
      handleClose()
    }
  } catch (error) {
    console.error('检查二维码状态失败:', error)
  }
}

const handleClose = () => {
  clearInterval(intervalId)
  props.onClose()
}

onMounted(() => {
  show.value = true
  getQrcodeUrl()
})

onUnmounted(() => {
  clearInterval(intervalId)
})
</script>

<style scoped>
.modal-content {
  width: 400px;
  padding: 30px 50px;
  border-bottom: 1px solid var(--color-interval);
}

.wechat-modal-content {
  text-align: center;
}

.wechat-modal-content p {
  margin-bottom: 10px;
  color: var(--color-text-secondary);
}

.qrcode-container {
  margin-top: 20px;
}

.qrcode-img {
  width: 200px;
  height: 200px;
  cursor: pointer;
  border: 1px solid var(--color-border);
  border-radius: 4px;
}

.qrcode-img:hover {
  opacity: 0.8;
}

.loading {
  display: inline-block;
  padding: 80px 20px;
  color: var(--color-text-secondary);
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 90px;

  .primary-button {
    width: 160px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    color: var(--color-obvious);
    background-color: var(--color-active);
    cursor: pointer;
    transition: opacity 0.15s ease;

    &:hover {
      opacity: 0.85;
    }
  }
}
</style>

