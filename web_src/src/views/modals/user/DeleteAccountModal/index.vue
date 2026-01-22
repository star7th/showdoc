<template>
  <div class="delete-account-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('user.delete_account')"
      @close="handleClose"
    >
      <div class="modal-content">
        <div class="warning-section">
          <div class="warning-title">
            <i class="fas fa-exclamation-triangle warning-icon"></i>
            <span>{{ $t('user.delete_account_warning_title') }}</span>
          </div>
          <div class="warning-content">
            <p><strong>{{ $t('user.delete_account_tips') }}</strong></p>
            <ul>
              <li>{{ $t('user.delete_account_tip1') }}</li>
              <li>{{ $t('user.delete_account_tip2') }}</li>
              <li>{{ $t('user.delete_account_tip3') }}</li>
            </ul>
            <p class="warning-text">{{ $t('user.delete_account_warning') }}</p>
          </div>
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
        <CommonButton type="danger" @click="handleSubmit" :loading="submitting">
          {{ $t('user.confirm_delete_account') }}
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
import ConfirmModal from '@/components/ConfirmModal'
import AlertModal from '@/components/AlertModal'
import request from '@/utils/request'
import Message from '@/components/Message'

const { t } = useI18n()

const props = defineProps<{
  onClose: (success: boolean) => void
}>()

const show = ref(false)
const showId = ref(0)
const submitting = ref(false)
const formData = ref({
  password: ''
})

const handleSubmit = async () => {
  if (!formData.value.password) {
    await AlertModal(t('user.password_required'))
    return
  }

  try {
    const confirmed = await ConfirmModal({
      title: t('common.confirm'),
      msg: t('user.confirm_delete_account_message'),
      confirmText: t('user.confirm_delete_account'),
      cancelText: t('common.cancel'),
    })

    if (confirmed) {
      submitting.value = true
      await request('/api/user/deleteAccount', {
        password: formData.value.password
      })
      // 使用弹窗提醒用户（但这之后会关闭整个用户中心，所以可能不需要callback）
      AlertModal(t('user.delete_account_success'), {
        callback: () => {
          props.onClose(true)
        }
      })
    }
  } catch (error) {
    console.error('注销账号失败:', error)
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

.warning-section {
  margin-bottom: 20px;
}

.warning-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 600;
  color: var(--color-text-primary);
  margin-bottom: 12px;

  .warning-icon {
    color: var(--color-warning);
  }
}

.warning-content {
  line-height: 1.8;
  color: var(--color-text-secondary);

  p {
    margin: 10px 0;
  }

  ul {
    margin: 10px 0;
    padding-left: 20px;

    li {
      margin: 5px 0;
    }
  }
}

.warning-text {
  color: var(--color-danger);
  font-weight: 600;
  margin-top: 10px;
}

.form-item {
  margin-top: 20px;

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

