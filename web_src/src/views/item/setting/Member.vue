<template>
  <CommonModal
    :show="true"
    :title="t('item.member_manage')"
    :showCancel="false"
    :showOk="false"
    width="800px"
    @close="handleClose"
  >
    <template #extraButtons>
      <CommonButton type="primary" @click="handleAddMember">
        <i class="fas fa-plus mr-2"></i>
        {{ t('item.add_member') }}
      </CommonButton>
      <CommonButton type="primary" @click="handleAddTeam">
        <i class="fas fa-plus mr-2"></i>
        {{ t('item.add_team') }}
      </CommonButton>
    </template>

    <div class="member-manage">
      <h4 v-if="members.length > 0">{{ t('item.item_member') }}</h4>
      <!-- 单个成员列表 -->
      <CommonTable
        v-if="members.length > 0"
        :tableHeader="memberTableHeader"
        :tableData="members"
        :pagination="false"
        :maxHeight="'300px'"
      >
        <template #cell-username="{ row }">{{ row.username }}</template>
        <template #cell-name="{ row }">{{ row.name }}</template>
        <template #cell-addtime="{ row }">{{ row.addtime }}</template>
        <template #cell-authority="{ row }">
          {{ memberGroupText(row.member_group_id, row.cat_name) }}
        </template>
        <template #cell-operation="{ row }">
          <a @click="handleDeleteMember(row.item_member_id)">
            {{ t('common.delete') }}
          </a>
        </template>
      </CommonTable>

      <h4 v-if="teamItems.length > 0">{{ t('item.item_team_info') }}</h4>
      <!-- 团队列表 -->
      <CommonTable
        v-if="teamItems.length > 0"
        :tableHeader="teamTableHeader"
        :tableData="teamItems"
        :pagination="false"
        :maxHeight="'300px'"
      >
        <template #cell-team_name="{ row }">{{ row.team_name }}</template>
        <template #cell-addtime="{ row }">{{ row.addtime }}</template>
        <template #cell-operation="{ row }">
          <a @click="handleTeamMemberAuthority(row.team_id)">
            {{ t('item.member_authority') }}
          </a>
          <a-divider type="vertical" />
          <a @click="handleDeleteTeam(row.id)">
            {{ t('common.delete') }}
          </a>
        </template>
      </CommonTable>

      <p
        v-if="members.length === 0 && teamItems.length === 0"
        class="empty-tips"
      >
        {{ t('item.no_member_tips') }}
      </p>
    </div>
  </CommonModal>

  <!-- 添加单个成员弹窗 -->
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
      <a-form-item :label="t('item.input_target_member')">
        <CommonInput
          v-model="memberForm.username"
          :placeholder="t('item.input_target_member')"
        />
        <a-dropdown
          v-if="myAllList.length > 0"
          trigger="click"
          @click="(e: MouseEvent) => e.preventDefault()"
        >
          <a class="dropdown-link">
            {{ t('item.select_member_before') }}
            <i class="fas fa-arrow-down"></i>
          </a>
          <template #overlay>
            <a-menu @click="handleSelectMember">
              <a-menu-item v-for="member in myAllList" :key="member.username">
                {{ member.username }}
              </a-menu-item>
            </a-menu>
          </template>
        </a-dropdown>
      </a-form-item>

      <a-form-item :label="t('item.authority')">
        <a-radio-group v-model:value="memberForm.member_group_id">
          <a-radio value="1">{{ t('item.edit_member') }}</a-radio>
          <a-radio value="0">{{ t('item.readonly_member') }}</a-radio>
          <a-radio value="2">{{ t('item.item_admin') }}</a-radio>
        </a-radio-group>
      </a-form-item>

      <a-form-item
        v-if="memberForm.member_group_id < '2'"
        :label="t('item.catalog')"
      >
        <a-select
          v-model:value="memberForm.cat_ids"
          mode="multiple"
          :placeholder="t('item.all_cat2')"
          :options="catalogOptions"
        />
      </a-form-item>
    </a-form>

    <p class="tips-text">
      {{ t('item.member_authority_tips') }}
    </p>
  </CommonModal>

  <!-- 添加团队弹窗 -->
  <CommonModal
    v-if="showAddTeam"
    :title="t('item.member_manage')"
    :showCancel="true"
    :showOk="true"
    width="400px"
    @close="handleCloseAddTeam"
    @confirm="handleSubmitAddTeam"
  >
    <template #extraButtons>
      <CommonButton type="primary" @click="handleCreateTeam">
        <i class="fas fa-plus mr-2"></i>
        {{ t('item.go_to_new_an_team') }}
      </CommonButton>
    </template>

    <a-form layout="vertical">
      <a-form-item :label="t('item.c_team')">
        <CommonSelector
          v-model:value="teamForm.team_id"
          :placeholder="t('item.please_choose')"
          :options="teamOptions"
        />
      </a-form-item>
    </a-form>
  </CommonModal>

  <!-- 团队成员权限弹窗 -->
  <CommonModal
    v-if="showTeamMember"
    :title="t('item.adjust_member_authority')"
    :showCancel="false"
    :showOk="false"
    width="800px"
    @close="handleCloseTeamMember"
  >
    <CommonTable
      :tableHeader="teamMemberTableHeader"
      :tableData="teamItemMembers"
      :pagination="false"
      :maxHeight="'400px'"
    >
      <template #cell-member_username="{ row }">{{
        row.member_username
      }}</template>
      <template #cell-name="{ row }">{{ row.name }}</template>
      <template #cell-member_group_id="{ row }">
        <a-select
          size="small"
          v-model:value="row.member_group_id"
          style="width: 100%"
          @change="(cat: any) => handleChangeTeamMemberGroup(cat.value, cat.id)"
        >
          <a-select-option value="1">{{
            t('item.edit_member')
          }}</a-select-option>
          <a-select-option value="0">{{
            t('item.readonly_member')
          }}</a-select-option>
          <a-select-option value="2">{{
            t('item.item_admin')
          }}</a-select-option>
        </a-select>
      </template>
      <template #cell-cat_ids="{ row }">
        <a-select
          v-if="row.member_group_id <= '1'"
          size="small"
          v-model:value="row.cat_ids"
          mode="multiple"
          style="width: 100%"
          @change="(cat: any) => handleChangeTeamMemberCats(cat.value, cat.id)"
        >
          <a-select-option
            v-for="cat in catalogs"
            :key="cat.cat_id"
            :value="cat.cat_id"
          >
            {{ cat.cat_name }}
          </a-select-option>
        </a-select>
      </template>
      <template #cell-addtime="{ row }">{{ row.addtime }}</template>
    </CommonTable>

    <p class="tips-text">
      {{ t('item.team_member_authority_tips') }}
    </p>
  </CommonModal>

  <!-- 团队管理弹窗 -->
  <Team v-if="showTeam" :callback="handleTeamCallback" />
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonTable from '@/components/CommonTable.vue'
import CommonInput from '@/components/CommonInput.vue'
import Team from '@/views/team/Index.vue'
import AlertModal from '@/components/AlertModal'
import {
  getMemberList,
  saveMember,
  deleteMember as deleteMemberApi,
  getMyAllList,
} from '@/models/member'
import {
  getTeamList,
  getTeamItemList,
  saveTeamItem,
  deleteTeamItem,
  getTeamItemMember,
  saveTeamItemMember,
  getCatalogList,
} from '@/models/team'
import ConfirmModal from '@/components/ConfirmModal'

const { t } = useI18n()

const props = defineProps<{
  item_id: number
  callback: () => void
}>()

// 数据状态
const members = ref<any[]>([])
const teamItems = ref<any[]>([])
const teamItemMembers = ref<any[]>([])
const teams = ref<any[]>([])
const catalogs = ref<any[]>([])
const myAllList = ref<any[]>([])
const catalogOptions = computed(() => {
  return catalogs.value.map((cat: any) => ({
    label: cat.cat_name,
    value: cat.cat_id,
  }))
})

const teamOptions = computed(() => {
  return teams.value.map((team: any) => ({
    label: team.team_name,
    value: team.id,
  }))
})

// 表头定义
const memberTableHeader = [
  { title: t('item.member_username'), key: 'username', width: 150 },
  { title: t('item.name'), key: 'name', width: 150 },
  { title: t('item.add_time'), key: 'addtime', width: 150 },
  { title: t('item.authority'), key: 'authority', width: 200 },
  { title: t('item.operation'), key: 'operation', width: 120, center: true },
]

const teamTableHeader = [
  { title: t('item.team_name'), key: 'team_name', width: 200 },
  { title: t('item.add_time'), key: 'addtime', width: 150 },
  { title: t('item.operation'), key: 'operation', width: 200, center: true },
]

const teamMemberTableHeader = [
  { title: t('item.member_username'), key: 'member_username', width: 150 },
  { title: t('item.name'), key: 'name', width: 150 },
  { title: t('item.member_authority'), key: 'member_group_id', width: 200 },
  { title: t('item.catalog'), key: 'cat_ids', width: 200 },
  { title: t('item.add_time'), key: 'addtime', width: 150 },
]

const memberForm = ref({
  username: '',
  cat_ids: [],
  member_group_id: '1',
})

const teamForm = ref({
  team_id: '',
})

const showAddMember = ref(false)
const showAddTeam = ref(false)
const showTeamMember = ref(false)
const showTeam = ref(false)

const currentTeamId = ref(0)

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

// 获取团队列表
const fetchTeams = async () => {
  try {
    const res = await getTeamList()
    if (res.error_code === 0) {
      teams.value = res.data || []
    }
  } catch (error) {
    console.error('获取团队列表失败:', error)
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

// 获取目录列表
const fetchCatalogs = async () => {
  try {
    const res = await getCatalogList(String(props.item_id))
    if (res.error_code === 0) {
      const list = res.data || []
      list.unshift({
        cat_id: 0,
        cat_name: t('item.all_cat'),
      })
      catalogs.value = list.map((cat: any) => ({
        ...cat,
        cat_id: Number(cat.cat_id),
      }))
    }
  } catch (error) {
    console.error('获取目录列表失败:', error)
  }
}

// 获取我之前添加过的成员列表
const fetchMyAllList = async () => {
  try {
    const res = await getMyAllList()
    if (res.error_code === 0) {
      myAllList.value = res.data || []
    }
  } catch (error) {
    console.error('获取成员历史记录失败:', error)
  }
}

// 添加成员
const handleAddMember = () => {
  showAddMember.value = true
}

// 提交添加成员
const handleSubmitAddMember = async () => {
  try {
    const res = await saveMember({
      item_id: props.item_id,
      username: memberForm.value.username,
      cat_ids: (memberForm.value.cat_ids || []).join(','),
      member_group_id: memberForm.value.member_group_id,
    })

    if (res.error_code === 0) {
      message.success(t('common.op_success'))
      showAddMember.value = false
      memberForm.value = {
        username: '',
        cat_ids: [],
        member_group_id: '1',
      }
      fetchMembers()
    } else if (res.error_code === 10310) {
      AlertModal(t('item.member_limit_exceeded_with_link'), {
        dangerouslyUseHTMLString: true,
      })
    } else {
      message.error(res.error_message || t('common.op_failed'))
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

// 选择成员
const handleSelectMember = ({ key }: { key: string }) => {
  memberForm.value.username = key
}

// 删除成员
const handleDeleteMember = async (item_member_id: number) => {
  const confirmed = await ConfirmModal({
    title: t('common.confirm_delete'),
    msg: t('item.confirm_delete_member'),
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

// 添加团队
const handleAddTeam = () => {
  showAddTeam.value = true
}

// 提交添加团队
const handleSubmitAddTeam = async () => {
  try {
    const res = await saveTeamItem(
      String(props.item_id),
      teamForm.value.team_id
    )
    if (res.error_code === 0) {
      message.success(t('common.op_success'))
      showAddTeam.value = false
      teamForm.value = { team_id: '' }
      fetchTeamItems()
    } else {
      message.error(res.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('添加团队失败:', error)
    message.error(t('common.op_failed'))
  }
}

// 关闭添加团队弹窗
const handleCloseAddTeam = () => {
  showAddTeam.value = false
}

// 创建新团队
const handleCreateTeam = () => {
  showAddTeam.value = false
  showTeam.value = true
}

// 删除团队
const handleDeleteTeam = async (id: number) => {
  const confirmed = await ConfirmModal({
    title: t('common.confirm_delete'),
    msg: t('item.confirm_delete_team'),
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
  currentTeamId.value = team_id
  try {
    const res = await getTeamItemMember(String(props.item_id), team_id)
    if (res.error_code === 0) {
      teamItemMembers.value = (res.data || []).map((m: any) => ({
        ...m,
        cat_ids: Array.isArray(m.cat_ids)
          ? m.cat_ids.map((v: any) => Number(v))
          : [],
      }))
      showTeamMember.value = true
    }
  } catch (error) {
    console.error('获取团队成员失败:', error)
  }
}

// 关闭团队成员弹窗
const handleCloseTeamMember = () => {
  showTeamMember.value = false
}

// 修改团队成员权限组
const handleChangeTeamMemberGroup = async (value: string, id: number) => {
  try {
    const res = await saveTeamItemMember({
      member_group_id: value,
      id: id,
    })
    if (res.error_code === 0) {
      message.success(t('item.auth_success'))
    } else {
      message.error(res.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('修改权限失败:', error)
    message.error(t('common.op_failed'))
  }
}

// 修改团队成员目录
const handleChangeTeamMemberCats = async (value: number[], id: number) => {
  try {
    const res = await saveTeamItemMember({
      cat_ids: (value || []).join(','),
      id: id,
    })
    if (res.error_code === 0) {
      message.success(t('item.cat_success'))
    } else {
      message.error(res.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('修改目录失败:', error)
    message.error(t('common.op_failed'))
  }
}

// 团队管理回调
const handleTeamCallback = () => {
  showTeam.value = false
  fetchTeams()
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
  props.callback()
}

onMounted(() => {
  fetchMembers()
  fetchTeams()
  fetchTeamItems()
  fetchCatalogs()
  fetchMyAllList()
})
</script>

<style scoped lang="scss">
.member-manage {
  text-align: left;
}

h4 {
  color: var(--color-text-primary);
  margin: 24px 0 12px;
  font-size: 16px;
  font-weight: 600;
}

:deep(.ant-form-item) {
  margin-bottom: 16px;
}

:deep(.ant-radio-group) {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.empty-tips {
  color: var(--color-text-secondary);
  text-align: center;
  padding: 40px 0;
}

.dropdown-link {
  margin-left: 10px;
  cursor: pointer;
  color: var(--color-primary);

  i {
    margin-left: 4px;
  }
}

.tips-text {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin-top: 10px;
}

[data-theme='dark'] {
  .dropdown-link {
    color: var(--color-active);

    &:hover {
      color: var(--color-active);
      opacity: 0.8;
    }
  }

  :deep(.ant-radio-wrapper) {
    .ant-radio-wrapper-in-form-item {
      .ant-radio {
        .ant-radio-inner {
          &::after {
            background-color: var(--color-text-secondary);
          }
        }
      }
    }
  }

  :deep(.ant-select) {
    .ant-select-selector {
      background-color: var(--color-bg-secondary);
      color: var(--color-text-primary);
      border-color: var(--color-border);
    }

    .ant-select-selection-item {
      color: var(--color-text-primary);
    }

    &.ant-select-focused .ant-select-selector {
      border-color: var(--color-primary);
    }
  }
}
</style>
