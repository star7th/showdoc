<template>
  <CommonModal
    :show="show"
    :title="$t('page.template_list')"
    width="900px"
    @close="handleClose"
  >
    <div class="template-select">
      <a-spin :spinning="loading">
        <CommonTab
          :items="tabItems"
          :value="activeTab"
          type="card"
          @updateValue="handleTabChange"
        >
          <!-- 我的模板 -->
          <template #my>
            <CommonTable
              :tableHeader="myColumns"
              :tableData="myTemplates"
              :loading="myLoading"
              :maxHeight="'400px'"
              :emptyText="() => $t('page.no_my_template_text')"
            >
              <template #cell-addtime="{ row }">
                {{ formatDate(row.addtime) }}
              </template>
              <template #cell-action="{ row }">
                <CommonButton
                  :text="$t('page.insert_template')"
                  type="link"
                  :bold="false"
                  @click="handleInsertTemplate(row)"
                />
                <CommonButton
                  :text="`${$t('page.share_to_items')} (${row.share_item_count || 0})`"
                  type="link"
                  :bold="false"
                  @click="handleShareToItem(row)"
                />
                <CommonButton
                  :text="$t('common.delete')"
                  type="link"
                  danger
                  :bold="false"
                  @click="handleDeleteTemplate(row)"
                />
              </template>
            </CommonTable>
          </template>

          <!-- 项目模板 -->
          <template #item>
            <CommonTable
              :tableHeader="itemColumns"
              :tableData="itemTemplates"
              :loading="itemLoading"
              :maxHeight="'400px'"
              :emptyText="() => $t('page.no_item_template_text')"
            >
              <template #cell-created_at="{ row }">
                {{ formatDate(row.created_at) }}
              </template>
              <template #cell-action="{ row }">
                <CommonButton
                  :text="$t('page.insert_template')"
                  type="link"
                  :bold="false"
                  @click="handleInsertTemplate(row)"
                />
              </template>
            </CommonTable>
          </template>
        </CommonTab>
      </a-spin>
    </div>

    <template #footer>
      <CommonButton
        :text="$t('common.close')"
        @click="handleClose"
      />
    </template>
  </CommonModal>

  <!-- 共享到项目对话框 -->
  <CommonModal
    v-if="showShareModal"
    :title="$t('page.share_template_to_items')"
    width="500px"
    @close="showShareModal = false"
  >
    <div class="share-form">
      <label class="form-label">{{ $t('page.select_items_to_share') }}</label>
      <a-select
        v-model:value="shareItemIds"
        mode="multiple"
        :placeholder="$t('common.please_choose')"
        :options="itemOptions"
        :filter-option="filterOption"
        show-search
        style="width: 100%"
      />
      <div class="tips-text">{{ $t('page.share_items_tips') }}</div>
    </div>

    <template #footer>
      <CommonButton
        :text="$t('common.cancel')"
        @click="showShareModal = false"
      />
      <CommonButton
        :text="$t('common.confirm')"
        theme="dark"
        :spinning="sharing"
        @click="handleShareToItemConfirm"
      />
    </template>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import request from '@/utils/request'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonTable from '@/components/CommonTable.vue'
import CommonTab from '@/components/CommonTab.vue'
import ConfirmModal from '@/components/ConfirmModal/index'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'
import dayjs from 'dayjs'

// Props
interface Props {
  itemId: number
  onClose: () => void
  onInsert: (content: string) => void
}

const props = defineProps<Props>()

// Composables
const { t } = useI18n()

// Refs
const show = ref(false)
const loading = ref(false)
const myLoading = ref(false)
const itemLoading = ref(false)
const activeTab = ref('my')
const myTemplates = ref<any[]>([])
const itemTemplates = ref<any[]>([])
const showShareModal = ref(false)
const sharing = ref(false)
const shareItemIds = ref<number[]>([])
const currentTemplate = ref<any>(null)
const itemOptions = ref<{ label: string; value: number }[]>([])

// Tab items
const tabItems = computed(() => [
  {
    text: t('page.my_template'),
    value: 'my'
  },
  {
    text: t('page.item_template'),
    value: 'item'
  }
])

// Table columns
const myColumns = computed(() => [
  {
    title: t('page.save_time'),
    key: 'addtime',
    width: 170
  },
  {
    title: t('page.template_title'),
    key: 'template_title'
  },
  {
    title: t('common.operation'),
    key: 'action',
    width: 300
  }
])

const itemColumns = computed(() => [
  {
    title: t('page.save_time'),
    key: 'created_at',
    width: 170
  },
  {
    title: t('page.sharer'),
    key: 'username',
    width: 120
  },
  {
    title: t('page.template_title'),
    key: 'template_title'
  },
  {
    title: t('common.operation'),
    key: 'action',
    width: 150
  }
])

// Methods
const formatDate = (date: string) => {
  return dayjs(date).format('YYYY-MM-DD HH:mm:ss')
}

const filterOption = (input: string, option: { label: string; value: number }) => {
  return option.label.toLowerCase().includes(input.toLowerCase())
}

const loadMyTemplates = async () => {
  myLoading.value = true
  try {
    const data = await request('/api/template/getMyList', {}, 'post', false)
    if (data.error_code === 0 && data.data) {
      myTemplates.value = data.data
    }
  } catch (error) {
    console.error('获取我的模板失败:', error)
    await AlertModal(t('common.fetch_failed'))
  } finally {
    myLoading.value = false
  }
}

const loadItemTemplates = async () => {
  itemLoading.value = true
  try {
    const data = await request('/api/template/getItemList', {
      item_id: props.itemId
    }, 'post', false)
    if (data.error_code === 0 && data.data) {
      itemTemplates.value = data.data
    }
  } catch (error) {
    console.error('获取项目模板失败:', error)
    await AlertModal(t('common.fetch_failed'))
  } finally {
    itemLoading.value = false
  }
}

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

const handleTabChange = (value: string) => {
  activeTab.value = value
}

const handleInsertTemplate = (template: any) => {
  props.onInsert(template.template_content)
  handleClose()
}

const handleDeleteTemplate = async (template: any) => {
  // 显示确认对话框
  const confirmed = await ConfirmModal({
    msg: `${t('common.confirm_delete')}「${template.template_title}」?`,
    title: t('common.tips'),
    confirmText: t('common.confirm'),
    cancelText: t('common.cancel')
  })

  // 如果用户取消，则不执行删除
  if (!confirmed) {
    return
  }

  try {
    const data = await request('/api/template/delete', {
      id: template.id
    }, 'post', false)
    if (data.error_code === 0) {
      Message.success(t('common.delete_success'))
      loadMyTemplates()
      loadItemTemplates()
    } else {
      await AlertModal(data.error_message || t('common.delete_failed'))
    }
  } catch (error) {
    console.error('删除模板失败:', error)
    await AlertModal(t('common.delete_failed'))
  }
}

const handleShareToItem = (template: any) => {
  currentTemplate.value = template
  shareItemIds.value = []

  // 如果之前已经选择了项目，则填上默认值
  if (template.share_item_count > 0 && template.share_item) {
    shareItemIds.value = template.share_item.map((item: any) => item.item_id)
  }

  loadMyItems()
  showShareModal.value = true
}

const handleShareToItemConfirm = async () => {
  if (!currentTemplate.value) return

  sharing.value = true
  try {
    const data = await request('/api/template/shareToItem', {
      template_id: currentTemplate.value.id,
      item_id: shareItemIds.value.join(',')
    }, 'post', false)
    if (data.error_code === 0) {
      Message.success(t('common.save_success'))
      showShareModal.value = false
      loadMyTemplates()
      loadItemTemplates()
    } else {
      await AlertModal(data.error_message || t('common.save_failed'))
    }
  } catch (error) {
    console.error('共享模板失败:', error)
    await AlertModal(t('common.save_failed'))
  } finally {
    sharing.value = false
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
  loadMyTemplates()
  loadItemTemplates()
})
</script>

<style lang="scss" scoped>
.template-select {
  min-height: 400px;
}

.share-form {
  padding: 16px 0;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 500;
  color: var(--color-text-primary);
}

.tips-text {
  margin-top: 12px;
  font-size: 12px;
  color: var(--color-text-secondary);
  line-height: 1.6;
}

// CommonTab 内容区样式
:deep(.common-tab) {
  .tab-content-wrapper {
    margin-top: 16px;
  }
}
</style>
