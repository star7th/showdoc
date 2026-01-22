<template>
  <div class="mobile-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('user.bind_mobile')"
      @close="handleClose"
    >
      <div class="modal-content">
        <div class="info-text">
          {{ $t('user.bind_mobile_tips') }}
        </div>
        <div class="form-item">
          <label class="form-label">{{ $t('user.mobile') }}</label>
          <CommonInput
            v-model="formData.mobile"
            :placeholder="$t('user.input_mobile')"
          />
        </div>
        <div class="form-item">
          <label class="form-label">{{ $t('user.captcha') }}</label>
          <div class="captcha-wrapper">
            <CommonInput
              v-model="formData.captcha"
              :placeholder="$t('user.input_captcha')"
            />
            <img 
              v-if="captchaUrl" 
              :src="captchaUrl" 
              @click="refreshCaptcha"
              class="captcha-img"
              :alt="$t('user.captcha')"
            />
          </div>
        </div>
        <div class="form-item">
          <label class="form-label">{{ $t('user.sms_code') }}</label>
          <div class="code-input-wrapper">
            <CommonInput
              v-model="formData.code"
              :placeholder="$t('user.input_sms_code')"
            />
            <CommonButton
              v-if="!isCounting"
              type="primary"
              @click="handleSendCode"
              :loading="sendingCode"
            >
              {{ $t('user.send_sms_code') }}
            </CommonButton>
            <CommonButton v-else disabled>
              {{ countdown }}s
            </CommonButton>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <CommonButton @click="handleClose">
          {{ $t('common.cancel') }}
        </CommonButton>
        <CommonButton type="primary" @click="handleSubmit" :loading="submitting">
          {{ $t('common.confirm') }}
        </CommonButton>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import CommonInput from '@/components/CommonInput.vue'
import CommonButton from '@/components/CommonButton.vue'
import request from '@/utils/request'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'
import { getServerHost, appendUrlParams } from '@/utils/system'

const { t } = useI18n()

const props = defineProps<{
  mobile?: string
  onClose: () => void
}>()

const show = ref(false)
const showId = ref(0)
const submitting = ref(false)
const sendingCode = ref(false)
const isCounting = ref(false)
const countdown = ref(60)
const captchaUrl = ref('')
const imgCaptchaId = ref(0)
const smsCaptchaId = ref(0)
const formData = ref({
  mobile: props.mobile || '',
  captcha: '',
  code: ''
})

let countdownTimer: number | null = null

// 获取图形验证码
const getCaptcha = async () => {
  try {
    const data = await request('/api/common/createCaptcha', {})
    if (data && data.data && data.data.captcha_id) {
      imgCaptchaId.value = data.data.captcha_id
      const serverHost = getServerHost()
      let captchaUrlStr = `${serverHost}/api/common/showCaptcha`
      captchaUrl.value = appendUrlParams(captchaUrlStr, {
        captcha_id: imgCaptchaId.value,
        t: Date.now()
      })
    }
  } catch (error) {
    console.error('获取图形验证码失败:', error)
  }
}

// 刷新验证码
const refreshCaptcha = () => {
  getCaptcha()
}

const handleSendCode = async () => {
  if (!formData.value.mobile) {
    await AlertModal(t('user.mobile_required'))
    return
  }
  if (!formData.value.captcha) {
    await AlertModal(t('user.captcha_required'))
    return
  }

  sendingCode.value = true
  try {
    const data = await request('/api/user/getCaptcha', {
      mobile: formData.value.mobile,
      captcha_id: imgCaptchaId.value,
      captcha: formData.value.captcha
    })
    if (data && data.data && data.data.captcha_id) {
      smsCaptchaId.value = data.data.captcha_id
    }
    // 使用弹窗提醒用户
    AlertModal(t('user.sms_code_sent'))
    startCountdown()
    refreshCaptcha() // 发送成功后刷新图形验证码
  } catch (error) {
    console.error('发送验证码失败:', error)
    refreshCaptcha() // 失败后刷新验证码
  } finally {
    sendingCode.value = false
  }
}

const startCountdown = () => {
  isCounting.value = true
  countdown.value = 60
  countdownTimer = window.setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      stopCountdown()
    }
  }, 1000)
}

const stopCountdown = () => {
  if (countdownTimer) {
    clearInterval(countdownTimer)
    countdownTimer = null
  }
  isCounting.value = false
}

const handleSubmit = async () => {
  if (!formData.value.mobile) {
    await AlertModal(t('user.mobile_required'))
    return
  }
  if (!formData.value.code) {
    await AlertModal(t('user.code_required'))
    return
  }

  submitting.value = true
  try {
    await request('/api/user/bindingMobile', {
      mobile: formData.value.mobile,
      captcha_id: smsCaptchaId.value,
      captcha: formData.value.code
    })
    // 使用弹窗提醒用户
    AlertModal(t('user.bind_mobile_success'), {
      callback: () => {
        props.onClose()
      }
    })
  } catch (error) {
    console.error('绑定手机号失败:', error)
  } finally {
    submitting.value = false
  }
}

const handleClose = () => {
  stopCountdown()
  show.value = false
  setTimeout(() => {
    props.onClose()
  }, 300)
}

onMounted(() => {
  // 使用 showId 确保每次都是新的打开
  showId.value++
  getCaptcha()
  setTimeout(() => {
    show.value = true
  })
})
</script>

<style lang="scss" scoped>
.modal-content {
  padding: 30px 50px;
  border-bottom: 1px solid var(--color-interval);
}

.info-text {
  font-size: 13px;
  color: var(--color-grey);
  line-height: 1.6;
  margin-bottom: 20px;
}

.form-item {
  margin-bottom: 20px;

  &:last-child {
    margin-bottom: 0;
  }
}

.form-label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  color: var(--color-text-primary);
  font-weight: 500;
}

.captcha-wrapper {
  display: flex;
  gap: 12px;
  align-items: center;

  .common-input {
    flex: 1;
  }

  .captcha-img {
    width: 100px;
    height: 40px;
    cursor: pointer;
    border-radius: 4px;
    border: 1px solid var(--color-border);

    &:hover {
      opacity: 0.8;
    }
  }
}

.code-input-wrapper {
  display: flex;
  gap: 12px;

  .common-input {
    flex: 1;
  }

  .common-button {
    min-width: 100px;
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

