<template>
  <div class="kanban-item-container">
    <ItemHeader :item-info="itemInfo">
      <template #right>
        <ItemHeaderRight
          :item-info="itemInfo"
          @reload="handleReload"
        />
      </template>
    </ItemHeader>

    <div class="kanban-toolbar">
      <div class="toolbar-search">
        <i class="fas fa-search toolbar-search-icon"></i>
        <input
          v-model="searchKeyword"
          :placeholder="t('item.kanban_search_placeholder')"
          class="toolbar-search-input"
          @input="handleSearch"
        />
        <i
          v-if="searchKeyword"
          class="fas fa-times toolbar-search-clear"
          @click="searchKeyword = ''"
        ></i>
      </div>
      <span class="toolbar-btn" :class="{ active: showFilterBar }" @click="showFilterBar = !showFilterBar">
        <i class="fas fa-filter"></i>
        {{ t('item.kanban_filter') }}
      </span>
      <span v-if="hasArchivedLists" class="toolbar-btn" @click="showArchivedListsModal = true">
        <i class="fas fa-box-archive"></i>
        {{ t('item.kanban_archived_lists') }}
      </span>
      <span class="toolbar-btn" @click="showActivityHistory = true">
        <i class="fas fa-clock-rotate-left"></i>
        {{ t('item.kanban_activity_history') }}
      </span>
    </div>

    <FilterBar
      v-if="showFilterBar"
      :item-info="itemInfo"
      :members="itemMembers"
      :all-tags="allTags"
      @filter="handleFilter"
    />

    <KanbanBoard
      v-if="boardData"
      :board-data="boardData"
      :task-pages="taskPages"
      :task-details-cache="taskDetailsCache"
      :is-editable="isEditable"
      :search-keyword="searchKeyword"
      :filters="activeFilters"
      :highlighted-task-ids="highlightedTaskIds"
      :show-filter-bar="showFilterBar"
      @save-board="handleSaveBoard"
      @move-task="handleMoveTask"
      @create-task="handleCreateTask"
      @open-task-detail="handleOpenTaskDetail"
      @delete-task="handleDeleteTask"
      @complete-task="handleCompleteTask"
      @copy-task="handleCopyTask"
      @add-list="handleAddList"
      @update-list="handleUpdateList"
      @delete-list="handleDeleteList"
      @archive-list="handleArchiveList"
      @batch-move="handleBatchMove"
      @batch-complete="handleBatchComplete"
      @batch-delete="handleBatchDelete"
    />

    <CommonModal
      v-if="showArchivedListsModal"
      :show="showArchivedListsModal"
      :title="t('item.kanban_archived_lists')"
      @close="showArchivedListsModal = false"
    >
      <div class="archived-lists-body">
        <div v-if="!boardData?.archived_lists?.length" class="archived-empty">{{ t('item.kanban_no_archived_lists') }}</div>
        <div v-for="list in boardData?.archived_lists" :key="list.id" class="archived-list-item">
          <div class="archived-list-info">
            <div class="archived-list-title">{{ list.title }}</div>
            <div class="archived-list-count">{{ (boardData?.archived_tasks_order?.[list.id] || []).length }} {{ t('item.kanban_tasks_unit') }}</div>
          </div>
          <CommonButton size="small" @click="handleRestoreList(list.id)">{{ t('item.kanban_restore_list') }}</CommonButton>
        </div>
      </div>
      <template #footer>
        <CommonButton @click="showArchivedListsModal = false">{{ t('common.close') }}</CommonButton>
      </template>
    </CommonModal>

    <CommonModal
      :show="showBatchMoveModal"
      :title="t('item.kanban_batch_move')"
      @close="showBatchMoveModal = false"
    >
      <div class="batch-move-body">
        <div class="batch-move-label">{{ t('item.kanban_select_list') }}</div>
        <a-select
          v-model:value="batchMoveTargetListId"
          class="batch-move-select"
          :placeholder="t('item.kanban_select_list')"
        >
          <a-select-option v-for="list in batchMoveListOptions" :key="list.value" :value="list.value">
            {{ list.label }}
          </a-select-option>
        </a-select>
        <div v-if="batchMovePageIds.length" class="batch-move-count">
          {{ t('item.kanban_batch_move_count', { count: batchMovePageIds.length }) }}
        </div>
      </div>
      <template #footer>
        <CommonButton @click="showBatchMoveModal = false">{{ t('common.cancel') }}</CommonButton>
        <CommonButton type="primary" @click="confirmBatchMove">{{ t('common.confirm') }}</CommonButton>
      </template>
    </CommonModal>

    <ActivityHistory
      v-if="showActivityHistory"
      :item-id="props.itemInfo.item_id"
      :task-pages="taskPages"
      @close="showActivityHistory = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import request from '@/utils/request'
import { unescapeHTML } from '@/utils/tools'
import ConfirmModal from '@/components/ConfirmModal'
import CommonButton from '@/components/CommonButton.vue'
import CommonModal from '@/components/CommonModal.vue'
import { getAllItemMemberList } from '@/models/member'
import { useUserStore } from '@/store/user'
import ItemHeader from '@/views/item/components/ItemHeader.vue'
import ItemHeaderRight from './HeaderRight.vue'
import KanbanBoard from './KanbanBoard.vue'
import TaskDetailModal from './TaskDetailModal'
import FilterBar from './FilterBar.vue'
import ActivityHistory from './ActivityHistory.vue'
import type { KanbanBoardData, KanbanTaskData, TaskPageInfo } from './types'

interface Props {
  itemInfo: any
}

const props = defineProps<Props>()
const emit = defineEmits<{ reload: [] }>()

const { t } = useI18n()
const userStore = useUserStore()

const kanbanExtInfo = (taskData: KanbanTaskData) => JSON.stringify({ completed: !!taskData.completed, tags: taskData.tags || [] })

const boardData = ref<KanbanBoardData | null>(null)
const boardPageId = ref(0)
const boardVersion = ref(0)
const taskPages = ref<TaskPageInfo[]>([])
const taskDetailsCache = ref<Record<string, KanbanTaskData>>({})
const searchKeyword = ref('')
const showFilterBar = ref(false)
const activeFilters = ref<any>({})
const itemMembers = ref<any[]>([])
const saveTimer = ref<any>(null)

const isEditable = computed(() => props.itemInfo.item_edit == 1)
const hasArchivedLists = computed(() => (boardData.value?.archived_lists?.length || 0) > 0)
const showArchivedListsModal = ref(false)
const showActivityHistory = ref(false)

const showBatchMoveModal = ref(false)
const batchMovePageIds = ref<string[]>([])
const batchMoveTargetListId = ref('')
const batchMoveListOptions = computed(() => {
  if (!boardData.value) return []
  return boardData.value.lists.map(l => ({ label: l.title, value: l.id }))
})

const allTags = computed(() => {
  const tags = new Set<string>()
  Object.values(taskDetailsCache.value).forEach(task => {
    task.tags?.forEach(tag => {
      if (tag.text) tags.add(tag.text)
    })
  })
  return Array.from(tags)
})

const loadBoard = async () => {
  const pages = props.itemInfo.menu?.pages || []
  const boardPage = pages.find((p: any) => p.page_title === '__kanban_board__')
  if (!boardPage) return

  boardPageId.value = boardPage.page_id
  const res = await request('/api/page/info', { page_id: boardPage.page_id })
  if (res.data && res.data.page_content) {
    const content = JSON.parse(unescapeHTML(res.data.page_content))
    if (content.tasks_order) {
      for (const listId of Object.keys(content.tasks_order)) {
        const ids = content.tasks_order[listId]
        if (Array.isArray(ids)) {
          content.tasks_order[listId] = [...new Set(ids)]
        }
      }
    }
    if (content.archived_tasks_order) {
      for (const listId of Object.keys(content.archived_tasks_order)) {
        const ids = content.archived_tasks_order[listId]
        if (Array.isArray(ids)) {
          content.archived_tasks_order[listId] = [...new Set(ids)]
        }
      }
    }
    boardData.value = content
    boardVersion.value = content.meta?.version || 0
    if (!boardData.value.archived_lists) boardData.value.archived_lists = []
    if (!boardData.value.archived_tasks_order) boardData.value.archived_tasks_order = {}
  }

  taskPages.value = pages.filter((p: any) => p.page_title !== '__kanban_board__')
}

const loadItemMembers = async () => {
  const res = await getAllItemMemberList(props.itemInfo.item_id)
  if (res.data && Array.isArray(res.data)) {
    itemMembers.value = res.data
  }
}

const logKanbanEvent = (eventType: string, pageId: string | number = 0, eventData: Record<string, any> = {}) => {
  request('/api/kanban/logEvent', {
    item_id: props.itemInfo.item_id,
    page_id: pageId,
    event_type: eventType,
    event_data: JSON.stringify(eventData),
  }).catch(() => {})
}

const loadTaskDetail = async (pageId: string): Promise<KanbanTaskData | null> => {
  if (taskDetailsCache.value[pageId]) {
    return taskDetailsCache.value[pageId]
  }
  const res = await request('/api/page/info', { page_id: pageId })
  if (res.data && res.data.page_content) {
    const content = JSON.parse(unescapeHTML(res.data.page_content))
    taskDetailsCache.value[pageId] = content
    return content
  }
  return null
}

const saveBoard = async (data: KanbanBoardData, retryCount = 0): Promise<boolean> => {
  if (data.tasks_order) {
    for (const listId of Object.keys(data.tasks_order)) {
      const ids = data.tasks_order[listId]
      if (Array.isArray(ids)) {
        data.tasks_order[listId] = [...new Set(ids)]
      }
    }
  }
  data.meta.version = boardVersion.value
  const content = JSON.stringify(data)
  const res = await request('/api/page/save', {
    page_id: boardPageId.value,
    page_title: '__kanban_board__',
    item_id: props.itemInfo.item_id,
    page_content: content,
    is_urlencode: 1,
    cat_id: 0,
  })

  if (res.error_code === 0) {
    boardVersion.value = data.meta.version + 1
    boardData.value = data
    fetchLatestBoard().catch(() => {})
    return true
  } else if (res.error_code === 10410) {
    if (retryCount < 2) {
      let latestData = res.data
      if (typeof latestData === 'string') {
        latestData = JSON.parse(unescapeHTML(latestData))
      }
      boardVersion.value = latestData.meta?.version || 0
      data.meta.version = boardVersion.value
      return saveBoard(data, retryCount + 1)
    }
    message.warning(t('item.kanban_version_conflict'))
    if (res.data) {
      let latestData = res.data
      if (typeof latestData === 'string') {
        latestData = JSON.parse(unescapeHTML(latestData))
      }
      boardData.value = latestData
      boardVersion.value = latestData.meta?.version || 0
    }
    return false
  }
  return false
}

const highlightedTaskIds = ref<Set<string>>(new Set())

const fetchLatestBoard = async () => {
  try {
    const res = await request('/api/page/info', { page_id: boardPageId.value })
    if (res.data && res.data.page_content) {
      const content = JSON.parse(unescapeHTML(res.data.page_content))
      if (content.tasks_order) {
        for (const listId of Object.keys(content.tasks_order)) {
          const ids = content.tasks_order[listId]
          if (Array.isArray(ids)) {
            content.tasks_order[listId] = [...new Set(ids)]
          }
        }
      }
      const prevIds = new Set<string>()
      if (boardData.value) {
        Object.values(boardData.value.tasks_order).forEach(ids => {
          (ids as string[]).forEach(id => prevIds.add(id))
        })
      }
      const newIds: string[] = []
      Object.values(content.tasks_order || {}).forEach(ids => {
        (ids as string[]).forEach(id => {
          if (!prevIds.has(id)) newIds.push(id)
        })
      })
      boardData.value = content
      boardVersion.value = content.meta?.version || 0
      if (newIds.length > 0) {
        newIds.forEach(id => highlightedTaskIds.value.add(id))
        setTimeout(() => {
          newIds.forEach(id => highlightedTaskIds.value.delete(id))
        }, 2000)
      }
    }
  } catch (e) { /* ignore */ }
}

const debouncedSaveBoard = (data: KanbanBoardData) => {
  if (saveTimer.value) clearTimeout(saveTimer.value)
  saveTimer.value = setTimeout(() => {
    saveBoard(data)
  }, 1000)
}

const handleSaveBoard = (data: KanbanBoardData) => {
  debouncedSaveBoard(data)
}

const handleMoveTask = async (pageId: string, fromListId: string, toListId: string, toIndex: number) => {
  if (!boardData.value) return

  if (boardData.value.tasks_order[fromListId]) {
    boardData.value.tasks_order[fromListId] = boardData.value.tasks_order[fromListId].filter(id => id !== pageId)
  }

  if (!boardData.value.tasks_order[toListId]) {
    boardData.value.tasks_order[toListId] = []
  }
  boardData.value.tasks_order[toListId].splice(toIndex, 0, pageId)

  if (fromListId !== toListId) {
    const detail = await loadTaskDetail(pageId)
    if (detail) {
      detail.list_id = toListId
      const pageInfo = taskPages.value.find(p => p.page_id == pageId)
      await request('/api/page/save', {
        page_id: pageId,
        page_title: pageInfo?.page_title || '',
        item_id: props.itemInfo.item_id,
        page_content: JSON.stringify(detail),
        ext_info: kanbanExtInfo(detail),
        is_urlencode: 1,
        cat_id: 0,
      })
      taskDetailsCache.value[pageId] = detail
      const fromTitle = boardData.value.lists.find(l => l.id === fromListId)?.title || ''
      const toTitle = boardData.value.lists.find(l => l.id === toListId)?.title || ''
      logKanbanEvent('task_moved', pageId, { title: pageInfo?.page_title || '', from_list_title: fromTitle, to_list_title: toTitle })
    }
  }

  await saveBoard({ ...boardData.value })
}

const handleCreateTask = async (listId: string, title: string) => {
  const user = userStore.userInfo || {}
  const taskData: KanbanTaskData = {
    creator_uid: String(user.uid || ''),
    creator_username: user.username || '',
    list_id: listId,
    description: '',
    assignee_uid: '',
    assignee_username: '',
    due_date: '',
    tags: [],
    priority: 'medium',
    linked_pages: [],
    completed: false,
  }

  const res = await request('/api/page/save', {
    page_id: 0,
    page_title: title,
    item_id: props.itemInfo.item_id,
    page_content: JSON.stringify(taskData),
    ext_info: kanbanExtInfo(taskData),
    is_urlencode: 1,
    cat_id: 0,
  })

  if (res.error_code === 0 && res.data?.page_id) {
    const newPageId = String(res.data.page_id)
    taskPages.value.push({ page_id: newPageId, page_title: title })
    taskDetailsCache.value[newPageId] = taskData

    logKanbanEvent('task_created', newPageId, { title, list_id: listId })

    if (boardData.value) {
      if (!boardData.value.tasks_order[listId]) {
        boardData.value.tasks_order[listId] = []
      }
      boardData.value.tasks_order[listId].push(newPageId)
      await saveBoard({ ...boardData.value })
    }
  }
}

const handleOpenTaskDetail = async (pageId: string) => {
  const detail = await loadTaskDetail(pageId)
  if (!detail) return
  const pageInfo = taskPages.value.find(p => p.page_id == pageId)
  const taskData = { ...detail, _pageTitle: pageInfo?.page_title || '' } as any

  const result = await TaskDetailModal({
    taskData,
    taskPageId: pageId,
    itemInfo: props.itemInfo,
    lists: boardData.value?.lists || [],
    members: itemMembers.value,
  })

  if (result.action === 'save') {
    await handleSaveTaskDetail(pageId, result.title, result.taskData)
  } else if (result.action === 'delete') {
    await handleDeleteTask(pageId)
  }
}

const handleSaveTaskDetail = async (pageId: string, title: string, taskData: KanbanTaskData) => {
  const res = await request('/api/page/save', {
    page_id: pageId,
    page_title: title,
    item_id: props.itemInfo.item_id,
    page_content: JSON.stringify(taskData),
    ext_info: kanbanExtInfo(taskData),
    is_urlencode: 1,
    cat_id: 0,
  })

  if (res.error_code === 0) {
    taskDetailsCache.value[pageId] = taskData
    const idx = taskPages.value.findIndex(p => p.page_id == pageId)
    if (idx >= 0) {
      taskPages.value[idx].page_title = title
    }
    logKanbanEvent('task_updated', pageId, { title })
    message.success(t('common.op_success'))
  }
}

const handleDeleteTask = async (pageId: string) => {
  const confirmed = await ConfirmModal(t('item.kanban_confirm_delete_task'))
  if (!confirmed) return

  await request('/api/page/delete', { page_id: pageId })
  logKanbanEvent('task_deleted', pageId, { title: taskPages.value.find(p => p.page_id == pageId)?.page_title || '' })
  taskPages.value = taskPages.value.filter(p => String(p.page_id) !== String(pageId))
  delete taskDetailsCache.value[pageId]
  if (boardData.value) {
    Object.keys(boardData.value.tasks_order).forEach(listId => {
      boardData.value!.tasks_order[listId] = boardData.value!.tasks_order[listId].filter(id => String(id) !== String(pageId))
    })
    await saveBoard({ ...boardData.value })
  }
}

const handleCompleteTask = async (pageId: string) => {
  const detail = await loadTaskDetail(pageId)
  if (!detail) return

  detail.completed = !detail.completed

  await request('/api/page/save', {
    page_id: pageId,
    page_title: taskPages.value.find(p => p.page_id == pageId)?.page_title || '',
    item_id: props.itemInfo.item_id,
    page_content: JSON.stringify(detail),
    ext_info: kanbanExtInfo(detail),
    is_urlencode: 1,
    cat_id: 0,
  })

  taskDetailsCache.value[pageId] = detail
  logKanbanEvent(detail.completed ? 'task_completed' : 'task_uncompleted', pageId, { title: taskPages.value.find(p => p.page_id == pageId)?.page_title || '' })
  message.success(t('common.op_success'))
}

const handleCopyTask = async (pageId: string) => {
  const detail = await loadTaskDetail(pageId)
  if (!detail) return

  const pageInfo = taskPages.value.find(p => p.page_id == pageId)
  const title = (pageInfo?.page_title || '') + ' (' + t('item.kanban_copy_suffix') + ')'
  const taskData: KanbanTaskData = { ...detail, completed: false }

  const res = await request('/api/page/save', {
    page_id: 0,
    page_title: title,
    item_id: props.itemInfo.item_id,
    page_content: JSON.stringify(taskData),
    ext_info: kanbanExtInfo(taskData),
    is_urlencode: 1,
    cat_id: 0,
  })

  if (res.error_code === 0 && res.data?.page_id) {
      const newPageId = String(res.data.page_id)
    taskPages.value.push({ page_id: newPageId, page_title: title, ext_info: kanbanExtInfo(taskData) })
    taskDetailsCache.value[newPageId] = taskData

    logKanbanEvent('task_created', newPageId, { title, list_id: detail.list_id || '', copied_from: pageId })

    if (boardData.value) {
      const listId = detail.list_id || ''
      if (!boardData.value.tasks_order[listId]) {
        boardData.value.tasks_order[listId] = []
      }
      const idx = boardData.value.tasks_order[listId].findIndex(id => String(id) === String(pageId))
      boardData.value.tasks_order[listId].splice(idx + 1, 0, newPageId)
      await saveBoard({ ...boardData.value })
    }
    message.success(t('common.op_success'))
  }
}

const completeTaskInMemory = async (pageId: string) => {
  const detail = await loadTaskDetail(pageId)
  if (!detail) return

  detail.completed = true

  await request('/api/page/save', {
    page_id: pageId,
    page_title: taskPages.value.find(p => p.page_id == pageId)?.page_title || '',
    item_id: props.itemInfo.item_id,
    page_content: JSON.stringify(detail),
    ext_info: kanbanExtInfo(detail),
    is_urlencode: 1,
    cat_id: 0,
  })

  taskDetailsCache.value[pageId] = detail
}

const handleAddList = async (title: string, position: 'left' | 'right', refListId?: string) => {
  if (!boardData.value) return
  const newId = 'list_' + Date.now() + '_' + Math.floor(Math.random() * 9000 + 1000)
  const refList = boardData.value.lists.find(l => l.id === refListId)
  let pos = boardData.value.lists.length + 1
  if (refList) {
    if (position === 'left') {
      pos = refList.position
      boardData.value.lists.forEach(l => { if (l.position >= pos) l.position++ })
    } else {
      pos = refList.position + 1
      boardData.value.lists.forEach(l => { if (l.position >= pos) l.position++ })
    }
  }
  boardData.value.lists.push({ id: newId, title, position: pos })
  boardData.value.tasks_order[newId] = []
  boardData.value.lists.sort((a, b) => a.position - b.position)
  logKanbanEvent('list_created', 0, { list_id: newId, title })
  await saveBoard({ ...boardData.value })
}

const handleUpdateList = async (listId: string, title: string) => {
  if (!boardData.value) return
  const list = boardData.value.lists.find(l => l.id === listId)
  if (list) {
    list.title = title
    logKanbanEvent('list_updated', 0, { list_id: listId, title })
    await saveBoard({ ...boardData.value })
  }
}

const handleDeleteList = async (listId: string) => {
  if (!boardData.value) return
  if (boardData.value.lists.length <= 1) {
    message.warning(t('item.kanban_cannot_delete_last_list'))
    return
  }

  const taskIds = boardData.value.tasks_order[listId] || []
  for (const pageId of taskIds) {
    await completeTaskInMemory(pageId)
  }

  boardData.value.lists = boardData.value.lists.filter(l => l.id !== listId)
  delete boardData.value.tasks_order[listId]
  logKanbanEvent('list_deleted', 0, { list_id: listId, completed_tasks: taskIds.length })
  await saveBoard({ ...boardData.value })
}

const handleArchiveList = async (listId: string) => {
  if (!boardData.value) return
  const list = boardData.value.lists.find(l => l.id === listId)
  if (!list) return

  const confirmed = await ConfirmModal(t('item.kanban_confirm_archive_list'))
  if (!confirmed) return

  if (!boardData.value.archived_lists) boardData.value.archived_lists = []
  if (!boardData.value.archived_tasks_order) boardData.value.archived_tasks_order = {}

  boardData.value.archived_lists.push(list)
  boardData.value.archived_tasks_order[listId] = boardData.value.tasks_order[listId] || []

  boardData.value.lists = boardData.value.lists.filter(l => l.id !== listId)
  delete boardData.value.tasks_order[listId]
  logKanbanEvent('list_archived', 0, { list_id: listId, title: list.title, tasks_count: (boardData.value.archived_tasks_order?.[listId] || []).length })
  await saveBoard({ ...boardData.value })
}

const handleRestoreList = async (listId: string) => {
  if (!boardData.value) return
  const list = boardData.value.archived_lists?.find((l: any) => l.id === listId)
  if (!list) return

  boardData.value.lists.push(list)
  boardData.value.tasks_order[listId] = boardData.value.archived_tasks_order?.[listId] || []

  boardData.value.archived_lists = boardData.value.archived_lists.filter((l: any) => l.id !== listId)
  delete boardData.value.archived_tasks_order[listId]

  logKanbanEvent('list_restored', 0, { list_id: listId, title: list.title, tasks_count: (boardData.value.tasks_order[listId] || []).length })

  await saveBoard({ ...boardData.value })
  showArchivedListsModal.value = false
}

const handleBatchComplete = async (pageIds: string[]) => {
  if (!pageIds.length) return
  for (const pageId of pageIds) {
    await completeTaskInMemory(pageId)
    logKanbanEvent('task_completed', pageId, { title: taskPages.value.find(p => p.page_id == pageId)?.page_title || '' })
  }
  message.success(t('common.op_success'))
}

const handleBatchMove = async (pageIds: string[]) => {
  if (!boardData.value || !pageIds.length) return
  const lists = boardData.value.lists
  if (lists.length < 2) return
  batchMovePageIds.value = pageIds
  batchMoveTargetListId.value = lists[0]?.id || ''
  showBatchMoveModal.value = true
}

const confirmBatchMove = async () => {
  const targetListId = batchMoveTargetListId.value
  const pageIds = [...batchMovePageIds.value]
  if (!targetListId || !pageIds.length || !boardData.value) return

  const newTasksOrder: Record<string, string[]> = {}
  for (const listId of Object.keys(boardData.value.tasks_order)) {
    newTasksOrder[listId] = [...boardData.value.tasks_order[listId]]
  }

  const moves: { pageId: string; fromListId: string }[] = []
  for (const pageId of pageIds) {
    let fromListId = ''
    for (const listId of Object.keys(newTasksOrder)) {
      if (newTasksOrder[listId].some(id => String(id) === String(pageId))) fromListId = listId
    }
    if (fromListId && fromListId !== targetListId) {
      moves.push({ pageId, fromListId })
      newTasksOrder[fromListId] = newTasksOrder[fromListId].filter(id => String(id) !== String(pageId))
    }
  }

  if (!newTasksOrder[targetListId]) {
    newTasksOrder[targetListId] = []
  }
  for (const { pageId } of moves) {
    newTasksOrder[targetListId].push(pageId)
  }

  boardData.value.tasks_order = newTasksOrder

  for (const { pageId, fromListId } of moves) {
    const detail = await loadTaskDetail(pageId)
    if (detail) {
      detail.list_id = targetListId
      const pageInfo = taskPages.value.find(p => p.page_id == pageId)
      await request('/api/page/save', {
        page_id: pageId,
        page_title: pageInfo?.page_title || '',
        item_id: props.itemInfo.item_id,
        page_content: JSON.stringify(detail),
        ext_info: kanbanExtInfo(detail),
        is_urlencode: 1,
        cat_id: 0,
      })
      taskDetailsCache.value[pageId] = detail
      const fromTitle = boardData.value.lists.find(l => l.id === fromListId)?.title || ''
      const toTitle = boardData.value.lists.find(l => l.id === targetListId)?.title || ''
      logKanbanEvent('task_moved', pageId, { title: pageInfo?.page_title || '', from_list_title: fromTitle, to_list_title: toTitle })
    }
  }

  const newBoardData: KanbanBoardData = { ...boardData.value, tasks_order: newTasksOrder }
  await saveBoard(newBoardData)
  showBatchMoveModal.value = false
  message.success(t('common.op_success'))
}

const handleBatchDelete = async (pageIds: string[]) => {
  if (!pageIds.length) return
  for (const pageId of pageIds) {
    logKanbanEvent('task_deleted', pageId, { title: taskPages.value.find(p => p.page_id == pageId)?.page_title || '' })
    await request('/api/page/delete', { page_id: pageId })
    taskPages.value = taskPages.value.filter(p => String(p.page_id) !== String(pageId))
    delete taskDetailsCache.value[pageId]
    if (boardData.value) {
      Object.keys(boardData.value.tasks_order).forEach(listId => {
        boardData.value!.tasks_order[listId] = boardData.value!.tasks_order[listId].filter(id => String(id) !== String(pageId))
      })
    }
  }
  if (boardData.value) await saveBoard({ ...boardData.value })
  message.success(t('common.op_success'))
}

const handleFilter = (filters: any) => {
  activeFilters.value = filters
}
const handleSearch = () => {
}
const handleReload = () => {
  emit('reload')
}

onMounted(() => {
  loadBoard()
  loadItemMembers()
})

onBeforeUnmount(() => {
  if (saveTimer.value) clearTimeout(saveTimer.value)
})
</script>

<style lang="scss" scoped>
.kanban-item-container {
  min-height: 100vh;
  background: var(--color-bg-secondary);
}

.kanban-toolbar {
  position: fixed;
  top: 90px;
  left: 0;
  right: 0;
  z-index: 998;
  background: var(--color-obvious);
  border-bottom: 1px solid var(--color-border);
  padding: 8px 24px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.toolbar-search {
  display: flex;
  align-items: center;
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-interval);
  border-radius: 6px;
  padding: 0 10px;
  height: 32px;
  width: 200px;
  transition: all 0.15s ease;

  &:focus-within {
    border-color: var(--color-active);
    background: var(--color-obvious);
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
  }
}

.toolbar-search-icon {
  color: var(--color-text-secondary);
  font-size: 12px;
  flex-shrink: 0;
}

.toolbar-search-input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-size: var(--font-size-s);
  color: var(--color-text-primary);
  padding: 0 6px;
  height: 100%;

  &::placeholder {
    color: var(--color-text-secondary);
  }
}

.toolbar-search-clear {
  color: var(--color-text-secondary);
  font-size: 11px;
  cursor: pointer;
  flex-shrink: 0;
  transition: color 0.15s ease;

  &:hover {
    color: var(--color-text-primary);
  }
}

.toolbar-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 0 12px;
  height: 32px;
  font-size: var(--font-size-s);
  color: var(--color-text-secondary);
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-interval);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
  user-select: none;

  i {
    font-size: 12px;
  }

  &:hover {
    color: var(--color-text-primary);
    background: var(--hover-overlay);
    border-color: var(--color-border);
  }

  &.active {
    color: var(--color-active);
    border-color: var(--color-active);
    background: var(--hover-overlay);
  }
}

.archived-lists-body {
  padding: 20px 24px;
  max-height: 65vh;
  overflow-y: auto;
  min-height: 80px;
}

.archived-empty {
  text-align: center;
  color: var(--color-text-secondary);
  padding: 40px 0;
}

.archived-list-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  border-bottom: 1px solid var(--color-border-light);

  &:hover {
    background: var(--hover-overlay);
  }
}

.archived-list-info {
  flex: 1;
  min-width: 0;
}

.archived-list-title {
  font-size: var(--font-size-m);
  font-weight: 500;
  color: var(--color-text-primary);
}

.archived-list-count {
  font-size: var(--font-size-s);
  color: var(--color-text-secondary);
  margin-top: 4px;
}

.batch-move-body {
  padding: 20px 24px;
  min-width: 360px;
}

.batch-move-label {
  font-size: var(--font-size-m);
  color: var(--color-text-primary);
  margin-bottom: 10px;
}

.batch-move-select {
  width: 100%;
}

.batch-move-count {
  margin-top: 12px;
  font-size: var(--font-size-s);
  color: var(--color-text-secondary);
}
</style>                