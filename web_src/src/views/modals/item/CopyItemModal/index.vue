<template>
  <div class="my-modal">
    <CommonModal
      :class="{ show }"
      :title="t('item.copy_item')"
      :icon="['fa', 'fa-copy']"
      @close="handleClose"
    >
      <div class="modal-content">
        <a-form :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
          <!-- 选择要复制的项目 -->
          <a-form-item :label="t('common.source')">
            <CommonSelector
              v-model:value="copyItemId"
              :placeholder="t('common.please_choose')"
              :options="itemOptions"
              :show-search="true"
              @change="chooseCopyItem"
            />
          </a-form-item>

          <!-- 新项目名称 -->
          <a-form-item :label="t('item.item_name')">
            <CommonInput
              v-model="itemName"
              :placeholder="t('item.copy_item_tips2')"
            />
          </a-form-item>

          <!-- 项目类型：公开/私密 -->
          <a-form-item :label="t('item.item_type')">
            <a-radio-group v-model:value="isOpenItem">
              <a-radio :value="true">{{ t('item.public_item') }}</a-radio>
              <a-radio :value="false">{{ t('item.private_item') }}</a-radio>
            </a-radio-group>
          </a-form-item>

          <!-- 私密项目密码 -->
          <a-form-item v-if="!isOpenItem" :label="t('item.visit_password')">
            <CommonInput
              v-model="password"
              type="password"
              :placeholder="t('item.input_visit_password')"
            />
          </a-form-item>
        </a-form>
      </div>
      <div class="modal-footer">
        <CommonButton theme="light" :text="t('common.cancel')" @click="handleClose" />
        <CommonButton theme="dark" :text="t('common.confirm')" :spinning="loading" @click="handleSubmit" />
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonInput from '@/components/CommonInput.vue'
import CommonSelector from '@/components/CommonSelector.vue'
import { getMyList } from '@/models/item'
import { addItem } from '@/models/item'

const { t } = useI18n()

const props = defineProps<{
  item_id?: string | number
  onClose: (result: boolean) => void
}>()

// 数据状态
const show = ref(false)
const loading = ref(false)
const copyItemId = ref<string | number | undefined>(undefined)
const itemName = ref('')
const itemDescription = ref('')
const isOpenItem = ref(true)
const password = ref('')
const itemList = ref<any[]>([])

// 计算选项列表
const itemOptions = computed(() => {
  return itemList.value.map(item => ({
    label: item.item_name,
    value: String(item.item_id)
  }))
})

// 获取项目列表
const fetchItemList = async () => {
  try {
    const res = await getMyList()
    if (res.error_code === 0) {
      itemList.value = res.data || []
      // 如果传入了 item_id，默认选中
      if (props.item_id) {
        copyItemId.value = String(props.item_id)
        chooseCopyItem(props.item_id)
      }
    } else {
      message.error(res.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('获取项目列表失败:', error)
    message.error(t('common.op_failed'))
  }
}

// 选择复制的项目
const chooseCopyItem = (itemId: number | string) => {
  const item = itemList.value.find((i) => String(i.item_id) === String(itemId))
  if (item) {
    itemName.value = item.item_name + '--copy'
    itemDescription.value = item.item_description || ''
  }
}

// 提交复制
const handleSubmit = async () => {
  // 验证
  if (!copyItemId.value) {
    message.warning(t('common.please_choose'))
    return
  }

  if (!itemName.value.trim()) {
    message.warning(t('item.copy_item_tips2'))
    return
  }

  if (!isOpenItem.value && !password.value.trim()) {
    message.warning(t('item.private_item_password'))
    return
  }

  loading.value = true

  try {
    const res = await addItem({
      copy_item_id: Number(copyItemId.value),
      item_name: itemName.value,
      item_description: itemDescription.value,
      password: isOpenItem.value ? '' : password.value
    })
    if (res.error_code === 0) {
      message.success(t('common.op_success'))
      props.onClose(true)
    } else {
      message.error(res.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('复制项目失败:', error)
    message.error(t('common.op_failed'))
  } finally {
    loading.value = false
  }
}

// 关闭弹窗
const handleClose = () => {
  show.value = false
  setTimeout(() => {
    props.onClose(false)
  }, 300)
}

onMounted(() => {
  fetchItemList()
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
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 90px;
  gap: 20px;

  .common-button {
    width: 160px;
  }
}

:deep(.ant-form-item) {
  margin-bottom: 20px;
}

:deep(.ant-input),
:deep(.ant-select) {
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

:deep(.ant-form-item-label > label) {
  color: var(--color-text-primary);
}

// 暗黑主题适配
[data-theme='dark'] {
  :deep(.ant-input),
  :deep(.ant-select) {
    background-color: rgba(255, 255, 255, 0.08);
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
    background-color: rgba(255, 255, 255, 0.08);
  }

  :deep(.ant-select-selector) {
    background-color: rgba(255, 255, 255, 0.08) !important;
  }

  :deep(.ant-select-arrow) {
    color: var(--color-text-primary);
  }
}
</style>

