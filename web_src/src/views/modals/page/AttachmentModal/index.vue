<template>
  <CommonModal :show="show" :title="$t('page.attachment')" width="700px" @close="handleClose">
    <div class="attachment-modal">
      <!-- 上传按钮 -->
      <div class="upload-actions" v-if="manage">
        <a-button type="primary" @click="handleUpload">
          <i class="fas fa-upload"></i>
          {{ $t('page.upload') }}
        </a-button>
        <a-button @click="handleFromFileHub">
          <i class="fas fa-folder-open"></i>
          {{ $t('page.from_file_hub') }}
        </a-button>
      </div>

      <!-- 附件列表 -->
      <a-table
        :data-source="attachmentList"
        :columns="columns"
        :pagination="false"
        :loading="loading"
        row-key="file_id"
        size="small"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'addtime'">
            {{ record.addtime }}
          </template>
          <template v-else-if="column.key === 'display_name'">
            <a-tooltip :title="record.display_name">
              <span class="file-name">{{ record.display_name }}</span>
            </a-tooltip>
          </template>
          <template v-else-if="column.key === 'action'">
            <a-button type="link" size="small" @click="handleDownload(record)">
              {{ $t('common.download') }}
            </a-button>
            <a-button type="link" size="small" v-if="manage" @click="handleDelete(record)">
              {{ $t('common.delete') }}
            </a-button>
          </template>
        </template>
      </a-table>

      <!-- 空状态 -->
      <a-empty
        v-if="!loading && attachmentList.length === 0"
        :description="$t('page.no_attachment')"
        :image="simpleImage"
      />
    </div>

    <template #footer>
      <CommonButton @click="handleClose">{{ $t('common.close') }}</CommonButton>
    </template>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { Empty } from 'ant-design-vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import request from '@/utils/request'
import ConfirmModal from '@/components/ConfirmModal'
import Message from '@/components/Message'

const simpleImage = Empty.PRESENTED_IMAGE_SIMPLE

// Props
interface Props {
  itemId?: number
  pageId?: number
  manage?: boolean
  onClose?: () => void
}

const props = withDefaults(defineProps<Props>(), {
  manage: true,
  onClose: () => {}
})

// Refs
const show = ref(true)
const attachmentList = ref<any[]>([])
const loading = ref(false)

// Computed
const columns = computed(() => [
  {
    title: props.$t?.('page.add_time') || '上传时间',
    key: 'addtime',
    width: 170
  },
  {
    title: props.$t?.('page.file_name') || '文件名',
    key: 'display_name',
    ellipsis: true
  },
  {
    title: props.$t?.('common.operation') || '操作',
    key: 'action',
    width: 150
  }
])

// Methods
const loadAttachmentList = async () => {
  if (!props.pageId) return

  loading.value = true
  try {
    const data = await request('/api/page/uploadList', {
      page_id: props.pageId
    })
    attachmentList.value = data.data || []
  } catch (error) {
    console.error('加载附件列表失败:', error)
  } finally {
    loading.value = false
  }
}

const handleDownload = (record: any) => {
  if (record.url) {
    window.open(record.url, '_blank')
  }
}

const handleDelete = async (record: any) => {
  const confirmed = await ConfirmModal({
    title: props.$t?.('common.confirm_delete') || '确认删除',
    content: props.$t?.('page.confirm_delete_attachment') || '确定要删除这个附件吗？'
  })

  if (!confirmed) return

  try {
    await request('/api/page/deleteUploadFile', {
      file_id: record.file_id,
      page_id: props.pageId
    })
    Message.success(props.$t?.('common.delete_success') || '删除成功')
    await loadAttachmentList()
  } catch (error) {
    console.error('删除附件失败:', error)
  }
}

const handleUpload = () => {
  // TODO: 实现上传功能
  Message.info(props.$t?.('common.coming_soon') || '功能开发中...')
}

const handleFromFileHub = () => {
  // TODO: 实现从文件中心选择
  Message.info(props.$t?.('common.coming_soon') || '功能开发中...')
}

const handleClose = () => {
  show.value = false
  props.onClose()
}

// Lifecycle
onMounted(() => {
  loadAttachmentList()
})
</script>

<style lang="scss" scoped>
.attachment-modal {
  min-height: 300px;
}

.upload-actions {
  margin-bottom: 16px;
  display: flex;
  gap: 8px;
}

.file-name {
  display: inline-block;
  max-width: 300px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

:deep(.ant-table) {
  .ant-table-tbody > tr > td {
    padding: 8px 12px;
  }

  .ant-table-tbody > tr:hover > td {
    background-color: rgba(64, 158, 255, 0.05);
  }
}
</style>

