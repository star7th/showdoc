<template>
  <div class="my-modal">
    <CommonModal
      :class="{ show }"
      :title="t('item.open_api')"
      :icon="['fa', 'fa-plug']"
      @close="handleClose"
    >
      <div class="open-api-container">
        <!-- API 密钥表单 -->
        <a-form :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
          <a-form-item :label="t('item.api_key_label')">
            <a-input v-model:value="apiKey" :readonly="true" />
          </a-form-item>

          <a-form-item :label="t('item.api_token_label')">
            <a-input v-model:value="apiToken" :readonly="true" />
          </a-form-item>

          <a-form-item :wrapper-col="{ span: 18, offset: 6 }">
            <CommonButton
              :text="t('item.reset_token')"
              theme="dark"
              @click="handleResetToken"
            >
            </CommonButton>
          </a-form-item>
        </a-form>

        <!-- 提示信息 -->
        <div class="tips-section">
          <p v-html="t('item.open_api_tips1')"></p>
          <p v-html="t('item.open_api_tips2')"></p>
          <p v-html="t('item.open_api_tips3')"></p>
          <p v-html="t('item.open_api_tips4')"></p>
          <p v-html="t('item.open_api_tips5')"></p>
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
import ConfirmModal from '@/components/ConfirmModal'
import { getItemApiKey, resetItemApiKey } from '@/models/item'

const { t } = useI18n()

const props = defineProps<{
  item_id: string | number
  onClose: (result: boolean) => void
}>()

// 数据状态
const show = ref(false)
const apiKey = ref('')
const apiToken = ref('')

// 获取 API 密钥信息
const fetchApiKey = async () => {
  try {
    const res = await getItemApiKey(String(props.item_id))
    if (res.error_code === 0) {
      apiKey.value = res.data.api_key || ''
      apiToken.value = res.data.api_token || ''
    } else {
      message.error(res.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('获取 API 密钥失败:', error)
    message.error(t('common.op_failed'))
  }
}

// 重置 API Token
const handleResetToken = async () => {
  const confirmed = await ConfirmModal(t('item.confirm_reset_token'))

  if (confirmed) {
    try {
      const res = await resetItemApiKey(String(props.item_id))
      if (res.error_code === 0) {
        message.success(t('common.op_success'))
        // 重新获取密钥信息
        await fetchApiKey()
      } else {
        message.error(res.error_message || t('common.op_failed'))
      }
    } catch (error) {
      console.error('重置 API Token 失败:', error)
      message.error(t('common.op_failed'))
    }
  }
}

// 关闭弹窗
const handleClose = () => {
  show.value = false
  setTimeout(() => {
    props.onClose(true)
  }, 300)
}

onMounted(() => {
  fetchApiKey()
  setTimeout(() => {
    show.value = true
  })
})
</script>

<style scoped lang="scss">
.open-api-container {
  padding: 20px 0;
}

.tips-section {
  margin-top: 24px;
  padding: 20px 30px;
  background-color: var(--color-bg-secondary);
  border-radius: 6px;

  p {
    margin-bottom: 16px;
    font-size: 14px;
    line-height: 1.6;
    color: var(--color-text-secondary);

    &:last-child {
      margin-bottom: 0;
    }

    a {
      color: var(--color-active);
      text-decoration: none;
      transition: text-decoration 0.15s ease;

      &:hover {
        text-decoration: underline;
      }
    }
  }
}

:deep(.ant-form-item) {
  margin-bottom: 20px;
}

:deep(.ant-input[readonly]) {
  background-color: var(--color-bg-secondary);
  color: var(--color-text-primary);
  cursor: default;
  user-select: all;
}

[data-theme='dark'] :deep(.ant-input[readonly]) {
  background-color: rgba(255, 255, 255, 0.04);
  border-color: var(--color-border);
}

[data-theme='dark'] .tips-section {
  background-color: rgba(255, 255, 255, 0.04);
}

.my-modal :deep(.modal-content) {
  width: 500px;
  max-width: 90vw;
}
</style>
