<template>
  <div class="attachment-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('attachment.my_attachment')"
      :width="'1100px'"
      @close="closeHandle(false)"
    >
      <!-- 搜索和操作栏 -->
      <div class="search-bar">
        <div class="search-item">
          <CommonInput
            v-model="displayName"
            :placeholder="$t('attachment.display_name')"
            @keyup.enter="handleSearch"
          />
        </div>
        <div class="search-item">
          <CommonSelector
            v-model:value="attachmentType"
            :options="attachmentTypeOptions"
            style="width: 150px"
            @change="handleSearch"
          />
        </div>
        <div class="search-item">
          <CommonButton @click="handleSearch">{{ $t('common.search') }}</CommonButton>
        </div>
        <div class="search-item">
          <CommonButton type="primary" @click="showUpload = true">
            {{ $t('common.upload') }}
          </CommonButton>
        </div>
      </div>

      <!-- 空间和流量信息 -->
      <div class="info-bar">
        <span>
          {{ $t('attachment.accumulated_used_space') }} {{ used }}M ,
          {{ $t('attachment.month_flow') }} {{ usedFlow }}M
        </span>
      </div>

      <!-- 附件列表 -->
      <CommonTable
        :tableHeader="tableHeader"
        :tableData="tableData"
        :pagination="pagination"
        :loading="loading"
        :maxHeight="'400px'"
        @pageChange="handlePageChange"
      >
        <!-- 操作列插槽 -->
        <template #cell-tools="{ row }">
          <div class="tools">
            <span class="tools-item clickable" @click="handleVisit(row)">
              {{ $t('attachment.visit') }}
            </span>
            <span class="tools-item clickable" @click="handleCopyLink(row)">
              {{ $t('attachment.copy_link') }}
            </span>
            <span class="tools-item clickable" @click="handleDelete(row)">
              {{ $t('common.delete') }}
            </span>
          </div>
        </template>
      </CommonTable>
    </CommonModal>

    <!-- 上传弹窗 -->
    <UploadModal
      v-if="showUpload"
      :callback="() => { showUpload = false; fetchList() }"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonInput from '@/components/CommonInput.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonSelector from '@/components/CommonSelector.vue'
import CommonTable from '@/components/CommonTable.vue'
import UploadModal from '../UploadModal/index'
import {
  getMyAttachmentList,
  deleteMyAttachment,
  type AttachmentItem
} from '@/models/attachment'

const { t } = useI18n()

const props = defineProps<{
  itemId?: number
  pageId?: number
  manage?: boolean
  onClose: (result: boolean) => void
}>()

const show = ref(false)
const loading = ref(false)
const displayName = ref('')
const attachmentType = ref('-1')
const attachmentTypeOptions = computed(() => [
  { label: t('attachment.all_attachment_type'), value: '-1' },
  { label: t('attachment.image'), value: '1' },
  { label: t('attachment.general_attachment'), value: '2' }
])
const used = ref(0)
const usedFlow = ref(0)
const showUpload = ref(false)

// 表格数据
const tableData = ref<AttachmentItem[]>([])
const pagination = ref({
  current: 1,
  pageSize: 10,
  total: 0
})

// 表格表头
const tableHeader = computed(() => [
  {
    title: t('attachment.file_id'),
    key: 'file_id',
    width: 60
  },
  {
    title: t('attachment.display_name'),
    key: 'display_name',
    width: 180
  },
  {
    title: t('attachment.file_type'),
    key: 'file_type',
    width: 80
  },
  {
    title: t('attachment.file_size_m'),
    key: 'file_size_m',
    width: 70
  },
  {
    title: t('attachment.visit_times'),
    key: 'visit_times',
    width: 70
  },
  {
    title: t('attachment.add_time'),
    key: 'addtime',
    width: 120
  },
  {
    title: t('common.operation'),
    key: 'tools',
    width: 180,
    tools: true
  }
])

// 获取附件列表
const fetchList = async () => {
  loading.value = true
  try {
    const res = await getMyAttachmentList({
      page: pagination.value.current,
      count: pagination.value.pageSize,
      attachment_type: attachmentType.value,
      display_name: displayName.value
    })

    if (res.data) {
      tableData.value = res.data.list || []
      pagination.value.total = res.data.total || 0
      used.value = res.data.used_m || 0
      usedFlow.value = res.data.used_flow_m || 0
    }
  } catch (error) {
    console.error('Failed to fetch attachment list:', error)
  } finally {
    loading.value = false
  }
}

// 搜索
const handleSearch = () => {
  pagination.value.current = 1
  fetchList()
}

// 分页改变
const handlePageChange = (page: number) => {
  pagination.value.current = page
  fetchList()
}

// 访问文件
const handleVisit = (row: AttachmentItem) => {
  if (row.url) {
    window.open(row.url, '_blank')
  }
}

// 复制链接
const handleCopyLink = (row: AttachmentItem) => {
  if (row.url) {
    navigator.clipboard
      .writeText(row.url)
      .then(() => {
        message.success(t('common.copy_success'))
      })
      .catch(() => {
        message.error(t('common.copy_failed'))
      })
  }
}

// 删除文件
const handleDelete = async (row: AttachmentItem) => {
  try {
    await new Promise((resolve, reject) => {
      import('@/components/ConfirmModal').then(({ default: confirmModal }) => {
        confirmModal({
          msg: t('attachment.confirm_delete'),
          title: t('common.tips')
        }).then(resolve).catch(reject)
      })
    })

    await deleteMyAttachment({ file_id: row.file_id })
    message.success(t('common.op_success'))
    fetchList()
  } catch (error) {
    // 用户取消删除
  }
}

const closeHandle = (result: boolean) => {
  show.value = false
  setTimeout(() => {
    props.onClose(result)
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
.attachment-modal :deep(.modal-content) {
  padding: 20px;
}

.search-bar {
  display: flex;
  gap: 10px;
  margin-bottom: 15px;
  flex-wrap: wrap;

  .search-item {
    display: flex;
    align-items: center;
  }
}

.info-bar {
  margin-bottom: 15px;
  padding: 10px;
  background-color: var(--color-bg-secondary);
  border-radius: 4px;
  color: var(--color-text-secondary);
  font-size: var(--font-size-s);
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
</style>

