<template>
  <CommonModal width="900px" :show="show" :title="$t('item.recycle')" :useClose="true" @close="handleClose">
    <a-spin :spinning="loading">
      <div class="recycle-content">
        <p class="tips">{{ $t('item.recycle_tips') }}</p>

        <!-- 页面列表 -->
        <CommonTable
          v-if="lists.length > 0"
          :tableData="lists"
          :tableHeader="tableHeader"
          :pagination="false"
          :rowKey="'page_id'"
          :maxHeight="'380px'"
        >
          <template #cell-action="{ row }">
            <a-button type="link" size="small" @click="handleRecover(row)">
              {{ $t('item.recover') }}
            </a-button>
          </template>
        </CommonTable>

        <a-empty v-else :description="$t('item.recycle_empty')" />
      </div>
    </a-spin>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import CommonTable from '@/components/CommonTable.vue'
import { getRecycleList, recoverPage } from '@/models/recycle'
import ConfirmModal from '@/components/ConfirmModal'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'

const { t } = useI18n()

const props = defineProps<{
  itemId: string | number
  onClose: () => void
}>()

const show = ref(false)
const loading = ref(false)
const lists = ref<any[]>([])

// 表格列配置
const tableHeader = computed(() => [
  {
    title: t('item.page_title'),
    key: 'page_title',
    width: 180,
  },
  {
    title: t('item.deleter'),
    key: 'del_by_username',
    width: 120,
  },
  {
    title: t('item.del_time'),
    key: 'del_time',
    width: 150,
  },
  {
    title: t('item.operation'),
    key: 'action',
    width: 80,
  },
])

// 获取回收站列表
const getList = async () => {
  loading.value = true
  try {
    const data = await getRecycleList(String(props.itemId))
    if (data && data.data) {
      lists.value = data.data
    }
  } catch (error) {
    console.error('获取回收站列表失败:', error)
  } finally {
    loading.value = false
  }
}

// 恢复页面
const handleRecover = async (record: any) => {
  const result = await ConfirmModal(t('item.recover_tips'), t('common.confirm'))

  if (result) {
    loading.value = true
    try {
      await recoverPage(String(props.itemId), String(record.page_id))
      Message.success(t('common.op_success'))
      await getList() // 重新获取列表
    } catch (error) {
      console.error('恢复页面失败:', error)
      // request 已经会自动弹窗，不需要额外的 Message.error
    } finally {
      loading.value = false
    }
  }
}

const handleClose = () => {
  props.onClose()
}

onMounted(() => {
  show.value = true
  getList()
})
</script>

<style scoped lang="scss">
:deep(.common-modal .modal-content) {
  max-width: 700px;
}

.recycle-content {
  padding: 0;
}

.tips {
  margin-bottom: 16px;
  padding: 0 20px;
  color: var(--color-text-secondary);
  font-size: 12px;
}
</style>

