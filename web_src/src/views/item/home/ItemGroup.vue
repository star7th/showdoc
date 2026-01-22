<template>
  <div class="item-group-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('item.manage_item_group')"
      :headerButtons="headerButtons"
      @close="handleClose"
    >
      <div class="modal-content">
        <p class="tips" v-if="list && list.length > 1">
          {{ t('item.draggable_tips') }}
        </p>
        <SortableList
          handle=".group-item"
          listClass="group-list"
          :animate="200"
          @drop="onDropEnd"
        >
          <a-empty
            v-if="!list || list.length === 0"
            :description="t('item.item_group_empty_tips')"
          />
          <div
            v-for="item in list"
            :key="item.id"
            class="group-item"
          >
            <div class="group-info">
              <i class="fas fa-bars handle-icon"></i>
              <span class="group-name">{{ item.group_name }}</span>
            </div>
            <div class="group-actions">
              <a-button
                type="link"
                size="small"
                @click="edit(item)"
              >
                {{ t('common.edit') }}
              </a-button>
              <a-button
                type="link"
                size="small"
                danger
                @click="del(item.id)"
              >
                {{ t('common.delete') }}
              </a-button>
            </div>
          </div>
        </SortableList>
      </div>
      <div class="modal-footer">
        <div class="secondary-button" @click="handleClose">
          {{ t('common.close') }}
        </div>
      </div>
    </CommonModal>

    <!-- 编辑/添加分组弹窗 -->
    <CommonModal
      v-if="dialogFormVisible"
      :class="{ show: dialogFormVisible }"
      :title="MyForm.id ? t('item.edit_group') : t('item.add_group')"
      @close="dialogFormVisible = false"
    >
      <div class="edit-modal-content">
        <div class="form-group">
          <label class="form-label">{{ t('item.group_name') }}:</label>
          <CommonInput
            v-model="MyForm.group_name"
            :placeholder="t('item.group_name')"
          />
        </div>
        <div class="item-selection">
          <p class="selection-title">{{ t('item.select_item') }}</p>
          <CommonTable
            :table-header="tableHeader"
            :table-data="itemList"
            :row-selection="rowSelectionConfig"
            row-key="item_id"
            :pagination="false"
            max-height="400px"
            @selectionChange="handleSelectionChange"
          >
            <template #cell-item_name="{ value }">
              {{ value }}
            </template>
          </CommonTable>
        </div>
      </div>
      <div class="modal-footer">
        <div class="secondary-button" @click="dialogFormVisible = false">
          {{ t('common.cancel') }}
        </div>
        <div class="primary-button" @click="myFormSubmit">
          {{ t('common.confirm') }}
        </div>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import CommonTable from '@/components/CommonTable.vue'
import CommonInput from '@/components/CommonInput.vue'
import SortableList from '@/components/SortableList.vue'
import { getGroupList, saveGroup, deleteGroup, saveGroupSort } from '@/models/itemGroup'
import { getMyList } from '@/models/item'
import ConfirmModal from '@/components/ConfirmModal'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'

const { t } = useI18n()

const props = defineProps<{
  callback: () => void
}>()

const show = ref(false)
const MyForm = ref({
  id: '',
  group_name: ''
})
const list = ref<any[]>([])
const dialogFormVisible = ref(false)
const itemList = ref<any[]>([])
const selectedItemIds = ref<number[]>([])

// 表格列定义
const tableHeader = computed(() => [
  {
    title: t('item.item_name'),
    key: 'item_name',
    width: 300
  }
])

// 行选择配置
const rowSelectionConfig = computed(() => ({
  selectedRowKeys: selectedItemIds.value,
  onChange: (keys: (string | number)[]) => {
    selectedItemIds.value = keys as number[]
  }
}))

// 处理表格选择变化
const handleSelectionChange = (selectedRowKeys: (string | number)[], _selectedRows: any[]) => {
  selectedItemIds.value = selectedRowKeys as number[]
}

// 添加分组
const addDialog = () => {
  MyForm.value = {
    id: '',
    group_name: ''
  }
  selectedItemIds.value = []
  dialogFormVisible.value = true
}

// 头部按钮配置
const headerButtons = computed(() => [
  {
    text: t('item.add_group'),
    icon: 'fas fa-plus',
    type: 'default' as const,
    size: 'small' as const,
    onClick: addDialog
  }
])

// 获取分组列表
const getGroupListData = async () => {
  try {
    const data = await getGroupList()
    if (data && data.data) {
      list.value = data.data
    }
  } catch (error) {
    console.error('Get group list failed:', error)
  }
}

// 获取项目列表
const getItemListData = async () => {
  try {
    const data = await getMyList(0)
    if (data && data.data) {
      itemList.value = data.data
    }
  } catch (error) {
    console.error('Get item list failed:', error)
  }
}

// 提交表单（新增/编辑）
const myFormSubmit = async () => {
  const group_name = MyForm.value.group_name || 'default'
  const id = MyForm.value.id
  const item_ids = selectedItemIds.value.join(',')

  try {
    await saveGroup({ group_name, id, item_ids })
    await getGroupListData()
    dialogFormVisible.value = false
    selectedItemIds.value = []
    MyForm.value = { id: '', group_name: '' }
    Message.success(t('common.op_success'))
    // 不关闭主弹窗，只关闭编辑弹窗，回到分组管理界面
  } catch (error) {
    console.error('Save group failed:', error)
    await AlertModal(t('common.error'))
  }
}

// 编辑分组
const edit = (row: any) => {
  MyForm.value.id = row.id
  MyForm.value.group_name = row.group_name
  selectedItemIds.value = []
  const item_ids_array = row.item_ids ? row.item_ids.split(',') : []
  item_ids_array.forEach((item_id: string) => {
    const found = itemList.value.find((element: any) => String(element.item_id) === item_id)
    if (found) {
      selectedItemIds.value.push(found.item_id)
    }
  })
  dialogFormVisible.value = true
}

// 删除分组
const del = async (id: string) => {
  const confirmed = await ConfirmModal({
    title: t('common.confirm'),
    msg: t('common.confirm_delete'),
    confirmText: t('common.confirm'),
    cancelText: t('common.cancel')
  })

  if (confirmed) {
    try {
      await deleteGroup({ id })
      await getGroupListData()
      Message.success(t('common.op_success'))
      // 不关闭主弹窗，只刷新分组列表
    } catch (error) {
      console.error('Delete group failed:', error)
      await AlertModal(t('common.error'))
    }
  }
}

// 关闭弹窗
const handleClose = () => {
  show.value = false
  setTimeout(() => {
    props.callback()
  }, 300)
}

// 拖拽排序结束
const onDropEnd = async ({ oldIndex, newIndex }: { oldIndex: number, newIndex: number }) => {
  // 手动调整数据顺序
  const targetRow = list.value[oldIndex]
  list.value.splice(oldIndex, 1)
  list.value.splice(newIndex, 0, targetRow)

  // 保存排序
  const groups_array = list.value.map((item: any, index: number) => ({
    id: item.id,
    s_number: index + 1
  }))

  try {
    await saveGroupSort({ groups: JSON.stringify(groups_array) })
    // 不关闭主弹窗，只保存排序
    // 排序可能会影响显示顺序，所以需要通知父组件刷新
    props.callback()
  } catch (error) {
    console.error('Save group sort failed:', error)
  }
}

// 组件挂载时显示弹窗
onMounted(async () => {
  setTimeout(() => {
    show.value = true
  })
  await getItemListData()
  await getGroupListData()
})
</script>

<style scoped lang="scss">
.modal-content {
  width: 600px;
  padding: 30px 40px;
  border-bottom: 1px solid var(--color-interval);
}

.tips {
  margin-bottom: 20px;
  font-size: 13px;
  color: var(--color-text-secondary);
}

.group-list {
  min-height: 200px;
  max-height: 400px;
  overflow-y: auto;
}

.group-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  margin-bottom: 8px;
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  transition: all 0.15s ease;

  &:hover {
    background: var(--hover-overlay);
  }
}

.group-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.handle-icon {
  color: var(--color-text-secondary);
  cursor: move;
  font-size: 14px;
}

.group-name {
  font-size: 14px;
  color: var(--color-text-primary);
  font-weight: 500;
}

.group-actions {
  display: flex;
  gap: 8px;

  .ant-btn-link {
    padding: 0 4px;
    height: auto;
    font-size: 13px;
  }
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 90px;

  .secondary-button,
  .primary-button {
    width: 160px;
    margin: 0 7.5px;
  }

  .primary-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
  }
}

.edit-modal-content {
  width: 500px;
  padding: 30px 40px;
  border-bottom: 1px solid var(--color-interval);
}

.form-group {
  margin-bottom: 24px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  color: var(--color-text-primary);
  font-weight: 500;
}

.item-selection {
  margin-top: 20px;
}

.selection-title {
  margin-bottom: 12px;
  font-size: 14px;
  color: var(--color-text-primary);
  font-weight: 500;
}

:deep(.common-table-wrapper) {
  max-height: 400px;
  overflow-y: auto;
}

</style>
