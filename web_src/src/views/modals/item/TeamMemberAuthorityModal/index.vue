<template>
  <div class="team-member-authority-modal">
    <CommonModal
      :class="{ show }"
      :title="t('item.adjust_member_authority')"
      :icon="['fas', 'fa-user-cog']"
      maxWidth="900px"
      @close="handleClose"
    >
      <div class="modal-content">
        <CommonTable
          :tableHeader="tableHeader"
          :tableData="teamItemMembers"
          :pagination="false"
          :maxHeight="'400px'"
        >
          <template #cell-member_username="{ row }">{{ row.member_username }}</template>
          <template #cell-name="{ row }">{{ row.name }}</template>
          <template #cell-member_group_id="{ row }">
            <a-select
              size="small"
              v-model:value="row.member_group_id"
              style="width: 100%"
              @change="(value: string) => handleChangeMemberGroup(value, row.id)"
            >
              <a-select-option value="1">{{ t('item.edit_member') }}</a-select-option>
              <a-select-option value="0">{{ t('item.readonly_member') }}</a-select-option>
              <a-select-option value="2">{{ t('item.item_admin') }}</a-select-option>
            </a-select>
          </template>
          <template #cell-cat_ids="{ row }">
            <a-select
              v-if="row.member_group_id <= '1'"
              size="small"
              v-model:value="row.cat_ids"
              mode="multiple"
              style="width: 100%"
              @change="(value: number[]) => handleChangeMemberCats(value, row.id)"
            >
              <a-select-option
                v-for="cat in catalogs"
                :key="cat.cat_id"
                :value="cat.cat_id"
              >
                {{ cat.cat_name }}
              </a-select-option>
            </a-select>
            <span v-else class="all-cats-text">{{ t('item.all_cat') }}</span>
          </template>
          <template #cell-addtime="{ row }">{{ row.addtime }}</template>
        </CommonTable>

        <p class="tips-text">
          {{ t('item.team_member_authority_tips') }}
        </p>
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
import { getTeamItemMember, saveTeamItemMember, getCatalogList } from '@/models/team'

const { t } = useI18n()

const props = defineProps<{
  item_id: string | number
  team_id: number
  onClose: (result: boolean) => void
}>()

const show = ref(false)
const teamItemMembers = ref<any[]>([])
const catalogs = ref<any[]>([])

// 表头定义
const tableHeader = [
  { title: t('item.member_username'), key: 'member_username', width: 150 },
  { title: t('item.name'), key: 'name', width: 150 },
  { title: t('item.member_authority'), key: 'member_group_id', width: 200 },
  { title: t('item.catalog'), key: 'cat_ids', width: 200 },
  { title: t('item.add_time'), key: 'addtime', width: 150 },
]

// 获取团队成员
const fetchTeamItemMembers = async () => {
  try {
    const res = await getTeamItemMember(String(props.item_id), props.team_id)
    if (res.error_code === 0) {
      teamItemMembers.value = (res.data || []).map((m: any) => ({
        ...m,
        cat_ids: Array.isArray(m.cat_ids) ? m.cat_ids.map((v: string) => Number(v)) : []
      }))
    }
  } catch (error) {
    console.error('获取团队成员失败:', error)
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

// 关闭
const handleClose = () => {
  props.onClose(true)
}

onMounted(() => {
  show.value = true
  fetchTeamItemMembers()
  fetchCatalogs()
})
</script>

<style scoped lang="scss">
.team-member-authority-modal {
  :deep(.common-modal .modal-content) {
    max-width: 900px;
  }
}

.modal-content {
  max-height: calc(100vh - 200px);
  overflow-y: auto;
  padding: 20px;
}

.tips-text {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin: 16px 0;
}

.footer-buttons {
  display: flex;
  justify-content: center;
  align-items: center;

  :deep(.common-button) {
    width: 160px;
  }
}

.all-cats-text {
  color: var(--color-text-secondary);
  font-size: 13px;
}

:deep(.ant-select) {
  .ant-select-selector {
    background-color: var(--color-default);
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

[data-theme="dark"] {
  .modal-content {
    background-color: transparent;
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
      border-color: var(--color-active);
    }
  }
}
</style>
