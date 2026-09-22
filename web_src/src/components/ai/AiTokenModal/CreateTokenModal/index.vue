<template>
  <div class="create-token-modal">
    <CommonModal
      :class="{ show }"
      :title="isEditMode ? $t('user.edit_token') : $t('user.create_token')"
      :icon="['fas', isEditMode ? 'edit' : 'plus']"
      width="500px"
      @close="handleClose"
    >
      <div class="modal-content">
        <a-form
          layout="horizontal"
          :label-col="{ span: 6 }"
          :wrapper-col="{ span: 18 }"
        >
          <a-form-item :label="$t('user.token_name')">
            <CommonInput
              v-model="form.name"
              :placeholder="$t('user.token_name_placeholder')"
              maxlength="50"
            />
          </a-form-item>
          <a-form-item :label="$t('user.permission_level')">
            <a-select v-model:value="form.permission">
              <a-select-option
                v-for="opt in permissionOptions"
                :key="opt.value"
                :value="opt.value"
              >
                {{ opt.label }}
              </a-select-option>
            </a-select>
          </a-form-item>
          <a-form-item :label="$t('user.project_scope')">
            <a-radio-group v-model:value="form.scope">
              <a-radio value="all">{{ $t('user.scope_all') }}</a-radio>
              <a-radio value="selected">{{
                $t('user.scope_selected')
              }}</a-radio>
            </a-radio-group>
          </a-form-item>
          <a-form-item
            v-if="form.scope === 'selected'"
            :label="$t('user.select_projects')"
          >
            <div class="project-checkbox-list">
              <a-checkbox-group v-model:value="form.allowedItems">
                <div
                  v-for="item in projects"
                  :key="item.item_id"
                  class="project-checkbox-item"
                >
                  <a-checkbox :value="item.item_id">{{
                    item.item_name
                  }}</a-checkbox>
                </div>
              </a-checkbox-group>
            </div>
          </a-form-item>
          <a-form-item :label="$t('user.expires_at')">
            <a-select v-model:value="form.expiresAt">
              <a-select-option
                v-for="opt in expiresOptions"
                :key="opt.value"
                :value="opt.value"
              >
                {{ opt.label }}
              </a-select-option>
            </a-select>
          </a-form-item>
          <a-form-item :label="$t('user.extra_permissions')">
            <div class="checkbox-item">
              <a-checkbox v-model:checked="form.canCreateItem">{{
                $t('user.can_create_item')
              }}</a-checkbox>
            </div>
            <div class="checkbox-item">
              <a-checkbox v-model:checked="form.canDeleteItem">{{
                $t('user.can_delete_item')
              }}</a-checkbox>
            </div>
            <div v-if="form.scope === 'selected'" class="checkbox-item">
              <a-checkbox v-model:checked="form.autoAddCreatedItem">
                {{ $t('user.auto_add_created_item') }}
              </a-checkbox>
            </div>
          </a-form-item>
        </a-form>
      </div>
      <div class="modal-footer">
        <div class="secondary-button" @click="() => handleClose()">
          {{ $t('common.cancel') }}
        </div>
        <div class="primary-button" @click="handleConfirm">
          {{ $t('common.confirm') }}
        </div>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import CommonInput from '@/components/CommonInput.vue'
import Message from '@/components/Message'
import request from '@/utils/request'

interface Project {
  item_id: string
  item_name: string
}

const { t } = useI18n()

const props = defineProps<{
  onClose: (result: boolean) => void
  editTokenId: string | null
  projects: Project[]
}>()

// 弹窗显示状态
const show = ref(false)

// 是否为编辑模式
const isEditMode = computed(() => !!props.editTokenId)

// 表单数据
const form = ref({
  name: '',
  permission: 'write',
  scope: 'all',
  allowedItems: [] as string[],
  expiresAt: '',
  canCreateItem: true,
  canDeleteItem: false,
  autoAddCreatedItem: true,
})

// 权限选项
const permissionOptions = computed(() => [
  { value: 'read', label: t('user.permission_read') },
  { value: 'write', label: t('user.permission_write') },
])

// 过期时间选项
const expiresOptions = computed(() => [
  { value: '', label: t('user.never_expire') },
  { value: '30', label: t('user.expire_30_days') },
  { value: '90', label: t('user.expire_90_days') },
  { value: '180', label: t('user.expire_180_days') },
  { value: '365', label: t('user.expire_365_days') },
])

// 加载 Token 详情（编辑模式）
const loadTokenDetail = async () => {
  if (!props.editTokenId) return

  try {
    const data = await request('/api/ai_token/detail', {
      id: props.editTokenId,
    })
    if (data && data.data) {
      const detail = data.data
      form.value.name = detail.name || ''
      form.value.permission = detail.permission || 'write'
      form.value.scope = detail.scope || 'all'
      // 解析 allowed_items
      if (
        detail.allowed_items_detail &&
        Array.isArray(detail.allowed_items_detail)
      ) {
        form.value.allowedItems = detail.allowed_items_detail.map((item: any) =>
          String(item.item_id),
        )
      } else {
        form.value.allowedItems = []
      }
      // 过期时间不回显
      form.value.expiresAt = ''
      // 后端返回的可能是字符串 "0" 或 "1"，需要转换为布尔值
      form.value.canCreateItem =
        detail.can_create_item === '1' || detail.can_create_item === 1
      form.value.canDeleteItem =
        detail.can_delete_item === '1' || detail.can_delete_item === 1
      form.value.autoAddCreatedItem =
        detail.auto_add_created_item === '1' ||
        detail.auto_add_created_item === 1
    }
  } catch (error) {
    console.error('获取 Token 详情失败:', error)
    Message.error(t('common.load_failed'))
    handleClose()
  }
}

// 计算过期时间
const calculateExpiresAt = (): string | null => {
  if (!form.value.expiresAt) {
    return null
  }
  const days = parseInt(form.value.expiresAt, 10)
  const date = new Date()
  date.setDate(date.getDate() + days)
  return date.toISOString().slice(0, 19).replace('T', ' ')
}

// 确认提交
const handleConfirm = async () => {
  if (!form.value.name.trim()) {
    Message.error(t('user.token_name_required'))
    return
  }

  // 如果选择了指定项目但没有选择任何项目，提示错误
  if (form.value.scope === 'selected' && form.value.allowedItems.length === 0) {
    Message.error(t('user.select_projects_required'))
    return
  }

  try {
    const expiresAt = calculateExpiresAt()
    const requestData: Record<string, any> = {
      name: form.value.name.trim(),
      permission: form.value.permission,
      scope: form.value.scope,
      // 使用逗号分隔的字符串格式，兼容性更好
      allowed_items:
        form.value.scope === 'selected'
          ? form.value.allowedItems.join(',')
          : '',
      can_create_item: form.value.canCreateItem ? 1 : 0,
      can_delete_item: form.value.canDeleteItem ? 1 : 0,
      auto_add_created_item: form.value.autoAddCreatedItem ? 1 : 0,
    }
    // 只有设置了过期时间才传递 expires_at
    if (expiresAt) {
      requestData.expires_at = expiresAt
    }

    if (isEditMode.value) {
      // 编辑模式
      requestData.id = props.editTokenId
      const data = await request('/api/ai_token/update', requestData)
      if (data && data.data) {
        Message.success(t('user.update_success'))
        handleClose(true)
      }
    } else {
      // 创建模式
      const data = await request('/api/ai_token/create', requestData)
      if (data && data.data) {
        Message.success(t('user.create_success'))
        handleClose(true)
      }
    }
  } catch (error) {
    console.error('保存 Token 失败:', error)
  }
}

// 关闭弹窗
const handleClose = (result = false) => {
  show.value = false
  setTimeout(() => {
    props.onClose(result)
  }, 300)
}

onMounted(() => {
  show.value = true
  if (isEditMode.value) {
    loadTokenDetail()
  }
})
</script>

<style lang="scss" scoped>
.create-token-modal {
  .modal-content {
    padding: 24px;
  }

  .project-checkbox-list {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid var(--color-border);
    border-radius: 6px;
    padding: 8px 12px;

    .project-checkbox-item {
      padding: 6px 0;
    }
  }

  .checkbox-item {
    padding: 4px 0;
  }

  :deep(.ant-select-selector) {
    height: 40px !important;
    font-size: 13px;

    .ant-select-selection-item {
      line-height: 38px !important;
    }
  }

  :deep(.ant-select-selection-search-input) {
    height: 38px !important;
  }

  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid var(--color-border);
  }
}
</style>
