<template>
  <div class="public-square-page">
    <!-- 页面头部 -->
    <div class="page-header">
      <div class="header-left">
        <button class="back-button" @click="handleBack">
          <i class="fas fa-arrow-left"></i>
        </button>
        <h2 class="page-title">{{ t('item.public_square') }}</h2>
        <button class="theme-toggle-button" @click="handleToggleTheme">
          <i class="fas fa-circle-half-stroke"></i>
        </button>
      </div>
      <div class="header-right">
        <div class="search-box">
          <a-input
            v-model:value="keyword"
            :placeholder="t('item.search_placeholder')"
            @pressEnter="handleSearch"
          >
            <template #prepend>
              <CommonSelector
                v-model:value="searchType"
                :options="searchTypeOptions"
                style="width: 120px"
              />
            </template>
            <template #suffix>
              <i class="fas fa-search search-icon" @click="handleSearch"></i>
            </template>
          </a-input>
        </div>
      </div>
    </div>

    <!-- 项目列表 -->
    <div class="public-square-container">
      <div class="project-grid" v-if="!loading && items.length > 0">
        <div
          class="project-card"
          v-for="item in items"
          :key="item.item_id"
          @click="handleGoToItem(item)"
        >
          <!-- 卡片头部：图标 + 标题 -->
          <div class="project-card-header">
            <div class="item-icon-wrapper">
              <i v-if="item.item_type == '2'" class="item-icon fas fa-file"></i>
              <i v-else-if="item.item_type == '4'" class="item-icon fas fa-table"></i>
              <i v-else-if="item.item_type == '3'" class="item-icon fas fa-terminal"></i>
              <i v-else-if="item.item_type == '5'" class="item-icon fas fa-chalkboard"></i>
              <i v-else class="item-icon fas fa-book"></i>
            </div>
            <h3 class="project-title">{{ item.item_name }}</h3>
          </div>
          
          <!-- 卡片描述 -->
          <p class="project-desc">
            {{ item.item_description || t('item.no_description_item') }}
          </p>
          
          <!-- 卡片底部：元数据 -->
          <div class="project-card-footer">
            <span class="meta-item">
              <i class="far fa-clock"></i>
              {{ formatTime(item.last_update_time) }}
            </span>
            <div class="item-type-badge" :class="`type-${item.item_type}`">
              {{ getItemTypeName(item.item_type) }}
            </div>
          </div>
        </div>
      </div>

      <!-- 空状态 -->
      <div class="empty-container" v-if="!loading && items.length === 0">
        <a-empty :description="t('item.no_public_items')" />
      </div>

      <!-- 加载状态 -->
      <div class="loading-container" v-if="loading">
        <a-spin size="large" />
      </div>
    </div>

    <!-- 分页 -->
    <div class="pagination-wrapper" v-if="total > 0">
      <a-pagination
        v-model:current="currentPage"
        :page-size="pageSize"
        :total="total"
        :show-size-changer="false"
        :show-total="showTotal"
        @change="handlePageChange"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import CommonSelector from '@/components/CommonSelector.vue'
import { useAppStore } from '@/store/app'
import { checkPublicSquareEnabled, getPublicItems } from '@/models/publicSquare'

const { t } = useI18n()
const router = useRouter()
const appStore = useAppStore()

// 数据状态
const items = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(12)
const keyword = ref('')
const searchType = ref('title')
const searchTypeOptions = computed(() => [
  { label: t('item.search_title'), value: 'title' },
  { label: t('item.search_content'), value: 'content' }
])
const loading = ref(false)
const featureEnabled = ref(false)

// 显示分页总数
const showTotal = (total: number, range: [number, number]) => {
  return t('common.pagination_info', {
    start: range[0],
    end: range[1],
    total: total
  })
}

// 返回上一页
const handleBack = () => {
  router.push('/item/index')
}

// 切换主题
const handleToggleTheme = () => {
  appStore.toggleTheme()
}

// 检查功能是否启用
const checkFeatureEnabled = async () => {
  try {
    const data = await checkPublicSquareEnabled()
    if (data && data.data && data.data.enable === 1) {
      featureEnabled.value = true
      await getList()
    } else {
      message.error(t('item.enable_public_square_tips'))
      router.push('/item/index')
    }
  } catch (error) {
    message.error(t('common.op_failed'))
    router.push('/item/index')
  }
}

// 获取公开项目列表
const getList = async () => {
  if (!featureEnabled.value) return

  loading.value = true
  try {
    const data = await getPublicItems({
      page: currentPage.value,
      count: pageSize.value,
      keyword: keyword.value,
      search_type: searchType.value
    })
    if (data && data.data) {
      items.value = data.data.items || []
      total.value = data.data.total || 0
    }
  } catch (error) {
    console.error('获取公开项目列表失败:', error)
    message.error(t('common.op_failed'))
  } finally {
    loading.value = false
  }
}

// 搜索
const handleSearch = () => {
  currentPage.value = 1
  getList()
}

// 分页变化
const handlePageChange = (page: number) => {
  currentPage.value = page
  getList()
}

// 跳转到项目
const handleGoToItem = (item: any) => {
  const url = '#/' + (item.item_domain || item.item_id)
  window.open(url, '_blank')
}

// 获取项目类型名称
const getItemTypeName = (itemType: string | number) => {
  const typeMap: Record<string, string> = {
    '1': t('item.type_regular'),
    '2': t('item.type_single'),
    '3': t('item.type_runapi'),
    '4': t('item.type_table'),
    '5': t('item.type_whiteboard')
  }
  return typeMap[String(itemType)] || t('item.type_regular')
}

// 格式化时间
const formatTime = (timestamp: number | string) => {
  if (!timestamp) return ''
  
  // 如果是日期字符串格式（如 "2025-01-15 12:00:00"），转换为时间戳
  let timestampValue: number
  if (typeof timestamp === 'string') {
    timestampValue = new Date(timestamp).getTime() / 1000
    // 如果转换失败（返回 NaN），返回空字符串
    if (isNaN(timestampValue)) {
      return ''
    }
  } else {
    timestampValue = timestamp
  }
  
  const now = Date.now() / 1000
  const diff = now - timestampValue
  
  // 1小时内
  if (diff < 3600) {
    const minutes = Math.floor(diff / 60)
    return minutes <= 1 ? t('time.just_now') : t('time.minutes_ago', { n: minutes })
  }
  
  // 24小时内
  if (diff < 86400) {
    const hours = Math.floor(diff / 3600)
    return t('time.hours_ago', { n: hours })
  }
  
  // 7天内
  if (diff < 604800) {
    const days = Math.floor(diff / 86400)
    return t('time.days_ago', { n: days })
  }
  
  // 显示日期
  const date = new Date(timestampValue * 1000)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  
  // 今年内不显示年份
  const currentYear = new Date().getFullYear()
  if (year === currentYear) {
    return `${month}-${day}`
  }
  
  return `${year}-${month}-${day}`
}

onMounted(() => {
  checkFeatureEnabled()
})
</script>

<style scoped lang="scss">
.public-square-page {
  min-height: 100vh;
  background-color: var(--color-bg-secondary);
  padding: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 1400px;
  margin: 0 auto 24px;
  gap: 20px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
  flex: 1;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.back-button {
  width: 40px;
  height: 40px;
  background-color: var(--color-bg-primary);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-xs);
  transition: all 0.15s ease;

  i {
    color: var(--color-text-primary);
  }

  &:hover {
    background-color: var(--hover-overlay);
    border-color: var(--color-active);
  }
}

.page-title {
  font-size: 24px;
  font-weight: 600;
  color: var(--color-text-primary);
  margin: 0;
}

.theme-toggle-button {
  width: 40px;
  height: 40px;
  background-color: var(--color-bg-primary);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-xs);
  transition: all 0.15s ease;

  i {
    color: var(--color-orange);
  }

  &:hover {
    background-color: var(--hover-overlay);
    border-color: var(--color-active);
  }
}

.search-box {
  width: 450px;

  :deep(.ant-input-group-wrapper) {
    box-shadow: var(--shadow-xs);
    border-radius: 8px;
    overflow: hidden;
  }

  :deep(.ant-input) {
    background-color: var(--color-bg-primary);
    color: var(--color-text-primary);
  }

  :deep(.ant-select) {
    background-color: var(--color-bg-primary);
    border-right: 1px solid var(--color-border);

    .ant-select-selector {
      background-color: var(--color-bg-primary);
      color: var(--color-text-primary);
    }
  }
}

.search-icon {
  color: var(--color-text);
  cursor: pointer;
  padding: 8px;
  transition: color 0.15s ease;

  &:hover {
    color: var(--color-active);
  }
}

.public-square-container {
  max-width: 1400px;
  margin: 0 auto;
  min-height: 400px;
}

.project-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.project-card {
  background-color: var(--color-bg-primary);
  border-radius: 8px;
  padding: 20px;
  cursor: pointer;
  transition: all 0.15s ease;
  box-shadow: var(--shadow-xs);
  border: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  min-height: 140px;
  position: relative;
  overflow: hidden;

  &:hover {
    box-shadow: var(--shadow-sm);
    border-color: var(--color-active);
  }
}

// 卡片头部：图标 + 标题
.project-card-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 16px;
}

.item-icon-wrapper {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg-secondary);
  border-radius: 8px;
  transition: all 0.15s ease;

  .item-icon {
    font-size: 18px;
    color: var(--color-text-secondary);
  }
}

.project-card:hover .item-icon-wrapper {
  background: var(--hover-overlay);
}

.project-title {
  font-size: 15px;
  font-weight: 500;
  line-height: 1.5;
  color: var(--color-text-primary);
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  word-break: break-word;
  padding-top: 2px;
}

.project-desc {
  color: var(--color-text-secondary);
  margin-bottom: auto;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  font-size: 14px;
  line-height: 1.6;
}

// 卡片底部：元数据
.project-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 16px;
  padding-top: 12px;
  border-top: 1px solid var(--color-border-light);
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--color-text-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
  
  i {
    font-size: 12px;
    opacity: 0.7;
  }
}

// 项目类型徽章
.item-type-badge {
  flex-shrink: 0;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
  line-height: 1;
  background: var(--color-bg-secondary);
  color: var(--color-text-secondary);
  transition: all 0.15s ease;
  
  &.type-1 {
    background: rgba(0, 123, 255, 0.1);
    color: var(--color-active);
  }
  
  &.type-2 {
    background: rgba(40, 167, 69, 0.1);
    color: var(--color-green);
  }
  
  &.type-3 {
    background: rgba(253, 126, 20, 0.1);
    color: var(--color-orange);
  }
  
  &.type-4 {
    background: rgba(220, 53, 69, 0.1);
    color: var(--color-red);
  }
  
  &.type-5 {
    background: rgba(108, 117, 125, 0.1);
    color: var(--color-text-secondary);
  }
}

.empty-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 300px;

  :deep(.ant-empty-description) {
    color: var(--color-text-secondary);
  }
}

.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 300px;
}

.pagination-wrapper {
  max-width: 1400px;
  margin: 30px auto 0;
  display: flex;
  justify-content: center;

  :deep(.ant-pagination-item) {
    background-color: var(--color-bg-primary);
    color: var(--color-text-primary);
    border-color: var(--color-border);

    &:hover {
      border-color: var(--color-primary);
      a {
        color: var(--color-primary);
      }
    }

    &.ant-pagination-item-active {
      background-color: var(--color-primary);
      border-color: var(--color-primary);

      a {
        color: var(--color-obvious);
      }
    }
  }

  :deep(.ant-pagination-prev),
  :deep(.ant-pagination-next) {
    background-color: var(--color-bg-primary);
    color: var(--color-text-primary);
    border-color: var(--color-border);

    &:hover {
      border-color: var(--color-primary);
      .anticon {
        color: var(--color-primary);
      }
    }
  }

  :deep(.ant-pagination-disabled) {
    opacity: 0.5;
    cursor: not-allowed;

    &:hover {
      border-color: var(--color-border);
      .anticon {
        color: var(--color-text);
      }
    }
  }
}

// 暗黑主题适配
[data-theme='dark'] {
  .back-button {
    box-shadow: var(--shadow-sm);
  }

  .project-card {
    box-shadow: var(--shadow-sm);

    &:hover {
      box-shadow: var(--shadow-base);
    }
  }

  .search-box {
    :deep(.ant-input-group-wrapper) {
      box-shadow: var(--shadow-sm);
    }
  }

  .theme-toggle-button {
    box-shadow: var(--shadow-sm);

    i {
      color: var(--color-orange);
    }

    &:hover {
      background-color: var(--hover-overlay);
      border-color: var(--color-active);
    }
  }

  .item-type-badge {
    &.type-1 {
      background: rgba(74, 158, 255, 0.15);
    }
    
    &.type-2 {
      background: rgba(63, 185, 80, 0.15);
    }
    
    &.type-3 {
      background: rgba(255, 140, 46, 0.15);
    }
    
    &.type-4 {
      background: rgba(248, 81, 73, 0.15);
    }
    
    &.type-5 {
      background: rgba(140, 152, 163, 0.15);
    }
  }

  .pagination-wrapper {
    :deep(.ant-pagination-total-text) {
      color: var(--color-text-secondary);
    }
  }
}

// 响应式布局
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }

  .header-right {
    width: 100%;
  }

  .search-box {
    width: 100%;
  }

  .project-grid {
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 16px;
  }
  
  .project-card {
    padding: 16px;
    min-height: 120px;
  }
  
  .project-card-header {
    gap: 10px;
    margin-bottom: 12px;
  }
  
  .item-icon-wrapper {
    width: 36px;
    height: 36px;
    
    .item-icon {
      font-size: 16px;
    }
  }
  
  .project-title {
    font-size: 14px;
  }
}

@media (max-width: 576px) {
  .project-grid {
    grid-template-columns: 1fr;
  }
}
</style>
