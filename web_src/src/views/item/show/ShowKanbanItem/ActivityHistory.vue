<template>
  <CommonModal :show="true" :title="t('item.kanban_activity_history')" @close="$emit('close')">
    <div class="activity-body">
      <div class="activity-controls">
        <div class="activity-tabs">
          <span v-for="tab in tabs" :key="tab.key" class="activity-tab" :class="{ active: activeTab === tab.key }" @click="switchTab(tab.key)">{{ tab.label }}</span>
        </div>
        <div class="activity-filter">
          <span class="activity-filter-btn" :class="{ active: filterMode === 'all' }" @click="filterMode = 'all'; page = 1; loadActivities()">{{ t('item.kanban_activity_all') }}</span>
          <span class="activity-filter-btn" :class="{ active: filterMode === 'completed' }" @click="filterMode = 'completed'; page = 1; loadActivities()">{{ t('item.kanban_activity_completed_only') }}</span>
        </div>
      </div>

      <div v-if="customRange" class="activity-custom-range">
        <input type="date" v-model="customStart" class="activity-date-input" />
        <span class="activity-date-sep">{{ t('item.kanban_activity_to') }}</span>
        <input type="date" v-model="customEnd" class="activity-date-input" />
        <CommonButton size="small" type="primary" @click="loadActivities">{{ t('common.confirm') }}</CommonButton>
      </div>

      <div v-if="loading" class="activity-loading">{{ t('common.loading') || '加载中...' }}</div>
      <div v-else-if="activities.length === 0" class="activity-empty">{{ t('item.kanban_activity_no_data') }}</div>
      <div v-else class="activity-list">
        <div v-for="item in activities" :key="item.id" class="activity-item">
          <div class="activity-icon">
            <i :class="getEventIcon(item.event_type)"></i>
          </div>
          <div class="activity-content">
            <div class="activity-desc">
              <span v-if="item.operator_username" class="activity-operator">{{ item.operator_username }}</span>
              <span class="activity-action">{{ t('item.kanban_activity_' + item.event_type) }}</span>
              <span v-if="item.page_id > 0" class="activity-task-title">{{ getTaskTitle(item) }}</span>
              <span v-if="item.event_data?.title && item.page_id == 0" class="activity-list-title">「{{ item.event_data.title }}」</span>
            </div>
            <div class="activity-meta">
              <span class="activity-time">{{ formatTime(item.addtime) }}</span>
              <span v-if="item.event_data?.from_list_title && item.event_data?.to_list_title" class="activity-move-info">
                {{ item.event_data.from_list_title }} → {{ item.event_data.to_list_title }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="total > pageSize" class="activity-pagination">
        <CommonButton size="small" :disabled="page <= 1" @click="page--; loadActivities()">{{ '<' }}</CommonButton>
        <span class="activity-page-info">{{ page }} / {{ totalPages }}</span>
        <CommonButton size="small" :disabled="page >= totalPages" @click="page++; loadActivities()">{{ '>' }}</CommonButton>
      </div>
    </div>

    <template #footer>
      <div class="activity-footer">
        <span v-if="total > 0" class="activity-summary">{{ t('item.kanban_activity_summary', { count: total }) }}</span>
        <div class="activity-footer-actions">
          <CommonButton v-if="total > 0" type="primary" @click="exportExcel">{{ t('item.export') }}</CommonButton>
          <CommonButton @click="$emit('close')">{{ t('common.close') }}</CommonButton>
        </div>
      </div>
    </template>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import request from '@/utils/request'
import { getServerHost, appendUrlParams } from '@/utils/system'
import { getUserInfoFromStorage } from '@/models/user'
import CommonButton from '@/components/CommonButton.vue'
import CommonModal from '@/components/CommonModal.vue'

const props = defineProps<{ itemId: string | number; taskPages: { page_id: string; page_title: string }[] }>()
defineEmits<{ close: [] }>()

const { t } = useI18n()

interface ActivityItem {
  id: number
  item_id: number
  page_id: number
  event_type: string
  event_data: Record<string, any>
  operator_uid: number
  operator_username: string
  addtime: number
}

const activities = ref<ActivityItem[]>([])
const loading = ref(false)
const total = ref(0)
const page = ref(1)
const pageSize = 10
const activeTab = ref('this_week')
const filterMode = ref('all')
const customStart = ref('')
const customEnd = ref('')
const customRange = ref(false)

const totalPages = computed(() => Math.ceil(total.value / pageSize))

const tabs = computed(() => [
  { key: 'this_week', label: t('item.kanban_activity_this_week') },
  { key: 'last_week', label: t('item.kanban_activity_last_week') },
  { key: 'this_month', label: t('item.kanban_activity_this_month') },
  { key: 'last_month', label: t('item.kanban_activity_last_month') },
  { key: 'this_year', label: t('item.kanban_activity_this_year') },
  { key: 'last_year', label: t('item.kanban_activity_last_year') },
  { key: 'custom', label: t('item.kanban_activity_custom') },
])

const switchTab = (key: string) => {
  activeTab.value = key
  customRange.value = key === 'custom'
  page.value = 1
  if (key !== 'custom') {
    loadActivities()
  }
}

const getDateRange = (): { startTime: number; endTime: number } => {
  const now = new Date()
  const tab = activeTab.value

  if (tab === 'this_week') {
    const day = now.getDay() || 7
    const monday = new Date(now)
    monday.setDate(now.getDate() - day + 1)
    monday.setHours(0, 0, 0, 0)
    const sunday = new Date(monday)
    sunday.setDate(monday.getDate() + 6)
    sunday.setHours(23, 59, 59, 999)
    return { startTime: Math.floor(monday.getTime() / 1000), endTime: Math.floor(sunday.getTime() / 1000) }
  }

  if (tab === 'last_week') {
    const day = now.getDay() || 7
    const thisMonday = new Date(now)
    thisMonday.setDate(now.getDate() - day + 1)
    thisMonday.setHours(0, 0, 0, 0)
    const lastMonday = new Date(thisMonday)
    lastMonday.setDate(thisMonday.getDate() - 7)
    const lastSunday = new Date(lastMonday)
    lastSunday.setDate(lastMonday.getDate() + 6)
    lastSunday.setHours(23, 59, 59, 999)
    return { startTime: Math.floor(lastMonday.getTime() / 1000), endTime: Math.floor(lastSunday.getTime() / 1000) }
  }

  if (tab === 'this_month') {
    const start = new Date(now.getFullYear(), now.getMonth(), 1)
    const end = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59)
    return { startTime: Math.floor(start.getTime() / 1000), endTime: Math.floor(end.getTime() / 1000) }
  }

  if (tab === 'last_month') {
    const start = new Date(now.getFullYear(), now.getMonth() - 1, 1)
    const end = new Date(now.getFullYear(), now.getMonth(), 0, 23, 59, 59)
    return { startTime: Math.floor(start.getTime() / 1000), endTime: Math.floor(end.getTime() / 1000) }
  }

  if (tab === 'this_year') {
    const start = new Date(now.getFullYear(), 0, 1)
    const end = new Date(now.getFullYear(), 11, 31, 23, 59, 59)
    return { startTime: Math.floor(start.getTime() / 1000), endTime: Math.floor(end.getTime() / 1000) }
  }

  if (tab === 'last_year') {
    const start = new Date(now.getFullYear() - 1, 0, 1)
    const end = new Date(now.getFullYear() - 1, 11, 31, 23, 59, 59)
    return { startTime: Math.floor(start.getTime() / 1000), endTime: Math.floor(end.getTime() / 1000) }
  }

  if (tab === 'custom' && customStart.value && customEnd.value) {
    const start = new Date(customStart.value)
    const end = new Date(customEnd.value)
    end.setHours(23, 59, 59, 999)
    return { startTime: Math.floor(start.getTime() / 1000), endTime: Math.floor(end.getTime() / 1000) }
  }

  return { startTime: 0, endTime: 0 }
}

const loadActivities = async () => {
  loading.value = true
  try {
    const { startTime, endTime } = getDateRange()
    const params: Record<string, any> = {
      item_id: props.itemId,
      start_time: startTime,
      end_time: endTime,
      page: page.value,
      page_size: pageSize,
    }
    if (filterMode.value === 'completed') {
      params.event_types = JSON.stringify(['task_completed', 'task_uncompleted'])
    }

    const res = await request('/api/kanban/getActivity', params)
    if (res && res.data) {
      activities.value = res.data.activities || []
      total.value = res.data.total || 0
    }
  } finally {
    loading.value = false
  }
}

const getTaskTitle = (item: ActivityItem): string => {
  const found = props.taskPages.find(p => p.page_id == item.page_id)
  return found?.page_title || item.event_data?.title || ''
}

const getEventIcon = (eventType: string): string => {
  const icons: Record<string, string> = {
    task_created: 'fas fa-plus-circle',
    task_completed: 'fas fa-check-circle',
    task_uncompleted: 'fas fa-undo',
    task_moved: 'fas fa-arrows-alt',
    task_deleted: 'fas fa-trash',
    task_updated: 'fas fa-edit',
    list_created: 'fas fa-plus',
    list_updated: 'fas fa-pen',
    list_deleted: 'fas fa-trash-alt',
    list_archived: 'fas fa-archive',
    list_restored: 'fas fa-box-open',
  }
  return icons[eventType] || 'fas fa-circle'
}

const formatTime = (timestamp: number): string => {
  const d = new Date(timestamp * 1000)
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}

const exportExcel = () => {
  const userInfo = getUserInfoFromStorage()
  const userToken = userInfo?.user_token || ''
  const { startTime, endTime } = getDateRange()
  const params: Record<string, string | number> = {
    item_id: props.itemId,
    user_token: userToken,
  }
  if (startTime > 0) params.start_time = startTime
  if (endTime > 0) params.end_time = endTime
  if (filterMode.value === 'completed') {
    params.event_types = JSON.stringify(['task_completed', 'task_uncompleted'])
  }
  const url = appendUrlParams(`${getServerHost()}/api/kanban/exportActivity`, params)
  window.location.href = url
}

onMounted(() => {
  loadActivities()
})
</script>

<style lang="scss" scoped>
.activity-body {
  padding: 16px 24px;
  min-height: 300px;
  max-height: 70vh;
  display: flex;
  flex-direction: column;
}

.activity-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  gap: 12px;
  flex-wrap: wrap;
}

.activity-tabs {
  display: flex;
  gap: 4px;
}

.activity-tab {
  padding: 4px 12px;
  font-size: var(--font-size-s);
  color: var(--color-text-secondary);
  cursor: pointer;
  border-radius: 4px;
  transition: all 0.15s ease;

  &:hover {
    background: var(--hover-overlay);
    color: var(--color-text-primary);
  }

  &.active {
    background: var(--color-active);
    color: #fff;
  }
}

.activity-filter {
  display: flex;
  gap: 4px;
}

.activity-filter-btn {
  padding: 4px 10px;
  font-size: var(--font-size-s);
  color: var(--color-text-secondary);
  cursor: pointer;
  border-radius: 4px;
  border: 1px solid var(--color-interval);
  transition: all 0.15s ease;

  &:hover {
    border-color: var(--color-border);
  }

  &.active {
    color: var(--color-active);
    border-color: var(--color-active);
  }
}

.activity-custom-range {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.activity-date-input {
  padding: 4px 8px;
  border: 1px solid var(--color-interval);
  border-radius: 4px;
  font-size: var(--font-size-s);
  color: var(--color-text-primary);
  background: var(--color-obvious);
}

.activity-date-sep {
  color: var(--color-text-secondary);
  font-size: var(--font-size-s);
}

.activity-loading,
.activity-empty {
  text-align: center;
  padding: 60px 0;
  color: var(--color-text-secondary);
  font-size: var(--font-size-m);
}

.activity-list {
  flex: 1;
  overflow-y: auto;
}

.activity-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid var(--color-border-light);

  &:last-child {
    border-bottom: none;
  }
}

.activity-icon {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg-secondary);
  flex-shrink: 0;

  i {
    font-size: 12px;
    color: var(--color-text-secondary);
  }
}

.activity-content {
  flex: 1;
  min-width: 0;
}

.activity-desc {
  font-size: var(--font-size-m);
  color: var(--color-text-primary);
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.activity-action {
  color: var(--color-text-secondary);
}

.activity-task-title,
.activity-list-title {
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 200px;
}

.activity-meta {
  margin-top: 2px;
  font-size: var(--font-size-s);
  color: var(--color-text-secondary);
  display: flex;
  gap: 12px;
}

.activity-operator {
  font-weight: 500;
  &::after {
    content: '·';
    margin-left: 4px;
  }
}

.activity-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding-top: 12px;
  border-top: 1px solid var(--color-border-light);
}

.activity-page-info {
  font-size: var(--font-size-s);
  color: var(--color-text-secondary);
}

.activity-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.activity-footer-actions {
  display: flex;
  gap: 8px;
}

.activity-summary {
  font-size: var(--font-size-s);
  color: var(--color-text-secondary);
}
</style>
