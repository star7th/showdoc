<template>
  <div class="login-by-token-page">
    <!-- 加载中提示 -->
    <div class="loading-container">
      <div class="loading-spinner"></div>
      <p class="loading-text">{{ loadingText }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import request from '@/utils/request'
import { saveUserInfoToStorage } from '@/models/user'
import { useAppStore } from '@/store/app'

const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const appStore = useAppStore()

const loadingText = ref(t('user.token_login_loading'))

onMounted(async () => {
  try {
    // 从 URL 参数获取 user_token
    const user_token = route.query.user_token as string

    if (!user_token) {
      loadingText.value = t('user.token_login_parameter_error')
      setTimeout(() => {
        router.replace('/user/login')
      }, 2000)
      return
    }

    // 获取用户信息
    const response = await request(
      '/api/user/info',
      {
        user_token: user_token,
        redirect_login: false
      },
      'post',
      false
    )

    if (response.error_code === 0 && response.data) {
      // 保存用户信息
      const userinfo = response.data
      userinfo.user_token = user_token
      saveUserInfoToStorage(userinfo)

      // 设置 cookie_token（有效期 180 天）
      const d = new Date()
      d.setTime(d.getTime() + 180 * 24 * 60 * 60 * 1000)
      const expires = `expires=${d.toGMTString()}`
      document.cookie = `cookie_token=${user_token}; samesite=strict; path=/; ${expires}`

      // 更新应用状态
      appStore.setNewMsg(response.data.new_msg || 0)

      loadingText.value = t('user.token_login_success')
    } else {
      loadingText.value = t('user.token_login_failed')
    }
  } catch (error) {
    console.error('Token 登录失败:', error)
    loadingText.value = t('user.token_login_failed')
  }

  // 无论是否登录成功，都跳转
  setTimeout(() => {
    const redirect = decodeURIComponent(
      (route.query.redirect_uri as string) || '/item/index'
    )
    router.replace({ path: redirect })
  }, 1000)
})
</script>

<style scoped>
.login-by-token-page {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: var(--color-bg-secondary);
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.loading-text {
  font-size: 16px;
  color: var(--color-text-secondary);
}
</style>

