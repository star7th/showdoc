<template>
  <div class="task-detail-modal">
    <CommonModal
      :class="{ show }"
      :title="editForm._pageTitle || ''"
      width="560px"
      @close="handleClose"
    >
      <template #right>
        <span class="header-action-btn danger" @click="handleDelete" :title="$t('common.delete')">
          <i class="fas fa-trash"></i>
        </span>
      </template>

      <div class="detail-body" v-if="taskData">
        <div
          class="completed-row"
          :class="{ active: editForm.completed }"
          @click="editForm.completed = !editForm.completed"
        >
          <i :class="editForm.completed ? 'fas fa-check-circle' : 'far fa-circle'" class="completed-icon"></i>
          <span class="completed-text">{{ editForm.completed ? $t('item.kanban_mark_incomplete') : $t('item.kanban_mark_completed') }}</span>
        </div>

        <div class="form-group">
          <div class="form-label">{{ $t('item.kanban_task_title') }}</div>
          <input v-model="editForm._pageTitle" :maxlength="100" class="form-input" />
        </div>

        <div class="form-group">
          <div class="form-label form-label-row">
            <span>{{ $t('item.kanban_task_description') }}</span>
            <span class="desc-toolbar">
              <span class="desc-toolbar-btn" @click="handleUploadImage" :title="$t('item.kanban_upload_image')">
                <i class="fas fa-image"></i>
              </span>
              <span class="desc-toolbar-btn" :class="{ active: descPreview }" @click="descPreview = !descPreview">
                <i class="fas fa-eye"></i>
              </span>
            </span>
          </div>
          <a-textarea
            v-if="!descPreview"
            ref="descTextareaRef"
            v-model:value="editForm.description"
            :rows="4"
            :placeholder="$t('item.kanban_task_description_placeholder')"
            @paste="handleDescPaste"
          />
          <div v-else class="desc-preview" v-html="descPreviewHtml"></div>
          <input ref="fileInputRef" type="file" accept="image/*" style="display:none" @change="onFileSelected" />
        </div>

        <div class="form-row">
          <div class="form-group" style="flex:1">
            <div class="form-label">{{ $t('item.kanban_task_priority') }}</div>
            <a-select v-model:value="editForm.priority" style="width:100%">
              <a-select-option v-for="p in PRIORITY_OPTIONS" :key="p.value" :value="p.value">
                {{ p.icon }} {{ $t(p.labelKey) }}
              </a-select-option>
            </a-select>
          </div>
          <div class="form-group" style="flex:1">
            <div class="form-label">{{ $t('item.kanban_task_due_date') }}</div>
            <a-date-picker
              v-model:value="dueDateObj"
              style="width:100%"
              value-format="YYYY-MM-DD"
              @change="onDueDateChange"
            />
          </div>
        </div>

        <div class="form-group">
          <div class="form-label">{{ $t('item.kanban_task_assignee') }}</div>
          <a-select
            v-model:value="editForm.assignee_uid"
            style="width:100%"
            allow-clear
            :placeholder="$t('item.kanban_task_assignee')"
          >
            <a-select-option v-for="m in members" :key="m.uid" :value="String(m.uid)">
              {{ m.username }}
            </a-select-option>
          </a-select>
        </div>

        <div class="form-group">
          <div class="form-label">{{ $t('item.kanban_task_tags') }}</div>
          <div class="tags-editor">
            <div v-for="(tag, idx) in editForm.tags" :key="idx" class="tag-item">
              <span class="tag-color-dot" :style="{ background: tagColorMap[tag.color] || tagColorMap.gray }"></span>
              <a-input v-model:value="tag.text" size="small" style="width:80px" :maxlength="20" />
              <a-select v-model:value="tag.color" size="small" style="width:70px">
                <a-select-option v-for="c in TAG_COLORS" :key="c" :value="c">
                  <span class="tag-color-dot" :style="{ background: tagColorMap[c] }"></span>
                </a-select-option>
              </a-select>
              <span class="icon-btn" @click="editForm.tags.splice(idx, 1)"><i class="fas fa-times"></i></span>
            </div>
            <a-button v-if="editForm.tags.length < 3" size="small" @click="addTag">
              <i class="fas fa-plus" style="margin-right:4px"></i>{{ $t('common.add') }}
            </a-button>
          </div>
        </div>

        <div class="form-meta">
          <span class="meta-creator">{{ $t('item.kanban_task_creator') }}: {{ taskData.creator_username || '-' }}</span>
        </div>

        <div class="form-group" style="margin-bottom:0">
          <div class="form-label form-label-row">
            <span>{{ $t('item.kanban_attachments') }}</span>
            <span class="desc-toolbar">
              <span class="desc-toolbar-btn" @click="handleUploadAttachment" :title="$t('item.kanban_upload_attachment')">
                <i class="fas fa-paperclip"></i>
              </span>
            </span>
          </div>
          <input ref="attachmentInputRef" type="file" style="display:none" @change="onAttachmentSelected" />
          <div v-if="attachmentList.length === 0" class="attachment-empty">{{ $t('item.kanban_no_attachments') }}</div>
          <div v-else class="attachment-list">
            <div v-for="att in attachmentList" :key="att.file_id" class="attachment-item">
              <i class="fas fa-file"></i>
              <a :href="att.url" target="_blank" class="attachment-name">{{ att.display_name }}</a>
              <span class="attachment-time">{{ att.addtime }}</span>
              <span class="icon-btn" @click="handleDeleteAttachment(att)"><i class="fas fa-times"></i></span>
            </div>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:0">
          <PageComment :page-id="Number(taskPageId)" :item-info="itemInfo" />
        </div>
      </div>

      <div class="modal-footer">
        <CommonButton @click="handleClose">{{ $t('common.cancel') }}</CommonButton>
        <CommonButton theme="dark" @click="handleSave">{{ $t('common.save') }}</CommonButton>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, reactive, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import dayjs from 'dayjs'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import ConfirmModal from '@/components/ConfirmModal'
import PageComment from '@/views/item/show/ShowRegularItem/components/PageComment.vue'
import request from '@/utils/request'
import { uploadFile, getPageAttachments, deletePageAttachment } from '@/models/attachment'
import { TAG_COLORS, PRIORITY_OPTIONS } from '../types'
import type { KanbanTaskData } from '../types'

interface Props {
  taskData: KanbanTaskData | null
  taskPageId: string
  itemInfo: any
  lists: any[]
  members: any[]
  onClose: (result: any) => void
}

const props = defineProps<Props>()
const { t } = useI18n()

const show = ref(false)

const tagColorMap: Record<string, string> = {
  red: '#f5222d', orange: '#fa8c16', yellow: '#fadb14',
  green: '#52c41a', blue: '#1890ff', purple: '#722ed1', gray: '#8c8c8c',
}

const editForm = reactive<any>({})
const dueDateObj = ref<any>(null)
const descPreview = ref(false)
const descTextareaRef = ref<any>(null)
const fileInputRef = ref<HTMLInputElement | null>(null)
const attachmentInputRef = ref<HTMLInputElement | null>(null)
const attachmentList = ref<any[]>([])

watch(() => props.taskData, (val) => {
  if (val) {
    Object.assign(editForm, JSON.parse(JSON.stringify(val)))
    editForm._pageTitle = props.taskData?._pageTitle || ''
    dueDateObj.value = editForm.due_date ? dayjs(editForm.due_date) : null
    descPreview.value = false
  }
}, { immediate: true })

const fetchAttachments = async () => {
  if (!props.taskPageId) return
  try {
    const data = await getPageAttachments({ page_id: props.taskPageId })
    if (data.error_code === 0 && data.data) {
      attachmentList.value = data.data || []
    }
  } catch { /* ignore */ }
}

const descPreviewHtml = computed(() => {
  const md = editForm.description || ''
  if (!md) return `<p style="color:var(--color-text-secondary)">${t('item.kanban_task_description_placeholder')}</p>`
  return md
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/^### (.+)$/gm, '<h3>$1</h3>')
    .replace(/^## (.+)$/gm, '<h2>$1</h2>')
    .replace(/^# (.+)$/gm, '<h1>$1</h1>')
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.+?)\*/g, '<em>$1</em>')
    .replace(/`(.+?)`/g, '<code>$1</code>')
    .replace(/!\[([^\]]*)\]\(([^)]+)\)/g, '<img src="$2" alt="$1" style="max-width:100%;border-radius:4px" />')
    .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" style="color:var(--color-active)">$1</a>')
    .replace(/^- (.+)$/gm, '<li>$1</li>')
    .replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>')
    .replace(/\n\n/g, '</p><p>')
    .replace(/\n/g, '<br>')
})

const onDueDateChange = (val: string) => {
  editForm.due_date = val || ''
}

const addTag = () => {
  if (!editForm.tags) editForm.tags = []
  if (editForm.tags.length < 3) {
    editForm.tags.push({ color: 'blue', text: '' })
  }
}

const handleUploadImage = () => {
  fileInputRef.value?.click()
}

const handleUploadAttachment = () => {
  attachmentInputRef.value?.click()
}

const onAttachmentSelected = async (e: Event) => {
  const target = e.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return
  target.value = ''
  if (file.size > 50 * 1024 * 1024) {
    message.warning(t('item.kanban_file_too_large'))
    return
  }
  const formData = new FormData()
  formData.append('file', file)
  formData.append('item_id', String(props.itemInfo.item_id))
  formData.append('page_id', props.taskPageId)
  try {
    const res = await uploadFile(formData)
    if (res.success === 1 || (res.data && res.data.url)) {
      message.success(t('item.kanban_attachment_upload_success'))
      fetchAttachments()
    } else {
      message.error(res.message || res.error_message || t('item.kanban_attachment_upload_failed'))
    }
  } catch {
    message.error(t('item.kanban_attachment_upload_failed'))
  }
}

const handleDeleteAttachment = async (att: any) => {
  try {
    await ConfirmModal({ msg: t('item.kanban_delete_attachment_confirm'), title: t('common.tips') })
  } catch { return }
  try {
    const data = await deletePageAttachment({ file_id: att.file_id, page_id: props.taskPageId })
    if (data.error_code === 0) {
      message.success(t('common.delete_success'))
      fetchAttachments()
    } else {
      message.error(data.error_message || t('common.delete_failed'))
    }
  } catch {
    message.error(t('common.delete_failed'))
  }
}

const onFileSelected = async (e: Event) => {
  const target = e.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return
  target.value = ''
  await uploadAndInsert(file)
}

const handleDescPaste = async (e: ClipboardEvent) => {
  const items = e.clipboardData?.items
  if (!items) return
  for (const item of items) {
    if (item.type.startsWith('image/')) {
      e.preventDefault()
      const file = item.getAsFile()
      if (file) await uploadAndInsert(file)
      return
    }
  }
}

const uploadAndInsert = async (file: File) => {
  if (file.size > 10 * 1024 * 1024) {
    message.warning(t('item.kanban_image_too_large'))
    return
  }
  const formData = new FormData()
  formData.append('file', file)
  formData.append('item_id', String(props.itemInfo.item_id))
  formData.append('page_id', props.taskPageId)
  try {
    const res = await uploadFile(formData)
    const url = res.url || res.data?.url
    if (url) {
      insertAtCursor(`![${file.name}](${url})`)
    } else {
      message.error(res.message || res.error_message || t('common.op_failed'))
    }
  } catch {
    message.error(t('common.op_failed'))
  }
}

const insertAtCursor = (text: string) => {
  const el = descTextareaRef.value?.$el?.querySelector('textarea') || descTextareaRef.value?.$el
  if (!el) {
    editForm.description = (editForm.description || '') + text
    return
  }
  const ta = el as HTMLTextAreaElement
  const start = ta.selectionStart
  const end = ta.selectionEnd
  const before = (editForm.description || '').slice(0, start)
  const after = (editForm.description || '').slice(end)
  editForm.description = before + text + after
  requestAnimationFrame(() => {
    ta.selectionStart = ta.selectionEnd = start + text.length
    ta.focus()
  })
}

const doClose = (result: any) => {
  show.value = false
  setTimeout(() => {
    props.onClose(result)
  }, 300)
}

const handleSave = () => {
  const member = props.members.find((m: any) => String(m.uid) === editForm.assignee_uid)
  editForm.assignee_username = member?.username || ''
  const { _pageTitle, ...taskData } = editForm
  doClose({ action: 'save', pageId: props.taskPageId, title: editForm._pageTitle, taskData })
}

const handleDelete = async () => {
  const confirmed = await ConfirmModal(t('item.kanban_confirm_delete_task'))
  if (!confirmed) return
  doClose({ action: 'delete', pageId: props.taskPageId })
}

const handleClose = () => {
  doClose({ action: 'close' })
}

onMounted(() => {
  setTimeout(() => {
    show.value = true
  })
  fetchAttachments()
})
</script>

<style lang="scss" scoped>
.detail-body {
  padding: 20px 24px;
  max-height: 65vh;
  overflow-y: auto;
}

.form-group {
  margin-bottom: 14px;
}

.form-label {
  font-size: var(--font-size-s);
  color: var(--color-text-secondary);
  margin-bottom: 4px;
  font-weight: 500;
}

.form-label-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.form-input {
  width: 100%;
  height: 36px;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  padding: 0 12px;
  font-size: var(--font-size-m);
  color: var(--color-text-primary);
  background: var(--color-obvious);
  outline: none;
  transition: border-color 0.15s ease;

  &:focus {
    border-color: var(--color-active);
  }
}

.form-row {
  display: flex;
  gap: 12px;
}

.tags-editor {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
}

.tag-item {
  display: flex;
  align-items: center;
  gap: 4px;
}

.tag-color-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
}

.icon-btn {
  cursor: pointer;
  color: var(--color-text-secondary);
  padding: 2px;
  transition: color 0.15s ease;
  &:hover { color: var(--color-red); }
}

.form-meta {
  font-size: var(--font-size-s);
  color: var(--color-text-secondary);
  margin-bottom: 12px;
  padding-top: 8px;
  border-top: 1px solid var(--color-border-light);
}

.meta-creator {
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 16px 24px;
  border-top: 1px solid var(--color-interval);
}

.desc-toolbar {
  display: inline-flex;
  gap: 4px;
}

.desc-toolbar-btn {
  cursor: pointer;
  padding: 2px 6px;
  border-radius: 3px;
  color: var(--color-text-secondary);
  font-size: 12px;
  transition: all 0.15s ease;

  &:hover {
    background: var(--hover-overlay);
    color: var(--color-text-primary);
  }

  &.active {
    color: var(--color-active);
    background: var(--hover-overlay);
  }
}

.desc-preview {
  min-height: 60px;
  padding: 8px;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  font-size: var(--font-size-m);
  line-height: 1.6;
  color: var(--color-text-primary);
  background: var(--color-obvious);
  word-break: break-word;

  :deep(h1) { font-size: 18px; margin: 8px 0 4px; }
  :deep(h2) { font-size: 16px; margin: 8px 0 4px; }
  :deep(h3) { font-size: 14px; margin: 6px 0 4px; }
  :deep(strong) { font-weight: 600; }
  :deep(code) {
    background: var(--color-bg-tertiary);
    padding: 1px 4px;
    border-radius: 3px;
    font-size: var(--font-size-s);
  }
  :deep(img) { max-width: 100%; border-radius: 4px; margin: 4px 0; }
  :deep(ul) { padding-left: 20px; }
  :deep(li) { margin: 2px 0; }
}

.attachment-empty {
  color: var(--color-text-secondary);
  font-size: var(--font-size-s);
  padding: 6px 0;
}

.attachment-list {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.attachment-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 0;
  font-size: var(--font-size-m);

  > i {
    color: var(--color-text-secondary);
    font-size: 12px;
    flex-shrink: 0;
  }
}

.attachment-name {
  color: var(--color-active);
  text-decoration: none;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
  min-width: 0;

  &:hover { text-decoration: underline; }
}

.attachment-time {
  color: var(--color-text-secondary);
  font-size: var(--font-size-s);
  flex-shrink: 0;
}

.header-action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 4px;
  cursor: pointer;
  color: var(--color-text-secondary);
  font-size: 13px;
  transition: all 0.15s ease;
  margin-right: 4px;

  &:hover {
    background: var(--hover-overlay);
    color: var(--color-text-primary);
  }

  &.danger:hover {
    color: var(--color-red);
    background: var(--hover-overlay);
  }
}

.completed-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 6px;
  cursor: pointer;
  margin-bottom: 14px;
  transition: all 0.15s ease;
  border: 1px solid var(--color-border);
  color: var(--color-text-secondary);
  background: var(--color-obvious);

  &:hover {
    border-color: var(--color-success, #52c41a);
    background: var(--hover-overlay);
  }

  &.active {
    border-color: var(--color-success, #52c41a);
    background: rgba(82, 196, 26, 0.06);
    color: var(--color-success, #52c41a);
  }
}

.completed-icon {
  font-size: 18px;
}

.completed-text {
  font-size: var(--font-size-m);
  font-weight: 500;
}
</style>
