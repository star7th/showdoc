<template>
  <div class="attorn-modal">
    <CommonModal
      :show="show"
      :title="t('item.attorn')"
      @close="handleClose"
    >
      <div class="modal-content">
        <a-form layout="vertical">
          <a-form-item :label="t('item.attorn_username')">
            <CommonInput
              v-model="form.username"
              :placeholder="t('item.attorn_username')"
              auto-complete="new-password"
            />
          </a-form-item>
          <a-form-item :label="t('user.input_login_password')">
            <CommonInput
              v-model="form.password"
              type="password"
              :placeholder="t('user.input_login_password')"
              auto-complete="new-password"
            />
          </a-form-item>
        </a-form>
        <p class="tips-text">
          {{ t('item.attorn_tips') }}
        </p>
        <div class="button-bar">
          <CommonButton theme="light" :text="t('common.cancel')" @click="handleClose" />
          <CommonButton theme="dark" :text="t('common.confirm')" @click="handleSubmit" />
        </div>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonInput from '@/components/CommonInput.vue'
import { attornItem } from '@/models/item'

const { t } = useI18n()

const props = defineProps<{
  item_id: string | number
  onClose: (result: boolean) => void
}>()

const show = ref(false)
const form = ref({
  username: '',
  password: ''
})

// 提交
const handleSubmit = async () => {
  if (!form.value.username) {
    message.error(t('item.attorn_username') + t('user.required'))
    return
  }
  if (!form.value.password) {
    message.error(t('user.password_required'))
    return
  }

  try {
    const res = await attornItem(String(props.item_id), form.value.username, form.value.password)

    if (res.error_code === 0) {
      message.success(t('common.op_success'))
      props.onClose(true)  // 操作成功，返回 true 触发列表刷新
    } else {
      message.error(res.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('转让项目失败:', error)
    message.error(t('common.op_failed'))
  }
}

// 关闭
const handleClose = () => {
  show.value = false
  setTimeout(() => {
    props.onClose(false)
  }, 300)
}

onMounted(() => {
  show.value = true
})
</script>

<style scoped lang="scss">
.attorn-modal {
  :deep(.common-modal .modal-content) {
    max-width: 500px;
    background-color: var(--color-default);
  }
}

.modal-content {
  padding: 20px;
  color: var(--color-text-primary);
}

:deep(.ant-form-item) {
  margin-bottom: 16px;
}

:deep(.ant-form-item-label > label) {
  color: var(--color-text-primary);
}

:deep(.ant-input),
:deep(.ant-input-password) {
  width: 100%;
  background-color: var(--color-obvious);
  border-color: var(--color-border);
  color: var(--color-text-primary);

  &:hover {
    border-color: var(--color-primary);
  }

  &:focus {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.1);
  }
}

:deep(.ant-input-password-input) {
  background-color: var(--color-obvious);
  color: var(--color-text-primary);
}

.tips-text {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin-top: 10px;
  margin-bottom: 20px;
  line-height: 1.5;
}

.button-bar {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

// 暗黑主题适配
[data-theme='dark'] {
  .attorn-modal {
    :deep(.common-modal .modal-content) {
      background-color: var(--color-bg-primary);
    }
  }

  :deep(.ant-input),
  :deep(.ant-input-password) {
    background-color: var(--color-bg-secondary);
    border-color: var(--color-border);

    &:hover {
      border-color: var(--color-active);
    }

    &:focus {
      border-color: var(--color-active);
      box-shadow: 0 0 0 2px var(--color-active-hover-shadow);
    }
  }

  :deep(.ant-input-password-input) {
    background-color: var(--color-bg-secondary);
  }

  :deep(.ant-input-password-icon) {
    color: var(--color-text-secondary);
  }
}
</style>
