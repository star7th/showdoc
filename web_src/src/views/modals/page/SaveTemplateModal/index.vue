<template>
  <CommonModal
    :show="show"
    :title="$t('page.save_to_template')"
    height="600px"
    @close="handleClose"
  >
    <div class="save-template-modal">
      <!-- 模板标题 -->
      <div class="form-item">
        <div class="form-label">
          <span class="label-text">{{ $t('page.template_title') }}</span>
          <span class="label-required">*</span>
        </div>
        <CommonInput
          v-model="form.title"
          :placeholder="$t('page.input_template_title')"
          :maxlength="100"
        />
        <div class="input-counter">{{ form.title.length }}/100</div>
      </div>
      
      <!-- 模板内容预览 -->
      <div class="form-item">
        <div class="form-label">
          <span class="label-text">{{ $t('page.template_content_preview') }}</span>
        </div>
        <div class="content-preview">
          <textarea
            :value="content"
            :rows="10"
            readonly
            class="readonly-textarea"
          />
        </div>
        <div class="input-counter">{{ content.length }}</div>
      </div>

      <!-- 共享到项目 -->
      <div class="form-item">
        <div class="form-label">
          <span class="label-text">{{ $t('page.share_template') }}</span>
        </div>
        <div class="share-option">
          <CommonSwitch v-model="form.shareToItem" />
          <div class="tips-text">
            {{ $t('page.share_template_tips') }}
          </div>
        </div>
      </div>

      <!-- 选择项目 -->
      <div v-if="form.shareToItem === '1'" class="form-item">
        <div class="form-label">
          <span class="label-text">{{ $t('page.share_to_items') }}</span>
        </div>
        <div class="item-selector">
          <div
            v-for="item in itemOptions"
            :key="item.value"
            class="item-checkbox"
            @click="toggleItem(item.value)"
          >
            <i :class="isItemSelected(item.value) ? 'fas fa-check-square' : 'fas fa-square'" class="checkbox-icon"></i>
            <span class="item-label">{{ item.label }}</span>
          </div>
          <div v-if="itemOptions.length === 0" class="empty-tip">
            {{ $t('page.no_items_to_share') }}
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <CommonButton @click="handleClose">{{ $t('common.cancel') }}</CommonButton>
      <CommonButton type="primary" @click="handleSave" :loading="saving">
        {{ $t('common.save') }}
      </CommonButton>
    </template>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import request from '@/utils/request'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonInput from '@/components/CommonInput.vue'
import CommonSwitch from '@/components/CommonSwitch.vue'

// Props
interface Props {
  content: string
  onSuccess?: () => void
  onClose: () => void
}

const props = defineProps<Props>()

// Composables
const { t } = useI18n()

// Refs
const show = ref(false)
const saving = ref(false)
const form = ref({
  title: '',
  shareToItem: '0',
  shareItemIds: [] as number[]
})
const itemOptions = ref<{ label: string; value: number }[]>([])

// Computed
const isItemSelected = (itemId: number) => {
  return form.value.shareItemIds.includes(itemId)
}

// Methods
const loadMyItems = async () => {
  try {
    const data = await request('/api/item/myList', {}, 'post', false)
    if (data.error_code === 0 && data.data) {
      itemOptions.value = data.data.map((item: any) => ({
        label: item.item_name,
        value: item.item_id
      }))
    }
  } catch (error) {
    console.error('获取项目列表失败:', error)
  }
}

const toggleItem = (itemId: number) => {
  const index = form.value.shareItemIds.indexOf(itemId)
  if (index > -1) {
    form.value.shareItemIds.splice(index, 1)
  } else {
    form.value.shareItemIds.push(itemId)
  }
}

const handleSave = async () => {
  if (!form.value.title.trim()) {
    await AlertModal(t('page.template_title_required'))
    return
  }

  saving.value = true
  try {
    const data = await request('/api/template/save', {
      template_title: form.value.title,
      template_content: props.content
    }, 'post', false)

    if (data.error_code === 0) {
      Message.success(t('page.save_template_success'))
      
      // 如果需要共享到项目
      if (form.value.shareToItem === '1' && form.value.shareItemIds.length > 0 && data.data?.id) {
        await request('/api/template/shareToItem', {
          template_id: data.data.id,
          item_id: form.value.shareItemIds.join(',')
        }, 'post', false)
      }
      
      if (props.onSuccess) {
        props.onSuccess()
      }
      handleClose()
    } else {
      await AlertModal(data.error_message || t('common.save_failed'))
    }
  } catch (error) {
    console.error('保存模板失败:', error)
    await AlertModal(t('common.save_failed'))
  } finally {
    saving.value = false
  }
}

const handleClose = () => {
  show.value = false
  setTimeout(() => {
    props.onClose()
  }, 300)
}

// Lifecycle
onMounted(() => {
  show.value = true
  loadMyItems()
})
</script>

<style lang="scss" scoped>
.save-template-modal {
  .form-item {
    margin-bottom: 16px;

    &:last-child {
      margin-bottom: 0;
    }
  }

  .form-label {
    display: flex;
    align-items: center;
    margin-bottom: 6px;

    .label-text {
      font-size: 14px;
      color: var(--color-text-primary);
    }

    .label-required {
      color: var(--color-error);
      margin-left: 4px;
    }
  }

  .input-counter {
    text-align: right;
    font-size: 12px;
    color: var(--color-text-secondary);
    margin-top: 4px;
  }

  .content-preview {
    background-color: var(--color-bg-secondary);
    border-radius: 4px;
    padding: 12px;

    .readonly-textarea {
      width: 100%;
      min-height: 120px;
      max-height: 150px;
      padding: 8px;
      border: 1px solid var(--color-border);
      border-radius: 4px;
      background-color: var(--color-bg-primary);
      color: var(--color-text-primary);
      font-family: Consolas, Menlo, Courier, monospace;
      font-size: 13px;
      line-height: 20px;
      resize: none;
      outline: none;
    }
  }

  .share-option {
    display: flex;
    align-items: flex-start;
    gap: 12px;

    .tips-text {
      margin-top: 2px;
      font-size: 12px;
      color: var(--color-text-secondary);
      line-height: 20px;
    }
  }

  .item-selector {
    max-height: 180px;
    overflow-y: auto;
    border: 1px solid var(--color-border);
    border-radius: 4px;
    background-color: var(--color-bg-primary);

    &::-webkit-scrollbar {
      width: 6px;
    }

    &::-webkit-scrollbar-thumb {
      background-color: var(--color-border);
      border-radius: 3px;
    }

    .item-checkbox {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      cursor: pointer;
      transition: background-color 0.15s ease;
      border-bottom: 1px solid var(--color-border);

      &:last-child {
        border-bottom: none;
      }

      &:hover {
        background-color: var(--color-bg-secondary);
      }

      .checkbox-icon {
        color: var(--color-text-secondary);
        font-size: 14px;
        flex-shrink: 0;

        &.fa-check-square {
          color: var(--color-active);
        }
      }

      .item-label {
        flex: 1;
        font-size: 14px;
        color: var(--color-text-primary);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
    }

    .empty-tip {
      padding: 20px;
      text-align: center;
      font-size: 14px;
      color: var(--color-text-secondary);
    }
  }
}
</style>

