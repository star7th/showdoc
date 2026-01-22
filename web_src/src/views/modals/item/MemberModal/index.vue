<template>
  <div class="member-modal">
    <CommonModal
      :class="{ show }"
      :title="t('item.member_manage')"
      :icon="['fas', 'fa-users']"
      :headerButtons="headerButtons"
      width="800px"
      height="600px"
      @close="handleClose"
    >
      <div class="modal-content">
        <div class="content-wrapper">
        <!-- 成员列表 -->
          <div v-if="members.length > 0" class="section">
        <CommonTable
          :tableHeader="memberTableHeader"
          :tableData="members"
          :pagination="false"
        >
          <template #cell-username="{ row }">{{ row.username }}</template>
          <template #cell-name="{ row }">{{ row.name }}</template>
          <template #cell-addtime="{ row }">{{ row.addtime }}</template>
          <template #cell-authority="{ row }">
            {{ memberGroupText(row.member_group_id, row.cat_name) }}
          </template>
          <template #cell-operation="{ row }">
            <a class="delete-action" @click="handleDeleteMember(row.item_member_id)">
              {{ t('common.delete') }}
            </a>
          </template>
        </CommonTable>
          </div>

        <!-- 团队列表 -->
          <div v-if="teamItems.length > 0" class="section">
        <CommonTable
          :tableHeader="teamTableHeader"
          :tableData="teamItems"
          :pagination="false"
        >
          <template #cell-team_name="{ row }">{{ row.team_name }}</template>
          <template #cell-addtime="{ row }">{{ row.addtime }}</template>
          <template #cell-operation="{ row }">
            <a @click="handleTeamMemberAuthority(row.team_id)">
              {{ t('item.member_authority') }}
            </a>
            <a-divider type="vertical" />
            <a class="delete-action" @click="handleDeleteTeam(row.id)">
              {{ t('common.delete') }}
            </a>
          </template>
        </CommonTable>
          </div>

        <p
          v-if="members.length === 0 && teamItems.length === 0"
          class="empty-tips"
        >
          {{ t('item.no_member_tips') }}
        </p>
        </div>
      </div>

      <template #footer>
        <div class="footer-buttons">
          <CommonButton
            :text="t('common.close')"
            theme="dark"
            @click="handleClose"
          />
        </div>
      </template>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonTable from '@/components/CommonTable.vue'
import AddMemberModal from '@/views/modals/item/AddMemberModal'
import AddTeamModal from '@/views/modals/item/AddTeamModal'
import TeamMemberAuthorityModal from '@/views/modals/item/TeamMemberAuthorityModal'
import ConfirmModal from '@/components/ConfirmModal'
import {
  getMemberList,
  deleteMember as deleteMemberApi
} from '@/models/member'
import {
  getTeamItemList,
  deleteTeamItem
} from '@/models/team'

const { t } = useI18n()

const props = defineProps<{
  item_id: string | number
  onClose: (result: boolean) => void
}>()

// 数据状态
const show = ref(false)
const members = ref<any[]>([])
const teamItems = ref<any[]>([])

// 获取项目成员列表
const fetchMembers = async () => {
  try {
    const res = await getMemberList(String(props.item_id))
    if (res.error_code === 0) {
      members.value = res.data || []
    }
  } catch (error) {
    console.error('获取成员列表失败:', error)
  }
}

// 获取项目关联的团队列表
const fetchTeamItems = async () => {
  try {
    const res = await getTeamItemList(String(props.item_id))
    if (res.error_code === 0) {
      teamItems.value = res.data || []
    }
  } catch (error) {
    console.error('获取团队项目列表失败:', error)
  }
}

// 表头定义（成员列表）
const memberTableHeader = [
  { title: t('item.member_username'), key: 'username', width: 150 },
  { title: t('item.name'), key: 'name', width: 120 },
  { title: t('item.add_time'), key: 'addtime', width: 160 },
  { title: t('item.authority'), key: 'authority', width: 140 },
  { title: t('item.operation'), key: 'operation',  center: true },
]

// 表头定义（团队列表）
const teamTableHeader = [
  { title: t('item.team_name'), key: 'team_name', width: 300 },
  { title: t('item.add_time'), key: 'addtime', width: 180 },
  { title: t('item.operation'), key: 'operation', center: true },
]

// 添加成员
const handleAddMember = async () => {
  const result = await AddMemberModal(props.item_id)
  if (result) {
    fetchMembers()
  }
}

// 添加团队
const handleAddTeam = async () => {
  const result = await AddTeamModal(props.item_id)
  if (result) {
    fetchTeamItems()
  }
}

// 删除成员
const handleDeleteMember = async (item_member_id: number) => {
  const confirmed = await ConfirmModal({
    title: t('common.confirm_delete'),
    msg: t('item.confirm_delete_member')
  })

  if (confirmed) {
    try {
      const res = await deleteMemberApi(String(props.item_id), item_member_id)
      if (res.error_code === 0) {
        message.success(t('common.op_success'))
        fetchMembers()
      } else {
        message.error(res.error_message || t('common.op_failed'))
      }
    } catch (error) {
      console.error('删除成员失败:', error)
      message.error(t('common.op_failed'))
    }
  }
}

// 删除团队
const handleDeleteTeam = async (id: number) => {
  const confirmed = await ConfirmModal({
    title: t('common.confirm_delete'),
    msg: t('item.confirm_delete_team')
  })

  if (confirmed) {
    try {
      const res = await deleteTeamItem(id)
      if (res.error_code === 0) {
        message.success(t('common.op_success'))
        fetchTeamItems()
      } else {
        message.error(res.error_message || t('common.op_failed'))
      }
    } catch (error) {
      console.error('删除团队失败:', error)
      message.error(t('common.op_failed'))
    }
  }
}

// 团队成员权限
const handleTeamMemberAuthority = async (team_id: number) => {
  await TeamMemberAuthorityModal(props.item_id, team_id)
}

// 权限文本
const memberGroupText = (member_group_id: string, cat_name: string) => {
  if (member_group_id === '2') {
    return t('item.item_admin')
  }
  if (member_group_id === '1') {
    return `${t('item.edit')}/${t('item.catalog')}: ${cat_name}`
  }
  return `${t('item.readonly')}/${t('item.catalog')}: ${cat_name}`
}

// 关闭弹窗
const handleClose = () => {
  show.value = false
  setTimeout(() => {
    props.onClose(true)
  }, 300)
}

// 右上角按钮定义
const headerButtons = [
  { text: t('item.add_member'), icon: ['fas', 'fa-plus'], onClick: handleAddMember },
  { text: t('item.add_team'), icon: ['fas', 'fa-plus'], onClick: handleAddTeam },
]

onMounted(() => {
  show.value = true
  fetchMembers()
  fetchTeamItems()
})
</script>

<style scoped lang="scss">
.member-modal {
  :deep(.ant-table) {
    font-size: 14px;
  }

  :deep(.common-table-wrapper) {
    width: 100%;
  }

  :deep(table) {
    width: 100%;
    table-layout: auto;
  }

  :deep(th),
  :deep(td) {
    min-width: auto;
  }
}

.modal-content {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 0;
}

.content-wrapper {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 16px;
}

.section {
  margin-bottom: 20px;

  &:last-child {
    margin-bottom: 0;
  }
}

.footer-buttons {
  display: flex;
  justify-content: center;
  align-items: center;

  :deep(.common-button) {
    width: 160px;
  }
}

.delete-action {
  color: var(--color-error);
  cursor: pointer;

  &:hover {
    color: var(--color-error);
    opacity: 0.8;
  }
}

.empty-tips {
  color: var(--color-text-secondary);
  text-align: center;
  padding: 40px 20px;
  font-size: 14px;
}
</style>
