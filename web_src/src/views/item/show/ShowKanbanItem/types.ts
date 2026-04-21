export interface KanbanList {
  id: string
  title: string
  position: number
}

export interface KanbanTag {
  color: string
  text: string
}

export interface KanbanLinkedPage {
  item_id: string
  page_id: string
  page_title: string
}

export interface KanbanTaskData {
  creator_uid: string
  creator_username: string
  list_id: string
  description: string
  assignee_uid: string
  assignee_username: string
  due_date: string
  tags: KanbanTag[]
  priority: 'high' | 'medium' | 'low'
  linked_pages: KanbanLinkedPage[]
  completed: boolean
}

export interface KanbanBoardData {
  lists: KanbanList[]
  tasks_order: Record<string, string[]>
  archived_lists: KanbanList[]
  archived_tasks_order: Record<string, string[]>
  meta: {
    version: number
    last_updated: number
  }
}

export interface TaskPageInfo {
  page_id: string
  page_title: string
  ext_info?: string
}

export const TAG_COLORS = ['red', 'orange', 'yellow', 'green', 'blue', 'purple', 'gray'] as const

export const PRIORITY_OPTIONS = [
  { value: 'high', labelKey: 'item.kanban_priority_high', color: '#f5222d', icon: '🔺' },
  { value: 'medium', labelKey: 'item.kanban_priority_medium', color: '#1890ff', icon: '' },
  { value: 'low', labelKey: 'item.kanban_priority_low', color: '#52c41a', icon: '🟢' },
] as const
