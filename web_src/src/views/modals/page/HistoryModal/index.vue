<template>
  <CommonModal :show="show" :title="$t('page.page_history_version')" @close="handleClose" width="900px">
    <div class="history-modal">
      <CommonTable
        :tableHeader="tableHeader"
        :tableData="historyList"
        :loading="loading"
        :pagination="pagination"
        @pageChange="handlePageChange"
        rowKey="page_history_id"
      >
        <!-- 备注列自定义渲染 -->
        <template #cell-page_comments="{ row }">
          <span>{{ row.page_comments || '-' }}</span>
          <a-button
            v-if="allowEdit"
            type="link"
            size="small"
            @click="handleEditComment(row)"
          >
            {{ $t('page.edit') }}
          </a-button>
        </template>

        <!-- 操作列自定义渲染 -->
        <template #cell-actions="{ row }">
          <a-button
            type="link"
            size="small"
            @click="handlePreviewDiff(row)"
          >
            {{ $t('page.overview') }}
          </a-button>
          <a-button
            v-if="allowRecover"
            type="link"
            size="small"
            danger
            @click="handleRecover(row)"
          >
            {{ $t('page.recover_to_this_version') }}
          </a-button>
        </template>
      </CommonTable>
    </div>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import request from '@/utils/request'
import { unescapeHTML } from '@/models/page'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'
import ConfirmModal from '@/components/ConfirmModal/index'
import PromptModal from '@/components/PromptModal/index'
import CommonModal from '@/components/CommonModal.vue'
import CommonTable from '@/components/CommonTable.vue'

const { t } = useI18n()
const router = useRouter()

interface Props {
  pageId: number
  onRestore?: (pageContent: string) => void
  onClose: () => void
  // 是否允许恢复（编辑器页面可以恢复，展示页不能恢复）
  allowRecover?: boolean
  // 是否允许编辑备注
  allowEdit?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  allowRecover: true,  // 默认允许恢复
  allowEdit: true       // 默认允许编辑
})

// Refs
const show = ref(true)
const loading = ref(false)
const historyList = ref<any[]>([])

// 分页配置
const pagination = ref({
  current: 1,
  pageSize: 20,
  total: 0
})

// 表头配置
const tableHeader = computed(() => [
  {
    title: t('page.update_time'),
    key: 'addtime',
    width: 170
  },
  {
    title: t('page.update_by_who'),
    key: 'author_username',
    width: 120
  },
  {
    title: t('page.remark'),
    key: 'page_comments',
    width: 300
  },
  {
    title: t('page.operation'),
    key: 'actions',
    width: 200
  }
])

// Methods
const fetchHistoryList = async () => {
  loading.value = true
  try {
    const result = await request('/api/page/history', {
      page_id: props.pageId
    }, 'post', false)

    if (result.error_code === 0 && result.data) {
      historyList.value = result.data
      pagination.value.total = result.data.length
    } else {
      await AlertModal(result.error_message || t('common.fetch_failed'))
    }
  } catch (error) {
    console.error('获取历史版本失败:', error)
    await AlertModal(t('common.fetch_failed'))
  } finally {
    loading.value = false
  }
}

const handlePageChange = (page: number) => {
  pagination.value.current = page
}

// 查看版本差异
const handlePreviewDiff = (row: any) => {
  const pageHistoryId = row.page_history_id
  const url = router.resolve({
    path: `/page/diff/${props.pageId}/${pageHistoryId}`
  })
  window.open(url.href, '_blank')
}

// 恢复到此版本
const handleRecover = async (row: any) => {
  const confirmed = await ConfirmModal({
    title: t('page.recover_to_this_version'),
    msg: t('page.confirm_recover_version')
  })

  if (!confirmed) return

  try {
    // 使用 unescapeHTML 解码内容
    const content = unescapeHTML(row.page_content)

    // 从编辑器调用时（有 onRestore 回调），直接回调，不保存到服务器
    if (props.onRestore) {
      props.onRestore(content)
      Message.success(t('common.op_success'))
      handleClose()
      return
    }

    // 从展示页调用时（无 onRestore 回调），调用接口保存到服务器
    const result = await request('/api/page/save', {
      page_id: props.pageId,
      page_content: row.page_content,
      is_urlencode: 1
    }, 'post', false)

    if (result.error_code === 0) {
      Message.success(t('common.op_success'))
      handleClose()
    } else {
      await AlertModal(result.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('恢复版本失败:', error)
    await AlertModal(t('common.op_failed'))
  }
}

// 编辑备注
const handleEditComment = async (row: any) => {
  const newComment = await PromptModal(
    t('page.edit_remark'),
    row.page_comments || '',
    t('page.remark')
  )

  if (newComment === null || newComment === row.page_comments) return

  try {
    const result = await request('/api/page/updateHistoryComments', {
      page_id: props.pageId,
      page_history_id: row.page_history_id,
      page_comments: newComment
    }, 'post', false)

    if (result.error_code === 0) {
      Message.success(t('common.save_success'))
      fetchHistoryList() // 重新获取列表
    } else {
      await AlertModal(result.error_message || t('common.save_failed'))
    }
  } catch (error) {
    console.error('更新备注失败:', error)
    await AlertModal(t('common.save_failed'))
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
  fetchHistoryList()
})
</script>

<style scoped lang="scss">
.history-modal {
  min-height: 200px;

  :deep(.common-table-wrapper) {
    main {
      max-height: 500px;
    }
  }
}

:deep(.ant-btn-link) {
  padding: 0 4px;
  margin: 0 2px;
}
</style>
