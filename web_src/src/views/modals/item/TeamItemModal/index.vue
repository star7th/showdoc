<template>
  <div class="team-item-modal">
    <CommonModal
      :class="{ show }"
      :title="t('team.item_manage')"
      :icon="['fas', 'folder']"
      width="900px"
      :headerButtons="headerButtons"
      @close="handleClose"
    >
      <div class="modal-content">
        <CommonTable
          :tableHeader="tableHeader"
          :tableData="itemList"
          :loading="loading"
        >
          <template #cell-item_name="{ row }">
            <div class="item-info">
              <span>{{ row.item_name }}</span>
            </div>
          </template>
          <template #cell-operation="{ row }">
            <div class="operation-buttons">
              <span class="operation-btn" @click="handleViewItem(row.item_id)">
                {{ t('item.check_item') }}
              </span>

              <template v-if="team_manage > 0">
                <span class="operation-divider"></span>
                <span class="operation-btn" @click="handleMemberAuthority(row.item_id)">
                  {{ t('item.member_authority') }}
                </span>
                <span class="operation-divider"></span>
                <span class="operation-btn danger" @click="handleUnbindItem(row.id)">
                  {{ t('item.unassign') }}
                </span>
              </template>
            </div>
          </template>
        </CommonTable>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import CommonModal from '@/components/CommonModal.vue'
import CommonTable from '@/components/CommonTable.vue'
import ConfirmModal from '@/components/ConfirmModal'
import AlertModal from '@/components/AlertModal'
import Message from '@/components/Message'
import BindItemModalFunc from '@/views/modals/item/BindItemModal'
import TeamItemMemberModalFunc from '@/views/modals/item/TeamItemMemberModal'
import { getTeamItemListByTeam, deleteTeamItem } from '@/models/team'

const { t } = useI18n()
const router = useRouter()

const props = defineProps<{
  team_id: number
  team_manage: number
  onClose: (result: boolean) => void
}>()

const show = ref(false)
const loading = ref(false)
const itemList = ref<any[]>([])

// 头部按钮配置
const headerButtons = computed(() => {
  if (props.team_manage > 0) {
    return [
      {
        text: t('item.binding_item'),
        icon: ['fas', 'plus'],
        onClick: handleBindItem
      }
    ]
  }
  return []
})

// 表头配置
const tableHeader = computed(() => [
  { title: t('item.item_name'), key: 'item_name', width: 300 },
  { title: t('team.join_time'), key: 'addtime', width: 200 },
  { title: t('common.operation'), key: 'operation', width: 300 }
])

// 获取团队项目列表
const fetchItemList = async () => {
  loading.value = true
  try {
    const res = await getTeamItemListByTeam(props.team_id)
    if (res.error_code === 0) {
      itemList.value = res.data || []
    }
  } catch (error) {
    console.error('获取团队项目列表失败:', error)
  } finally {
    loading.value = false
  }
}

// 绑定项目
const handleBindItem = async () => {
  const result = await BindItemModalFunc(props.team_id)
  if (result) {
    fetchItemList()
  }
}

// 查看项目 - 使用路由跳转到项目详情页
const handleViewItem = (itemId: string) => {
  // 这里要新窗口打开，同事url用路由生成
  window.open(router.resolve({ path: `/${itemId}` }).href, '_blank')
}

// 解绑项目
const handleUnbindItem = async (id: number) => {
  const confirmed = await ConfirmModal(t('item.confirm_unassign_item'))

  if (confirmed) {
    try {
      await deleteTeamItem(id)
      Message.success(t('common.op_success'))
      fetchItemList()
    } catch (error) {
      console.error('解绑项目失败:', error)
    }
  }
}

// 成员权限
const handleMemberAuthority = async (itemId: string) => {
  await TeamItemMemberModalFunc(itemId, props.team_id)
}

// 关闭弹窗
const handleClose = () => {
  show.value = false
  setTimeout(() => {
    props.onClose(false)
  }, 300)
}

onMounted(() => {
  setTimeout(() => {
    show.value = true
    fetchItemList()
  })
})
</script>

<style scoped lang="scss">
.modal-content {
  padding: 0;
}

.item-info {
  display: flex;
  align-items: center;
}

.operation-buttons {
  display: flex;
  align-items: center;
}

.operation-btn {
  color: var(--color-active);
  cursor: pointer;

  &:hover {
    color: var(--color-active);
  }

  &.danger {
    color: var(--color-error);

    &:hover {
      color: var(--color-error);
      opacity: 0.8;
    }
  }
}

.operation-divider {
  width: 1px;
  height: 12px;
  background-color: var(--color-border);
  margin: 0 4px;
}

// 暗黑主题适配
[data-theme='dark'] {
  .operation-divider {
    background-color: var(--color-border);
  }
}
</style>
