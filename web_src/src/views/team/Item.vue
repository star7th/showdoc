<template>
  <CommonModal
    :show="true"
    :title="t('item.item_manage')"
    :showCancel="false"
    :showOk="false"
    width="800px"
    @close="handleClose"
  >
    <template #extraButtons>
      <CommonButton
        v-if="team_manage > 0"
        type="primary"
        @click="handleBindItem"
      >
        <i class="fas fa-plus mr-2"></i>
        {{ t('item.binding_item') }}
      </CommonButton>
    </template>

    <a-table
      :dataSource="itemList"
      :pagination="false"
      :emptyText="t('item.empty_team_item_tips')"
    >
      <a-table-column key="item_name" :title="t('item.item_name')">
        <template #default="{ record }">{{ record.item_name }}</template>
      </a-table-column>
      <a-table-column key="addtime" :title="t('item.Join_time')">
        <template #default="{ record }">{{ record.addtime }}</template>
      </a-table-column>
      <a-table-column key="operation" :title="t('item.operation')">
        <template #default="{ record }">
          <a @click="handleViewItem(record.item_id)" target="_blank">
            {{ t('item.check_item') }}
          </a>

          <template v-if="team_manage > 0">
            <a-divider type="vertical" />
            <a @click="handleMemberAuthority(record.item_id)">
              {{ t('item.member_authority') }}
            </a>
            <a-divider type="vertical" />
            <a @click="handleUnbindItem(record.id)">
              {{ t('item.unassign') }}
            </a>
          </template>
        </template>
      </a-table-column>
    </a-table>
  </CommonModal>

  <!-- 绑定项目弹窗 -->
  <CommonModal
    v-if="showBindItem"
    :title="t('item.item')"
    :showCancel="true"
    :showOk="true"
    width="400px"
    @close="handleCloseBindItem"
    @confirm="handleSubmitBindItem"
  >
    <a-form layout="vertical">
      <a-form-item :label="t('item.please_choose')">
        <CommonSelector
          v-model:value="bindForm.item_id"
          :multiple="true"
          :placeholder="t('item.please_choose')"
          :options="myItemList"
        />
      </a-form-item>
    </a-form>
    <p>
      <a href="/item/index" target="_blank">
        {{ t('item.go_to_new_an_item') }}
      </a>
    </p>
  </CommonModal>

  <!-- 成员权限弹窗 -->
  <CommonModal
    v-if="showMemberAuthority"
    :title="t('item.adjust_member_authority')"
    :showCancel="false"
    :showOk="false"
    width="800px"
    @close="handleCloseMemberAuthority"
  >
    <p>
      <a @click="handleSetAllMemberRead">
        {{ t('item.all_member_read') }}
      </a>
    </p>
    <a-table
      :dataSource="teamItemMembers"
      :pagination="false"
      :emptyText="t('item.team_member_empty_tips')"
    >
      <a-table-column key="member_username" :title="t('item.username')">
        <template #default="{ record }">{{ record.member_username }}</template>
      </a-table-column>
      <a-table-column key="name" :title="t('item.name')">
        <template #default="{ record }">{{ record.name }}</template>
      </a-table-column>
      <a-table-column key="member_group_id" :title="t('item.authority')" width="130">
        <template #default="{ record }">
          <a-select
            size="small"
            v-model:value="record.member_group_id"
            style="width: 100%"
            @change="(value) => handleChangeMemberGroup(value, record.id)"
          >
            <a-select-option value="1">{{ t('item.edit_member') }}</a-select-option>
            <a-select-option value="0">{{ t('item.readonly_member') }}</a-select-option>
            <a-select-option value="2">{{ t('item.item_admin') }}</a-select-option>
          </a-select>
        </template>
      </a-table-column>
      <a-table-column key="cat_ids" :title="t('item.catalog')" width="200">
        <template #default="{ record }">
          <a-select
            v-if="record.member_group_id <= '1'"
            size="small"
            v-model:value="record.cat_ids"
            mode="multiple"
            style="width: 100%"
            @change="(value) => handleChangeMemberCats(value, record.id)"
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
      </a-table-column>
      <a-table-column key="addtime" :title="t('item.add_time')">
        <template #default="{ record }">{{ record.addtime }}</template>
      </a-table-column>
    </a-table>
    <p class="tips-text">
      {{ t('item.team_member_authority_tips') }}
    </p>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import { useRouter } from 'vue-router'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonSelector from '@/components/CommonSelector.vue'
import { getTeamItemListByTeam, saveTeamItem, deleteTeamItem, getTeamItemMember, saveTeamItemMember, getCatalogList } from '@/models/team'
import { getMyList } from '@/models/item'

const { t } = useI18n()
const router = useRouter()

const props = defineProps<{
  team_id: number
  team_manage: number
  callback: () => void
}>()

// 数据状态
const itemList = ref<any[]>([])
const myItemList = ref<any[]>([])
const teamItemMembers = ref<any[]>([])
const catalogs = ref<any[]>([])

const showBindItem = ref(false)
const showMemberAuthority = ref(false)

const bindForm = ref({
  item_id: []
})

const currentItemId = ref(0)

// 转换项目列表格式
const myItemListOptions = computed(() => {
  return myItemList.value.map(item => ({
    label: item.item_name,
    value: item.item_id
  }))
})

// 获取团队项目列表
const fetchItemList = async () => {
  try {
    const res = await getTeamItemListByTeam(props.team_id)
    if (res.error_code === 0) {
      itemList.value = res.data || []
    }
  } catch (error) {
    console.error('获取团队项目列表失败:', error)
  }
}

// 获取我的项目列表
const fetchMyItemList = async () => {
  try {
    const res = await getMyList(0)
    if (res.error_code === 0) {
      myItemList.value = (res.data || []).filter((item: any) => item.original === 1)
    }
  } catch (error) {
    console.error('获取项目列表失败:', error)
  }
}

// 绑定项目
const handleBindItem = () => {
  bindForm.value = { item_id: [] }
  showBindItem.value = true
}

// 关闭绑定项目弹窗
const handleCloseBindItem = () => {
  showBindItem.value = false
}

// 提交绑定项目
const handleSubmitBindItem = async () => {
  try {
    // 批量绑定
    for (const itemId of bindForm.value.item_id) {
      await saveTeamItem(String(itemId), String(props.team_id))
    }
    message.success(t('common.op_success'))
    showBindItem.value = false
    bindForm.value = { item_id: [] }
    fetchItemList()
  } catch (error) {
    console.error('绑定项目失败:', error)
    message.error(t('common.op_failed'))
  }
}

// 查看项目
const handleViewItem = (itemId: string) => {
  const url = router.resolve({
    path: `/${itemId}`
  }).href
  window.open(url, '_blank')
}

// 解绑项目
const handleUnbindItem = async (id: number) => {
  const confirmed = await ConfirmModal.confirm({
    title: t('item.confirm_unassign'),
    content: t('item.confirm_unassign_item')
  })

  if (confirmed) {
    try {
      const res = await deleteTeamItem(id)
      if (res.error_code === 0) {
        message.success(t('common.op_success'))
        fetchItemList()
      } else {
        message.error(res.error_message || t('common.op_failed'))
      }
    } catch (error) {
      console.error('解绑项目失败:', error)
      message.error(t('common.op_failed'))
    }
  }
}

// 成员权限
const handleMemberAuthority = async (itemId: string) => {
  currentItemId.value = Number(itemId)
  try {
    await fetchCatalogs(itemId)
    const res = await getTeamItemMember(itemId, props.team_id)
    if (res.error_code === 0) {
      teamItemMembers.value = (res.data || []).map((m: any) => ({
        ...m,
        cat_ids: Array.isArray(m.cat_ids) ? m.cat_ids.map((v: any) => Number(v)) : []
      }))
      showMemberAuthority.value = true
    }
  } catch (error) {
    console.error('获取团队成员失败:', error)
  }
}

// 关闭成员权限弹窗
const handleCloseMemberAuthority = () => {
  showMemberAuthority.value = false
}

// 修改成员权限组
const handleChangeMemberGroup = async (value: string, id: number) => {
  try {
    const res = await saveTeamItemMember({
      member_group_id: value,
      id: id
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

// 修改成员目录
const handleChangeMemberCats = async (value: number[], id: number) => {
  try {
    const res = await saveTeamItemMember({
      cat_ids: (value || []).join(','),
      id: id
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

// 一键全部设置为只读
const handleSetAllMemberRead = async () => {
  for (const member of teamItemMembers.value) {
    try {
      await saveTeamItemMember({
        member_group_id: '0',
        id: member.id
      })
      // 重新获取列表
      setTimeout(() => {
        handleMemberAuthority(String(member.item_id))
      }, 500)
    } catch (error) {
      console.error('修改权限失败:', error)
    }
  }
}

// 获取目录列表
const fetchCatalogs = async (itemId: string) => {
  try {
    const res = await getCatalogList(itemId)
    if (res.error_code === 0) {
      const list = res.data || []
      list.unshift({
        cat_id: 0,
        cat_name: t('item.all_cat')
      })
      catalogs.value = list.map((cat: any) => ({
        ...cat,
        cat_id: Number(cat.cat_id)
      }))
    }
  } catch (error) {
    console.error('获取目录列表失败:', error)
  }
}

// 关闭弹窗
const handleClose = () => {
  props.callback()
}

onMounted(() => {
  fetchItemList()
  fetchMyItemList()
})
</script>

<script lang="ts">
import ConfirmModal from '@/components/ConfirmModal'
export default {
  components: { ConfirmModal }
}
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
