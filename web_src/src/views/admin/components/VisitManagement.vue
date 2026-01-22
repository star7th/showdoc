<template>
  <div class="visit-management">
    <!-- 搜索表单 -->
    <div class="search-section">
      <div class="search-row">
        <div class="search-selector">
          <CommonSelector
            :selector-label="numberTypeLabel"
            :selector-value="numberType"
            :options="numberTypeOptions"
            @update:selector-label="numberTypeLabel = $event"
            @update:selector-value="numberType = $event"
          />
        </div>
        <div class="search-selector">
          <CommonSelector
            :selector-label="dayNumLabel"
            :selector-value="dayNum"
            :options="dayNumOptions"
            @update:selector-label="dayNumLabel = $event"
            @update:selector-value="dayNum = $event"
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
        <CommonButton
          theme="dark"
          :text="$t('common.search')"
          :leftIcon="['fas', 'search']"
          @click="handleSearch"
        />
      </div>
    </div>

    <!-- 访问量列表表格 -->
    <div class="table-section">
      <CommonTable
        :table-header="tableHeader"
        :table-data="itemList"
        :pagination="pagination"
        :loading="loading"
        row-key="item_id"
        max-height="calc(100vh - 280px)"
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
          <span class="link-btn" @click="handleViewItem(row)">
            {{ $t('admin.view') }}
          </span>
        </template>

        <!-- 操作列 -->
        <template #cell-action="{ row }">
          <div class="table-action-buttons">
            <span class="table-action-btn whitelist" @click="handleWhite(row)">
              <i class="fas fa-user-plus"></i>
              {{ $t('admin.add_whitelist') }}
            </span>
            <span class="table-action-btn ban" @click="handleBan(row)">
              <i class="fas fa-ban"></i>
              {{ $t('admin.add_blacklist') }}
            </span>
            <span class="table-action-btn recommend" @click="handleRecommend(row)">
              <i class="fas fa-star"></i>
              {{ $t('admin.recommend_homepage') }}
            </span>
          </div>
        </template>
      </CommonTable>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import { useRouter } from 'vue-router'
import ConfirmModal from '@/components/ConfirmModal'
import CommonButton from '@/components/CommonButton.vue'
import CommonSelector from '@/components/CommonSelector.vue'
import CommonTable from '@/components/CommonTable.vue'
import { getVisitList, banItem, whiteItem, recommendItem } from '@/models/admin'
import addWhitelistModal from '../modals/AddWhitelistModal'
import banItemModal from '../modals/BanItemModal'

const { t } = useI18n()
const router = useRouter()

// 数据状态
const itemList = ref<any[]>([])
const loading = ref(false)

// 搜索条件
const numberType = ref('only_visitor')
const dayNum = ref('0')
const itemType = ref('no_white')

// 搜索器标签
const numberTypeLabel = ref(t('admin.only_visitor'))
const dayNumLabel = ref(t('admin.sort_by_today'))
const itemTypeLabel = ref(t('admin.filter_whitelist'))

// 分页配置
const pagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0
})

// 搜索器选项
const numberTypeOptions = computed(() => [
  { label: t('admin.only_visitor'), value: 'only_visitor' },
  { label: t('admin.all_visit'), value: 'all' }
])

const dayNumOptions = computed(() => [
  { label: t('admin.sort_by_today'), value: '0' },
  { label: t('admin.sort_by_7days'), value: '7' }
])

const itemTypeOptions = computed(() => [
  { label: t('admin.all_items'), value: 'all' },
  { label: t('admin.filter_whitelist'), value: 'no_white' }
])

// 表格头部配置
const tableHeader = computed(() => [
  { title: t('admin.item_name'), key: 'item_name', width: 160 },
  { title: t('admin.item_desc'), key: 'item_description', width: 200 },
  { title: t('admin.privacy'), key: 'password', width: 100, center: true },
  { title: t('admin.access_link'), key: 'item_id', width: 100, center: true },
  { title: t('admin.owner'), key: 'username', width: 140 },
  { title: t('admin.visit_count'), key: 'views', width: 100, center: true },
  { title: t('admin.create_time'), key: 'addtime', width: 160 },
  { title: t('common.operation'), key: 'action', width: 280, center: true }
])

// 方法
const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getVisitList({
      page: pagination.current,
      count: pagination.pageSize,
      number_type: numberType.value,
      day_num: dayNum.value,
      item_type: itemType.value
    })
    itemList.value = res.data.items || []
    pagination.total = res.data.total || 0
  } catch (error) {
    console.error('获取访问列表失败:', error)
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

const handleWhite = async (record: any) => {
  const result = await addWhitelistModal({ item_id: record.item_id, item_name: record.item_name })
  if (result) {
    try {
      await whiteItem({
        item_id: record.item_id,
        remark: result.remark || ''
      })
      message.success(t('admin.add_whitelist_success'))
      fetchList()
    } catch (error) {
      message.error(t('common.op_failed'))
    }
  }
}

const handleBan = async (record: any) => {
  const result = await banItemModal({
    item_id: record.item_id,
    item_name: record.item_name
  })
  if (result) {
    try {
      await banItem({
        item_id: result.item_id,
        remark: result.remark,
        allow_paid_access: result.allow_paid_access ? 1 : 0,
        forbid_visitor: result.forbid_visitor ? 1 : 0,
        forbid_all: result.forbid_all ? 1 : 0
      })
      message.success(t('admin.ban_success'))
      fetchList()
    } catch (error) {
      message.error(t('common.op_failed'))
    }
  }
}

const handleRecommend = async (record: any) => {
  const confirmed = await ConfirmModal(t('admin.confirm_recommend'))
  if (confirmed) {
    try {
      await recommendItem({ item_id: record.item_id })
      message.success(t('common.op_success'))
      fetchList()
    } catch (error) {
      message.error(t('common.op_failed'))
    }
  }
}

onMounted(() => {
  fetchList()
})
</script>

<style lang="scss" scoped>
.visit-management {
  .search-section {
    background: var(--color-bg-secondary);
    padding: 20px;
    border-radius: 8px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 20px;

    .search-row {
      display: flex;
      gap: 12px;
      align-items: center;
      flex-wrap: wrap;

      .search-selector {
        width: 180px;
      }
    }
  }

  .table-section {
    background: var(--color-bg-secondary);
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
      background: var(--color-active);
      color: var(--color-obvious);
    }

    &.private {
      background: var(--color-inactive);
      color: var(--color-text-primary);
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
}
</style>
