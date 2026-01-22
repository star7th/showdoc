<template>
  <div class="item-password-page">
    <div class="password-card">
      <h2 class="title">{{ $t('item.input_visit_password') }}</h2>

      <div class="form-group">
        <CommonInput
          v-model="password"
          type="password"
          :placeholder="$t('item.visit_password_placeholder')"
          @keyup.enter="handleSubmit"
        />
      </div>

      <div class="form-group captcha-group">
        <CommonInput
          v-model="captcha"
          :placeholder="$t('user.verification_code')"
          @keyup.enter="handleSubmit"
        />
        <img
          v-if="captchaImg"
          :src="captchaImg"
          class="captcha-img"
          @click="refreshCaptcha"
          :title="$t('item.click_to_refresh')"
        />
      </div>

      <CommonButton
        theme="dark"
        :loading="loading"
        :full-width="true"
        @click="handleSubmit"
        class="submit-btn"
      >
        {{ $t('common.submit') }}
      </CommonButton>

      <div class="footer-links">
        <router-link :to="`/user/login?redirect=${encodeURIComponent(redirect)}`">
          {{ $t('user.login') }}
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import CommonInput from '@/components/CommonInput.vue'
import CommonButton from '@/components/CommonButton.vue'
import AlertModal from '@/components/AlertModal'
import request from '@/utils/request'
import { getServerHost, appendUrlParams } from '@/utils/system'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const password = ref('')
const captcha = ref('')
const captchaId = ref(0)
const captchaImg = ref('')
const loading = ref(false)
const redirect = ref('/')

// 刷新验证码
const refreshCaptcha = async () => {
  try {
    const response = await request('/api/common/createCaptcha', {})
    if (response.error_code === 0) {
      const json = response.data
      captchaId.value = json.captcha_id

      // 构建验证码图片URL
      const serverHost = getServerHost()
      let captchaUrl = `${serverHost}/api/common/showCaptcha`
      captchaUrl = appendUrlParams(captchaUrl, {
        captcha_id: json.captcha_id,
        t: Date.now()
      })
      captchaImg.value = captchaUrl
    }
  } catch (error) {
    console.error('获取验证码失败:', error)
    AlertModal(t('common.error'))
  }
}

// 提交密码验证
const handleSubmit = async () => {
  // 表单验证
  if (!password.value) {
    AlertModal(t('item.visit_password_placeholder'))
    return
  }
  if (!captcha.value) {
    AlertModal(t('user.captcha_required'))
    return
  }

  const itemId = route.params.item_id || 0
  const pageId = route.query.page_id || 0

  loading.value = true

  try {
    const params = {
      item_id: itemId,
      page_id: pageId,
      password: password.value,
      captcha: captcha.value,
      captcha_id: captchaId.value
    }

    const response = await request('/api/item/pwd', params, 'post', false)

    if (response.error_code === 0) {
      // 保存密码到 sessionStorage
      sessionStorage.setItem('_item_pwd', password.value)

      // 跳转到原页面
      const redirectUrl = decodeURIComponent(redirect.value || `/${itemId}`)
      router.replace(redirectUrl)
    } else {
      // 验证失败，刷新验证码
      refreshCaptcha()
      AlertModal(response.error_message || t('common.error'))
    }
  } catch (error) {
    console.error('密码验证失败:', error)
    refreshCaptcha()
    AlertModal(t('common.error'))
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  // 初始化
  const itemId = route.params.item_id || 0
  redirect.value = route.query.redirect as string || `/${itemId}`

  // 刷新验证码
  refreshCaptcha()
})
</script>

<style lang="scss" scoped>
.item-password-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: var(--color-bg-secondary);
}

.password-card {
  width: 100%;
  max-width: 400px;
  padding: 40px;
  background: var(--color-bg-primary);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  box-shadow: var(--shadow-lg);
}

.title {
  margin-bottom: 30px;
  text-align: center;
  color: var(--color-text-primary);
  font-size: 24px;
  font-weight: 600;
}

.form-group {
  margin-bottom: 20px;

  &.captcha-group {
    display: flex;
    gap: 10px;

    .CommonInput {
      flex: 1;
    }
  }
}

.captcha-img {
  height: 40px;
  border: 1px solid var(--color-border);
  border-radius: 4px;
  cursor: pointer;
  transition: opacity 0.15s ease;

  &:hover {
    opacity: 0.7;
  }
}

.submit-btn {
  margin-top: 10px;
}

.footer-links {
  margin-top: 20px;
  text-align: center;
  font-size: 14px;

  a {
    color: var(--color-active);
    text-decoration: underline;
    transition: opacity 0.15s ease;

    &:hover {
      opacity: 0.7;
    }
  }
}
</style>

