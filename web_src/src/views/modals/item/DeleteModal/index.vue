<template>
  <div class="delete-modal">
    <CommonModal
      :class="{ show }"
      :title="t('item.delete_item')"
      @close="handleClose"
    >
      <div class="modal-content">
        <a-form layout="vertical">
          <a-form-item :label="t('user.input_login_password')">
            <CommonInput
              v-model="form.password"
              type="password"
              :placeholder="t('user.input_login_password')"
              auto-complete="new-password"
            />
          </a-form-item>
        </a-form>
        <div class="tips-container">
          <div class="danger-tag">
            {{ t('item.delete_tips') }}
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <div class="secondary-button" @click="handleClose">{{ t('common.cancel') }}</div>
        <div class="primary-button danger-button" @click="handleSubmit">{{ t('item.delete_confirm_text') }}</div>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonInput from '@/components/CommonInput.vue'
import { deleteItem } from '@/models/item'

const { t } = useI18n()

const props = defineProps<{
  item_id: string | number
  onClose: (result: boolean) => void
}>()

const show = ref(false)
const form = ref({
  password: ''
})

// 提交
const handleSubmit = async () => {
  if (!form.value.password) {
    message.error(t('user.password_required'))
    return
  }

  try {
    const res = await deleteItem(String(props.item_id), form.value.password)

    if (res.error_code === 0) {
      message.success(t('common.op_success'))
      props.onClose(true)  // 操作成功，返回 true 触发列表刷新
    } else {
      message.error(res.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('删除项目失败:', error)
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

// 组件挂载时显示弹窗（延迟一点让动画更流畅）
onMounted(() => {
  setTimeout(() => {
    show.value = true
  })
})
</script>

<style scoped lang="scss">
.modal-content {
  width: 500px;
  padding: 30px 40px;
  border-bottom: 1px solid var(--color-interval);
  color: var(--color-text-primary);
}

:deep(.ant-form-item) {
  margin-bottom: 16px;
}

:deep(.ant-form-item-label > label) {
  color: var(--color-text-primary);
}

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

.tips-container {
  margin-top: 16px;
  margin-bottom: 0;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 90px;

  .secondary-button,
  .primary-button {
    width: 160px;
    margin: 0 7.5px;
  }
}

// 暗黑主题适配
[data-theme='dark'] {
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

