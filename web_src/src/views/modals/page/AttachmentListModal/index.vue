<template>
  <div class="page-attachment-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('page.attachments')"
      :width="'900px'"
      @close="closeHandle(false)"
    >
      <!-- 操作栏 -->
      <div class="action-bar" v-if="manage">
        <CommonButton @click="showFilehub = true">
          <i class="fas fa-folder-open"></i>
          {{ $t('page.from_filehub') }}
        </CommonButton>
        <CommonButton type="primary" @click="showUpload = true">
          <i class="fas fa-cloud-upload-alt"></i>
          {{ $t('common.upload') }}
        </CommonButton>
        <small class="tips">{{ $t('page.file_size_tips') }}</small>
      </div>

      <!-- 附件列表 -->
      <CommonTable
        :tableHeader="tableHeader"
        :tableData="tableData"
        :loading="loading"
        :maxHeight="'400px'"
        :pagination="null"
      >
        <!-- 操作列插槽 -->
        <template #cell-tools="{ row }">
          <div class="tools">
            <span class="tools-item clickable" @click="handleDownload(row)">
              {{ $t('common.download') }}
            </span>
            <span
              v-if="manage"
              class="tools-item clickable"
              @click="handleInsert(row)"
            >
              {{ $t('page.insert') }}
            </span>
            <span
              v-if="manage"
              class="tools-item clickable"
              @click="handleDelete(row)"
            >
              {{ $t('common.delete') }}
            </span>
          </div>
        </template>
      </CommonTable>

      <!-- 空状态 -->
      <div v-if="!loading && tableData.length === 0" class="empty-state">
        <i class="fas fa-paperclip"></i>
        <p>{{ $t('page.no_attachments') }}</p>
      </div>
    </CommonModal>

    <!-- 从文件库导入 -->
    <FilehubModal
      v-if="showFilehub"
      :itemId="itemId"
      :pageId="pageId"
      :callback="handleFilehubCallback"
    />

    <!-- 上传文件 -->
    <UploadModal
      v-if="showUpload"
      :itemId="itemId"
      :pageId="pageId"
      :callback="handleUploadCallback"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonTable from '@/components/CommonTable.vue'
import FilehubModal from '../FilehubModal/index'
import UploadModal from '../UploadModal/index'
import ConfirmModal from '@/components/ConfirmModal/index'
import request from '@/utils/request'

const { t } = useI18n()

const props = defineProps<{
  itemId: number
  pageId: number
  manage?: boolean
  onClose: () => void
  onInsert?: (markdown: string) => void
}>()

const show = ref(false)
const loading = ref(false)
const showFilehub = ref(false)
const showUpload = ref(false)
const tableData = ref<any[]>([])

// 表格表头
const tableHeader = computed(() => [
  {
    title: t('page.add_time'),
    key: 'addtime',
    width: 170
  },
  {
    title: t('page.file_name'),
    key: 'display_name',
    minWidth: 200
  },
  {
    title: t('common.operation'),
    key: 'tools',
    width: 180,
    tools: true
  }
])

// 获取页面附件列表
const fetchList = async () => {
  if (!props.pageId) return

  loading.value = true
  try {
    const data = await request('/api/page/uploadList', {
      page_id: props.pageId
    }, 'post', false)

    if (data.error_code === 0 && data.data) {
      tableData.value = data.data || []
    }
  } catch (error) {
    console.error('获取页面附件列表失败:', error)
    message.error(t('page.fetch_attachments_failed'))
  } finally {
    loading.value = false
  }
}

// 下载文件
const handleDownload = (row: any) => {
  if (row.url) {
    window.open(row.url, '_blank')
  }
}

// 插入文件到编辑器
const handleInsert = (row: any) => {
  const markdown = `[${row.display_name}](${row.url} "${row.display_name}")`
  if (props.onInsert) {
    props.onInsert(markdown)
    message.success(t('page.insert_success'))
  }
}

// 删除文件
const handleDelete = async (row: any) => {
  try {
    await ConfirmModal({
      msg: t('common.confirm_delete'),
      title: t('common.tips')
    })

    const data = await request('/api/page/deleteUploadFile', {
      file_id: row.file_id,
      page_id: props.pageId
    }, 'post', false)

    if (data.error_code === 0) {
      message.success(t('common.delete_success'))
      fetchList()
    } else {
      message.error(data.error_message || t('common.delete_failed'))
    }
  } catch (error) {
    // 用户取消
  }
}

// 文件库回调
const handleFilehubCallback = () => {
  showFilehub.value = false
  fetchList()
}

// 上传回调
const handleUploadCallback = () => {
  showUpload.value = false
  fetchList()
}

const closeHandle = (result: boolean) => {
  show.value = false
  setTimeout(() => {
    props.onClose()
  }, 300)
}

onMounted(() => {
  setTimeout(() => {
    show.value = true
  })
  fetchList()
})
</script>

<style scoped lang="scss">
.page-attachment-modal :deep(.modal-content) {
  padding: 20px;
}

.action-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
  flex-wrap: wrap;

  .tips {
    color: var(--color-text-secondary);
    font-size: 12px;
    margin-left: auto;
  }
}

.tools {
  display: flex;
  justify-content: center;
  gap: 10px;
}

.tools-item {
  color: var(--color-active);
  cursor: pointer;
  transition: color 0.15s ease;

  &:hover {
    color: var(--color-active);
  }
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  color: var(--color-text-secondary);

  i {
    font-size: 48px;
    margin-bottom: 10px;
    opacity: 0.5;
  }

  p {
    margin: 0;
    font-size: 14px;
  }
}
</style>

