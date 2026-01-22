<template>
  <div class="team-modal">
    <CommonModal
      :class="{ show }"
      :title="t('team.team_manage')"
      :icon="['fas', 'users']"
      width="900px"
      height="400px"
      :headerButtons="headerButtons"
      @close="handleClose"
    >

      <div class="modal-content">
        <div class="table-wrapper">
        <CommonTable
          :tableHeader="tableHeader"
          :tableData="teamList"
          :loading="loading"
          :rowClickable="true"
          :emptyText="t('team.empty_team_tips')"
          @rowClick="handleRowClick"
        >
          <template #cell-memberCount="{ row }">
            <a @click="handleClickToMember(row)" class="clickable-link">{{ row.memberCount }}</a>
          </template>
          <template #cell-itemCount="{ row }">
            <a @click="handleClickToItem(row)" class="clickable-link">{{ row.itemCount }}</a>
          </template>
          <template #cell-operation="{ row }">
            <div class="operation-buttons">
              <span class="operation-btn" @click="handleClickToMember(row)">{{ t('team.member') }}</span>
              <span class="operation-divider"></span>
              <span class="operation-btn" @click="handleClickToItem(row)">{{ t('team.team_item') }}</span>
              <template v-if="row.team_manage > 0">
                <span class="operation-divider"></span>
                <span class="operation-btn" @click="handleEditTeam(row)">{{ t('common.edit') }}</span>
                <span class="operation-divider"></span>
                <span class="operation-btn" @click="handleAttornTeam(row)">{{ t('item.attorn_team') }}</span>
                <span class="operation-divider"></span>
                <span class="operation-btn danger" @click="handleDeleteTeam(row.id)">{{ t('common.delete') }}</span>
              </template>
              <template v-else>
                <span class="operation-divider"></span>
                <span class="operation-btn danger" @click="handleExitTeam(row.id)">{{ t('team.team_exit') }}</span>
              </template>
            </div>
          </template>
        </CommonTable>
      </div>
      </div>

      <template #footer>
        <div class="footer-buttons">
          <CommonButton @click="handleClose">
            {{ t('common.close') }}
          </CommonButton>
        </div>
      </template>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import CommonTable from '@/components/CommonTable.vue'
import CommonButton from '@/components/CommonButton.vue'
import ConfirmModal from '@/components/ConfirmModal'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'
import PromptModal from '@/components/PromptModal'
import TeamMemberModalFunc from '@/views/modals/item/TeamMemberModal'
import TeamItemModalFunc from '@/views/modals/item/TeamItemModal'
import AttornTeamModalFunc from '@/views/modals/item/AttornTeamModal'
import {
  getTeamList,
  saveTeam,
  deleteTeam as deleteTeamApi,
  exitTeam
} from '@/models/team'

const { t } = useI18n()

const props = defineProps<{
  onClose: (result: boolean) => void
}>()

const show = ref(false)
const loading = ref(false)
const teamList = ref<any[]>([])
const currentOperationRow = ref<any>({
  id: 0,
  team_manage: 0,
  team_name: ''
})


// 头部按钮配置
const headerButtons = computed(() => [
  {
    text: t('item.add_team'),
    icon: ['fas', 'plus'],
    onClick: handleAddTeam
  }
])

// 表头配置
const tableHeader = computed(() => [
  { title: t('item.team_name'), key: 'team_name', width: 200 },
  { title: t('team.member_count'), key: 'memberCount', width: 100 },
  { title: t('team.item_count'), key: 'itemCount', width: 100 },
  { title: t('common.operation'), key: 'operation', width: 300 }
])

// 获取团队列表
const fetchTeamList = async () => {
  loading.value = true
  try {
    const res = await getTeamList()
    if (res.error_code === 0) {
      teamList.value = res.data || []
    } else {
      await AlertModal(res.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('获取团队列表失败:', error)
    await AlertModal(t('common.op_failed'))
  } finally {
    loading.value = false
  }
}

// 行点击
const handleRowClick = () => {
  // 可以在这里实现点击行后的操作
}

// 添加团队
const handleAddTeam = async () => {
  const teamName = await PromptModal(
    t('item.add_team'),
    '',
    t('item.team_name')
  )

  if (teamName && teamName.trim()) {
    try {
      const res = await saveTeam({
        id: '',
        team_name: teamName.trim()
      })

      if (res.error_code === 0) {
        Message.success(t('common.op_success'))
        fetchTeamList()
      } else {
        await AlertModal(res.error_message || t('common.op_failed'))
      }
    } catch (error) {
      console.error('保存团队失败:', error)
      await AlertModal(t('common.op_failed'))
    }
  }
}

// 编辑团队
const handleEditTeam = async (row: any) => {
  const teamName = await PromptModal(
    t('item.edit_team'),
    row.team_name,
    t('item.team_name')
  )

  if (teamName && teamName.trim()) {
    try {
      const res = await saveTeam({
        id: row.id,
        team_name: teamName.trim()
      })

      if (res.error_code === 0) {
        Message.success(t('common.op_success'))
        fetchTeamList()
      } else {
        await AlertModal(res.error_message || t('common.op_failed'))
      }
    } catch (error) {
      console.error('保存团队失败:', error)
      await AlertModal(t('common.op_failed'))
    }
  }
}

// 删除团队
const handleDeleteTeam = async (id: number) => {
  const confirmed = await ConfirmModal({
    msg: t('item.confirm_delete_team'),
    title: t('item.confirm_delete')
  })

  if (confirmed) {
    try {
      const res = await deleteTeamApi(id)
      if (res.error_code === 0) {
        Message.success(t('common.op_success'))
        fetchTeamList()
      } else {
        await AlertModal(res.error_message || t('common.op_failed'))
      }
    } catch (error) {
      console.error('删除团队失败:', error)
      await AlertModal(t('common.op_failed'))
    }
  }
}

// 转让团队
const handleAttornTeam = async (row: any) => {
  const result = await AttornTeamModalFunc(row.id)
  if (result) {
    fetchTeamList()
  }
}

// 退出团队
const handleExitTeam = async (id: number) => {
  const confirmed = await ConfirmModal({
    msg: t('item.team_exit_confirm_content'),
    title: t('item.team_exit_confirm')
  })

  if (confirmed) {
    try {
      const res = await exitTeam(id)
      if (res.error_code === 0) {
        Message.success(t('common.op_success'))
        fetchTeamList()
      } else {
        await AlertModal(res.error_message || t('common.op_failed'))
      }
    } catch (error) {
      console.error('退出团队失败:', error)
      await AlertModal(t('common.op_failed'))
    }
  }
}

// 点击成员
const handleClickToMember = async (row: any) => {
  currentOperationRow.value = row
  await TeamMemberModalFunc({ team_id: row.id, team_manage: row.team_manage })
  fetchTeamList()
}

// 点击团队项目
const handleClickToItem = async (row: any) => {
  currentOperationRow.value = row
  await TeamItemModalFunc({ team_id: row.id, team_manage: row.team_manage })
  fetchTeamList()
}

// 团队成员回调（已由工厂函数处理）
// 团队项目回调（已由工厂函数处理）

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
    fetchTeamList()
  })
})
</script>

<style scoped lang="scss">
.team-modal {
  :deep(.ant-table) {
    font-size: 14px;
  }
}

.modal-content {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 0;
}

.table-wrapper {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
}

.footer-buttons {
  display: flex;
  justify-content: center;
  align-items: center;

  :deep(.common-button) {
    width: 160px;
  }
}

.operation-buttons {
  display: flex;
  align-items: center;
  gap: 0;
}

.operation-btn {
  color: var(--color-primary);
  cursor: pointer;
  padding: 4px 8px;
  transition: opacity 0.15s ease;

  &:hover {
    opacity: 0.8;
  }

  &.danger {
    color: var(--color-error);

    &:hover {
      opacity: 0.8;
    }
  }
}

.operation-divider {
  width: 1px;
  height: 16px;
  background-color: var(--color-border);
  margin: 0 4px;
}

.clickable-link {
  color: var(--color-active);
  cursor: pointer;

  &:hover {
    color: var(--color-active);
  }
}

.form-content {
  padding: 20px 0;
}

.form-item {
  margin-bottom: 20px;

  &:last-child {
    margin-bottom: 0;
  }
}

.form-label {
  font-size: 14px;
  color: var(--color-text-primary);
  margin-bottom: 8px;
  font-weight: 500;
}

.tips-text {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin-top: 12px;
  padding: 8px 12px;
  background-color: var(--color-bg-secondary);
  border-radius: 4px;
}

// 暗黑主题适配
[data-theme='dark'] {
  .operation-divider {
    background-color: var(--color-border);
  }

  .tips-text {
    background-color: var(--color-bg-secondary);
  }
}
</style>

