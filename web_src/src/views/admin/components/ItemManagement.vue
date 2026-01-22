<template>
  <div class="item-management">
    <!-- Tab切换 -->
    <CommonTab
      :items="mainTabItems"
      :value="activeTab"
      type="segmented"
      @update-value="handleMainTabChange"
    />

    <!-- 搜索表单 -->
    <div class="search-section">
      <div class="search-row">
        <div class="search-input">
          <CommonInput
            v-model="itemName"
            :placeholder="$t('admin.item_name_placeholder')"
          />
        </div>
        <div class="search-selector">
          <CommonSelector
            :selector-label="itemTypeLabel"
            :selector-value="itemType"
            :options="itemTypeOptions"
            @update:selector-label="itemTypeLabel = $event"
            @update:selector-value="itemType = $event"
          />
        </div>
        <div class="search-input">
          <CommonInput
            v-model="ownerName"
            :placeholder="$t('admin.owner_placeholder')"
          />
        </div>
        <div class="search-selector">
          <CommonSelector
            :selector-label="privacyTypeLabel"
            :selector-value="privacyType"
            :options="privacyTypeOptions"
            @update:selector-label="privacyTypeLabel = $event"
            @update:selector-value="privacyType = $event"
          />
        </div>
        <CommonButton
          theme="dark"
          :text="$t('common.search')"
          :leftIcon="['fas', 'search']"
          @click="handleSearch"
        />
      </div>
    </div>

    <!-- 项目列表表格 -->
    <div class="table-section">
      <CommonTable
        :table-header="tableHeader"
        :table-data="itemList"
        :pagination="pagination"
        :loading="loading"
        row-key="item_id"
        max-height="calc(100vh - 350px)"
        @page-change="handleTableChange"
      >
        <!-- 隐私设置列 -->
        <template #cell-password="{ row }">
          <span class="privacy-badge" :class="row.password ? 'private' : 'public'">
            {{ formatPrivacy(row) }}
          </span>
        </template>

        <!-- 访问链接列 -->
        <template #cell-item_id="{ row }">
          <span v-if="activeTab === '0'" class="link-btn" @click="handleViewItem(row)">
            {{ $t('admin.view') }}
          </span>
          <span v-else class="deleted-text">{{ $t('admin.deleted') }}</span>
        </template>

        <!-- 成员数量列 -->
        <template #cell-member_num="{ row }">
          <span class="member-btn" @click="handleShowMember(row)">
            {{ row.member_num }}
          </span>
        </template>

        <!-- 操作列 -->
        <template #cell-action="{ row }">
          <div class="table-action-buttons" v-if="activeTab === '0'">
            <span class="table-action-btn attorn" @click="handleAttorn(row)">
              <i class="fas fa-exchange-alt"></i>
              {{ $t('admin.attorn') }}
            </span>
            <span class="table-action-btn member" @click="handleShowMember(row)">
              <i class="fas fa-users"></i>
              {{ $t('admin.member_manage') }}
            </span>
            <span class="table-action-btn delete" @click="handleDelete(row)">
              <i class="fas fa-trash-alt"></i>
              {{ $t('common.delete') }}
            </span>
          </div>
          <div class="table-action-buttons" v-else>
            <span class="table-action-btn recover" @click="handleRecover(row)">
              <i class="fas fa-undo"></i>
              {{ $t('admin.recover') }}
            </span>
            <span class="table-action-btn hard-delete" @click="handleHardDelete(row)">
              <i class="fas fa-trash"></i>
              {{ $t('admin.permanent_delete') }}
            </span>
          </div>
        </template>
      </CommonTable>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import ConfirmModal from '@/components/ConfirmModal'
import CommonButton from '@/components/CommonButton.vue'
import CommonInput from '@/components/CommonInput.vue'
import CommonSelector from '@/components/CommonSelector.vue'
import CommonTable from '@/components/CommonTable.vue'
import {
  getAdminItemList,
  deleteItem,
  attornItem,
  recoverItem,
  hardDeleteItem
} from '@/models/admin'
import attornItemModal from '../modals/AttornItemModal'
import memberManageModal from '../modals/MemberManageModal'

const { t } = useI18n()
const router = useRouter()

// 数据状态
const itemList = ref<any[]>([])
const loading = ref(false)
const activeTab = ref('0')

// Tab 选项
const mainTabItems = computed(() => [
  { text: t('admin.item_tab_list'), value: '0' },
  { text: t('admin.item_tab_deleted'), value: '1' }
])

const handleMainTabChange = (value: string) => {
  activeTab.value = value
  handleSearch()
}

// 搜索条件
const itemName = ref('')
const ownerName = ref('')
const itemType = ref('-1')
const privacyType = ref('1')

// 搜索器标签
const itemTypeLabel = ref(t('admin.all_item_type'))
const privacyTypeLabel = ref(t('admin.all_privacy_type'))

// 分页配置
const pagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0
})

// 搜索器选项
const itemTypeOptions = computed(() => [
  { label: t('admin.all_item_type'), value: '-1' },
  { label: t('admin.normal_item'), value: '1' },
  { label: t('admin.single_page_item'), value: '2' },
  { label: t('admin.runapi_item'), value: '3' },
  { label: t('admin.table_item'), value: '4' }
])

const privacyTypeOptions = computed(() => [
  { label: t('admin.all_privacy_type'), value: '1' },
  { label: t('admin.public_items'), value: '2' },
  { label: t('admin.private_items'), value: '3' }
])

// 表格头部配置
const tableHeader = computed(() => [
  { title: t('admin.item_name'), key: 'item_name', width: 160 },
  { title: t('admin.item_desc'), key: 'item_description', width: 180 },
  { title: t('admin.privacy'), key: 'password', width: 150, center: true },
  { title: t('admin.access_link'), key: 'item_id', width: 130, center: true },
  { title: t('admin.owner'), key: 'username', width: 140 },
  { title: t('admin.member_count'), key: 'member_num', width: 100, center: true },
  { title: t('admin.create_time'), key: 'addtime', width: 160 },
  ...(activeTab.value === '1' ? [{ title: t('admin.delete_time'), key: 'del_time', width: 160 }] : []),
  { title: t('common.operation'), key: 'action', width: 220, center: true }
])


// 方法
const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getAdminItemList({
      item_name: itemName.value,
      username: ownerName.value,
      page: pagination.current,
      count: pagination.pageSize,
      item_type: itemType.value,
      privacy_type: privacyType.value,
      is_del: activeTab.value
    })
    itemList.value = res.data.items || []
    pagination.total = res.data.total || 0
  } catch (error) {
    console.error('获取项目列表失败:', error)
  } finally {
    loading.value = false
  }
}

const formatPrivacy = (record: any) => {
  return record.password ? t('admin.password_access') : t('admin.public_access')
}

const handleSearch = () => {
  pagination.current = 1
  fetchList()
}

const handleTableChange = (page: number, pageSize: number) => {
  pagination.current = page
  pagination.pageSize = pageSize
  fetchList()
}

const handleViewItem = (record: any) => {
  // 使用 path 而不是 name+params，避免参数解析问题
  const url = router.resolve({
    path: `/${record.item_id}`
  }).href
  window.open(url, '_blank')
}

const handleAttorn = async (record: any) => {
  const result = await attornItemModal({ item_id: record.item_id })
  if (result && result.username) {
    try {
      await attornItem({
        item_id: record.item_id,
        username: result.username
      })
      message.success(t('common.save_success'))
      fetchList()
    } catch (error) {
      message.error(t('common.save_failed'))
    }
  }
}

const handleDelete = async (record: any) => {
  const confirmed = await ConfirmModal(t('common.confirm_delete'))
  if (confirmed) {
    try {
      await deleteItem({ item_id: record.item_id })
      message.success(t('common.delete_success'))
      fetchList()
    } catch (error) {
      message.error(t('common.delete_failed'))
    }
  }
}

const handleRecover = async (record: any) => {
  const confirmed = await ConfirmModal(t('admin.confirm_recover'))
  if (confirmed) {
    try {
      await recoverItem({ item_id: record.item_id })
      message.success(t('admin.recover_success'))
      fetchList()
    } catch (error) {
      message.error(t('common.op_failed'))
    }
  }
}

const handleHardDelete = async (record: any) => {
  const confirmed = await ConfirmModal({
    title: t('admin.confirm_permanent_delete'),
    msg: t('admin.permanent_delete_warning')
  })
  if (confirmed) {
    try {
      await hardDeleteItem({ item_id: record.item_id })
      message.success(t('admin.permanent_delete_success'))
      fetchList()
    } catch (error) {
      message.error(t('common.op_failed'))
    }
  }
}

const handleShowMember = async (record: any) => {
  await memberManageModal({ item_id: record.item_id })
}


onMounted(() => {
  fetchList()
})
</script>

<style lang="scss" scoped>
.item-management {
  .search-section {
    background: var(--color-obvious);
    padding: 20px;
    border-radius: 8px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 20px;

    .search-row {
      display: flex;
      gap: 12px;
      align-items: center;
      flex-wrap: wrap;

      .search-input {
        flex: 1;
        min-width: 180px;
        max-width: 220px;
      }

      .search-selector {
        width: 180px;
      }
    }
  }

  .table-section {
    background: var(--color-obvious);
    padding: 20px;
    border-radius: 8px;
    box-shadow: var(--shadow-sm);
  }

  .privacy-badge {
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;

    &.public {
      background: var(--color-inactive);
      color: var(--color-primary);
    }

    &.private {
      background: var(--hover-overlay);
      color: var(--color-primary);
    }
  }

  .link-btn {
    color: var(--color-active);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;

    &:hover {
      background: var(--hover-overlay);
    }
  }

  .deleted-text {
    color: var(--color-grey);
    font-size: 13px;
  }

  .member-btn {
    color: var(--color-active);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;

    &:hover {
      background: var(--hover-overlay);
    }
  }
}

// 暗黑主题适配
[data-theme="dark"] {
  .item-management {
    .search-section,
    .table-section {
      background: var(--color-secondary);
      box-shadow: var(--shadow-sm);
    }

    .privacy-badge {
      &.public {
        background: var(--color-default);
        color: var(--color-primary);
      }

      &.private {
        background: var(--color-default);
        color: var(--color-primary);
      }
    }

    .action-buttons {
      .action-btn {
        background: var(--color-default);
        color: var(--color-primary);
        border-color: var(--color-inactive);

        &:hover {
          background: var(--hover-overlay);
        }

        &.whitelist:hover {
          background: var(--color-green);
          color: var(--color-obvious);
        }

        &.recommend:hover {
          background: var(--color-orange);
          color: var(--color-obvious);
        }

        &.recover:hover {
          background: var(--color-green);
          color: var(--color-obvious);
        }

        &.hard-delete:hover {
          background: var(--color-red);
          color: var(--color-obvious);
        }
      }
    }

    .tip-text {
      color: var(--color-grey);
    }
  }
}
</style>
