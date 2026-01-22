<template>
  <div class="reset-password-by-url-page">
    <div class="reset-password-card">
      <div class="card-header">
        <h2 class="title">{{ $t('user.reset_password') }}</h2>
      </div>

      <div class="form-inputs">
        <CommonInput
          v-model="form.password"
          type="password"
          :placeholder="$t('user.new_password')"
          @keyup.enter="handleSubmit"
        />
      </div>

      <CommonButton
        :text="loading ? $t('common.submitting') : $t('common.submit')"
        theme="dark"
        :loading="loading"
        :full-width="true"
        @click="handleSubmit"
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
import { useRoute, useRouter } from 'vue-router'
import CommonInput from '@/components/CommonInput.vue'
import CommonButton from '@/components/CommonButton.vue'
import AlertModal from '@/components/AlertModal/index'
import request from '@/utils/request'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const form = ref({
  password: '',
})

const loading = ref(false)

// 提交新密码
const handleSubmit = async () => {
  if (!form.value.password) {
    AlertModal(t('user.password_required'))
    return
  }

  loading.value = true

  try {
    const data = await request(
      '/api/user/resetPasswordByUrlToken',
      {
        new_password: form.value.password,
        token: route.params.token,
      },
      'post',
      false
    )

    if (data.error_code === 0) {
      AlertModal(t('user.reset_password_success'), {
        callback: () => {
          router.replace({ path: '/user/login' })
        },
      })
    } else {
      AlertModal(data.error_message || t('common.error'))
    }
  } catch (error) {
    console.error('重置密码失败:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  // 清除可能的遮罩层
  const elements = document.getElementsByClassName('v-modal-leave')
  for (let index = 0; index < elements.length; index++) {
    elements[index].remove()
  }
})
</script>

<style scoped>
.reset-password-by-url-page {
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

