<template>
  <div class="filehub-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('page.filehub')"
      :width="'900px'"
      @close="closeHandle"
    >
      <!-- 顶部工具栏 -->
      <div class="modal-toolbar">
        <CommonTab
          class="toolbar-tabs"
          :items="filterTabs"
          :value="attachmentType"
          type="segmented"
          @updateValue="handleFilterChange"
        />
        <div class="toolbar-actions">
          <CommonInput
            v-model="displayName"
            :placeholder="$t('attachment.display_name')"
            @keyup.enter="handleSearch"
            style="width: 180px"
          />
          <CommonButton @click="handleSearch">{{ $t('common.search') }}</CommonButton>
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
            <span class="tools-item clickable" @click="handleSelect(row)">
              {{ $t('page.select') }}
            </span>
            <span class="tools-item clickable" @click="handleVisit(row)">
              {{ $t('attachment.visit') }}
            </span>
            <span class="tools-item clickable" @click="handleDelete(row)">
              {{ $t('common.delete') }}
            </span>
          </div>
        </template>
      </CommonTable>
    </CommonModal>

    <!-- 上传弹窗 -->
    <FilehubUploadModal
      v-if="showUpload"
      :callback="handleUploadCallback"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonInput from '@/components/CommonInput.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonTab from '@/components/CommonTab.vue'
import CommonTable from '@/components/CommonTable.vue'
import FilehubUploadModal from '../FilehubUploadModal/index'
import ConfirmModal from '@/components/ConfirmModal/index'
import request from '@/utils/request'

const { t } = useI18n()

const props = defineProps<{
  itemId: number
  pageId: number
  callback: () => void
}>()

const show = ref(false)
const loading = ref(false)
const displayName = ref('')
const attachmentType = ref('-1')
const filterTabs = computed(() => [
  { text: t('attachment.all_attachment_type'), value: '-1' },
  { text: t('attachment.general_attachment'), value: '2' },
  { text: t('attachment.image'), value: '1' }
])
const used = ref(0)
const usedFlow = ref(0)
const showUpload = ref(false)

// 表格数据
const tableData = ref<any[]>([])
const pagination = ref({
  current: 1,
  pageSize: 5,
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
    width: 120
  },
  {
    title: t('attachment.file_size_m'),
    key: 'file_size_m',
    width: 80
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
    width: 150,
    tools: true
  }
])

// 获取文件库列表
const fetchList = async () => {
  loading.value = true
  try {
    const data = await request('/api/attachment/getMyList', {
      page: pagination.value.current,
      count: pagination.value.pageSize,
      attachment_type: attachmentType.value,
      display_name: displayName.value
    }, 'post', false)

    if (data.error_code === 0 && data.data) {
      tableData.value = data.data.list || []
      pagination.value.total = data.data.total || 0
      used.value = data.data.used_m || 0
      usedFlow.value = data.data.used_flow_m || 0
    }
  } catch (error) {
    console.error('获取文件库列表失败:', error)
    message.error(t('attachment.fetch_list_failed'))
  } finally {
    loading.value = false
  }
}

// 筛选 Tab 切换
const handleFilterChange = (value: string | number) => {
  attachmentType.value = String(value)
  handleSearch()
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

// 选择文件（绑定到页面）
const handleSelect = async (row: any) => {
  try {
    const data = await request('/api/attachment/bindingPage', {
      file_id: row.file_id,
      page_id: props.pageId
    }, 'post', false)

    if (data.error_code === 0) {
      message.success(t('page.bind_success'))
      closeHandle()
    } else {
      message.error(data.error_message || t('page.bind_failed'))
    }
  } catch (error) {
    console.error('绑定文件失败:', error)
    message.error(t('page.bind_failed'))
  }
}

// 访问文件
const handleVisit = (row: any) => {
  if (row.url) {
    window.open(row.url, '_blank')
  }
}

// 删除文件
const handleDelete = async (row: any) => {
  try {
    await ConfirmModal({
      msg: t('attachment.confirm_delete'),
      title: t('common.tips')
    })

    const data = await request('/api/attachment/deleteMyAttachment', {
      file_id: row.file_id
    }, 'post', false)

    if (data.error_code === 0) {
      message.success(t('common.op_success'))
      fetchList()
    } else {
      message.error(data.error_message || t('common.op_failed'))
    }
  } catch (error) {
    // 用户取消
  }
}

// 上传回调
const handleUploadCallback = () => {
  showUpload.value = false
  fetchList()
}

const closeHandle = () => {
  show.value = false
  setTimeout(() => {
    props.callback()
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
.filehub-modal :deep(.modal-content) {
  padding: 0;
}

.modal-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px 12px;
  border-bottom: 1px solid var(--color-border, #eee);

  .toolbar-tabs {
    width: auto;
    min-width: 240px;
  }

  .toolbar-actions {
    display: flex;
    align-items: center;
    gap: 8px;
  }
}

.info-bar {
  margin: 0;
  padding: 8px 20px;
  background-color: var(--color-bg-secondary);
  color: var(--color-text-secondary);
  font-size: var(--font-size-s);
}

:deep(.common-table-wrapper) {
  padding: 0 20px 20px;
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

