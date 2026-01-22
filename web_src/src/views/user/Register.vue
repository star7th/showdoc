<template>
  <div class="register-page">
    <div class="toggle-wrapper">
      <div class="toggle-group">
        <ThemeToggle />
        <LanguageToggle />
      </div>
    </div>
    <div class="register-card">
      <div class="card-header">
        <h2 class="title">{{ $t('user.register') }}</h2>
      </div>

      <div class="form-inputs">
        <CommonInput
          v-model="form.username"
          :placeholder="$t('user.username_description')"
          @keyup.enter="handleRegister"
        />

        <CommonInput
          v-model="form.password"
          type="password"
          :placeholder="$t('user.password')"
          @keyup.enter="handleRegister"
        />

        <CommonInput
          v-model="form.confirmPassword"
          type="password"
          :placeholder="$t('user.password_again')"
          @keyup.enter="handleRegister"
        />

        <div class="captcha-input">
          <CommonInput
            v-model="form.captcha"
            :placeholder="$t('user.verification_code')"
            @keyup.enter="handleRegister"
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
        :text="loading ? $t('user.register_loading') : $t('user.register')"
        theme="dark"
        :loading="loading"
        :full-width="true"
        @click="handleRegister"
      />

      <div class="footer-links">
        <router-link to="/user/login">{{ $t('user.login') }}</router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/store/user'
import CommonInput from '@/components/CommonInput.vue'
import CommonButton from '@/components/CommonButton.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
import LanguageToggle from '@/components/LanguageToggle.vue'
import AlertModal from '@/components/AlertModal/index'
import Message from '@/components/Message'
import { getServerHost, appendUrlParams } from '@/utils/system'
import request from '@/utils/request'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const form = ref({
  username: '',
  password: '',
  confirmPassword: '',
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

// 注册处理
const handleRegister = async () => {
  loading.value = true

  // 设置3秒后自动关闭loading，防止接口回调失败时一直显示
  const loadingTimer = setTimeout(() => {
    loading.value = false
  }, 3000)

  try {
    const data = await request(
      '/api/user/registerByVerify',
      {
        username: form.value.username,
        password: form.value.password,
        confirm_password: form.value.confirmPassword,
        captcha: form.value.captcha,
        captcha_id: captchaId.value,
      },
      'post',
      false
    )

    clearTimeout(loadingTimer)

    if (data.error_code === 0) {
      // 保存用户信息
      localStorage.setItem('userinfo', JSON.stringify(data.data))
      userStore.setUserInfo(data.data)

      Message.success(t('user.register_success'))

      // 跳转到项目列表
      router.push({ path: '/item/index' })
    } else {
      refreshCaptcha()
      AlertModal(data.error_message || t('common.error'))
    }
  } catch (error) {
    clearTimeout(loadingTimer)
    refreshCaptcha()
    console.error('注册失败:', error)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  // 刷新验证码
  await refreshCaptcha()

  // 检查是否已登录
  try {
    const data = await request('/api/user/info', { redirect_login: false }, 'post', false)

    if (data.error_code === 0) {
      // 已登录，跳转到项目列表
      router.push({ path: '/item/index' })
    }
  } catch {
    // 未登录，保持当前页面
  }

  // 如果是从对话框中跳转到注册页面，可能遮罩层来不及关闭，导致注册页面无法点击。这个时候，写js去掉遮罩层。
  const elements = document.getElementsByClassName('v-modal-leave')
  for (let index = 0; index < elements.length; index++) {
    elements[index].remove()
  }
})
</script>

<style scoped>
.register-page {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background-color: var(--color-bg-secondary);
}

.toggle-wrapper {
  position: absolute;
  top: 20px;
  right: 20px;

  .toggle-group {
    display: flex;
    align-items: center;
    gap: 12px;
    background-color: var(--color-bg-secondary);
    padding: 4px;
    border-radius: 8px;
    box-shadow: var(--shadow-xs);
  }
}

.register-card {
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

.separator {
  margin: 0 10px;
  color: var(--color-text-secondary);
}
</style>
