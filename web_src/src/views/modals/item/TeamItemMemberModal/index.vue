<template>
  <div class="team-item-member-modal">
    <CommonModal
      :class="{ show }"
      :title="t('item.adjust_member_authority')"
      :icon="['fas', 'user-shield']"
      width="1000px"
      @close="handleClose"
    >
      <div class="modal-content">
        <div class="action-bar">
          <a @click="handleSetAllRead" class="set-all-read">
            {{ t('item.all_member_read') }}
          </a>
        </div>
        <CommonTable
          :tableHeader="tableHeader"
          :tableData="memberList"
          :loading="loading"
          :emptyText="t('item.team_member_empty_tips')"
        >
          <template #cell-member_group_id="{ row }">
            <a-select
              v-model:value="row.member_group_id"
              size="small"
              style="width: 120px"
              @change="handleChangeMemberGroup(row)"
              :options="authorityOptions"
              :fieldNames="{ label: 'label', value: 'value' }"
            />
          </template>
          <template #cell-cat_ids="{ row }">
            <a-select
              v-if="row.member_group_id <= 1"
              v-model:value="row.cat_ids"
              mode="multiple"
              size="small"
              style="width: 200px"
              @change="handleChangeCats(row)"
            >
              <a-select-option v-for="cat in catalogs" :key="cat.cat_id" :value="cat.cat_id">
                {{ cat.cat_name }}
              </a-select-option>
            </a-select>
            <span v-else class="all-cats-text">{{ t('item.all_cat') }}</span>
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
import Message from '@/components/Message'
import { getTeamItemMember, saveTeamItemMember, getCatalogList } from '@/models/team'

const { t } = useI18n()

const props = defineProps<{
  item_id: string
  team_id: number
  onClose: (result: boolean) => void
}>()

const show = ref(false)
const loading = ref(false)
const memberList = ref<any[]>([])
const catalogs = ref<any[]>([])

// 权限选项
const authorityOptions = computed(() => [
  { label: t('item.readonly_member'), value: 0 },
  { label: t('item.edit_member'), value: 1 },
  { label: t('item.item_admin'), value: 2 }
])

const tableHeader = computed(() => [
  { title: t('item.username'), key: 'member_username', width: 150 },
  { title: t('team.name'), key: 'name', width: 150 },
  { title: t('item.member_authority'), key: 'member_group_id', width: 150 },
  { title: t('item.catalog'), key: 'cat_ids', width: 220 },
  { title: t('item.addtime'), key: 'addtime', width: 180 }
])

// 获取成员列表
const fetchMemberList = async () => {
  loading.value = true
  try {
    const res = await getTeamItemMember(props.item_id, props.team_id)
    if (res.error_code === 0) {
      memberList.value = (res.data || []).map((item: any) => ({
        ...item,
        member_group_id: Number(item.member_group_id), // 转换为整型，确保下拉框能正确回显
        // 兼容 cat_ids 的多种格式：数组、逗号分隔的字符串
        cat_ids: Array.isArray(item.cat_ids)
          ? item.cat_ids.map((v: any) => Number(v))
          : (typeof item.cat_ids === 'string' ? item.cat_ids.split(',').map((v: string) => Number(v.trim())) : [])
      }))
    }
  } catch (error) {
    console.error('获取成员列表失败:', error)
  } finally {
    loading.value = false
  }
}

// 获取目录列表
const fetchCatalogList = async () => {
  try {
    const res = await getCatalogList(props.item_id)
    if (res.error_code === 0) {
      let data = res.data || []

      // 检查 data 是否是数组
      if (!Array.isArray(data)) {
        // 可能数据在其他字段里，比如 items、list 等
        if (Array.isArray(data.items)) {
          data = data.items
        } else if (Array.isArray(data.list)) {
          data = data.list
        }
      }

      // 添加"全部目录"选项
      data.unshift({
        cat_id: 0,
        cat_name: t('item.all_cat')
      })

      catalogs.value = data.map((item: any) => ({
        ...item,
        cat_id: Number(item.cat_id)
      }))
    }
  } catch (error) {
    console.error('获取目录列表失败:', error)
  }
}

// 全部设置为只读
const handleSetAllRead = async () => {
  for (const member of memberList.value) {
    try {
      await saveTeamItemMember({
        id: member.id,
        member_group_id: 0
      })
      // 同时更新前端下拉框的值
      member.member_group_id = 0
    } catch (error) {
      console.error('设置成员为只读失败:', error)
    }
  }
  Message.success(t('item.auth_success'))
}

// 修改成员权限
const handleChangeMemberGroup = async (row: any) => {
  try {
    await saveTeamItemMember({
      id: row.id,
      member_group_id: row.member_group_id
    })
    Message.success(t('item.auth_success'))
  } catch (error) {
    console.error('修改权限失败:', error)
  }
}

// 修改成员目录
const handleChangeCats = async (row: any) => {
  try {
    await saveTeamItemMember({
      id: row.id,
      cat_ids: (row.cat_ids || []).map((v: any) => Number(v)).join(',')
    })
    Message.success(t('item.cat_success'))
  } catch (error) {
    console.error('修改目录失败:', error)
  }
}

const handleClose = () => {
  show.value = false
  setTimeout(() => {
    props.onClose(false)
  }, 300)
}

onMounted(() => {
  fetchMemberList()
  fetchCatalogList()
  setTimeout(() => {
    show.value = true
  })
})
</script>

<style scoped lang="scss">
.modal-content {
  padding: 0;
}

.action-bar {
  padding: 10px 0;
  border-bottom: 1px solid var(--color-interval);
}

.set-all-read {
  color: var(--color-active);
  cursor: pointer;
  font-size: 14px;
  text-decoration: none;

  &:hover {
    color: var(--color-active);
    text-decoration: underline;
  }
}

.all-cats-text {
  color: var(--color-text-secondary);
  font-size: 13px;
}
</style>

