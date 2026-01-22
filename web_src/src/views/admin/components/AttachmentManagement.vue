<template>
  <div class="attachment-management">
    <!-- 搜索表单 -->
    <div class="search-section">
      <div class="search-row">
        <div class="search-input">
          <CommonInput
            v-model="displayName"
            :placeholder="$t('admin.display_name_placeholder')"
          />
        </div>
        <div class="search-selector">
          <CommonSelector
            :selector-label="attachmentTypeLabel"
            :selector-value="attachmentType"
            :options="attachmentTypeOptions"
            @update:selector-label="attachmentTypeLabel = $event"
            @update:selector-value="attachmentType = $event"
          />
        </div>
        <div class="search-input">
          <CommonInput
            v-model="username"
            :placeholder="$t('admin.uploader')"
          />
        </div>
        <CommonButton
          theme="dark"
          :text="$t('common.search')"
          :leftIcon="['fas', 'search']"
          @click="handleSearch"
        />
        <CommonButton
          theme="light"
          :text="$t('admin.cleanup_unused_attachments')"
          :leftIcon="['fas', 'broom']"
          @click="handleCleanup"
        />
      </div>
    </div>

    <!-- 清理未使用附件弹窗 -->
    <CommonModal :show="cleanupModalVisible" :title="$t('admin.cleanup_dialog_title')" width="80%" @close="handleCloseCleanupModal">
      <div class="cleanup-modal-body">
        <div class="cleanup-search">
          <CommonInput
            v-model="cleanupSearch.displayName"
            :placeholder="$t('admin.display_name_placeholder')"
          />
          <CommonInput
            v-model="cleanupSearch.username"
            :placeholder="$t('admin.uploader')"
          />
          <CommonButton
            theme="dark"
            :text="$t('common.search')"
            :leftIcon="['fas', 'search']"
            @click="handleCleanupSearch"
          />
        </div>
        <CommonTable
          :table-header="cleanupTableHeader"
          :table-data="cleanupList"
          :row-selection="cleanupRowSelection"
          :pagination="cleanupPagination"
          :loading="cleanupLoading"
          row-key="file_id"
          max-height="500px"
          @page-change="handleCleanupPageChange"
          @selection-change="handleCleanupSelectionChange"
        >
          <!-- 操作列 -->
          <template #cell-action="{ row }">
            <div class="table-action-buttons">
              <span class="table-action-btn visit" @click="handleVisit(row)">
                <i class="fas fa-eye"></i>
                {{ $t('admin.visit') }}
              </span>
            </div>
          </template>
        </CommonTable>
      </div>
      <template #footer>
        <div class="modal-footer">
          <CommonButton
            theme="light"
            :text="$t('common.cancel')"
            @click="handleCloseCleanupModal"
          />
          <CommonButton
            theme="dark"
            :text="$t('admin.delete_selected')"
            :disabled="cleanupSelectedKeys.length === 0"
            @click="handleBatchDeleteUnused"
          />
        </div>
      </template>
    </CommonModal>

    <!-- 统计信息 -->
    <div class="stats-section">
      <div class="stats-card">
        <div class="stats-icon">
          <i class="fas fa-hdd"></i>
        </div>
        <div class="stats-content">
          <div class="stats-label">{{ $t('admin.accumulated_used_space') }}</div>
          <div class="stats-value">{{ usedSpace }}M</div>
        </div>
      </div>
    </div>

    <!-- 附件列表表格 -->
    <div class="table-section">
      <CommonTable
        :table-header="tableHeader"
        :table-data="attachmentList"
        :row-selection="rowSelection"
        :pagination="pagination"
        :loading="loading"
        row-key="file_id"
        max-height="calc(100vh - 380px)"
        @page-change="handleTableChange"
        @selection-change="handleSelectionChange"
      >
        <!-- 操作列 -->
        <template #cell-action="{ row }">
          <div class="table-action-buttons">
            <span class="table-action-btn visit" @click="handleVisit(row)">
              <i class="fas fa-eye"></i>
              {{ $t('admin.visit') }}
            </span>
            <span class="table-action-btn delete" @click="handleDelete(row)">
              <i class="fas fa-trash-alt"></i>
              {{ $t('common.delete') }}
            </span>
          </div>
        </template>
      </CommonTable>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import ConfirmModal from '@/components/ConfirmModal'
import CommonInput from '@/components/CommonInput.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonSelector from '@/components/CommonSelector.vue'
import CommonTable from '@/components/CommonTable.vue'
import CommonModal from '@/components/CommonModal.vue'
import { deleteAttachment, getAllAttachments, getUnusedAttachments, batchDeleteAttachments } from '@/models/admin'

const { t } = useI18n()

// 数据状态
const attachmentList = ref<any[]>([])
const loading = ref(false)
const selectedRowKeys = ref<number[]>([])
const displayName = ref('')
const username = ref('')
const attachmentType = ref('-1')
const usedSpace = ref(0)

// 清理未使用附件相关
const cleanupModalVisible = ref(false)
const cleanupList = ref<any[]>([])
const cleanupLoading = ref(false)
const cleanupSelectedKeys = ref<number[]>([])
const cleanupSearch = reactive({
  displayName: '',
  username: ''
})
const cleanupPagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0
})

// 搜索器标签
const attachmentTypeLabel = ref(t('admin.all_attachment_type'))

// 分页配置
const pagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0
})

// 搜索器选项
const attachmentTypeOptions = computed(() => [
  { label: t('admin.all_attachment_type'), value: '-1' },
  { label: t('admin.image'), value: '1' },
  { label: t('admin.general_attachment'), value: '2' }
])

// 表格头部配置
const tableHeader = computed(() => [
  { title: t('admin.file_id'), key: 'file_id', width: 100 },
  { title: t('admin.display_name'), key: 'display_name', width: 200 },
  { title: t('admin.file_type'), key: 'file_type', width: 140 },
  { title: t('admin.file_size'), key: 'file_size_m', width: 120 },
  { title: t('admin.visit_times'), key: 'visit_times', width: 100, center: true },
  { title: t('admin.uploader'), key: 'username', width: 140 },
  { title: t('admin.add_time'), key: 'addtime', width: 160 },
  { title: t('common.operation'), key: 'action', width: 240, center: true }
])

// 清理弹窗表格头部
const cleanupTableHeader = computed(() => [
  { title: t('admin.file_id'), key: 'file_id', width: 100 },
  { title: t('admin.display_name'), key: 'display_name', width: 200 },
  { title: t('admin.file_type'), key: 'file_type', width: 140 },
  { title: t('admin.file_size'), key: 'file_size_m', width: 140 },
  { title: t('admin.uploader'), key: 'username', width: 140 },
  { title: t('admin.add_time'), key: 'addtime', width: 160 },
  { title: t('common.operation'), key: 'action', width: 120, center: true }
])

// 行选择配置
const rowSelection = computed(() => ({
  selectedRowKeys: selectedRowKeys.value,
  onChange: (keys: number[]) => {
    selectedRowKeys.value = keys
  }
}))

// 清理弹窗行选择配置
const cleanupRowSelection = computed(() => ({
  selectedRowKeys: cleanupSelectedKeys.value,
  onChange: (keys: number[]) => {
    cleanupSelectedKeys.value = keys
  }
}))

// 方法
const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getAllAttachments({
      page: pagination.current,
      count: pagination.pageSize,
      attachment_type: attachmentType.value,
      display_name: displayName.value,
      username: username.value
    })
    attachmentList.value = res.data.list || []
    pagination.total = parseInt(res.data.total) || 0
    usedSpace.value = res.data.used_m || 0
  } catch (error) {
    console.error('获取附件列表失败:', error)
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  pagination.current = 1
  fetchList()
}

const handleTableChange = (page: number, pageSize: number) => {
  pagination.current = page
  pagination.pageSize = pageSize
  fetchList()
}

const handleSelectionChange = (keys: number[], rows: any[]) => {
  selectedRowKeys.value = keys
}

const handleVisit = (record: any) => {
  window.open(record.url)
}

const handleDelete = async (record: any) => {
  const confirmed = await ConfirmModal(t('common.confirm_delete'))
  if (confirmed) {
    try {
      await deleteAttachment({ file_id: record.file_id })
      message.success(t('common.op_success'))
      fetchList()
    } catch (error) {
      message.error(t('common.op_failed'))
    }
  }
}

// 清理未使用附件相关方法
const handleCleanup = () => {
  cleanupModalVisible.value = true
  cleanupPagination.current = 1
  fetchCleanupList()
}

const handleCloseCleanupModal = () => {
  cleanupModalVisible.value = false
  cleanupSelectedKeys.value = []
  cleanupSearch.displayName = ''
  cleanupSearch.username = ''
}

const fetchCleanupList = async () => {
  cleanupLoading.value = true
  try {
    const res: any = await getUnusedAttachments({
      page: cleanupPagination.current,
      count: cleanupPagination.pageSize,
      display_name: cleanupSearch.displayName,
      username: cleanupSearch.username
    })
    cleanupList.value = res.data.list || []
    cleanupPagination.total = parseInt(res.data.total) || 0
  } catch (error) {
    console.error('获取未使用附件列表失败:', error)
  } finally {
    cleanupLoading.value = false
  }
}

const handleCleanupSearch = () => {
  cleanupPagination.current = 1
  fetchCleanupList()
}

const handleCleanupPageChange = (page: number, pageSize: number) => {
  cleanupPagination.current = page
  cleanupPagination.pageSize = pageSize
  fetchCleanupList()
}

const handleCleanupSelectionChange = (keys: number[], rows: any[]) => {
  cleanupSelectedKeys.value = keys
}

const handleBatchDeleteUnused = async () => {
  if (cleanupSelectedKeys.value.length === 0) {
    message.warning(t('admin.please_select_to_delete'))
    return
  }
  const confirmed = await ConfirmModal({
    title: t('admin.confirm_delete_selected'),
    msg: t('admin.confirm_delete_selected_message')
  })
  if (confirmed) {
    try {
      await batchDeleteAttachments({
        file_ids: cleanupSelectedKeys.value.join(',')
      })
      message.success(t('common.op_success'))
      fetchCleanupList()
      fetchList()
      cleanupSelectedKeys.value = []
    } catch (error) {
      message.error(t('common.op_failed'))
    }
  }
}

onMounted(() => {
  fetchList()
})
</script>

<style lang="scss" scoped>
.attachment-management {
  .search-section {
    background: var(--color-obvious);
    padding: 20px;
    border-radius: 8px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 20px;

    .search-row {
      display: flex;
      gap: 12px;
      align-items: center;
      flex-wrap: wrap;

      .search-input {
        flex: 1;
        max-width: 220px;
      }

      .search-selector {
        width: 180px;
      }
    }
  }

  .stats-section {
    background: var(--color-obvious);
    padding: 20px;
    border-radius: 8px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 20px;

    .stats-card {
      display: flex;
      align-items: center;
      gap: 16px;

      .stats-icon {
        width: 60px;
        height: 60px;
        background: var(--color-active);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: var(--color-obvious);
      }

      .stats-content {
        flex: 1;

        .stats-label {
          font-size: 14px;
          color: var(--color-primary);
          font-weight: 500;
          margin-bottom: 4px;
        }

        .stats-value {
          font-size: 28px;
          font-weight: 700;
          color: var(--color-active);
        }
      }
    }
  }

  .table-section {
    background: var(--color-obvious);
    padding: 20px;
    border-radius: 8px;
    box-shadow: var(--shadow-sm);
  }

  .modal-form {
    .form-label {
      display: block;
      margin-bottom: 8px;
      font-size: 14px;
      font-weight: 600;
      color: var(--color-primary);
    }
  }

  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
  }

  .cleanup-modal-body {
    .cleanup-search {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;

      .common-input {
        width: 200px;
      }
    }
  }
}

// 暗黑主题适配
[data-theme="dark"] {
  .attachment-management {
    .search-section,
    .stats-section,
    .table-section {
      background: var(--color-secondary);
      box-shadow: var(--shadow-sm);
    }

    .stats-section {
      .stats-card {
        .stats-icon {
          background: var(--color-active);
          color: var(--color-obvious);
        }

        .stats-content {
          .stats-label {
            color: var(--color-primary);
          }

          .stats-value {
            color: var(--color-active);
          }
        }
      }
    }

    .action-buttons {
      .action-btn {
        background: var(--color-default);
        color: var(--color-primary);
        border-color: var(--color-inactive);

        &:hover {
          background: var(--hover-overlay);
        }

        &.visit:hover {
          color: var(--color-primary);
        }

        &.transfer:hover {
          color: var(--color-primary);
        }

        &.delete:hover {
          color: var(--color-red);
        }
      }
    }

    .modal-form {
      .form-label {
        color: var(--color-primary);
      }
    }
  }
}
</style>
