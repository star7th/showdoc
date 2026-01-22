<template>
  <CommonModal
    :show="true"
    :title="t('item.manage_members')"
    :showCancel="false"
    :showOk="false"
    @close="handleClose"
  >
    <template #extraButtons>
      <CommonButton
        v-if="team_manage == 1 || team_manage == 2"
        theme="dark"
        @click="handleAddTeamMember"
      >
        <i class="fas fa-plus mr-2"></i>
        {{ t('item.add_member') }}
      </CommonButton>
    </template>

    <a-table
      :dataSource="memberList"
      :pagination="false"
      :emptyText="t('item.empty_team_member_tips')"
    >
      <a-table-column key="member_username" :title="t('item.member_username')">
        <template #default="{ record }">{{ record.member_username }}</template>
      </a-table-column>
      <a-table-column key="team_member_group_id" :title="t('item.member_authority')">
        <template #default="{ record }">
          {{ record.team_member_group_id == 2 ? t('item.team_admin') : t('item.ordinary_member') }}
        </template>
      </a-table-column>
      <a-table-column key="name" :title="t('item.name')">
        <template #default="{ record }">{{ record.name }}</template>
      </a-table-column>
      <a-table-column key="addtime" :title="t('item.addtime')">
        <template #default="{ record }">{{ record.addtime }}</template>
      </a-table-column>
      <a-table-column key="operation" :title="t('item.operation')">
        <template #default="{ record }">
          <a
            v-if="team_manage == 1 || team_manage == 2"
            @click="handleDeleteTeamMember(record.id)"
          >
            {{ t('common.delete') }}
          </a>
        </template>
      </a-table-column>
    </a-table>
  </CommonModal>

  <!-- 添加团队成员弹窗 -->
  <CommonModal
    v-if="showAddMember"
    :title="t('item.add_member')"
    :showCancel="true"
    :showOk="true"
    width="500px"
    @close="handleCloseAddMember"
    @confirm="handleSubmitAddMember"
  >
    <a-form layout="vertical">
      <a-form-item :label="t('item.member_username')">
        <CommonSelector
          v-model:value="memberForm.member_username"
          :options="memberOptions"
          :placeholder="t('item.search_member_placeholder')"
          :loading="searchLoading"
          :multiple="true"
          :show-search="true"
        />
      </a-form-item>

      <a-form-item>
        <a-radio-group v-model:value="memberForm.team_member_group_id">
          <a-radio value="1">{{ t('item.ordinary_member') }}</a-radio>
          <a-radio value="2">{{ t('item.team_admin') }}</a-radio>
        </a-radio-group>
      </a-form-item>
    </a-form>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonInput from '@/components/CommonInput.vue'
import CommonSelector from '@/components/CommonSelector.vue'
import ConfirmModal from '@/components/ConfirmModal'
import AlertModal from '@/components/AlertModal'
import {
  getTeamMemberList,
  saveTeamMember,
  deleteTeamMember as deleteTeamMemberApi
} from '@/models/team'
import { getAllUser } from '@/models/user'

const { t } = useI18n()

const props = defineProps<{
  team_id: number
  team_manage: number
  callback: () => void
}>()

// 数据状态
const memberList = ref<any[]>([])
const memberOptions = ref<any[]>([])
const searchLoading = ref(false)
const showAddMember = ref(false)

const memberForm = ref({
  member_username: [] as string[],
  team_member_group_id: '1'
})

// 获取团队成员列表
const fetchMemberList = async () => {
  try {
    const res = await getTeamMemberList(props.team_id)
    if (res.error_code === 0) {
      memberList.value = res.data || []
    }
  } catch (error) {
    console.error('获取团队成员列表失败:', error)
  }
}

// 获取全站用户列表
const fetchAllUser = async () => {
  searchLoading.value = true
  try {
    const res = await getAllUser({ username: '' })
    if (res.error_code === 0 && res.data) {
      const userInfo = res.data
      const newInfo: any[] = []

      // 过滤掉已经是团队成员的用户
      for (let i = 0; i < userInfo.length; i++) {
        const isMember = memberList.value.some(m => m.member_username === userInfo[i].username)
        if (!isMember) {
          newInfo.push(userInfo[i])
        }
      }

      // 格式化为下拉选项
      memberOptions.value = newInfo.map(user => ({
        label: user.name
          ? `${user.username}(${user.name})`
          : user.username,
        value: user.username
      }))
    }
  } catch (error) {
    console.error('获取全站用户列表失败:', error)
  } finally {
    searchLoading.value = false
  }
}

// 添加团队成员
const handleAddTeamMember = () => {
  memberForm.value = {
    member_username: [],
    team_member_group_id: '1'
  }
  fetchAllUser()
  showAddMember.value = true
}

// 提交添加成员
const handleSubmitAddMember = async () => {
  if (!memberForm.value.member_username || memberForm.value.member_username.length === 0) {
    await AlertModal(t('item.member_username') + t('common.required'))
    return
  }

  try {
    // 批量添加成员，逐个调用接口
    let successCount = 0
    let failCount = 0

    for (const username of memberForm.value.member_username) {
      const res = await saveTeamMember({
        team_id: props.team_id,
        member_username: username,
        team_member_group_id: memberForm.value.team_member_group_id
      })

      if (res.error_code === 0) {
        successCount++
      } else {
        failCount++
        console.error(`添加成员 ${username} 失败:`, res.error_message)
      }
    }

    if (successCount > 0) {
      let messageText = t('common.op_success')
      if (failCount > 0) {
        messageText = `${messageText} (成功${successCount}个，失败${failCount}个)`
      }
      message.success(messageText)
      showAddMember.value = false
      fetchMemberList()
    } else {
      message.error(t('common.op_failed'))
    }
  } catch (error) {
    console.error('添加成员失败:', error)
    message.error(t('common.op_failed'))
  }
}

// 关闭添加成员弹窗
const handleCloseAddMember = () => {
  showAddMember.value = false
}

// 删除团队成员
const handleDeleteTeamMember = async (id: number) => {
  const confirmed = await ConfirmModal(t('item.confirm_delete'), t('item.confirm_delete_member'))

  if (confirmed) {
    try {
      const res = await deleteTeamMemberApi(id)
      if (res.error_code === 0) {
        message.success(t('common.op_success'))
        fetchMemberList()
      } else {
        message.error(res.error_message || t('common.op_failed'))
      }
    } catch (error) {
      console.error('删除成员失败:', error)
      message.error(t('common.op_failed'))
    }
  }
}

// 关闭弹窗
const handleClose = () => {
  props.callback()
}

onMounted(() => {
  fetchMemberList()
})
</script>

<style scoped lang="scss">
:deep(.ant-table) {
  font-size: 14px;
}

.input-hint {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin-top: 6px;
  line-height: 1.5;
}
</style>
