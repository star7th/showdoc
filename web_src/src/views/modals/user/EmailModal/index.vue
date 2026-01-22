<template>
  <div class="email-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('user.email')"
      @close="handleClose"
    >
      <div class="modal-content">
        <div class="form-item">
          <label class="form-label">{{ $t('user.email') }}</label>
          <CommonInput
            v-model="formData.email"
            :placeholder="$t('user.input_email')"
          />
        </div>
        <div class="form-item">
          <label class="form-label">{{ $t('user.password') }}</label>
          <CommonInput
            v-model="formData.password"
            type="password"
            :placeholder="$t('user.input_login_password')"
          />
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

const { t } = useI18n()

const props = defineProps<{
  email?: string
  onClose: (result: boolean) => void
}>()

const show = ref(false)
const showId = ref(0)
const submitting = ref(false)
const formData = ref({
  email: props.email || '',
  password: ''
})

const handleSubmit = async () => {
  if (!formData.value.email) {
    await AlertModal(t('user.email_required'))
    return
  }
  if (!formData.value.password) {
    await AlertModal(t('user.password_required'))
    return
  }

  submitting.value = true
  try {
    await request('/api/user/updateEmail', {
      email: formData.value.email,
      password: formData.value.password
    })
    // 使用弹窗提醒用户
    AlertModal(t('user.email_verify_sent'), {
      callback: () => {
        props.onClose(true)
      }
    })
  } catch (error) {
    console.error('更新邮箱失败:', error)
  } finally {
    submitting.value = false
  }
}

const handleClose = () => {
  show.value = false
  setTimeout(() => {
    props.onClose(false)
  }, 300)
}

onMounted(() => {
  // 使用 showId 确保每次都是新的打开
  showId.value++
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

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 90px;
  gap: 15px;

  .primary-button {
    width: 160px;
  }
}
</style>

