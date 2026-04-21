<template>
  <div class="kanban-list" :data-list-id="list.id">
    <div class="list-header">
      <div class="list-title" v-if="!isRenaming">{{ list.title }}</div>
      <input
        v-else
        v-model="newListName"
        class="list-rename-input"
        @pressEnter="confirmRename"
        @blur="confirmRename"
      />
      <span class="task-count">{{ filteredTaskIds.length }}</span>
      <span v-if="isEditable && !isBatchMode" class="list-header-action" @click="enterBatchMode">
        <i class="fas fa-check-double"></i>
      </span>
      <CommonDropdownMenu
        v-if="isEditable"
        :list="listMenuItems"
        trigger="click"
        placement="bottom"
        @select="handleListMenuSelect"
      >
        <span class="list-menu-trigger"><i class="fas fa-ellipsis"></i></span>
      </CommonDropdownMenu>
    </div>

    <div v-if="isBatchMode" class="batch-bar">
      <a-checkbox :checked="isAllSelected" :indeterminate="selectedTasks.length > 0 && selectedTasks.length < filteredTaskIds.length" @change="toggleSelectAll" />
      <span class="batch-count">{{ selectedTasks.length }}/{{ filteredTaskIds.length }}</span>
      <span class="batch-action" :class="{ disabled: !selectedTasks.length }" @click="selectedTasks.length && emit('batchMove', selectedTasks)">
        <i class="fas fa-arrows-alt"></i>{{ $t('item.kanban_batch_move') }}
      </span>
      <span class="batch-action" :class="{ disabled: !selectedTasks.length }" @click="selectedTasks.length && emit('batchComplete', selectedTasks)">
        <i class="fas fa-check-double"></i>{{ $t('item.kanban_batch_complete') }}
      </span>
      <span class="batch-action danger" :class="{ disabled: !selectedTasks.length }" @click="selectedTasks.length && handleBatchDelete()">
        <i class="fas fa-trash"></i>{{ $t('common.delete') }}
      </span>
      <span class="batch-close" @click="exitBatchMode"><i class="fas fa-times"></i></span>
    </div>

    <div class="list-tasks" :data-list-id="list.id" ref="tasksContainerRef">
      <div v-for="taskId in filteredTaskIds" :key="taskId" :data-task-id="taskId" class="task-wrapper" :class="{ 'task-selected': selectedTasks.includes(taskId), 'task-highlight': highlightedTaskIds?.has(taskId) }" @click.ctrl="toggleSelect(taskId)" @click.meta="toggleSelect(taskId)">
        <div v-if="isBatchMode" class="task-checkbox" @click.stop>
          <a-checkbox :checked="selectedTasks.includes(taskId)" @change="toggleSelect(taskId)" />
        </div>
        <KanbanTask
          :page-id="taskId"
          :task-page="taskPages.find(p => p.page_id == taskId)"
          :task-data="taskDetailsCache[taskId]"
          :is-editable="isEditable"
          @click="handleTaskClick(taskId)"
        @delete="$emit('deleteTask', taskId)"
        @complete="$emit('completeTask', taskId)"
        @copy="$emit('copyTask', taskId)"
        />
      </div>
      <div v-if="isEditable" class="add-task-btn" @click="openAddTaskModal">
        <i class="fas fa-plus"></i>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import Sortable from 'sortablejs'
import KanbanTask from './KanbanTask.vue'
import PromptModal from '@/components/PromptModal'
import ConfirmModal from '@/components/ConfirmModal'
import CommonDropdownMenu from '@/components/CommonDropdownMenu.vue'
import type { DropdownMenuItem } from '@/components/CommonDropdownMenu.vue'
import type { KanbanList as KanbanListType, KanbanTaskData, KanbanBoardData, TaskPageInfo } from './types'

interface Props {
  list: KanbanListType
  taskIds: string[]
  taskPages: TaskPageInfo[]
  taskDetailsCache: Record<string, KanbanTaskData>
  isEditable: boolean
  searchKeyword: string
  filters: any
  isLastList: boolean
  highlightedTaskIds: Set<string>
}

const props = defineProps<Props>()
const emit = defineEmits<{
  saveBoard: [data: KanbanBoardData]
  moveTask: [pageId: string, fromListId: string, toListId: string, toIndex: number]
  createTask: [listId: string, title: string]
  openTaskDetail: [pageId: string]
  copyTask: [pageId: string]
  deleteTask: [pageId: string]
  completeTask: [pageId: string]
  addList: [title: string, position: 'left' | 'right']
  updateList: [listId: string, title: string]
  deleteList: [listId: string]
  archiveList: [listId: string]
  batchMove: [pageIds: string[]]
  batchComplete: [pageIds: string[]]
  batchDelete: [pageIds: string[]]
}>()

const { t } = useI18n()
const isRenaming = ref(false)
const newListName = ref('')
const tasksContainerRef = ref<HTMLElement | null>(null)
let taskSortable: Sortable | null = null

const isTaskCompleted = (id: string): boolean => {
  const detail = props.taskDetailsCache[id]
  if (detail) return !!detail.completed
  const page = props.taskPages.find(p => p.page_id == id)
  if (page?.ext_info) {
    try { return !!JSON.parse(page.ext_info).completed } catch { return false }
  }
  return false
}

const getTaskTags = (id: string) => {
  const detail = props.taskDetailsCache[id]
  if (detail?.tags?.length) return detail.tags
  const page = props.taskPages.find(p => p.page_id == id)
  if (page?.ext_info) {
    try {
      const info = JSON.parse(page.ext_info)
      if (Array.isArray(info.tags) && info.tags.length) return info.tags
    } catch { /* ignore */ }
  }
  return []
}

const filteredTaskIds = computed(() => {
  let ids = props.taskIds
  const showCompleted = props.filters?.show_completed
  if (!showCompleted) {
    ids = ids.filter(id => !isTaskCompleted(id))
  }
  if (props.searchKeyword) {
    ids = ids.filter(id => {
      const page = props.taskPages.find(p => p.page_id == id)
      return page?.page_title?.toLowerCase().includes(props.searchKeyword.toLowerCase())
    })
  }
  if (props.filters && Object.keys(props.filters).length > 0) {
    ids = ids.filter(id => {
      const detail = props.taskDetailsCache[id]
      if (!detail) return true
      if (props.filters.assignee_uid !== undefined) {
        if (props.filters.assignee_uid === '' && detail.assignee_uid) return false
        if (props.filters.assignee_uid !== '' && detail.assignee_uid !== props.filters.assignee_uid) return false
      }
      if (props.filters.creator_uid && detail.creator_uid !== props.filters.creator_uid) return false
      if (props.filters.priority && detail.priority !== props.filters.priority) return false
      if (props.filters.tag) {
        const tags = getTaskTags(id)
        const hasTag = tags.some(t => t.text === props.filters.tag)
        if (!hasTag) return false
      }
      if (props.filters.due_date_start || props.filters.due_date_end) {
        if (!detail.due_date) return false
        if (props.filters.due_date_start && detail.due_date < props.filters.due_date_start) return false
        if (props.filters.due_date_end && detail.due_date > props.filters.due_date_end) return false
      }
      if (props.filters.no_due_date && detail.due_date) return false
      return true
    })
  }
  return ids
})

const openAddTaskModal = async () => {
  const title = await PromptModal(t('item.kanban_add_task'), '', t('item.kanban_task_title'))
  if (title) {
    emit('createTask', props.list.id, title)
  }
}

const listMenuItems = computed<DropdownMenuItem[]>(() => [
  { icon: ['fas', 'fa-pen'], text: t('item.kanban_rename_list'), value: 'rename' },
  { icon: ['fas', 'fa-arrow-left'], text: t('item.kanban_add_list_left'), value: 'addLeft' },
  { icon: ['fas', 'fa-arrow-right'], text: t('item.kanban_add_list_right'), value: 'addRight' },
  { icon: ['fas', 'fa-check-double'], text: t('item.kanban_complete_list_tasks'), value: 'completeAll' },
  { icon: ['fas', 'fa-box-archive'], text: t('item.kanban_archive_list'), value: 'archive' },
  { icon: ['fas', 'fa-trash'], text: t('item.kanban_delete_list'), value: 'delete' },
])

const handleListMenuSelect = async (item: DropdownMenuItem) => {
  switch (item.value) {
    case 'rename':
      newListName.value = props.list.title
      isRenaming.value = true
      break
    case 'addLeft':
      promptAddList('left')
      break
    case 'addRight':
      promptAddList('right')
      break
    case 'completeAll':
      props.taskIds.forEach(id => emit('completeTask', id))
      break
    case 'archive':
      emit('archiveList', props.list.id)
      break
    case 'delete':
      if (props.isLastList) {
        message.warning(t('item.kanban_cannot_delete_last_list'))
        return
      }
      const count = props.taskIds.length
      const confirmed = await ConfirmModal(
        count > 0
          ? t('item.kanban_delete_list_confirm').replace('{n}', String(count))
          : t('item.kanban_delete_list_confirm_empty')
      )
      if (confirmed) emit('deleteList', props.list.id)
      break
  }
}

const confirmRename = () => {
  const name = newListName.value.trim()
  if (name && name !== props.list.title) {
    emit('updateList', props.list.id, name)
  }
  isRenaming.value = false
}

const promptAddList = async (pos: 'left' | 'right') => {
  const title = await PromptModal(t('item.kanban_add_list'), '', t('item.kanban_list_name'))
  if (title) {
    emit('addList', title, pos)
  }
}

const isBatchMode = ref(false)
const selectedTasks = ref<string[]>([])
const isAllSelected = computed(() => filteredTaskIds.value.length > 0 && selectedTasks.value.length === filteredTaskIds.value.length)

const enterBatchMode = () => {
  isBatchMode.value = true
  selectedTasks.value = []
  taskSortable?.option('disabled', true)
}

const exitBatchMode = () => {
  isBatchMode.value = false
  selectedTasks.value = []
  taskSortable?.option('disabled', false)
}

const toggleSelect = (taskId: string) => {
  if (!isBatchMode.value) return
  const idx = selectedTasks.value.indexOf(taskId)
  if (idx >= 0) {
    selectedTasks.value.splice(idx, 1)
  } else {
    selectedTasks.value.push(taskId)
  }
}

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedTasks.value = []
  } else {
    selectedTasks.value = [...filteredTaskIds.value]
  }
}

const handleTaskClick = (taskId: string) => {
  if (isBatchMode.value) {
    toggleSelect(taskId)
  } else {
    emit('openTaskDetail', taskId)
  }
}

const handleBatchDelete = async () => {
  if (!selectedTasks.value.length) return
  const confirmed = await ConfirmModal(t('item.kanban_batch_delete_confirm', { n: selectedTasks.value.length }))
  if (!confirmed) return
  emit('batchDelete', [...selectedTasks.value])
  exitBatchMode()
}

const initTaskSortable = () => {
  if (!tasksContainerRef.value || !props.isEditable) return
  taskSortable = new Sortable(tasksContainerRef.value, {
    animation: 150,
    group: 'kanban-tasks',
    draggable: '.task-wrapper',
    ghostClass: 'task-ghost',
    chosenClass: 'task-chosen',
    onEnd: (evt) => {
      const pageId = evt.item.getAttribute('data-task-id') || ''
      const fromListId = evt.from.getAttribute('data-list-id') || ''
      const toListId = evt.to.getAttribute('data-list-id') || ''
      const toIndex = evt.newIndex || 0
      emit('moveTask', pageId, fromListId, toListId, toIndex)
    },
  })
}

onMounted(() => {
  initTaskSortable()
})

onBeforeUnmount(() => {
  taskSortable?.destroy()
})
</script>

<style lang="scss" scoped>
.kanban-list {
  min-width: 280px;
  max-width: 320px;
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  max-height: calc(100vh - 180px);
}

.list-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px 8px;
  font-weight: 500;
  font-size: var(--font-size-m);
  color: var(--color-text-primary);
  cursor: grab;

  &:active {
    cursor: grabbing;
  }

  :deep(.common-dropdown-menu) {
    width: auto;
    flex-shrink: 0;
  }
}

.list-title {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.list-rename-input {
  flex: 1;
  border: 1px solid var(--color-active);
  border-radius: 4px;
  padding: 2px 8px;
  font-size: var(--font-size-m);
  color: var(--color-text-primary);
  background: var(--color-obvious);
  outline: none;
  max-width: 160px;
}

.task-count {
  font-size: var(--font-size-s);
  color: var(--color-text-secondary);
  background: var(--color-border);
  padding: 1px 6px;
  border-radius: 10px;
  font-weight: normal;
}

.list-menu-trigger {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  cursor: pointer;
  color: var(--color-text-secondary);
  transition: all 0.15s ease;

  &:hover {
    background: var(--hover-overlay);
    color: var(--color-text-primary);
  }
}

.list-header-action {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  cursor: pointer;
  color: var(--color-text-secondary);
  font-size: 12px;
  transition: all 0.15s ease;

  &:hover {
    background: var(--hover-overlay);
    color: var(--color-text-primary);
  }
}

.list-tasks {
  flex: 1;
  overflow-y: auto;
  padding: 8px 8px 8px;
  min-height: 40px;
}

.add-task-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 32px;
  border: 1px dashed var(--color-border);
  border-radius: 6px;
  color: var(--color-text-secondary);
  cursor: pointer;
  margin-top: 4px;
  transition: all 0.15s ease;

  &:hover {
    border-color: var(--color-active);
    color: var(--color-active);
    background: var(--hover-overlay);
  }
}

.task-ghost {
  opacity: 0.4;
  background: var(--color-bg-secondary);
  border-radius: 6px;
}

.task-chosen {
  box-shadow: var(--shadow-default);
}

.batch-bar {
  display: flex;
  align-items: center;
  gap: 0;
  padding: 0 8px;
  height: 36px;
  background: var(--color-bg-secondary);
  border-bottom: 1px solid var(--color-border);
  font-size: 12px;
}

.batch-count {
  color: var(--color-text-secondary);
  margin: 0 6px 0 4px;
  font-size: 11px;
  white-space: nowrap;
}

.batch-action {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 4px 8px;
  cursor: pointer;
  color: var(--color-text-primary);
  border-radius: 4px;
  white-space: nowrap;
  transition: background 0.15s ease;

  &:hover { background: var(--hover-overlay); }
  &.danger { color: var(--color-red); }
  &.disabled {
    opacity: 0.35;
    cursor: not-allowed;
    &:hover { background: transparent; }
  }

  i { font-size: 11px; }
}

.batch-close {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-left: auto;
  cursor: pointer;
  color: var(--color-text-secondary);
  border-radius: 4px;
  flex-shrink: 0;
  transition: background 0.15s ease;

  &:hover {
    background: var(--hover-overlay);
    color: var(--color-text-primary);
  }
}

.task-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  gap: 4px;

  :deep(.kanban-task) {
    flex: 1;
    min-width: 0;
  }

  &.task-selected {
    outline: 2px solid var(--color-active);
    border-radius: 6px;
  }
  &.task-highlight {
    :deep(.kanban-task) {
      animation: task-highlight-pulse 2s ease-out;
    }
  }
}

@keyframes task-highlight-pulse {
  0% { background: rgba(0, 123, 255, 0.08); }
  100% { background: var(--color-obvious); }
}

.task-checkbox {
  flex-shrink: 0;
  padding: 0 4px;
  display: flex;
  align-items: center;
  justify-content: center;

  :deep(.ant-checkbox-inner) {
    width: 16px;
    height: 16px;
    border: 2px solid var(--color-text-secondary);
    background: var(--color-obvious);
  }

  :deep(.ant-checkbox-checked .ant-checkbox-inner) {
    background-color: var(--color-active);
    border-color: var(--color-active);
  }
}
</style>
