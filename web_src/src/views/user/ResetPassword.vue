<template>
  <div class="reset-password-page">
    <div class="reset-password-card">
      <div class="card-header">
        <h2 class="title">{{ $t('user.reset_password') }}</h2>
      </div>

      <div class="form-inputs">
        <CommonInput
          v-model="form.email"
          type="email"
          :placeholder="$t('user.email')"
          @keyup.enter="handleReset"
        />

        <div class="captcha-input">
          <CommonInput
            v-model="form.captcha"
            :placeholder="$t('user.verification_code')"
            @keyup.enter="handleReset"
          />
          <img
            v-if="captchaImg"
            :src="captchaImg"
            alt="验证码"
            class="captcha-img"
            @click="refreshCaptcha"
          />
        </div>
      </div>

      <CommonButton
        :text="loading ? $t('common.submitting') : $t('common.submit')"
        theme="dark"
        :loading="loading"
        :full-width="true"
        @click="handleReset"
      />

      <div class="footer-links">
        <router-link to="/user/login">{{ $t('user.remember_password_login') }}</router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonInput from '@/components/CommonInput.vue'
import CommonButton from '@/components/CommonButton.vue'
import AlertModal from '@/components/AlertModal/index'
import { getServerHost, appendUrlParams } from '@/utils/system'
import request from '@/utils/request'

const { t } = useI18n()

const form = ref({
  email: '',
  captcha: '',
})

const loading = ref(false)
const captchaId = ref(0)
const captchaImg = ref('')

// 刷新验证码
const refreshCaptcha = async () => {
  try {
    const data = await request('/api/common/createCaptcha', {}, 'post', false)
    if (data.error_code === 0) {
      captchaId.value = data.data.captcha_id
      // 构建验证码图片URL
      const serverHost = getServerHost()
      let captchaUrl = `${serverHost}/api/common/showCaptcha`
      captchaUrl = appendUrlParams(captchaUrl, {
        captcha_id: captchaId.value,
        t: Date.now()
      })
      captchaImg.value = captchaUrl
    }
  } catch (error) {
    console.error('获取验证码失败:', error)
  }
}

// 重置密码处理
const handleReset = async () => {
  if (!form.value.email) {
    AlertModal(t('user.email_required'))
    return
  }

  if (!form.value.captcha) {
    AlertModal(t('user.captcha_required'))
    return
  }

  loading.value = true

  try {
    const data = await request(
      '/api/user/resetPasswordEmail',
      {
        email: form.value.email,
        captcha: form.value.captcha,
        captcha_id: captchaId.value,
      },
      'post',
      false
    )

    if (data.error_code === 0) {
      AlertModal(t('user.reset_password_email_sent'))
      refreshCaptcha()
    } else {
      refreshCaptcha()
      AlertModal(data.error_message || t('common.error'))
    }
  } catch (error) {
    refreshCaptcha()
    console.error('重置密码失败:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  // 刷新验证码
  refreshCaptcha()

  // 清除可能的遮罩层
  const elements = document.getElementsByClassName('v-modal-leave')
  for (let index = 0; index < elements.length; index++) {
    elements[index].remove()
  }
})
</script>

<style scoped>
.reset-password-page {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background-color: var(--color-bg-secondary);
}

.reset-password-card {
  width: 400px;
  padding: 40px;
  background: var(--color-bg-primary);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  box-shadow: var(--shadow-lg);
}

.card-header {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 20px;
}

.title {
  color: var(--color-text-primary);
  font-size: 24px;
  font-weight: 600;
  text-align: center;
}

.form-inputs {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 20px;
}

.captcha-input {
  display: flex;
  gap: 10px;
  align-items: flex-start;
}

.captcha-img {
  width: 100px;
  height: 40px;
  margin-top: 2px;
  cursor: pointer;
  border: 1px solid var(--color-border);
  border-radius: 4px;
}

.captcha-img:hover {
  opacity: 0.7;
  transition: opacity 0.15s ease;
}

.footer-links {
  margin-top: 20px;
  text-align: center;
}

.footer-links a {
  font-size: 12px;
  color: var(--color-text-secondary);
  text-decoration: none;
  transition: color 0.15s ease;
}

.footer-links a:hover {
  color: var(--color-active);
}
</style>
