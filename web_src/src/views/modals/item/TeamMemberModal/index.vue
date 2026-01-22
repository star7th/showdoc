<template>
  <div class="team-member-modal">
    <CommonModal
      :class="{ show }"
      :title="t('item.manage_members')"
      :icon="['fas', 'user-group']"
      width="1000px"
      :headerButtons="headerButtons"
      @close="handleClose"
    >

      <div class="modal-content">
        <CommonTable
          :tableHeader="tableHeader"
          :tableData="memberList"
          :loading="loading"
        >
          <template #cell-member_username="{ row }">
            <div class="member-info">
              <span>{{ row.member_username }}</span>
            </div>
          </template>
          <template #cell-team_member_group_id="{ row }">
            <a-tag :color="row.team_member_group_id == 2 ? 'blue' : 'default'">
              {{ row.team_member_group_id == 2 ? t('team.team_admin') : t('item.ordinary_member') }}
            </a-tag>
          </template>
          <template #cell-name="{ row }">
            <span>{{ row.name }}</span>
          </template>
          <template #cell-addtime="{ row }">
            <span>{{ row.addtime }}</span>
          </template>
          <template #cell-operation="{ row }">
            <template v-if="team_manage > 0">
              <span class="operation-btn danger" @click="handleDeleteTeamMember(row.id)">
                {{ t('common.delete') }}
              </span>
            </template>
          </template>
        </CommonTable>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import CommonTable from '@/components/CommonTable.vue'
import ConfirmModal from '@/components/ConfirmModal'
import AlertModal from '@/components/AlertModal'
import Message from '@/components/Message'
import AddTeamMemberModalFunc from '@/views/modals/item/AddTeamMemberModal'
import {
  getTeamMemberList,
  deleteTeamMember as deleteTeamMemberApi
} from '@/models/team'

const { t } = useI18n()

const props = defineProps<{
  team_id: number
  team_manage: number
  onClose: (result: boolean) => void
}>()

const show = ref(false)
const loading = ref(false)
const memberList = ref<any[]>([])

// 表头配置
const tableHeader = computed(() => [
  { title: t('item.member_username'), key: 'member_username', width: 200 },
  { title: t('item.member_authority'), key: 'team_member_group_id', width: 150 },
  { title: t('team.name'), key: 'name', width: 150 },
  { title: t('item.addtime'), key: 'addtime', width: 180 },
  { title: t('common.operation'), key: 'operation', width: 100 }
])

// 头部按钮配置
const headerButtons = computed(() => {
  if (props.team_manage > 0) {
    return [
      {
        text: t('item.add_member'),
        icon: ['fas', 'plus'],
        onClick: handleAddTeamMember
      }
    ]
  }
  return []
})

// 获取团队成员列表
const fetchMemberList = async () => {
  loading.value = true
  try {
    const res = await getTeamMemberList(props.team_id)
    if (res.error_code === 0) {
      memberList.value = res.data || []
    }
  } catch (error) {
    console.error('获取团队成员列表失败:', error)
  } finally {
    loading.value = false
  }
}

// 添加团队成员
const handleAddTeamMember = async () => {
  const result = await AddTeamMemberModalFunc(props.team_id)
  if (result) {
    fetchMemberList()
  }
}

// 删除团队成员
const handleDeleteTeamMember = async (id: number) => {
  const confirmed = await ConfirmModal({
    msg: t('item.confirm_delete_member'),
    title: t('item.confirm_delete')
  })

  if (confirmed) {
    try {
      await deleteTeamMemberApi(id)
      Message.success(t('common.op_success'))
      fetchMemberList()
    } catch (error) {
      console.error('删除成员失败:', error)
    }
  }
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
    fetchMemberList()
  })
})
</script>

<style scoped lang="scss">
.modal-content {
  padding: 0;
}

.member-info {
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
  margin-right: 8px;

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
</style>
