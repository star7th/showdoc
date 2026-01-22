<template>
  <div class="password-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('user.modify_password')"
      @close="handleClose"
    >
      <div class="modal-content">
        <div class="form-item">
          <label class="form-label">{{ $t('user.old_password') }}</label>
          <CommonInput
            v-model="formData.password"
            type="password"
            :placeholder="$t('user.old_password')"
          />
        </div>
        <div class="form-item">
          <label class="form-label">{{ $t('user.new_password') }}</label>
          <CommonInput
            v-model="formData.new_password"
            type="password"
            :placeholder="$t('user.new_password')"
          />
        </div>
        <div class="form-item">
          <label class="form-label">{{ $t('user.confirm_password') }}</label>
          <CommonInput
            v-model="formData.confirm_password"
            type="password"
            :placeholder="$t('user.confirm_password')"
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
  onClose: (result: boolean) => void
}>()

const show = ref(false)
const showId = ref(0)
const submitting = ref(false)
const formData = ref({
  password: '',
  new_password: '',
  confirm_password: ''
})

const handleSubmit = async () => {
  if (!formData.value.password) {
    await AlertModal(t('user.old_password_required'))
    return
  }
  if (!formData.value.new_password) {
    await AlertModal(t('user.new_password_required'))
    return
  }
  if (formData.value.new_password !== formData.value.confirm_password) {
    await AlertModal(t('user.password_mismatch'))
    return
  }

  submitting.value = true
  try {
    await request('/api/user/resetPassword', {
      new_password: formData.value.new_password,
      password: formData.value.password
    })
    // 使用弹窗提醒用户
    AlertModal(t('user.modify_success'), {
      callback: () => {
        props.onClose(true)
      }
    })
  } catch (error) {
    console.error('修改密码失败:', error)
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

  .primary-button {
    width: 160px;
    margin: 0 7.5px;
  }
}
</style>

