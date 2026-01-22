<template>
  <CommonModal :show="show" :title="$t('admin.member_manage')" width="700px" @close="handleClose">
    <div class="modal-toolbar">
      <CommonButton
        theme="dark"
        :text="$t('admin.add_member')"
        :leftIcon="['fas', 'plus']"
        @click="showAddMemberModal"
      />
    </div>

    <CommonTab
      :items="memberTabItems"
      :value="memberActiveTab"
      type="segmented"
      @update-value="handleMemberTabChange"
    />

    <!-- 成员列表 -->
    <div v-if="memberActiveTab === 'members'" class="member-content">
      <p v-if="members.length > 0" class="tip-text">{{ $t('admin.member_tip') }}</p>
      <CommonTable
        v-if="members.length > 0"
        :table-header="memberHeader"
        :table-data="members"
        :pagination="false"
        row-key="item_member_id"
        max-height="250px"
      >
        <template #cell-action="{ row }">
          <div class="table-action-buttons">
            <span class="table-action-btn delete" @click="handleDeleteMember(row)">
              <i class="fas fa-trash-alt"></i>
              {{ $t('common.delete') }}
            </span>
          </div>
        </template>
      </CommonTable>
    </div>

    <!-- 团队列表 -->
    <div v-else class="member-content">
      <p v-if="teamList.length > 0" class="tip-text">{{ $t('admin.team_tip') }}</p>
      <p v-else class="tip-text">{{ $t('admin.no_team') }}</p>
      <CommonTable
        v-if="teamList.length > 0"
        :table-header="teamHeader"
        :table-data="teamList"
        :pagination="false"
        row-key="id"
        max-height="250px"
      >
        <template #cell-action="{ row }">
          <div class="table-action-buttons">
            <span class="table-action-btn view" @click="handleShowTeamMembers(row)">
              <i class="fas fa-eye"></i>
              {{ $t('admin.view_member') }}
            </span>
          </div>
        </template>
      </CommonTable>
    </div>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import ConfirmModal from '@/components/ConfirmModal'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonTable from '@/components/CommonTable.vue'
import CommonTab from '@/components/CommonTab.vue'
import { getMemberList, deleteMember as deleteMemberApi } from '@/models/member'
import { getTeamItemList } from '@/models/team'
import addMemberToItemModal from '../AddMemberToItemModal'
import viewTeamMembersModal from '../ViewTeamMembersModal'

const props = defineProps<{
  item_id: number
  onClose: () => void
}>()

const { t } = useI18n()

const show = ref(false)
const memberActiveTab = ref('members')
const members = ref<any[]>([])
const teamList = ref<any[]>([])

const memberTabItems = computed(() => [
  { text: t('admin.project_members'), value: 'members' },
  { text: t('admin.project_teams'), value: 'teams' }
])

const memberHeader = computed(() => [
  { title: t('admin.member_username'), key: 'username', width: 120 },
  { title: t('user.name'), key: 'name', width: 120 },
  { title: t('admin.add_time'), key: 'addtime', width: 140 },
  { title: t('admin.authority'), key: 'member_group', width: 100, center: true },
  { title: t('common.operation'), key: 'action', width: 80, center: true }
])

const teamHeader = computed(() => [
  { title: t('admin.team_name'), key: 'team_name', width: 160 },
  { title: t('admin.creator'), key: 'username', width: 120 },
  { title: t('admin.bind_time'), key: 'addtime', width: 150 },
  { title: t('common.operation'), key: 'action', width: 100, center: true }
])

const handleMemberTabChange = (value: string) => {
  memberActiveTab.value = value
  if (value === 'members' && members.value.length === 0) {
    fetchMembers()
  } else if (value === 'teams' && teamList.value.length === 0) {
    fetchTeamList()
  }
}

const fetchMembers = async () => {
  try {
    const res: any = await getMemberList(String(props.item_id))
    members.value = res.data || []
  } catch (error) {
    console.error('获取成员列表失败:', error)
  }
}

const fetchTeamList = async () => {
  try {
    const res: any = await getTeamItemList(String(props.item_id))
    teamList.value = res.data || []
  } catch (error) {
    console.error('获取团队列表失败:', error)
  }
}

const showAddMemberModal = async () => {
  const username = await addMemberToItemModal({ item_id: props.item_id })
  if (username) {
    fetchMembers()
  }
}

const handleDeleteMember = async (record: any) => {
  const confirmed = await ConfirmModal(t('common.confirm_delete'))
  if (confirmed) {
    try {
      await deleteMemberApi(String(props.item_id), record.item_member_id)
      fetchMembers()
    } catch (error) {
      message.error(t('common.delete_failed'))
    }
  }
}

const handleShowTeamMembers = async (record: any) => {
  await viewTeamMembersModal({ team_id: record.team_id })
}

const handleClose = () => props.onClose()

onMounted(() => {
  show.value = true
  fetchMembers()
  fetchTeamList()
})
</script>

<style lang="scss" scoped>
.modal-toolbar {
  margin-bottom: 16px;
}

.member-content {
  max-height: 400px;
  overflow: auto;
}

.tip-text {
  color: var(--color-text-secondary);
  font-size: 13px;
  line-height: 1.6;
  margin-bottom: 12px;
}
</style>

