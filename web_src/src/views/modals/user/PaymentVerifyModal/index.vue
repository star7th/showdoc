<template>
  <div class="payment-verify-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('user.payment_verify')"
      @close="handleClose"
    >
      <div class="modal-content">
        <div v-if="isVerified" class="verified-content">
          <i class="fas fa-check-circle verified-icon"></i>
          <h3 class="verified-title">{{ $t('user.verified') }}</h3>
          <p class="verified-desc">{{ $t('user.payment_verify_completed') }}</p>
        </div>
        <div v-else class="verify-info">
          <p class="info-text">{{ $t('user.payment_verify_tips1') }}</p>
          <p class="info-text">{{ $t('user.payment_verify_tips2') }}</p>
          <p class="info-text">{{ $t('user.payment_verify_tips3') }}</p>
        </div>
      </div>
      <div class="modal-footer">
        <CommonButton v-if="!isVerified" @click="handleClose">
          {{ $t('common.cancel') }}
        </CommonButton>
        <CommonButton
          v-if="!isVerified"
          type="primary"
          @click="handleStartVerify"
          :loading="verifying"
        >
          {{ $t('user.start_verify') }}
        </CommonButton>
        <CommonButton v-else type="primary" @click="handleClose">
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
import { getServerHost, appendUrlParam } from '@/utils/system'
import AlertModal from '@/components/AlertModal'
import { useUserStore } from '@/store/user'

const { t } = useI18n()
const userStore = useUserStore()

const props = defineProps<{
  isVerified: boolean
  onClose: () => void
}>()

const show = ref(false)
const showId = ref(0)
const verifying = ref(false)

let checkVerifyTimer: number | null = null

const handleStartVerify = () => {
  verifying.value = true
  // 跳转到支付页面
  const url = `${getServerHost()}/api/order/payForVerify`
  window.open(appendUrlParam(url, 'user_token', userStore.userToken))
  // 轮询检查支付状态
  checkPaymentVerifyStatus()
}

const checkPaymentVerifyStatus = () => {
  checkVerifyTimer = window.setInterval(async () => {
    try {
      const data = await request('/api/user/info', {})
      if (
        data &&
        data.data &&
        (data.data.payment_verify == 1 || data.data.payment_verify === '1')
      ) {
        if (checkVerifyTimer) {
          clearInterval(checkVerifyTimer!)
        }
        checkVerifyTimer = null
        verifying.value = false
        // 使用弹窗提醒用户
        AlertModal(t('user.payment_verify_success'), {
          callback: () => {
            props.onClose()
          },
        })
      }
    } catch (error) {
      console.error('检查支付验证状态失败:', error)
    }
  }, 2000) // 每2秒检查一次
  // 最长五分钟后停止检查
  setTimeout(() => {
    if (checkVerifyTimer) {
      clearInterval(checkVerifyTimer!)
    }
    checkVerifyTimer = null
    verifying.value = false
  }, 300000)
}

const handleClose = () => {
  if (checkVerifyTimer) {
    clearInterval(checkVerifyTimer!)
  }
  checkVerifyTimer = null
  verifying.value = false
  show.value = false
  setTimeout(() => {
    props.onClose()
  }, 300)
}

onMounted(() => {
  // 使用 showId 确保每次都是新的打开
  showId.value++
  setTimeout(() => {
    show.value = true
  })
})

onUnmounted(() => {
  // 清除定时器
  if (checkVerifyTimer) {
    clearInterval(checkVerifyTimer)
  }
})
</script>

<style lang="scss" scoped>
.modal-content {
  padding: 30px 50px;
  border-bottom: 1px solid var(--color-interval);
  min-height: 120px;
}

.verified-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;

  .verified-icon {
    font-size: 64px;
    color: var(--color-success);
    margin-bottom: 16px;
  }

  .verified-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--color-text-primary);
    margin: 0 0 12px 0;
  }

  .verified-desc {
    font-size: 14px;
    color: var(--color-grey);
    line-height: 1.6;
    margin: 0;
  }
}

.verify-info {
  line-height: 1.8;

  .info-text {
    font-size: 14px;
    color: var(--color-text-primary);
    margin: 8px 0;
  }
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 90px;
  gap: 15px;
}
</style>
