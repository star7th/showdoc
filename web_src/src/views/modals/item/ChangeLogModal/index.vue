<template>
  <div class="change-log-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('item.item_change_log_dialog_title')"
      :icon="['fa', 'fa-clock-rotate-left']"
      width="70%"
      height="600px"
      @close="handleClose"
    >
      <div class="modal-content">
        <CommonTable
          :table-header="tableHeader"
          :table-data="logList"
          :pagination="pagination"
          :loading="loading"
          :max-height="'calc(100vh - 350px)'"
          @page-change="handlePageChange"
        >
          <!-- 操作人列 -->
          <template #cell-oper="{ row }">
            {{ row.oper }}
          </template>

          <!-- 操作类型列 -->
          <template #cell-op_action_type_desc="{ row }">
            {{ getActionTypeText(row.op_action_type) }}
          </template>

          <!-- 对象类型列 -->
          <template #cell-op_object_type_desc="{ row }">
            {{ getObjectTypeText(row.op_object_type) }}
          </template>

          <!-- 对象名称列 -->
          <template #cell-op_object_name="{ row }">
            <a
              v-if="isPageLink(row)"
              class="page-link"
              @click="visitPage(row.op_object_id)"
            >
              {{ row.op_object_name }}
            </a>
            <span v-else>{{ row.op_object_name }}</span>
          </template>

          <!-- 备注列 -->
          <template #cell-remark="{ row }">
            {{ row.remark }}
          </template>
        </CommonTable>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import CommonModal from '@/components/CommonModal.vue'
import CommonTable from '@/components/CommonTable.vue'
import request from '@/utils/request'

interface TableHeaderItem {
  title: string
  key: string
  width?: number
  center?: boolean
  tools?: boolean
}

const { t } = useI18n()
const router = useRouter()

interface Props {
  itemId: number
  onClose?: () => void
}

const props = defineProps<Props>()

// 表格表头
const tableHeader = computed<TableHeaderItem[]>(() => [
  {
    title: t('item.op_time'),
    key: 'optime',
    width: 170
  },
  {
    title: t('item.oper'),
    key: 'oper',
    width: 120
  },
  {
    title: t('item.op_action_type_desc'),
    key: 'op_action_type_desc',
    width: 120
  },
  {
    title: t('item.op_object_type_desc'),
    key: 'op_object_type_desc',
    width: 110
  },
  {
    title: t('item.op_object_name'),
    key: 'op_object_name',
    minWidth: 150
  },
  {
    title: t('item.remark'),
    key: 'remark',
    width: 300
  }
])

// 状态管理
const show = ref(false)
const loading = ref(false)
const logList = ref<any[]>([])
const allLogList = ref<any[]>([])  // 保存所有日志数据
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)

// 分页配置
const pagination = computed(() => ({
  current: currentPage.value,
  pageSize: pageSize.value,
  total: total.value
}))

// 判断是否是页面可点击链接
const isPageLink = (row: any): boolean => {
  return (row.op_action_type === 'create' || row.op_action_type === 'update') &&
         row.op_object_type === 'page'
}

// 获取操作类型文本
const getActionTypeText = (type: string): string => {
  if (!type) return ''
  // 如果是中文环境，直接显示描述
  const actionTypeMap: Record<string, string> = {
    create: t('item.op_create'),
    update: t('item.op_update'),
    delete: t('item.op_delete'),
    copy: t('item.op_copy'),
    move: t('item.op_move')
  }
  return actionTypeMap[type] || type
}

// 获取对象类型文本
const getObjectTypeText = (type: string): string => {
  if (!type) return ''
  const objectTypeMap: Record<string, string> = {
    page: t('item.op_page'),
    catalog: t('item.catalog'),
    item: t('item.op_item'),
    member: t('item.op_member'),
    attachment: t('item.op_attachment')
  }
  return objectTypeMap[type] || type
}

// 获取变更日志列表
const fetchChangeLog = async () => {
  loading.value = true
  try {
    const result = await request('/api/item/getChangeLog', {
      page: currentPage.value,
      count: pageSize.value,
      item_id: props.itemId
    }, 'post', false)

    console.log('[fetchChangeLog] result:', result)
    if (result.error_code === 0) {
      // 将当前页数据替换掉对应位置的数据
      const start = (currentPage.value - 1) * pageSize.value
      const end = start + pageSize.value
      allLogList.value = [
        ...allLogList.value.slice(0, start),
        ...(result.data.list || []),
        ...allLogList.value.slice(end)
      ]
      logList.value = allLogList.value
      total.value = parseInt(result.data.total) || 0
      console.log('[fetchChangeLog] logList.value:', logList.value)
      console.log('[fetchChangeLog] total.value:', total.value)
    }
  } catch (error) {
    console.error('获取变更日志失败:', error)
  } finally {
    loading.value = false
  }
}

// 分页变化
const handlePageChange = (page: number) => {
  currentPage.value = page
  fetchChangeLog()
}

// 访问页面
const visitPage = (pageId: number) => {
  if (!pageId) return
  const url = router.resolve({
    path: `/${props.itemId}/${pageId}`
  })
  window.open(url.href, '_blank')
}

// 关闭弹窗
const handleClose = () => {
  if (props.onClose) {
    props.onClose()
  }
}

// 组件挂载时加载数据
onMounted(() => {
  show.value = true
  fetchChangeLog()
})
</script>

<style scoped lang="scss">
.change-log-modal {
  .modal-content {
    padding: 20px 0;
  }

  .page-link {
    color: var(--color-active);
    cursor: pointer;
    text-decoration: none;
    transition: color 0.15s ease;

    &:hover {
      color: var(--color-active);
      text-decoration: underline;
    }
  }
}
</style>
