<template>
  <EditorModal :show="show" :mask-closable="false" @close="handleClose">
    <div class="page-edit-container">
      <!-- 顶部工具栏 -->
      <div class="edit-header">
        <div class="header-left">
          <!-- 关闭按钮 -->
          <a-button class="close-btn" @click="handleClose">
            <i class="fas fa-times"></i>
          </a-button>

          <!-- 页面标题编辑 -->
          <template v-if="!isEditingTitle">
            <a-tooltip :title="$t('page.click_to_edit_page_title')">
              <span class="page-title" @click="isEditingTitle = true">
                {{ form.title || $t('page.untitled') }}
              </span>
            </a-tooltip>
          </template>
          <CommonInput
            v-else
            v-model="form.title"
            class="page-title-input"
            :placeholder="$t('page.input_page_title')"
            @blur="isEditingTitle = false"
            @keyup.enter="isEditingTitle = false"
          />

          <!-- 目录选择 -->
          <a-tooltip :title="$t('page.select_catalog')">
            <span class="catalog-selector" @click="handleShowSelectCatalog">
              <i class="fas fa-folder-open"></i>
              {{ catalogName }}
            </span>
          </a-tooltip>
        </div>

        <div class="header-right">
          <!-- 主题切换 -->
          <a-tooltip
            :title="
              appStore.theme === 'light'
                ? $t('common.dark_mode')
                : $t('common.light_mode')
            "
            placement="bottom"
          >
            <div class="icon-item theme-toggle-item" @click="handleToggleTheme">
              <i class="fas fa-circle-half-stroke"></i>
            </div>
          </a-tooltip>

          <!-- 保存按钮 -->
          <MenuButton
            :text="$t('common.save')"
            :theme="'dark'"
            :list="saveMenuList"
            :spinning="saving"
            :left-icon="['fas', 'fa-save']"
            :on-click="handleSave"
          />
        </div>
      </div>

      <!-- 工具按钮组 -->
      <div class="fun-btn-group">
        <!-- 模板 -->
        <MenuButton
          :text="$t('page.insert_template')"
          :theme="'light'"
          :list="templateMenuList"
          :left-icon="['far', 'fa-files']"
        />

        <!-- 格式工具 -->
        <MenuButton
          :text="$t('page.format_tools')"
          :theme="'light'"
          :list="formatToolMenuList"
          :left-icon="['far', 'fa-gear']"
        />

        <!-- 文档工具 -->
        <MenuButton
          :text="$t('page.document_tools')"
          :theme="'light'"
          :list="documentToolMenuList"
          :left-icon="['fas', 'fa-gear']"
        />

        <!-- 附件 -->
        <a-badge
          :count="attachmentCount"
          :offset="[10, 10]"
          :color="'var(--icon-tag-color)'"
        >
          <CommonButton
            :text="$t('page.attachments')"
            :left-icon="['fas', 'fa-paperclip']"
            :theme="'light'"
            @click="handleShowAttachment"
          />
        </a-badge>
      </div>

      <!-- 编辑器区域 -->
      <div class="edit-content">
        <EditormdEditor
          ref="editormdEditorRef"
          v-if="showEditor"
          v-model="form.content"
          mode="editor"
          height="70vh"
          :upload="uploadConfig"
          @load="onEditorReady"
          @upload-error="handleUploadError"
        />
      </div>
      <!-- 隐藏的pastebin元素，用于提取HTML的纯文本 -->
      <div id="pastebin" contenteditable="true"></div>
    </div>
  </EditorModal>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store'
import request from '@/utils/request'
import { renderPageContent } from '@/models/page'
import Message from '@/components/Message'
import EditorModal from '@/components/EditorModal.vue'
import {
  apiTemplateZh,
  databaseTemplateZh,
  apiTemplateEn,
  databaseTemplateEn,
} from '@/utils/template'
import CommonButton from '@/components/CommonButton.vue'
import CommonInput from '@/components/CommonInput.vue'
import MenuButton from '@/components/MenuButton.vue'
import type { ContextmenuModalItemInterface } from '@/components/ContextmenuModal'
// 引入 ShowDoc 编辑器适配器（包装底层 EditormdEditor 组件）
// 适配器提供了 ShowDoc 特定的默认配置和事件处理
import EditormdEditor from '@/components/EditormdEditor/ShowdocAdapter.vue'
import NotifyModal from '@/views/modals/page/NotifyModal/index'
import JsonToTableModal from '@/views/modals/page/JsonToTableModal/index'
import JsonBeautifyModal from '@/views/modals/page/JsonBeautifyModal/index'
import PasteTableModal from '@/views/modals/page/PasteTableModal/index'
import SqlToMarkdownModal from '@/views/modals/page/SqlToMarkdownModal/index'
import MockModal from '@/views/modals/page/MockModal/index'
import HistoryModal from '@/views/modals/page/HistoryModal/index'
import AttachmentListModal from '@/views/modals/page/AttachmentListModal/index'
import TemplateSelectModal from '@/views/modals/page/TemplateSelectModal/index'
import SaveTemplateModal from '@/views/modals/page/SaveTemplateModal/index'
import AIModal from '@/views/modals/page/AIModal/index'
import ConfirmModal from '@/components/ConfirmModal/index'
import AlertModal from '@/components/AlertModal'
import CatalogSelectModal from '@/views/modals/catalog/SelectCatalogModal/index'

// Props
interface Props {
  itemId: string | number
  editPageId?: string | number // 编辑模式: 传入pageId
  copyPageId?: string | number // 复制模式: 传入要复制的pageId
  catId?: string | number // 在指定目录下创建页面
  onClose: (result: boolean) => void
}

const props = withDefaults(defineProps<Props>(), {
  editPageId: '',
  copyPageId: '',
  catId: '',
})

// Composables
const router = useRouter()
const { t, locale } = useI18n()
const appStore = useAppStore()

// Refs
const form = ref({
  title: '',
  content: '',
  catId: 0,
})
const isEditingTitle = ref(false)
const saving = ref(false)
const catalogs = ref<any[]>([])
const editorRef = ref<any>(null) // 编辑器内部实例（由 @load 事件赋值）
const editormdEditorRef = ref<any>(null) // EditormdEditor 组件引用
const isLocked = ref(false)
const attachmentCount = ref(0)
const showAIBtn = ref(false)
const originalContent = ref({ title: '', content: '', catId: 0 }) // 存储原始内容
const showEditor = ref(false) // 控制编辑器显示时机，确保数据加载完成后再渲染编辑器
let draftTimer: any = null
let lockTimer: any = null
let lockHeartbeatTimer: any = null

// 占位函数，后面会实现
let toggleLock: any = null

// ========== 编辑器配置 ==========
const uploadConfig = computed(() => ({
  handler: async (files: File[]) => {
    const results: { url: string }[] = []
    for (const file of files) {
      try {
        const url = await uploadFile(file)
        results.push({ url })
      } catch (error) {
        console.error('上传文件失败:', error)
        throw error
      }
    }
    return results
  },
  maxFileSize: 10 * 1024 * 1024,
  accept: 'image/*',
  multiple: true,
}))

// 保存菜单
const saveMenuList = computed<ContextmenuModalItemInterface[]>(() => [
  {
    icon: ['fas', 'fa-comment-dots'],
    text: t('page.save_and_notify'),
    value: 'notify',
    onclick: handleNotify,
  },
  {
    icon: ['fas', 'fa-file-export'],
    text: t('page.save_as_template'),
    value: 'template',
    onclick: handleSaveTemplate,
  },
  {
    icon: isLocked.value ? ['fas', 'fa-unlock'] : ['fas', 'fa-lock'],
    text: isLocked.value ? t('page.unlock') : t('page.lock_edit'),
    value: 'lock',
    onclick: toggleLock,
  },
])

// 模板菜单
const templateMenuList = computed<ContextmenuModalItemInterface[]>(() => [
  {
    icon: ['fas', 'fa-plug'],
    text: t('page.insert_api_template'),
    value: 'apidoc',
    onclick: () => handleTemplateItem('apidoc'),
  },
  {
    icon: ['fas', 'fa-database'],
    text: t('page.insert_database_template'),
    value: 'database',
    onclick: () => handleTemplateItem('database'),
  },
  {
    icon: ['fas', 'fa-folder-open'],
    text: t('page.template_list'),
    value: 'more',
    onclick: handleOpenTemplateList,
  },
])

// 格式工具菜单
const formatToolMenuList = computed<ContextmenuModalItemInterface[]>(() => [
  {
    icon: ['fas', 'fa-table'],
    text: t('page.json_to_table'),
    value: 'toTable',
    onclick: () => handleFormatToolItem('toTable'),
  },
  {
    icon: ['fas', 'fa-th'],
    text: t('page.beautify_json'),
    value: 'beautify',
    onclick: () => handleFormatToolItem('beautify'),
  },
  {
    icon: ['fas', 'fa-table'],
    text: t('page.paste_insert_table'),
    value: 'pasteTable',
    onclick: () => handleFormatToolItem('pasteTable'),
  },
  {
    icon: ['fas', 'fa-database'],
    text: t('page.sql_to_markdown_table'),
    value: 'sqlToTable',
    onclick: () => handleFormatToolItem('sqlToTable'),
  },
])

// 文档工具菜单
const documentToolMenuList = computed<ContextmenuModalItemInterface[]>(() => {
  const list: ContextmenuModalItemInterface[] = [
    {
      icon: ['fas', 'fa-history'],
      text: t('page.page_history_version'),
      value: 'history',
      onclick: () => handleDocumentToolItem('history'),
    },
  ]
  if (showAIBtn.value) {
    list.push({
      icon: ['fas', 'fa-robot'],
      text: t('page.ai_assistant'),
      value: 'ai',
      onclick: () => handleDocumentToolItem('ai'),
    })
  }
  list.push(
    {
      icon: ['fas', 'fa-vial'],
      text: t('page.mock_config'),
      value: 'mock',
      onclick: () => handleDocumentToolItem('mock'),
    },
    {
      icon: ['fas', 'fa-link'],
      text: t('page.http_test_api'),
      value: 'runapi',
      onclick: () => handleDocumentToolItem('runapi'),
    }
  )
  return list
})

// Computed
const show = ref(true)
const itemId = computed(() => props.itemId)
const currentPageId = ref(props.editPageId || '') // 使用 ref，保存新页面后可更新（改为空字符串避免数字0）

const catalogName = computed(() => {
  const cat = catalogs.value.find((c) => c.catId === form.value.catId)
  return cat ? cat.title : t('catalog.root_catalog')
})

// Methods
const handleToggleTheme = () => {
  appStore.toggleTheme()
}

const handleClose = () => {
  props.onClose(false)
  // 如果有页面ID，跳转刷新页面
  if (currentPageId.value) {
    // 使用 router.replace 而不是 window.location.href，确保兼容 Hash 模式
    router.replace(`/${itemId.value}/${currentPageId.value}`)
    // 强制刷新页面，确保内容更新
    setTimeout(() => {
      window.location.reload()
    }, 100)
  }
}

const loadPageContent = async (loadPageId: string | number) => {
  if (!loadPageId) return

  try {
    const data = await request(
      '/api/page/info',
      {
        page_id: String(loadPageId),
      },
      'post',
      false
    )

    if (data.error_code === 0 && data.data) {
      form.value.title = data.data.page_title || ''
      form.value.catId = Number(data.data.cat_id || 0)
      form.value.content = renderPageContent(data.data.page_content || '')
      attachmentCount.value =
        data.data.attachment_count > 0 ? data.data.attachment_count : 0

      // 存储原始内容，用于比较是否有修改
      originalContent.value = {
        title: form.value.title,
        content: form.value.content,
        catId: form.value.catId,
      }
    }
  } catch (error) {
    console.error('获取页面内容失败:', error)
    await AlertModal(t('page.fetch_content_failed'))
  }
}

const fetchAttachmentCount = async () => {
  if (!currentPageId.value) return

  try {
    const data = await request(
      '/api/page/info',
      {
        page_id: String(currentPageId.value),
      },
      'post',
      false
    )

    if (data.error_code === 0 && data.data) {
      attachmentCount.value =
        data.data.attachment_count > 0 ? data.data.attachment_count : 0
    }
  } catch (error) {
    console.error('获取附件数量失败:', error)
  }
}

const loadCatalogs = async () => {
  if (!itemId.value) return

  try {
    const data = await request(
      '/api/catalog/catListName',
      {
        item_id: String(itemId.value),
      },
      'post',
      false
    )

    if (data.error_code === 0 && data.data) {
      catalogs.value = [
        { catId: 0, title: t('catalog.root_catalog') },
        ...(data.data || []).map((cat: any) => ({
          catId: Number(cat.cat_id),
          title: cat.cat_name,
        })),
      ]
    }
  } catch (error) {
    console.error('获取目录列表失败:', error)
  }
}

const handleSave = async (notify = false, notifyContent = '') => {
  if (!itemId.value) {
    await AlertModal(t('page.item_id_required'))
    return
  }

  // 如果标题为空，使用默认标题
  if (!form.value.title.trim()) {
    isEditingTitle.value = false
    form.value.title = t('page.untitled')
  }

  saving.value = true

  try {
    const response = await request(
      '/api/page/save',
      {
        page_id: currentPageId.value || '',
        item_id: String(itemId.value),
        cat_id: form.value.catId,
        page_title: form.value.title,
        is_urlencode: 1,
        page_content: encodeURIComponent(form.value.content),
        is_notify: notify ? 1 : 0,
        notify_content: notifyContent,
      },
      'post',
      false
    )

    if (response.error_code === 0) {
      Message.success(t('page.save_success'))

      // 更新页面ID（如果是新建页面）
      if (!currentPageId.value && response.data?.page_id) {
        currentPageId.value = response.data.page_id
      }

      // 清除草稿
      clearDraft()

      // 更新原始内容
      originalContent.value = {
        title: form.value.title,
        content: form.value.content,
        catId: form.value.catId,
      }

      // 通知父组件页面已更新（但不关闭弹窗）
      // props.onClose(true)
    } else if (response.error_code === 10401) {
      // 页面数超限
      const msg = t('page.page_limit_exceeded_with_link')
      await AlertModal(msg, { dangerouslyUseHTMLString: true })
      return
    } else {
      await AlertModal(response.error_message || t('page.save_failed'))
    }
  } catch (error) {
    console.error('保存失败:', error)
    await AlertModal(t('page.save_failed'))
  } finally {
    saving.value = false
  }
}

const handleShowSelectCatalog = async () => {
  const newCatId = await CatalogSelectModal({
    itemId: itemId.value,
    catId: form.value.catId,
  })

  if (newCatId > 0) {
    form.value.catId = newCatId
    await refreshCat()
    await handleSave()
  }
}

const refreshCat = async () => {
  await loadCatalogs()
}

const handleTemplateItem = async (key: string) => {
  if (key === 'more') {
    await TemplateSelectModal({
      itemId: itemId.value,
      onInsert: (content: string) => {
        insertAtCursor(content)
      },
    })
  } else if (key === 'apidoc') {
    // 插入 API 文档模板，根据当前语言选择中文或英文版本
    const template = locale.value === 'en-US' ? apiTemplateEn : apiTemplateZh
    insertAtCursor(template)
  } else if (key === 'database') {
    // 插入数据库文档模板，根据当前语言选择中文或英文版本
    const template =
      locale.value === 'en-US' ? databaseTemplateEn : databaseTemplateZh
    insertAtCursor(template)
  }
}

const handleFormatToolItem = async (key: string) => {
  if (key === 'toTable') {
    await JsonToTableModal({
      onInsert: (table: string) => {
        insertAtCursor(table)
      },
    })
  } else if (key === 'beautify') {
    await JsonBeautifyModal({
      onInsert: (json: string) => {
        insertAtCursor(json)
      },
    })
  } else if (key === 'pasteTable') {
    await PasteTableModal({
      onInsert: (table: string) => {
        insertAtCursor(table)
      },
    })
  } else if (key === 'sqlToTable') {
    await SqlToMarkdownModal({
      onInsert: (table: string) => {
        insertAtCursor(table)
      },
    })
  }
}

const handleDocumentToolItem = async (key: string) => {
  if (key === 'history') {
    if (!currentPageId.value) {
      await AlertModal(t('page.please_save_page_first'))
      return
    }
    await HistoryModal({
      pageId: currentPageId.value,
      onRestore: async (pageContent: string) => {
        form.value.content = pageContent
        Message.success(t('page.restore_success'))
      },
    })
  } else if (key === 'ai') {
    await handleOpenAI()
  } else if (key === 'mock') {
    if (!currentPageId.value) {
      await AlertModal(t('page.please_save_page_first'))
      return
    }
    await MockModal({
      pageId: currentPageId.value,
      itemId: itemId.value,
    })
  } else if (key === 'runapi') {
    window.open('http://runapi.showdoc.cc/')
  }
}

const handleSaveTemplate = async () => {
  await SaveTemplateModal({
    content: form.value.content,
    onSuccess: () => {
      Message.success(t('page.save_template_success'))
    },
  })
}

const handleNotify = async () => {
  await NotifyModal({
    itemId: itemId.value,
    pageId: currentPageId.value,
    onConfirm: async (content: string) => {
      await handleSave(true, content)
    },
  })
}

const handleOpenTemplateList = async () => {
  await TemplateSelectModal({
    itemId: itemId.value,
    onInsert: (content: string) => {
      insertAtCursor(content)
    },
  })
}

const handleToggleLock = async () => {
  if (!currentPageId.value) {
    await AlertModal(t('page.page_id_required'))
    return
  }

  try {
    // 使用与旧版相同的接口 /api/page/setLock
    // 解锁时传入 lock_to: 1000，锁定时不传此参数
    const params: any = {
      page_id: String(currentPageId.value),
      item_id: String(itemId.value),
    }

    if (isLocked.value) {
      // 解锁
      params.lock_to = 1000
    }

    const response = await request('/api/page/setLock', params, 'post', false)

    if (response.error_code === 0) {
      isLocked.value = !isLocked.value
      Message.success(
        isLocked.value ? t('page.lock_success') : t('page.unlock_success')
      )
    } else {
      await AlertModal(response.error_message || t('page.operation_failed'))
    }
  } catch (error) {
    console.error('锁定操作失败:', error)
    await AlertModal(t('page.operation_failed'))
  }
}

const handleOpenAI = async () => {
  await AIModal({
    pageId: currentPageId.value,
    itemId: itemId.value,
    onInsert: (content: string) => {
      insertAtCursor(content)
    },
  })
}

// 快捷键支持
const handleKeydown = (e: KeyboardEvent) => {
  // Ctrl+S 或 Cmd+S 保存
  if ((e.ctrlKey || e.metaKey) && e.key === 's') {
    e.preventDefault()
    handleSave()
  }
}

// 检查编辑器是否获得焦点
const isEditorFocused = (): boolean => {
  if (!editorRef.value) return false

  // 方法1: 尝试通过 CodeMirror 实例获取包装器
  let editorElement: HTMLElement | null = null

  // Editormd 实例结构: instance.cm (CodeMirror 实例)
  // CodeMirror 实例有 getWrapperElement() 方法
  if (editorRef.value.cm && typeof editorRef.value.cm.getWrapperElement === 'function') {
    editorElement = editorRef.value.cm.getWrapperElement()
  }

  // 方法2: 如果上面失败，尝试通过编辑器 ID 查找
  if (!editorElement && editorRef.value.id) {
    editorElement = document.querySelector(`#${editorRef.value.id} .CodeMirror-wrap`) as HTMLElement
  }

  // 方法3: 尝试通过 getInstance() 获取
  if (!editorElement && editormdEditorRef.value && editormdEditorRef.value.getInstance) {
    const instance = editormdEditorRef.value.getInstance()
    if (instance?.cm) {
      editorElement = instance.cm.getWrapperElement()
    }
  }

  if (!editorElement) return false

  return editorElement.contains(document.activeElement)
}

// 剪切板事件处理
const handlePaste = async (e: ClipboardEvent) => {
  // 只在编辑器获得焦点时处理粘贴事件
  if (!isEditorFocused()) return

  if (!editorRef.value) return

  const clipboard = e.clipboardData
  if (!clipboard) return

  for (let i = 0; i < clipboard.items.length; i++) {
    const item = clipboard.items[i]

    // 如果是图片 - 让编辑器的上传功能处理
    if (item.type.indexOf('image') > -1) {
      e.preventDefault()
      const imageFile = item.getAsFile()
      // 使用组件引用，确保能正确调用
      if (imageFile && editormdEditorRef.value) {
        // 使用编辑器的上传功能
        try {
          const url = await uploadFile(imageFile)
          editormdEditorRef.value.insertValue(`![${imageFile.name}](${url})`)
          editormdEditorRef.value.focus()
        } catch (error) {
          console.error('粘贴上传图片失败:', error)
        }
      }
      return
    }

    // 如果是HTML
    if (item.type === 'text/html') {
      e.preventDefault()

      // 使用 Promise 包装 getAsString
      const htmlData = await new Promise<string>((resolve) => {
        item.getAsString(resolve)
      })

      // 使用DOM方式提取纯文本（更准确，能正确处理换行、空白等）
      const pastebin = document.querySelector('#pastebin') as HTMLElement
      if (pastebin) {
        pastebin.innerHTML = htmlData
        const text = pastebin.innerText || pastebin.textContent || ''
        pastebin.innerHTML = '' // 清空，避免影响下次使用

        if (text.length < 200) {
          insertAtCursor(text)
        } else {
          // 使用公共弹窗组件询问用户是否转Markdown
          const convertToMarkdown = await ConfirmModal({
            msg: t('page.paste_html_tips'),
            confirmText: t('page.past_html_markdown'),
            cancelText: t('page.past_html_text'),
          })

          if (convertToMarkdown) {
            // 简单的HTML转Markdown（这里使用基础转换）
            const markdown = htmlToMarkdown(htmlData)
            insertAtCursor(markdown)
          } else {
            insertAtCursor(text)
          }
        }
      } else {
        // 如果pastebin不存在，降级使用正则方式
        const text = htmlData.replace(/<[^>]+>/g, '')
        if (text.length < 200) {
          insertAtCursor(text)
        } else {
          const convertToMarkdown = await ConfirmModal({
            msg: t('page.paste_html_tips'),
            confirmText: t('page.past_html_markdown'),
            cancelText: t('page.past_html_text'),
          })

          if (convertToMarkdown) {
            const markdown = htmlToMarkdown(htmlData)
            insertAtCursor(markdown)
          } else {
            insertAtCursor(text)
          }
        }
      }
      return
    }
  }
}

// 简单的HTML转Markdown函数
const htmlToMarkdown = (html: string): string => {
  return html
    .replace(/<strong[^>]*>(.*?)<\/strong>/gi, '**$1**')
    .replace(/<b[^>]*>(.*?)<\/b>/gi, '**$1**')
    .replace(/<em[^>]*>(.*?)<\/em>/gi, '*$1*')
    .replace(/<i[^>]*>(.*?)<\/i>/gi, '*$1*')
    .replace(/<h1[^>]*>(.*?)<\/h1>/gi, '# $1\n')
    .replace(/<h2[^>]*>(.*?)<\/h2>/gi, '## $1\n')
    .replace(/<h3[^>]*>(.*?)<\/h3>/gi, '### $1\n')
    .replace(/<code[^>]*>(.*?)<\/code>/gi, '`$1`')
    .replace(/<pre[^>]*><code[^>]*>(.*?)<\/code><\/pre>/gis, '```\n$1\n```')
    .replace(/<br\s*\/?>/gi, '\n')
    .replace(/<[^>]+>/g, '')
}

const uploadFile = async (file: File): Promise<string> => {
  const formData = new FormData()
  formData.append('file', file)
  formData.append('item_id', String(itemId.value))
  formData.append('page_id', String(currentPageId.value || 0))

  try {
    const result = (await request(
      '/api/attachment/attachmentUpload',
      formData,
      'post',
      false
    )) as any

    if (result && result.success === 1 && result.url) {
      return result.url
    } else {
      // 抛出异常，包含错误信息供上层处理
      const errorMsg = result?.message || result?.error_message || '上传失败'
      const err = new Error(errorMsg)
      ;(err as any).apiError = result
      throw err
    }
  } catch (error) {
    console.error('上传文件失败:', error)
    throw error
  }
}

const handleUploadError = (error: Error) => {
  console.error('上传失败:', error)
  // 从错误对象中获取具体的错误信息
  const errorMsg =
    (error as any).apiError?.message ||
    (error as any).apiError?.error_message ||
    error.message ||
    t('page.upload_failed')
  AlertModal(errorMsg)
}

const checkShowAI = async () => {
  try {
    const data = await request('/api/common/homePageSetting', {}, 'post', false)
    if (data.data && data.data.is_show_ai) {
      showAIBtn.value = true
    }
  } catch (error) {
    console.error('获取AI配置失败:', error)
  }
}

// 草稿自动保存相关
const saveDraftToLocal = () => {
  if (currentPageId.value) {
    // 只有内容发生变化时才保存草稿
    const hasChanged =
      form.value.title !== originalContent.value.title ||
      form.value.content !== originalContent.value.content ||
      form.value.catId !== originalContent.value.catId

    if (!hasChanged) {
      return // 没有变化，不保存草稿
    }

    const draftKey = `showdoc_draft_${currentPageId.value}`
    localStorage.setItem(
      draftKey,
      JSON.stringify({
        title: form.value.title,
        content: form.value.content,
        catId: form.value.catId,
        timestamp: Date.now(),
      })
    )
  }
}

const loadDraftFromLocal = () => {
  if (currentPageId.value) {
    const draftKey = `showdoc_draft_${currentPageId.value}`
    const draftStr = localStorage.getItem(draftKey)
    if (draftStr) {
      try {
        const draft = JSON.parse(draftStr)
        const time = new Date(draft.timestamp).toLocaleString()
        if (confirm(t('page.recover_draft_confirm', { time }))) {
          form.value.title = draft.title
          form.value.content = draft.content
          form.value.catId = draft.catId
          localStorage.removeItem(draftKey)
        }
      } catch (error) {
        console.error('读取草稿失败:', error)
        localStorage.removeItem(draftKey)
      }
    }
  }
}

const clearDraft = () => {
  if (currentPageId.value) {
    const draftKey = `showdoc_draft_${currentPageId.value}`
    localStorage.removeItem(draftKey)
  }
}

// 页面锁定检查
const checkPageLock = async () => {
  if (!currentPageId.value) return

  try {
    const data = await request(
      '/api/page/info',
      {
        page_id: String(currentPageId.value),
      },
      'post',
      false
    )

    if (data.error_code === 0 && data.data) {
      isLocked.value = data.data.is_locked === 1
    }
  } catch (error) {
    console.error('检查页面锁定状态失败:', error)
  }
}

// 锁定页面
const lockPage = async () => {
  if (!currentPageId.value) return

  try {
    const data = await request(
      '/api/page/setLock',
      {
        page_id: String(currentPageId.value),
        item_id: String(itemId.value),
      },
      'post',
      false
    )
    if (data.error_code === 0) {
      isLocked.value = true
    }
  } catch (error) {
    console.error('锁定页面失败:', error)
  }
}

// 解锁页面
const unlockPage = async () => {
  if (!isLocked.value) return

  try {
    const data = await request(
      '/api/page/setLock',
      {
        page_id: String(currentPageId.value),
        item_id: String(itemId.value),
        lock_to: 1000,
      },
      'post',
      false
    )
    if (data.error_code === 0) {
      isLocked.value = false
    }
  } catch (error) {
    console.error('解锁页面失败:', error)
  }
}

// 心跳保持锁定
const startHeartbeatLock = () => {
  lockHeartbeatTimer = setInterval(() => {
    if (isLocked.value) {
      lockPage()
    }
  }, 20 * 60 * 1000) // 20分钟
}

// 切换锁定/解锁状态（供菜单使用）
toggleLock = async () => {
  await handleToggleLock()
}

// 检查远程锁定状态
const checkRemoteLock = async () => {
  if (!currentPageId.value) return

  try {
    const data = await request(
      '/api/page/isLock',
      {
        page_id: String(currentPageId.value),
        item_id: String(itemId.value),
      },
      'post',
      false
    )

    if (data.error_code === 0 && data.data) {
      // 如果已被锁定
      if (data.data.lock > 0) {
        if (data.data.is_cur_user > 0) {
          // 是当前用户锁定
          isLocked.value = true
        } else {
          // 是其他用户锁定，提示并关闭
          alert(t('page.locking') + data.data.lock_username)
          props.onClose(false)
        }
      } else {
        // 没有被锁定，自己锁定
        await lockPage()
      }
    }
  } catch (error) {
    console.error('检查远程锁定状态失败:', error)
  }
}

const handleShowAttachment = async () => {
  await AttachmentListModal({
    itemId: itemId.value,
    pageId: currentPageId.value,
    manage: true,
    onClose: () => {
      // 刷新附件数量
      fetchAttachmentCount()
    },
    onInsert: (markdown: string) => {
      insertAtCursor(markdown)
    },
  })
}

const onEditorReady = (editor: any) => {
  editorRef.value = editor
}

const insertAtCursor = (text: string) => {
  // 优先使用 editormdEditorRef（组件引用），如果没有再尝试 editorRef（内部实例）
  if (editormdEditorRef.value) {
    editormdEditorRef.value.insertValue(text)
    // 插入后恢复焦点
    if (editormdEditorRef.value.focus) {
      editormdEditorRef.value.focus()
    }
  } else if (editorRef.value) {
    editorRef.value.insertValue(text)
    // 插入后恢复焦点
    if (editorRef.value.focus) {
      editorRef.value.focus()
    }
  }
}

// 监听表单变化，自动保存草稿
watch(
  () => [form.value.title, form.value.content, form.value.catId],
  () => {
    // 防抖，避免频繁保存
    clearTimeout(draftTimer)
    draftTimer = setTimeout(() => {
      saveDraftToLocal()
    }, 2000)
  },
  { deep: true }
)

// Lifecycle
onMounted(async () => {
  show.value = true

  // 加载目录列表
  await loadCatalogs()

  // 根据模式初始化
  if (props.copyPageId > 0) {
    // 复制模式: 获取原页面内容
    await loadPageContent(props.copyPageId)
    form.value.title = t('page.copy_of', { title: form.value.title })
  } else if (props.editPageId > 0) {
    // 编辑模式: 获取现有内容
    await loadPageContent(props.editPageId)
    await checkPageLock()
    // 检查是否有本地草稿
    loadDraftFromLocal()
    // 检查远程锁定状态并自动锁定
    await checkRemoteLock()
    // 启动心跳保持锁定
    startHeartbeatLock()
  } else {
    // 新建模式: 初始化空内容
    form.value.catId = Number(props.catId)
    form.value.title = ''
    form.value.content = ''
    originalContent.value = {
      title: '',
      content: '',
      catId: Number(props.catId),
    }
  }

  // 数据加载完成后，再显示编辑器，确保编辑器初始化时就有正确内容
  showEditor.value = true

  // 检查AI按钮显示
  await checkShowAI()

  // 添加快捷键监听
  window.addEventListener('keydown', handleKeydown)

  // 添加剪切板监听
  document.addEventListener('paste', handlePaste)
})

onBeforeUnmount(() => {
  // 清理定时器
  clearTimeout(draftTimer)
  clearInterval(lockTimer)
  clearInterval(lockHeartbeatTimer)

  // 清理草稿
  clearDraft()

  // 解锁页面
  unlockPage()

  // 移除事件监听
  window.removeEventListener('keydown', handleKeydown)
  document.removeEventListener('paste', handlePaste)
})
</script>

<style lang="scss" scoped>
.page-edit-container {
  display: flex;
  flex-direction: column;
  height: 100%;
}

// 顶部工具栏（克制设计）
.edit-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  background-color: var(--color-bg-primary);
  border-bottom: 1px solid var(--color-border);
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: nowrap;
}

// 右侧按钮组（克制设计）
.header-right {
  display: flex;
  align-items: center;
  gap: 10px;

  .icon-item {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--color-bg-secondary);
    border-radius: 8px;
    box-shadow: var(--shadow-xs);
    cursor: pointer;
    transition: all 0.15s ease;

    &:hover {
      background-color: var(--hover-overlay);
      box-shadow: var(--shadow-sm);
    }

    i {
      color: var(--color-text-primary);
      font-size: 16px;
    }
  }

  .theme-toggle-item i {
    color: var(--color-orange);
  }
}

// 关闭按钮（克制设计）
.close-btn {
  width: 40px;
  height: 40px;
  padding: 0;
  border-radius: 8px;
  font-size: 16px;
  color: var(--color-text-primary);
  border: 1px solid var(--color-border);
  background: var(--color-bg-primary);
  box-shadow: var(--shadow-xs);
  transition: all 0.15s ease;

  &:hover {
    background: var(--hover-overlay);
    box-shadow: var(--shadow-sm);
    color: var(--color-text-primary);
    border-color: var(--color-border);
  }
}

// 页面标题（克制设计）
.page-title {
  margin: 0 8px;
  font-size: 16px;
  font-weight: 500;
  color: var(--color-text-primary);
  cursor: pointer;
  padding: 8px 12px;
  border-radius: 6px;
  transition: all 0.15s ease;

  &:hover {
    background: var(--hover-overlay);
  }
}

.page-title-input {
  margin: 0 8px;
  min-width: 200px;
  max-width: 30vw;
}

// 目录选择器（克制设计）
.catalog-selector {
  display: inline-flex;
  align-items: center;
  padding: 8px 12px;
  background-color: var(--color-bg-secondary);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  box-shadow: var(--shadow-xs);
  cursor: pointer;
  font-size: 14px;
  color: var(--color-text-secondary);
  transition: all 0.15s ease;
  max-width: 300px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;

  i {
    margin-right: 6px;
    font-size: 14px;
  }

  &:hover {
    background-color: var(--hover-overlay);
    border-color: var(--color-active);
    color: var(--color-text-primary);
    box-shadow: var(--shadow-sm);
  }
}

// 工具按钮组（克制设计）
.fun-btn-group {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 24px;
  background-color: var(--color-bg-primary);
  border-bottom: 1px solid var(--color-border);

  .ant-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;

    i {
      margin-right: 4px;
    }
  }

  .ant-dropdown-trigger {
    display: inline-flex;
    align-items: center;
  }
}

.edit-content {
  flex: 1;
  background-color: var(--color-bg-primary);
  padding: 20px 24px;
  overflow-y: auto;
  overflow-x: hidden;
}

// 隐藏的pastebin元素，用于提取HTML的纯文本
#pastebin {
  opacity: 0.01;
  width: 100%;
  height: 1px;
  position: absolute;
  left: -9999px;
  overflow: hidden;
}

// 响应式
@media (max-width: 1200px) {
  .edit-header {
    flex-direction: column;
    gap: 12px;
    padding: 12px 16px;
  }

  .header-left,
  .header-right {
    width: 100%;
    justify-content: center;
    flex-wrap: wrap;
  }

  .fun-btn-group {
    flex-wrap: wrap;
    padding: 12px 16px;
  }
}
</style>
