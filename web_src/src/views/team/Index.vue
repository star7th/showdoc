<template>
  <CommonModal
    :show="true"
    :title="t('item.team_mamage')"
    :showCancel="false"
    :showOk="false"
    width="800px"
    @close="handleClose"
  >
    <template #extraButtons>
      <CommonButton type="primary" @click="handleAddTeam">
        <i class="fas fa-plus mr-2"></i>
        {{ t('item.add_team') }}
      </CommonButton>
    </template>

    <a-table
      :dataSource="teamList"
      :pagination="false"
      :emptyText="t('item.empty_team_tips')"
    >
      <a-table-column key="team_name" :title="t('item.team_name')">
        <template #default="{ record }">{{ record.team_name }}</template>
      </a-table-column>
      <a-table-column key="memberCount" :title="t('item.memberCount')">
        <template #default="{ record }">
          <a @click="handleClickToMember(record)">{{ record.memberCount }}</a>
        </template>
      </a-table-column>
      <a-table-column key="itemCount" :title="t('item.itemCount')">
        <template #default="{ record }">
          <a @click="handleClickToItem(record)">{{ record.itemCount }}</a>
        </template>
      </a-table-column>
      <a-table-column key="operation" :title="t('item.operation')">
        <template #default="{ record }">
          <a @click="handleClickToMember(record)">{{ t('item.member') }}</a>
          <a-divider type="vertical" />
          <a @click="handleClickToItem(record)">{{ t('item.team_item') }}</a>
          <template v-if="record.team_manage == 1 || record.team_manage == 2">
            <a-divider type="vertical" />
            <a @click="handleEditTeam(record)">{{ t('item.edit') }}</a>
            <a-divider type="vertical" />
            <a @click="handleAttornTeam(record)">{{ t('item.attorn_team') }}</a>
            <a-divider type="vertical" />
            <a @click="handleDeleteTeam(record.id)">{{ t('item.delete') }}</a>
          </template>
          <template v-else>
            <a-divider type="vertical" />
            <a @click="handleExitTeam(record.id)">{{ t('item.team_exit') }}</a>
          </template>
        </template>
      </a-table-column>
    </a-table>
  </CommonModal>

  <!-- 添加/编辑团队弹窗 -->
  <CommonModal
    v-if="showAddTeamDialog"
    :title="editTeamId ? t('item.edit_team') : t('item.add_team')"
    :showCancel="true"
    :showOk="true"
    width="400px"
    @close="handleCloseAddTeam"
    @confirm="handleSubmitAddTeam"
  >
    <a-form layout="vertical">
      <a-form-item :label="t('item.team_name')">
        <CommonInput
          v-model="teamForm.team_name"
          :placeholder="t('item.team_name')"
        />
      </a-form-item>
    </a-form>
  </CommonModal>

  <!-- 转让团队弹窗 -->
  <CommonModal
    v-if="showAttornDialog"
    :title="t('item.attorn_team')"
    :showCancel="true"
    :showOk="true"
    width="400px"
    @close="handleCloseAttorn"
    @confirm="handleSubmitAttorn"
  >
    <a-form layout="vertical">
      <a-form-item :label="t('item.attorn_username')">
        <CommonInput
          v-model="attornForm.username"
          :placeholder="t('item.attorn_username')"
          auto-complete="new-password"
        />
      </a-form-item>
      <a-form-item :label="t('item.input_login_password')">
        <CommonInput
          v-model="attornForm.password"
          type="password"
          :placeholder="t('item.input_login_password')"
          auto-complete="new-password"
        />
      </a-form-item>
    </a-form>
    <p class="tips-text">
      {{ t('item.attornTeamTips') }}
    </p>
  </CommonModal>

  <!-- 团队成员管理 -->
  <TeamMember
    v-if="showTeamMember"
    :team_id="currentOperationRow.id"
    :team_manage="currentOperationRow.team_manage"
    :callback="handleTeamMemberCallback"
  />

  <!-- 团队项目管理 -->
  <TeamItem
    v-if="showTeamItem"
    :team_id="currentOperationRow.id"
    :team_manage="currentOperationRow.team_manage"
    :callback="handleTeamItemCallback"
  />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonInput from '@/components/CommonInput.vue'
import TeamMember from './Member.vue'
import TeamItem from './Item.vue'
import ConfirmModal from '@/components/ConfirmModal'
import {
  getTeamList,
  saveTeam,
  deleteTeam as deleteTeamApi,
  attornTeam,
  exitTeam
} from '@/models/team'

const { t } = useI18n()

const props = defineProps<{
  callback?: () => void
}>()

// 数据状态
const teamList = ref<any[]>([])
const currentOperationRow = ref<any>({
  id: 0,
  team_manage: 0,
  team_name: ''
})

const editTeamId = ref<string>('')
const showAddTeamDialog = ref(false)
const showAttornDialog = ref(false)
const showTeamMember = ref(false)
const showTeamItem = ref(false)

const teamForm = ref({
  team_name: ''
})

const attornForm = ref({
  team_id: '',
  username: '',
  password: ''
})

// 获取团队列表
const fetchTeamList = async () => {
  try {
    const res = await getTeamList()
    if (res.error_code === 0) {
      teamList.value = res.data || []
    }
  } catch (error) {
    console.error('获取团队列表失败:', error)
  }
}

// 添加团队
const handleAddTeam = () => {
  editTeamId.value = ''
  teamForm.value = { team_name: '' }
  showAddTeamDialog.value = true
}

// 编辑团队
const handleEditTeam = (row: any) => {
  editTeamId.value = row.id
  teamForm.value = { team_name: row.team_name }
  showAddTeamDialog.value = true
}

// 关闭添加/编辑团队弹窗
const handleCloseAddTeam = () => {
  showAddTeamDialog.value = false
  teamForm.value = { team_name: '' }
}

// 提交添加/编辑团队
const handleSubmitAddTeam = async () => {
  try {
    const res = await saveTeam({
      id: editTeamId.value,
      team_name: teamForm.value.team_name
    })

    if (res.error_code === 0) {
      message.success(t('common.op_success'))
      showAddTeamDialog.value = false
      teamForm.value = { team_name: '' }
      editTeamId.value = ''
      fetchTeamList()
    } else {
      message.error(res.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('保存团队失败:', error)
    message.error(t('common.op_failed'))
  }
}

// 删除团队
const handleDeleteTeam = async (id: number) => {
  const confirmed = await ConfirmModal.confirm({
    title: t('item.confirm_delete'),
    content: t('item.confirm_delete_team')
  })

  if (confirmed) {
    try {
      const res = await deleteTeamApi(id)
      if (res.error_code === 0) {
        message.success(t('common.op_success'))
        fetchTeamList()
      } else {
        message.error(res.error_message || t('common.op_failed'))
      }
    } catch (error) {
      console.error('删除团队失败:', error)
      message.error(t('common.op_failed'))
    }
  }
}

// 转让团队
const handleAttornTeam = (row: any) => {
  attornForm.value = {
    team_id: row.id,
    username: '',
    password: ''
  }
  showAttornDialog.value = true
}

// 关闭转让弹窗
const handleCloseAttorn = () => {
  showAttornDialog.value = false
  attornForm.value = { team_id: '', username: '', password: '' }
}

// 提交转让
const handleSubmitAttorn = async () => {
  try {
    const res = await attornTeam(
      attornForm.value.team_id,
      attornForm.value.username,
      attornForm.value.password
    )

    if (res.error_code === 0) {
      message.success(t('common.op_success'))
      showAttornDialog.value = false
      attornForm.value = { team_id: '', username: '', password: '' }
      fetchTeamList()
    } else {
      message.error(res.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('转让团队失败:', error)
    message.error(t('common.op_failed'))
  }
}

// 退出团队
const handleExitTeam = async (id: number) => {
  const confirmed = await ConfirmModal.confirm({
    title: t('item.team_exit_confirm'),
    content: t('item.team_exit_confirm_content')
  })

  if (confirmed) {
    try {
      const res = await exitTeam(id)
      if (res.error_code === 0) {
        message.success(t('common.op_success'))
        fetchTeamList()
      } else {
        message.error(res.error_message || t('common.op_failed'))
      }
    } catch (error) {
      console.error('退出团队失败:', error)
      message.error(t('common.op_failed'))
    }
  }
}

// 点击成员
const handleClickToMember = (row: any) => {
  currentOperationRow.value = row
  showTeamMember.value = true
}

// 点击团队项目
const handleClickToItem = (row: any) => {
  currentOperationRow.value = row
  showTeamItem.value = true
}

// 团队成员回调
const handleTeamMemberCallback = () => {
  showTeamMember.value = false
  fetchTeamList()
}

// 团队项目回调
const handleTeamItemCallback = () => {
  showTeamItem.value = false
  fetchTeamList()
}

// 关闭弹窗
const handleClose = () => {
  if (props.callback) {
    props.callback()
  }
}

onMounted(() => {
  fetchTeamList()
})
</script>

<style scoped lang="scss">
:deep(.ant-table) {
  font-size: 14px;
}

.tips-text {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin-top: 10px;
}
</style>
