<template>
  <Teleport to="body">
  <div
    v-if="showWidget"
    class="ai-chat-widget"
    :class="{
      collapsed: isCollapsed,
      fullscreen: isFullscreen,
      'mobile-expanded': isMobile && !isCollapsed,
    }"
  >
    <!-- 折叠状态：悬浮按钮 -->
    <div
      v-if="isCollapsed"
      class="chat-button"
      :class="{ 'is-loading': isLoadingInternal }"
      @click="toggleCollapse"
      :title="t('ai.ai_assistant_button')"
    >
      <i class="fas fa-robot"></i>
      <!-- Loading 旋转动画 -->
      <div v-if="isLoadingInternal" class="chat-button-spinner"></div>
      <!-- 未读消息圆点 -->
      <div v-if="hasUnread" class="chat-button-unread"></div>
    </div>

    <!-- 展开状态：完整对话框 -->
    <div
      v-else
      class="chat-window"
      :class="{ 'chat-window-fullscreen': isFullscreen, 'chat-window-mobile': isMobile }"
      :style="[
        !isFullscreen && !isMobile ? { width: dialogWidth + 'px', height: dialogHeight + 'px' } : {},
        isMobile && keyboardOffset > 0 ? { paddingBottom: keyboardOffset + 'px' } : {},
      ]"
    >
      <!-- 顶部栏：会话管理 -->
      <AiSessionBar
        :sessions="sessions"
        :current-session-id="currentSessionId"
        :global-mode="globalMode"
        :item-name="itemName"
        :is-fullscreen="isFullscreen"
        :is-mobile="isMobile"
        @select="switchSession"
        @new-session="handleNewSession"
        @delete="handleDeleteSession"
        @toggle-collapse="toggleCollapse"
        @toggle-fullscreen="toggleFullscreen"
        @clear-session="handleClearSession"
        @toggle-mobile-drawer="toggleMobileSessionDrawer"
      />

      <!-- 输入展开提示 -->
      <div v-if="isInputExpanded" class="input-expanded-hint">
        <i class="fas fa-edit" style="margin-right: 4px"></i>
        {{ t('ai.editing_long_message') }}
      </div>

      <!-- 引导面板（无消息时且未展开输入） -->
      <AiGuidePanel
        v-if="!isInputExpanded"
        :visible="messages.length === 0 && !isLoading"
        :can-edit="canEdit"
        :global-mode="globalMode"
        :is-guest="isGuest"
        :welcome-message="welcomeMessage"
        @quick-send="handleSend"
      />

      <!-- 消息列表（展开输入时隐藏） -->
      <div v-if="!isInputExpanded && (messages.length > 0 || isLoading)" class="messages" ref="messagesContainer">
        <AiMessageBubble
          v-for="msg in messages"
          :key="msg.clientKey || msg.id || msg.created_at"
          :message="msg"
          :is-loading="isLastAssistantLoading(msg)"
          :status-text="getStatusText(msg)"
          :refs="getMessageRefs(msg)"
          :item-id="itemId"
          @regenerate="handleRegenerate"
          @feedback="handleFeedback"
        />
      </div>

      <!-- 输入区域 -->
      <AiChatInput
        ref="chatInputRef"
        :is-loading="isLoading"
        :disabled="!currentSessionId"
        :is-mobile="isMobile"
        :expanded="isInputExpanded"
        @send="handleSend"
        @stop="handleStop"
        @toggle-expand="toggleInputExpand"
      />
      <!-- 拖拽调整大小手柄（非全屏且非移动端时显示） -->
      <div
        v-if="!isFullscreen && !isMobile"
        class="chat-resize-handle"
        @mousedown.prevent="startResize"
      ></div>
    </div>

    <!-- 移动端会话列表抽屉（在 chat-window 外部，避免 overflow:hidden 影响） -->
    <Transition name="drawer-fade">
      <div v-if="!isCollapsed && isMobile && mobileSessionDrawer" class="mobile-drawer-overlay" @click="mobileSessionDrawer = false">
        <div class="mobile-drawer" @click.stop>
          <div class="mobile-drawer-header">
            <span>{{ t('ai.session_list') }}</span>
            <i class="fas fa-times" @click="mobileSessionDrawer = false"></i>
          </div>
          <div class="mobile-drawer-body">
            <div
              v-for="s in sessions"
              :key="s.session_id"
              class="mobile-session-item"
              :class="{ active: s.session_id === currentSessionId }"
              @click="switchSession(s); mobileSessionDrawer = false"
            >
              <div class="mobile-session-info">
                <span class="mobile-session-title">{{ s.title || t('ai.session_item_default_title', { id: s.session_id }) }}</span>
                <span class="mobile-session-meta">
                  {{ t('ai.message_count', { count: s.message_count }) }}
                </span>
              </div>
              <a-popconfirm
                :title="t('ai.confirm_delete_session')"
                @confirm.stop="handleDeleteSession(s)"
                :ok-text="t('ai.confirm')"
                :cancel-text="t('ai.cancel')"
              >
                <i class="fas fa-trash-alt mobile-session-delete" @click.stop></i>
              </a-popconfirm>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick, triggerRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import { useUserStore } from '@/store'
import AiSessionBar from './AiSessionBar.vue'
import AiGuidePanel from './AiGuidePanel.vue'
import AiMessageBubble from './AiMessageBubble.vue'
import AiChatInput from './AiChatInput.vue'
import {
  loadSession,
  createSession,
  deleteSession as apiDeleteSession,
  resetSession as apiResetSession,
  getAiConfig,
  sendFeedback,
  cancelAgent,
  sendAgentMessage,
  listSessions,
  type AiChatMessage,
  type AiChatSession,
  type SseRef,
} from '@/api/aiAgent'

// 生成前端临时消息稳定标识，避免占位消息 id:0 导致的引用串扰/Key 抖动/取消冲突
const genClientKey = () => Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 8)

// 移动端判断（≤768px）
const isMobile = ref(false)
const checkMobile = () => {
  isMobile.value = window.innerWidth <= 768
}

// visualViewport 键盘适配
let viewportResizeHandler: (() => void) | null = null
const keyboardOffset = ref(0)

interface Props {
  itemInfo?: { item_id: number; item_name?: string } | null
  globalMode?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  itemInfo: null,
  globalMode: false,
})

// Computed from props
const itemId = computed(() => props.itemInfo?.item_id || 0)
const itemName = computed(() => props.itemInfo?.item_name || '')

// User store
const userStore = useUserStore()
const isGuest = computed(() => !userStore.isLoggedIn)

// AI config state
const canEdit = ref(false)

// State
const showWidget = ref(false)
const isCollapsed = ref(true)
const sessions = ref<AiChatSession[]>([])
const currentSessionId = ref<number | null>(null)
// P2: 从 localStorage 恢复上一次活跃的 sessionId
const SESSION_ID_KEY = 'ai_chat_last_session_id'
const savedSessionId = localStorage.getItem(SESSION_ID_KEY)
if (savedSessionId) {
  const parsed = parseInt(savedSessionId, 10)
  if (!isNaN(parsed) && parsed > 0) {
    currentSessionId.value = parsed
  }
}
const messages = ref<AiChatMessage[]>([])

// P2: sessionId 变化时同步到 localStorage
watch(currentSessionId, (newId) => {
  if (newId) {
    localStorage.setItem(SESSION_ID_KEY, String(newId))
  } else {
    localStorage.removeItem(SESSION_ID_KEY)
  }
})
const isLoading = ref(false)
const currentStatus = ref('')
const welcomeMessage = ref('')
const messagesContainer = ref<HTMLElement | null>(null)
const chatInputRef = ref<InstanceType<typeof AiChatInput> | null>(null)
const messageRefs = ref<Map<string, SseRef[]>>(new Map())

// 当前一轮生成的 turn_token（用于按 turn 维度取消）
const currentTurnToken = ref('')
// mcp_unavailable 错误提示去重时间戳
const lastMcpWarnTime = ref(0)

// Fullscreen state
const isFullscreen = ref(false)

// 输入框展开状态
const isInputExpanded = ref(false)

const toggleInputExpand = () => {
  isInputExpanded.value = !isInputExpanded.value
  nextTick(() => {
    if (isInputExpanded.value) {
      chatInputRef.value?.focus()
    }
  })
}

// 悬浮按钮状态：loading + 未读圆点
const isLoadingInternal = ref(false) // AI 在后台生成（对话框折叠时）
const hasUnread = ref(false)        // 折叠时收到 AI 回复

// SSE abort handle
let abortHandle: { abort: () => void } | null = null

// 会话恢复时间阈值（30 分钟）
const SESSION_RESUME_THRESHOLD = 30 * 60 * 1000
// 刷新页面恢复阈值（30 分钟）
const SESSION_REFRESH_THRESHOLD = 30 * 60 * 1000
// localStorage key: 最后一次 AI 聊天活跃时间
const LAST_ACTIVITY_KEY = 'lastAiChatActivityTime'

/** 更新最后一次活跃时间到 localStorage */
const touchActivityTime = () => {
  localStorage.setItem(LAST_ACTIVITY_KEY, String(Date.now()))
}
const lastCloseTime = ref<number | null>(null)
const lastPageSwitchTime = ref<number | null>(null)

const { t } = useI18n()

// ─── Lifecycle ────────────────────────────────────────────

onMounted(() => {
  document.addEventListener('keydown', handleEscKey)
  checkMobile()
  window.addEventListener('resize', checkMobile)

  // 刷新页面时：判断上次活跃时间是否超过 30 分钟
  // 超过则视为用户可能已遗忘上下文，新建会话
  const savedActivity = localStorage.getItem(LAST_ACTIVITY_KEY)
  if (savedActivity) {
    const elapsed = Date.now() - parseInt(savedActivity, 10)
    if (elapsed > SESSION_REFRESH_THRESHOLD) {
      currentSessionId.value = null
      localStorage.removeItem(SESSION_ID_KEY)
    }
  }

  // visualViewport API：键盘弹出时动态调整布局
  if (window.visualViewport) {
    viewportResizeHandler = () => {
      const vv = window.visualViewport!
      // 键盘弹出时 visualViewport.height 会变小，计算偏移量
      const offset = window.innerHeight - vv.height - vv.offsetTop
      keyboardOffset.value = offset > 0 ? offset : 0
    }
    window.visualViewport.addEventListener('resize', viewportResizeHandler)
    window.visualViewport.addEventListener('scroll', viewportResizeHandler)
  }
})

onUnmounted(() => {
  handleStop()
  document.removeEventListener('keydown', handleEscKey)
  window.removeEventListener('resize', checkMobile)
  // 清理残留的拖拽监听器（组件在拖拽中销毁的情况）
  if (resizeMouseMove) document.removeEventListener('mousemove', resizeMouseMove)
  if (resizeMouseUp) document.removeEventListener('mouseup', resizeMouseUp)
  document.body.style.cursor = ''
  document.body.style.userSelect = ''
  // 清理 visualViewport 监听
  if (viewportResizeHandler && window.visualViewport) {
    window.visualViewport.removeEventListener('resize', viewportResizeHandler)
    window.visualViewport.removeEventListener('scroll', viewportResizeHandler)
  }
})

// ─── Config check ─────────────────────────────────────────

const checkConfig = async () => {
  try {
    // 游客仅在项目内可用：全局会话（无 item_id）时直接隐藏，
    // 不发请求，避免后端 config() 返回 10102 触发登录跳转/弹窗
    if (isGuest.value && !(itemId.value > 0)) {
      showWidget.value = false
      return
    }
    const res = await getAiConfig(itemId.value || undefined, false)
    const config = res
    if (config.enabled) {
      showWidget.value = true
    } else {
      showWidget.value = false
    }
    canEdit.value = !!config.can_edit

    // 游客模式下读取 dialog_collapsed 配置控制初始折叠状态
    // （开源版 getAiConfig 直接返回 dialog_collapsed，无需额外请求）
    if (isGuest.value && itemId.value > 0 && showWidget.value) {
      if (config.dialog_collapsed !== undefined) {
        isCollapsed.value = config.dialog_collapsed !== 0
      }
    }
  } catch {
    showWidget.value = false
  }
}

// 防竞态：只有最新的 checkConfig 调用才生效
let configCheckSeq = 0

// 记录上一次生效的 itemId，用于去重
let lastWatchedItemId = -1

watch(() => props.itemInfo, async (newVal, oldVal) => {
  const newId = newVal?.item_id || 0
  const oldId = oldVal?.item_id || 0

  // P1a: 去重 — itemId 未实际变化时不触发重新加载
  // （例如 null → null、null → 0、undefined → 0 等均视为无变化）
  if (newId === lastWatchedItemId) return
  lastWatchedItemId = newId

  // 项目切换时清空旧会话ID，避免 loadCurrentSession 带着旧ID请求新项目的会话
  currentSessionId.value = null

  // P1b: 项目真正切换时，中断正在进行的 SSE 流，避免旧上下文写入新会话
  if (newId !== oldId && newId > 0 && abortHandle) {
    abortHandle.abort()
    abortHandle = null
    isLoading.value = false
    isLoadingInternal.value = false
    currentStatus.value = ''
  }

  // 项目切换时重新检查配置并重载
  const seq = ++configCheckSeq
  await checkConfig()
  if (seq !== configCheckSeq) return // 被更新的调用抢占
  // 记录页面切换时间
  lastPageSwitchTime.value = Date.now()
  // 对话框已打开时加载会话（页面切换场景，不超过 10 分钟恢复上一会话）
  if (showWidget.value && !isCollapsed.value) {
    await loadCurrentSession()
  }
}, { immediate: true })

// ─── Toggle ───────────────────────────────────────────────

const toggleCollapse = async () => {
  if (isCollapsed.value) {
    // 展开
    isCollapsed.value = false
    hasUnread.value = false // 清除未读
    isLoadingInternal.value = false
    // 判断是否需要新建会话（折叠超过阈值）
    const preferNew = lastCloseTime.value !== null && (Date.now() - lastCloseTime.value > SESSION_RESUME_THRESHOLD)
    await loadCurrentSession(preferNew)
    nextTick(() => {
      scrollToBottom()
      chatInputRef.value?.focus()
    })
  } else {
    // 折叠
    isCollapsed.value = true
    isFullscreen.value = false
    isLoadingInternal.value = isLoading.value
    lastCloseTime.value = Date.now()
    // 折叠时不再中断 SSE 流：让后台继续生成，
    // spinner 由 isLoadingInternal 驱动，完成后 onDone/onError 设置 hasUnread
  }
}

const toggleFullscreen = () => {
  // 移动端禁用桌面全屏模式
  if (isMobile.value) return
  isFullscreen.value = !isFullscreen.value
}

// 移动端会话列表抽屉
const mobileSessionDrawer = ref(false)
const toggleMobileSessionDrawer = () => {
  mobileSessionDrawer.value = !mobileSessionDrawer.value
}

// ─── 拖拽调整大小 ────────────────────────────────────────
const dialogWidth = ref(420)
const dialogHeight = ref(600)

// 拖拽处理器引用，供 onUnmounted 清理
let resizeMouseMove: ((ev: MouseEvent) => void) | null = null
let resizeMouseUp: (() => void) | null = null

const startResize = (e: MouseEvent) => {
  const startX = e.clientX
  const startY = e.clientY
  const startW = dialogWidth.value
  const startH = dialogHeight.value

  resizeMouseMove = (ev: MouseEvent) => {
    // 向左拖拽变宽，向上拖拽变高
    const newW = Math.min(800, Math.max(360, startW - (ev.clientX - startX)))
    const newH = Math.min(1200, Math.max(500, startH - (ev.clientY - startY)))
    dialogWidth.value = newW
    dialogHeight.value = newH
  }

  resizeMouseUp = () => {
    document.removeEventListener('mousemove', resizeMouseMove!)
    document.removeEventListener('mouseup', resizeMouseUp!)
    resizeMouseMove = null
    resizeMouseUp = null
    document.body.style.cursor = ''
    document.body.style.userSelect = ''
  }

  document.body.style.cursor = 'nwse-resize'
  document.body.style.userSelect = 'none'
  document.addEventListener('mousemove', resizeMouseMove)
  document.addEventListener('mouseup', resizeMouseUp)
}

const handleEscKey = (e: KeyboardEvent) => {
  if (e.key === 'Escape' && !isCollapsed.value) {
    toggleCollapse()
  }
}

// ─── Session management ──────────────────────────────────

const loadCurrentSession = async (preferNew = false) => {
  try {
    // 需要新建会话时，先创建再加载
    if (preferNew) {
      const csRes = await createSession(itemId.value)
      currentSessionId.value = csRes.session_id
    }
    const res = await loadSession(itemId.value, currentSessionId.value || undefined)
    const data = res
    sessions.value = data.sessions || []
    if (data.session_id) {
      currentSessionId.value = data.session_id
    }
    // 没有现有 session 则自动创建
    if (!currentSessionId.value) {
      const csRes = await createSession(itemId.value)
      currentSessionId.value = csRes.session_id
      await loadCurrentSession()
      return
    }
    messages.value = data.messages || []
    // DB 消息无 clientKey，也无持久化 refs，重置 refs 容器
    messageRefs.value = new Map()

    // 保存欢迎消息，传递给引导面板展示（不 push 到 messages）
    if (data.welcome_message) {
      welcomeMessage.value = data.welcome_message
    } else {
      welcomeMessage.value = ''
    }
  } catch (err: any) {
    console.error('Failed to load session:', err)
  }
}

const handleNewSession = async () => {
  try {
    const res = await createSession(itemId.value)
    currentSessionId.value = res.session_id
    messages.value = []
    messageRefs.value.clear()
    await loadCurrentSession()
    nextTick(() => {
      chatInputRef.value?.focus()
    })
  } catch {
    message.error(t('ai.create_session_failed'))
  }
}

const switchSession = async (session: AiChatSession) => {
  currentSessionId.value = session.session_id
  await loadCurrentSession()
  nextTick(() => scrollToBottom())
}

const handleDeleteSession = async (session: AiChatSession) => {
  try {
    await apiDeleteSession(session.session_id)
    if (currentSessionId.value === session.session_id) {
      currentSessionId.value = null
      messages.value = []
    }
    await loadCurrentSession()
  } catch {
    message.error(t('ai.delete_session_failed'))
  }
}

const handleClearSession = async () => {
  if (!currentSessionId.value) return
  try {
    await apiResetSession(currentSessionId.value)
    messages.value = []
    await loadCurrentSession()
  } catch {
    message.error(t('ai.clear_session_failed'))
  }
}

// ─── Send message ─────────────────────────────────────────

const handleSend = async (text: string, regenerateFromMsgId?: number) => {
  if (!text.trim() || isLoading.value) return

  currentTurnToken.value = genClientKey()

  // 确保有会话
  if (!currentSessionId.value) {
    try {
      const res = await createSession(itemId.value)
      currentSessionId.value = res.session_id
    } catch {
      message.error(t('ai.create_session_failed'))
      return
    }
  }

  // 添加用户消息
  const userMsg: AiChatMessage = {
    id: Date.now(),
    role: 'user',
    content: text,
    clientKey: genClientKey(),
  }
  messages.value.push(userMsg)

  // 添加空的 AI 消息占位
  messages.value.push({
    id: 0,
    role: 'assistant',
    content: '',
    clientKey: genClientKey(),
  })
  // 必须取响应式代理引用：后续 onEvent 里通过代理修改 content 等属性才能触发子组件 (AiMessageBubble) 重渲染
  const assistantMsg = messages.value[messages.value.length - 1] as AiChatMessage

  // 展开模式下发送后自动收起
  isInputExpanded.value = false

  isLoading.value = true
  isLoadingInternal.value = true
  currentStatus.value = ''
  scrollToBottom()
  // 用户发送消息，记录活跃时间
  touchActivityTime()

  // 收集当前页面上下文
  const pageId = getCurrentPageId()
  const editorContent = getEditorContent()
  const currentPage = window.location.pathname + window.location.hash

  abortHandle = sendAgentMessage({
    message: text,
    sessionId: currentSessionId.value!,
    itemId: itemId.value || undefined,
    pageId: pageId || undefined,
    editorContent: editorContent || undefined,
    currentPage: currentPage,
    regenerateFromMsgId: regenerateFromMsgId,
    turnToken: currentTurnToken.value,
    onEvent: (event) => {
      if (event.type === 'text' && event.content) {
        assistantMsg.content += event.content
        triggerRef(messages)
        currentStatus.value = ''
        scrollToBottom()
      } else if (event.type === 'status' && event.content) {
        currentStatus.value = event.content
      } else if (event.type === 'ref') {
        // Backend sends flat structure: {type:'ref', kind:'page', page_id, page_title, ...}
        // Build SseRef from flat fields (no nested event.ref object)
        const ref: SseRef = {
          kind: event.kind,
        }
        if (event.page_id != null) ref.page_id = event.page_id
        if (event.page_title != null) ref.page_title = event.page_title
        if (event.item_id != null) ref.item_id = event.item_id
        if (event.item_name != null) ref.item_name = event.item_name
        const msgKey = String(assistantMsg.clientKey || assistantMsg.id || '0')
        const existing = messageRefs.value.get(msgKey) || []
        existing.push(ref)
        messageRefs.value.set(msgKey, existing)
      } else if (event.type === 'edit' && event.content) {
        // 将编辑内容通过全局 emitter 发送到编辑器
        const editEmitter = (window as any).__MAIN_EMITTER__
        if (editEmitter) {
          editEmitter.emit('ai:edit', { content: event.content, pageId: event.page_id, sessionId: currentSessionId.value })
        }
      } else if (event.type === 'changes') {
        if (!event.operations) return
        // 通知目录/项目列表刷新
        const emitter = (window as any).__MAIN_EMITTER__
        if (emitter) {
          emitter.emit('ai:changes', { operations: event.operations, sessionId: currentSessionId.value })
        }
      } else if (event.type === 'error' && event.code) {
        // mcp_unavailable 可能高频触发，3 秒内去重
        if (event.code === 'mcp_unavailable' && Date.now() - lastMcpWarnTime.value < 3000) return
        if (event.code === 'mcp_unavailable') lastMcpWarnTime.value = Date.now()
        // 分类错误事件：根据 code 显示用户友好的提示
        const errorMessages: Record<string, string> = {
          mcp_unavailable: t('ai.mcp_unavailable'),
          llm_timeout: t('ai.llm_timeout'),
          llm_error: t('ai.llm_error'),
          rate_limited: t('ai.rate_limited'),
        }
        const hint = errorMessages[event.code] || event.message || t('ai.unknown_error')
        message.warning(hint, 3)
      } else if (event.type === 'message_id' && event.content) {
        // 后端在保存助手消息后、[DONE] 之前发送的 message_id 事件，
        // 让前端在 loadCurrentSession() 之前更新本地消息 ID，避免重复渲染。
        const newId = parseInt(event.content) || 0
        if (newId > 0) {
          assistantMsg.id = newId
        }
      }
    },
    onError: (err) => {
      if ((err as any).type === 'rejected') {
        // 内容安全拦截：撤回本地占位消息（先 assistant 再 user）
        messages.value.pop()
        messages.value.pop()
        triggerRef(messages)
        message.warning(err.message || t('ai.ai_error_message'))
      } else {
        if (!assistantMsg.content) {
          assistantMsg.content = err.message || t('ai.ai_error_message')
        }
      }
      isLoading.value = false
      isLoadingInternal.value = false
      if (isCollapsed.value) hasUnread.value = true
      abortHandle = null
      // 收到 AI 回复（或错误），记录活跃时间
      touchActivityTime()
    },
    onDone: () => {
      isLoading.value = false
      isLoadingInternal.value = false
      if (isCollapsed.value) hasUnread.value = true
      abortHandle = null
      currentStatus.value = ''
      // 收到 AI 回复完成，记录活跃时间
      touchActivityTime()
      // 仅刷新会话列表（不重新加载 messages，避免清空 messageRefs 导致引用链接消失）
      listSessions(itemId.value).then(res => {
        sessions.value = res.sessions || []
      }).catch(() => {})
    },
  })
}

const handleStop = () => {
  if (abortHandle) {
    abortHandle.abort()
    abortHandle = null
  }
  // 通知后端取消当前轮次生成（开源版 cancelAgent 返回 void）
  if (isLoading.value && currentSessionId.value) {
    cancelAgent(currentSessionId.value, currentTurnToken.value).catch(() => {})
  }
  isLoading.value = false
  isLoadingInternal.value = false
  currentStatus.value = ''
}

// ─── Message actions ──────────────────────────────────────

const handleRegenerate = async (msg: AiChatMessage) => {
  // 找到该消息之前的 user 消息
  const idx = messages.value.findIndex(m => m === msg)
  if (idx < 1) return

  let userMsgIdx = idx - 1
  while (userMsgIdx >= 0 && messages.value[userMsgIdx].role !== 'user') {
    userMsgIdx--
  }
  if (userMsgIdx < 0) return

  const userContent = messages.value[userMsgIdx].content

  // Capture the AI message id before splicing (needed for regenerate_from_msg_id)
  const regenerateFromMsgId = msg.id || 0

  // Delete current AI reply and all messages after it
  messages.value.splice(idx)
  // Remove the last user message (will be re-added by handleSend)
  messages.value.splice(userMsgIdx)

  // Re-send with regenerate_from_msg_id so backend deletes old messages
  await handleSend(userContent, regenerateFromMsgId || undefined)
}

const handleFeedback = async (messageId: number, value: number) => {
  if (!messageId) {
    message.warning(t('ai.message_generating_retry'))
    return
  }
  try {
    await sendFeedback(messageId, value)
    const msg = messages.value.find(m => m.id === messageId)
    if (msg) {
      (msg as any).feedback = value
    }
  } catch {
    // ignore
  }
}

// ─── Helpers ──────────────────────────────────────────────

/** 判断消息是否是最后一个空的 assistant 消息且正在加载 */
const isLastAssistantLoading = (msg: AiChatMessage): boolean => {
  if (msg.role !== 'assistant' || !isLoading.value) return false
  const lastMsg = messages.value[messages.value.length - 1]
  return msg === lastMsg
}

const getStatusText = (msg: AiChatMessage): string => {
  // 只给最后一个空的 assistant 消息显示状态
  const lastMsg = messages.value[messages.value.length - 1]
  if (msg === lastMsg && msg.role === 'assistant' && isLoading.value) {
    return currentStatus.value
  }
  return ''
}

const getMessageRefs = (msg: AiChatMessage): SseRef[] => {
  return messageRefs.value.get(String(msg.clientKey || msg.id || '0')) || []
}

/** 获取当前页面 ID（优先从 router params，回退到 URL 正则） */
const getCurrentPageId = (): number | null => {
  // 后端已能从 current_page 解析 page_id，前端不再需要可靠提取
  // 仍保留此函数作为向后兼容（page_id 参数仍传给后端，但后端会优先用 current_page 解析的结果）
  try {
    const path = window.location.pathname + (window.location.hash || '')
    const match = path.match(/\/page\/(\d+)/) || path.match(/\/(\d+)\/(\d+)/)
    if (match) {
      // /page/123 → match[1]=123;  /item_id/page_id → match[2]=page_id
      return parseInt(match[2] || match[1])
    }
  } catch {
    // ignore
  }
  return null
}

/** 获取编辑器内容（如果正在编辑） */
const getEditorContent = (): string | null => {
  try {
    // 优先通过全局 getter 获取（由 EditPageModal 注册）
    const getContentFn = (window as any).__GET_EDITOR_CONTENT__
    if (typeof getContentFn === 'function') {
      const content = getContentFn()
      if (content) return content
    }
  } catch {
    // ignore
  }
  try {
    // 尝试获取 Vditor 编辑器内容
    const vditorEl = document.querySelector('.vditor') as any
    if (vditorEl && vditorEl.vditor) {
      return vditorEl.vditor.getValue()
    }
  } catch {
    // ignore
  }
  try {
    // 尝试获取 Editormd 编辑器内容（底层 CodeMirror）
    // Editormd 使用 CodeMirror，fromTextArea 将实例存储在 DOM 元素的 .CodeMirror 属性上
    const cmEl = document.querySelector('.CodeMirror') as any
    if (cmEl && cmEl.CodeMirror) {
      return cmEl.CodeMirror.getValue()
    }
  } catch {
    // ignore
  }
  return null
}

const scrollToBottom = () => {
  nextTick(() => {
    const container = messagesContainer.value
    if (container) {
      container.scrollTop = container.scrollHeight
    }
  })
}
</script>

<style scoped lang="scss">
.ai-chat-widget {
  position: fixed;
  right: 40px;
  bottom: 40px;
  z-index: 2000;
  transition: bottom 0.15s ease;

  /* 移动端全屏覆盖 */
  &.mobile-expanded {
    position: fixed;
    inset: 0;
    right: 0;
    bottom: 0;
    top: 0;
    left: 0;
    z-index: 10000;
    padding-top: env(safe-area-inset-top, 0px);
    padding-bottom: env(safe-area-inset-bottom, 0px);
    padding-left: env(safe-area-inset-left, 0px);
    padding-right: env(safe-area-inset-right, 0px);
    background: var(--color-bg-primary);
  }
}

/* 折叠状态：悬浮按钮 */
.ai-chat-widget.collapsed .chat-button {
  width: 48px;
  height: 48px;
  background-color: var(--color-bg-primary);
  border: 1px solid var(--color-border);
  border-radius: 50%;
  box-shadow: var(--shadow-sm);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  position: relative;

  i {
    color: var(--color-active);
    font-size: 20px;
  }

  &:hover {
    box-shadow: var(--shadow-md);
    border-color: var(--color-active);
    transform: scale(1.05);
  }

  [data-theme='dark'] & {
    background-color: var(--color-bg-primary);
    border-color: var(--color-border);
  }
}

/* Loading 旋转圈 */
.chat-button-spinner {
  position: absolute;
  top: -2px;
  left: -2px;
  right: -2px;
  bottom: -2px;
  border: 2px solid transparent;
  border-top-color: var(--color-active);
  border-radius: 50%;
  animation: btn-spin 1s linear infinite;
}

@keyframes btn-spin {
  to { transform: rotate(360deg); }
}

/* 未读消息圆点 */
.chat-button-unread {
  position: absolute;
  top: -2px;
  right: -2px;
  width: 10px;
  height: 10px;
  background: #ff4d4f;
  border-radius: 50%;
  border: 2px solid var(--color-bg-primary);
}

/* 展开状态：对话框 */
.chat-window {
  width: 420px;
  height: 600px;
  background: var(--color-bg-primary);
  border-radius: 12px;
  box-shadow: var(--shadow-md);
  border: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  overflow: hidden;

  [data-theme='dark'] & {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
  }
}

/* 消息列表 */
.messages {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
  background: var(--color-bg-secondary);

  [data-theme='dark'] & {
    background: var(--color-bg-secondary);
  }
}

/* 移动端适配 */
@media (max-width: 768px) {
  .ai-chat-widget {
    right: 12px;
    bottom: calc(12px + env(safe-area-inset-bottom, 0px));
  }

  .ai-chat-widget.collapsed .chat-button {
    width: 44px;
    height: 44px;
  }

  /* 移动端展开时不使用圆角窗口样式 */
  .chat-window {
    border-radius: 0;
  }
}

/* 全屏模式 */
.ai-chat-widget.fullscreen {
  right: 0;
  bottom: 0;
  top: 0;
  left: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ai-chat-widget.fullscreen .chat-window-fullscreen {
  width: 100%;
  height: 100%;
  border-radius: 0;
}

/* 拖拽调整大小手柄 */
.chat-resize-handle {
  position: absolute;
  right: 0;
  bottom: 0;
  width: 16px;
  height: 16px;
  cursor: nwse-resize;
  z-index: 10;

  &::after {
    content: '';
    position: absolute;
    right: 3px;
    bottom: 3px;
    width: 8px;
    height: 8px;
    border-right: 2px solid var(--color-border, #ccc);
    border-bottom: 2px solid var(--color-border, #ccc);
    opacity: 0.5;
  }

  &:hover::after {
    opacity: 1;
    border-color: var(--color-active, #1890ff);
  }
}

/* 输入展开提示条 */
.input-expanded-hint {
  display: flex;
  align-items: center;
  padding: 6px 12px;
  font-size: 12px;
  color: var(--color-active);
  background: var(--color-bg-secondary);
  flex-shrink: 0;
  animation: fade-slide-in 0.3s ease;
}

@keyframes fade-slide-in {
  from {
    opacity: 0;
    transform: translateY(-4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* 让 chat-window 支持 position: relative 以定位手柄 */
.chat-window {
  position: relative;
}

/* ─── 移动端样式 ─── */

/* 移动端对话框：全屏 */
.chat-window-mobile {
  width: 100% !important;
  height: 100% !important;
  border-radius: 0;
  border: none;
  box-shadow: none;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* 移动端消息列表：防止滚动穿透 */
.chat-window-mobile .messages {
  overscroll-behavior: contain;
  -webkit-overflow-scrolling: touch;
  /* safe-area-inset-bottom 由父级 .mobile-expanded 处理 */
}

/* 移动端会话抽屉遮罩 */
.mobile-drawer-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  z-index: 10001;
  display: flex;
}

.mobile-drawer {
  width: 280px;
  max-width: 80vw;
  height: 100%;
  background: var(--color-bg-primary);
  box-shadow: 2px 0 12px rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
  margin-left: auto;
  animation: drawer-slide-in 0.25s ease;
}

@keyframes drawer-slide-in {
  from { transform: translateX(100%); }
  to { transform: translateX(0); }
}

.mobile-drawer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 16px;
  font-size: 15px;
  font-weight: 600;
  border-bottom: 1px solid var(--color-border);
  padding-top: calc(14px + env(safe-area-inset-top, 0px));

  i {
    cursor: pointer;
    font-size: 18px;
    color: var(--color-text-secondary);
    padding: 8px;
  }
}

.mobile-drawer-body {
  flex: 1;
  overflow-y: auto;
  overscroll-behavior: contain;
  -webkit-overflow-scrolling: touch;
}

.mobile-session-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  cursor: pointer;
  border-bottom: 1px solid var(--color-border);
  transition: background 0.15s;
  min-height: 56px;

  &:active {
    background: var(--color-bg-secondary);
  }

  &.active {
    background: var(--color-bg-secondary);
  }
}

.mobile-session-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
  flex: 1;
}

.mobile-session-title {
  font-size: 14px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.mobile-session-meta {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin-top: 2px;
}

.mobile-session-delete {
  font-size: 14px;
  color: var(--color-text-secondary);
  padding: 10px;
  flex-shrink: 0;
  margin-left: 8px;

  &:active {
    color: #ff4d4f;
  }
}

/* 抽屉动画 */
.drawer-fade-enter-active,
.drawer-fade-leave-active {
  transition: opacity 0.25s ease;
}
.drawer-fade-enter-from,
.drawer-fade-leave-to {
  opacity: 0;
}
</style>
