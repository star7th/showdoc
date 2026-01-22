<template>
  <div class="verify-email-by-url-page">
    <!-- 页面加载中... -->
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import request from '@/utils/request'
import { useI18n } from 'vue-i18n'
import AlertModal from '@/components/AlertModal'
import ConfirmModal from '@/components/ConfirmModal'

const router = useRouter()
const route = useRoute()
const { t } = useI18n()

onMounted(async () => {
  const token = route.params.token as string
  if (!token) {
    await AlertModal(t('user.invalid_token'))
    router.replace('/user/login')
    return
  }

  try {
    await request('/api/user/verifyEmailByUrlToken', { token })
    await ConfirmModal({
      msg: t('user.verify_email_success'),
      confirmText: t('common.confirm')
    })
    router.replace('/user/login')
  } catch (error) {
    console.error('验证邮箱失败:', error)
    await AlertModal(t('common.error'))
    router.replace('/user/login')
  }
})
</script>

<style lang="scss" scoped>
.verify-email-by-url-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background-color: var(--color-bg-primary);
}
</style>

