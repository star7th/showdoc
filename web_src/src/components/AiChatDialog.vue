<template>
  <div
    v-if="showWidget"
    class="ai-chat-widget"
    :class="{ collapsed: isCollapsed }"
    :style="{ bottom: `${bottomPosition}px` }"
  >
    <!-- 折叠状态：只显示按钮 -->
    <div
      v-if="isCollapsed"
      class="chat-button"
      @click="toggleCollapse"
      :title="$t('ai.ai_assistant_tooltip')"
    >
      <i class="far fa-question-circle"></i>
    </div>

    <!-- 展开状态：显示完整对话框 -->
    <div v-else class="chat-window">
      <!-- 标题栏 -->
      <div class="chat-header">
        <div class="header-title">
          <i class="far fa-question-circle"></i>
          <span>{{ dialogTitle }}</span>
        </div>
        <div class="header-actions">
          <i
            class="fas fa-minus"
            @click="toggleCollapse"
            :title="$t('common.minimize')"
          ></i>
          <i class="fas fa-times" @click="closeChat" :title="$t('common.close')"></i>
        </div>
      </div>

      <!-- 消息列表 -->
      <div class="chat-container">
        <div class="messages" ref="messagesContainer">
          <div
            v-for="(message, index) in messages"
            :key="index"
            :class="['message', message.role]"
          >
            <div class="message-avatar">
              <i
                :class="
                  message.role === 'user'
                    ? 'fas fa-user'
                    : 'fas fa-comments'
                "
              ></i>
            </div>
            <div class="message-content">
              <div
                class="message-text"
                v-html="formatMessageSync(message.content)"
              ></div>
              <!-- 引用来源 -->
              <div
                v-if="message.sources && message.sources.length > 0"
                class="message-sources"
              >
                <div
                  v-for="(source, sIndex) in message.sources"
                  :key="sIndex"
                  class="source-item"
                  @click="goToSource(source)"
                >
                  <i class="fas fa-file-alt"></i>
                  <span>{{ source.page_title }}</span>
                  <span class="relevance">
                    {{ $t('ai.ai_relevance') }}: {{ formatRelevance(source.relevance) }}
                  </span>
                  <i class="fas fa-external-link-alt"></i>
                </div>
              </div>
            </div>
          </div>
          <!-- 正在输入指示器 -->
          <div v-if="isLoading" class="message assistant">
            <div class="message-avatar">
              <i class="far fa-question-circle"></i>
            </div>
            <div class="message-content">
              <div class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
              </div>
            </div>
          </div>
        </div>

        <!-- 输入框 -->
        <div class="input-area">
          <a-textarea
            v-model:value="inputText"
            :rows="3"
            :placeholder="$t('ai.ai_input_placeholder')"
            @keydown="handleKeydown"
            :auto-size="{ minRows: 3, maxRows: 6 }"
          />
          <div class="input-actions">
            <CommonButton
              theme="dark"
              :spinning="isLoading"
              @click="sendMessage"
              :disabled="!inputText.trim()"
            >
              {{ $t('common.send') }}
            </CommonButton>
            <CommonButton theme="light" @click="clearHistory">
              {{ $t('ai.ai_clear_history') }}
            </CommonButton>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/store/user'
import request from '@/utils/request'
import { getServerHost } from '@/utils/system'
import { message } from 'ant-design-vue'
import CommonButton from '@/components/CommonButton.vue'

interface Props {
  itemId: number
  itemName?: string
}

interface Message {
  role: 'user' | 'assistant'
  content: string
  sources: Array<{
    page_id: number
    page_title: string
    relevance: number
  }>
}

const props = withDefaults(defineProps<Props>(), {
  itemName: ''
})

const emit = defineEmits<{}>()

// Composables
const { t } = useI18n()
const router = useRouter()
const userStore = useUserStore()

// Refs
const showWidget = ref(false)
const isCollapsed = ref(false)
const messages = ref<Message[]>([])
const inputText = ref('')
const isLoading = ref(false)
const conversationId = ref<string | null>(null)
const abortController = ref<AbortController | null>(null)
const aiConfig = ref({
  dialog_collapsed: true,
  welcome_message: ''
})
const messagesContainer = ref<HTMLElement | null>(null)
const scrolled = ref(false)

// Computed
const dialogTitle = computed(() => {
  return (props.itemName || t('item.project')) + ' - ' + t('ai.ai_chat_assistant')
})

// 计算底部位置：滚动时 100px（回到顶部按钮上方），未滚动时 40px
const bottomPosition = computed(() => {
  return scrolled.value ? 100 : 40
})

// 监听滚动事件
const handleScroll = () => {
  const scrollTop = window.pageYOffset || document.documentElement.scrollTop
  const oldScrolled = scrolled.value
  scrolled.value = scrollTop > 100
  
  // 从未滚动变为滚动时，延迟移动按钮，避免突兀
  if (!oldScrolled && scrolled.value) {
    setTimeout(() => {
      scrollToBottom()
    }, 300)
  }
}

// Methods
const initAiWidget = async () => {
  // 检查项目是否开启了 AI 知识库功能
  try {
    const configRes = await request('/api/item/getAiKnowledgeBaseConfig', {
      item_id: props.itemId
    })

    if (configRes.error_code === 0 && configRes.data) {
      aiConfig.value = configRes.data

      // 项目开启了 AI 知识库功能，显示组件
      if (configRes.data.enabled) {
        showWidget.value = true
        // 设置初始折叠状态
        // dialog_collapsed: 1 = 收缩, 0 = 展开
        isCollapsed.value = !!aiConfig.value.dialog_collapsed
        if (!isCollapsed.value) {
          initChat()
        }
      } else {
        // 项目未开启 AI，不显示组件
        showWidget.value = false
      }
    } else {
      // 获取配置失败，不显示组件
      showWidget.value = false
    }
  } catch (error) {
    // 加载配置失败，不显示组件
    showWidget.value = false
  }
}

const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value
  if (!isCollapsed.value && messages.value.length === 0) {
    // 展开时如果还没有消息，初始化聊天
    initChat()
  }
  if (!isCollapsed.value) {
    // 展开后滚动到底部
    setTimeout(() => {
      scrollToBottom()
    }, 100)
  }
}

const closeChat = () => {
  cancelRequest()
  // 关闭时折叠，而不是隐藏整个组件
  isCollapsed.value = true
}

const initChat = () => {
  // 清空消息（如果是重新打开）
  messages.value = []

  // 添加欢迎消息（如果有配置的欢迎语，使用配置的；否则使用默认的）
  const welcomeMessage =
    aiConfig.value.welcome_message || t('ai.ai_welcome_message_default')
  if (welcomeMessage) {
    messages.value.push({
      role: 'assistant',
      content: welcomeMessage,
      sources: []
    })
  }

  // 滚动到底部
  setTimeout(() => {
    scrollToBottom()
  }, 100)
}

const handleKeydown = (event: KeyboardEvent) => {
  // 支持 Ctrl+Enter (Windows/Linux) 或 Cmd+Enter (Mac) 发送消息
  if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
    event.preventDefault()
    sendMessage()
  }
}

const sendMessage = async () => {
  if (!inputText.value.trim() || isLoading.value) {
    return
  }

  const question = inputText.value.trim()
  inputText.value = ''

  // 添加用户消息
  messages.value.push({
    role: 'user',
    content: question,
    sources: []
  })

  // 添加空的助手消息（用于流式填充）
  const assistantMessage: Message = {
    role: 'assistant',
    content: '',
    sources: []
  }
  messages.value.push(assistantMessage)

  isLoading.value = true
  scrollToBottom()

  try {
    // 使用 SSE 流式接收
    await sendMessageStream(question, assistantMessage)
  } catch (error) {
    assistantMessage.content = t('ai.ai_error_message')
    message.error(t('ai.ai_error_message'))
  } finally {
    isLoading.value = false
    scrollToBottom()
  }
}

const sendMessageStream = (question: string, assistantMessage: Message): Promise<void> => {
  return new Promise(async (resolve, reject) => {
    try {
      // 取消之前的请求（如果有）
      cancelRequest()

      // 创建新的 AbortController
      abortController.value = new AbortController()

      // 构建请求参数
      const params: any = {
        item_id: props.itemId,
        question: question,
        stream: 1
      }
      if (conversationId.value) {
        params.conversation_id = conversationId.value
      }

      // 获取用户 token（用于认证）
      if (userStore.userToken) {
        params.user_token = userStore.userToken
      }

      // 获取服务器地址
      const serverHost = getServerHost()
      const url = serverHost + '/api/ai/chat'

      // 使用 fetch 接收流式响应（POST 请求，参数放在 body 中）
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'text/event-stream',
          'Cache-Control': 'no-cache'
        },
        body: JSON.stringify(params),
        signal: abortController.value.signal // 支持取消
      })

      if (!res.ok) {
        throw new Error(`HTTP ${res.status}: ${res.statusText}`)
      }

      // 创建 reader 和 decoder
      const reader = res.body!.getReader()
      const decoder = new TextDecoder('utf-8')
      let buffer = '' // 用于累积不完整的数据

      // 读取流式数据
      const readChunk = async () => {
        try {
          const { value, done } = await reader.read()

          if (done) {
            // 流结束
            resolve()
            return
          }

          // 解码数据
          const chunk = decoder.decode(value, { stream: true })
          buffer += chunk

          // 按行分割（SSE 格式：data: ...\n\n）
          const lines = buffer.split('\n\n')
          // 保留最后一个不完整的行
          buffer = lines.pop() || ''

          // 处理每一行
          for (const line of lines) {
            if (!line.trim()) continue

            // 提取 data: 后面的内容
            const match = line.match(/^data: (.+)$/m)
            if (!match) continue

            const text = match[1].trim()
            if (!text) continue

            try {
              const data = JSON.parse(text)

              if (data.type === 'token') {
                assistantMessage.content += data.content || ''
                scrollToBottom()
              } else if (data.type === 'sources') {
                assistantMessage.sources = data.sources || []
              } else if (data.type === 'done') {
                if (data.conversation_id) {
                  conversationId.value = data.conversation_id
                }
                resolve()
                return
              } else if (data.type === 'error') {
                assistantMessage.content =
                  data.message || t('ai.ai_error_message')
                reject(new Error(data.message || t('ai.ai_service_error')))
                return
              }
            } catch (parseError) {
              // 忽略 JSON 解析错误（可能是部分数据）
            }
          }

          // 继续读取下一个 chunk
          await readChunk()
        } catch (error) {
          if (assistantMessage.content === '') {
            assistantMessage.content = t('ai.ai_error_message')
          }
          reject(error)
        }
      }

      // 开始读取
      await readChunk()
    } catch (error) {
      if (assistantMessage.content === '') {
        assistantMessage.content = t('ai.ai_error_message')
      }
      reject(error)
    }
  })
}

const formatMessageSync = (content: string) => {
  if (!content) return ''
  // 使用 Vditor.md2html 渲染 Markdown（同步模式）
  // 注意：Vditor.md2html 默认是异步的，这里需要一个临时解决方案
  // 先使用简单的标记进行渲染，后续可以优化
  let html = content
    .replace(/```(\w*)\n([\s\S]*?)```/g, '<pre><code>$2</code></pre>')
    .replace(/`([^`]+)`/g, '<code>$1</code>')
    .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
    .replace(/\*([^*]+)\*/g, '<em>$1</em>')
    .replace(/^#\s+(.*)$/gm, '<h1>$1</h1>')
    .replace(/^##\s+(.*)$/gm, '<h2>$1</h2>')
    .replace(/^###\s+(.*)$/gm, '<h3>$1</h3>')
    .replace(/^####\s+(.*)$/gm, '<h4>$1</h4>')
    .replace(/^#####\s+(.*)$/gm, '<h5>$1</h5>')
    .replace(/^######\s+(.*)$/gm, '<h6>$1</h6>')
    .replace(/\n/g, '<br>')
  return html
}

const formatRelevance = (relevance: number) => {
  if (!relevance) return ''
  const stars = Math.round(relevance * 5)
  return '★'.repeat(stars) + '☆'.repeat(5 - stars)
}

const goToSource = (source: any) => {
  if (source.page_id && props.itemId) {
    // 使用 router.resolve 生成链接，自动适配 History/Hash 模式
    const resolved = router.resolve({
      name: 'ItemShowPage',
      params: { item_id: props.itemId, page_id: source.page_id }
    })
    window.open(resolved.href, '_blank')
  }
}

const clearHistory = () => {
  messages.value = []
  conversationId.value = null
  initChat()
}

const scrollToBottom = () => {
  setTimeout(() => {
    const container = messagesContainer.value
    if (container) {
      container.scrollTop = container.scrollHeight
    }
  }, 100)
}

const cancelRequest = () => {
  if (abortController.value) {
    abortController.value.abort()
    abortController.value = null
  }
}

// Watchers
watch(() => props.itemId, (newVal) => {
  if (newVal) {
    initAiWidget()
  }
})

// Lifecycle
onMounted(() => {
  // 初始化时先检查项目是否开启了 AI
  if (props.itemId) {
    initAiWidget()
  }
  
  // 添加滚动监听
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  cancelRequest()
  // 移除滚动监听
  window.removeEventListener('scroll', handleScroll)
})
</script>

<style scoped lang="scss">
.ai-chat-widget {
  position: fixed;
  right: 40px;
  bottom: 40px;
  z-index: 1000;
  transition: bottom 0.15s ease;
}

/* 折叠状态：只显示按钮 */
.ai-chat-widget.collapsed .chat-button {
  width: 44px;
  height: 44px;
  background-color: var(--color-bg-primary);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  box-shadow: var(--shadow-xs);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  transition: all 0.15s ease;

  i {
    color: var(--color-primary);
    font-size: 18px;
  }

  [data-theme="dark"] & {
    background-color: var(--color-bg-primary);
    border-color: var(--color-border);
  }
}

/* 桌面端 hover 效果 */
@media (hover: hover) and (pointer: fine) {
  .ai-chat-widget.collapsed .chat-button:hover {
    box-shadow: var(--shadow-sm);
    border-color: var(--color-active);
    background: var(--hover-overlay);

    i {
      color: var(--color-active);
    }

    [data-theme="dark"] & {
      border-color: var(--color-active);
    }
  }

  .ai-chat-widget.collapsed .chat-button:active {
    box-shadow: var(--shadow-xs);
  }

  .header-actions i:hover {
    opacity: 0.8;
  }

  .source-item:hover {
    text-decoration: underline;
  }
}

/* 展开状态：显示完整对话框 */
.chat-window {
  width: 400px;
  height: 600px;
  background: var(--color-bg-primary);
  border-radius: 8px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  overflow: hidden;

  [data-theme="dark"] & {
    box-shadow: var(--shadow-lg);
  }
}

/* 移动端适配 */
@media (max-width: 768px) {
  .ai-chat-widget {
    right: 12px;
  }

  /* 移动端按钮更小 */
  .ai-chat-widget.collapsed .chat-button {
    width: 40px;
    height: 40px;
  }

  .ai-chat-widget.collapsed .chat-button i {
    font-size: 16px;
  }

  .ai-chat-widget.collapsed .chat-button:active {
    transform: scale(0.95);
  }

  .ai-chat-widget.collapsed .chat-button i {
    font-size: 22px;
  }

  /* 移动端对话框全屏 */
  .chat-window {
    width: calc(100vw - 24px);
    max-width: 100vw;
    height: 80vh;
    max-height: 80vh;
    border-radius: 12px 12px 0 0;
    right: 12px;
    bottom: 12px;
    top: auto;
    position: fixed;
  }

  /* 移动端标题栏更高，方便触摸 */
  .chat-header {
    height: 48px;
    padding: 0 16px;
  }

  .header-title {
    font-size: 15px;
  }

  .header-title i {
    font-size: 20px;
  }

  .header-actions i {
    font-size: 20px;
    padding: 4px;
    min-width: 44px;
    min-height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .header-actions i:active {
    opacity: 0.6;
    background: rgba(0, 0, 0, 0.1);
  }

  /* 移动端消息区域优化 */
  .messages {
    padding: 16px 12px;
    font-size: 14px;
  }

  .message {
    margin-bottom: 16px;
  }

  .message-avatar {
    width: 36px;
    height: 36px;
    margin: 0 10px;
  }

  .message-avatar i {
    font-size: 18px;
  }

  .message-content {
    max-width: 75%;
    padding: 10px 14px;
    font-size: 14px;
  }

  /* 移动端输入区域优化 */
  .input-area {
    padding: 16px;
  }

  .input-area :deep(.ant-input) {
    font-size: 14px;
    line-height: 1.5;
    padding: 10px 12px;
    min-height: 60px;
  }

  .input-actions {
    margin-top: 12px;
    gap: 10px;
    display: flex;
  }

  .input-actions .common-button {
    flex: 1;
    height: 44px;
  }

  /* 移动端表格优化 */
  .message-text :deep(table) {
    font-size: 12px;
    display: block;
    overflow-x: auto;
  }

  .message-text :deep(table th),
  .message-text :deep(table td) {
    padding: 8px 6px;
    white-space: nowrap;
  }

  /* 移动端代码块优化 */
  .message-text :deep(pre) {
    font-size: 12px;
    padding: 10px;
    overflow-x: auto;
  }

  /* 移动端引用来源优化 */
  .source-item {
    padding: 8px 0;
    font-size: 13px;
    min-height: 44px;
    display: flex;
    align-items: center;
    border-radius: 4px;
    padding-left: 4px;
    padding-right: 4px;
    margin: 2px 0;
  }

  .source-item:active {
    background: rgba(0, 123, 255, 0.1);
  }

  .source-item span {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .relevance {
    display: none; /* 移动端隐藏相关度，节省空间 */
  }
}

/* 超小屏幕优化（小于 360px） */
@media (max-width: 360px) {
  .ai-chat-widget {
    right: 8px;
    bottom: 8px;
  }

  .chat-window {
    width: calc(100vw - 16px);
    height: 80vh;
    max-height: 80vh;
    border-radius: 8px 8px 0 0;
    right: 8px;
    bottom: 8px;
  }

  .header-title {
    font-size: 14px;
  }

  .messages {
    padding: 12px 10px;
    font-size: 13px;
  }

  .message-content {
    max-width: 80%;
    padding: 8px 12px;
    font-size: 13px;
  }

  .input-area {
    padding: 12px;
  }
}

/* 标题栏 */
.chat-header {
  height: 42px;
  background: var(--color-bg-secondary);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 12px;
  flex-shrink: 0;

  [data-theme="dark"] & {
    background: var(--color-bg-secondary);
    color: var(--color-primary);
  }
}

.header-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 500;
}

.header-title i {
  font-size: 18px;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.header-actions i {
  font-size: 16px;
  cursor: pointer;
  transition: opacity 0.15s ease;
}

/* 聊天容器 */
.chat-container {
  display: flex;
  flex-direction: column;
  flex: 1;
  overflow: hidden;
}

.messages {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
  background: var(--color-bg-secondary);
  font-size: 13px;
  
  [data-theme="dark"] & {
    background: var(--color-bg-secondary);
  }
}

.message {
  display: flex;
  margin-bottom: 14px;
}

.message.user {
  flex-direction: row-reverse;
}

.message-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--color-grey);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin: 0 8px;

  [data-theme="dark"] & {
    background: var(--color-grey);
  }
}

.message.user .message-avatar {
  background: var(--color-active);
  color: #fff;

  [data-theme="dark"] & {
    background: var(--color-active);
  }
}

.message-avatar i {
  color: #fff;
  font-size: 16px;
}

.message-content {
  max-width: 70%;
  background: var(--color-bg-primary);
  padding: 9px 14px;
  border-radius: 8px;
  box-shadow: var(--shadow-xs);
  border: 1px solid var(--color-border);
  overflow-x: auto;
  
  [data-theme="dark"] & {
    background: var(--color-bg-primary);
    border-color: var(--color-border);
  }
}

.message.user .message-content {
  background: var(--color-bg-secondary);
  color: var(--color-primary);
  border: 1px solid var(--color-border);

  [data-theme="dark"] & {
    background: var(--color-white-alpha-05);
    color: var(--color-text-primary);
    border-color: var(--color-border);
  }
}

.message-text {
  line-height: 1.5;
  word-wrap: break-word;
  
  :deep(pre) {
    background: var(--color-bg-secondary);
    padding: 8px 10px;
    border-radius: 6px;
    overflow-x: auto;
    border: 1px solid var(--color-border);
    margin: 6px 0;

    [data-theme="dark"] & {
      background: var(--color-bg-secondary);
      border-color: var(--color-border);
    }
  }

  :deep(table) {
    width: 100%;
    border-collapse: collapse;
    margin: 8px 0;
    font-size: 13px;
    min-width: 100%;
    display: table;
  }

  :deep(table thead) {
    background: var(--color-bg-secondary);
    
    [data-theme="dark"] & {
      background: var(--color-bg-secondary);
    }
  }

  :deep(table th),
  :deep(table td) {
    padding: 6px 10px;
    border: 1px solid var(--color-border);
    text-align: left;
    
    [data-theme="dark"] & {
      border-color: var(--color-border);
    }
  }

  :deep(table tbody tr:nth-child(even)) {
    background: var(--color-bg-secondary);
    
    [data-theme="dark"] & {
      background: var(--color-bg-secondary);
    }
  }
}

.message.user .message-text :deep(pre) {
  background: var(--color-bg-primary);
  border-color: var(--color-border);

  [data-theme="dark"] & {
    background: var(--color-bg-secondary);
    border-color: var(--color-border);
  }
}

.message.user .message-text :deep(table thead) {
  background: var(--color-bg-secondary);

  [data-theme="dark"] & {
    background: var(--color-white-alpha-05);
  }
}

.message.user .message-text :deep(table th),
.message.user .message-text :deep(table td) {
  border-color: var(--color-border);

  [data-theme="dark"] & {
    border-color: var(--color-border);
  }
}

.message.user .message-text :deep(table tbody tr:nth-child(even)) {
  background: var(--color-bg-secondary);

  [data-theme="dark"] & {
    background: transparent;
  }
}

.message-sources {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid var(--color-border);
  
  [data-theme="dark"] & {
    border-top-color: var(--color-border);
  }
}

.message.user .message-sources {
  border-top-color: var(--color-border);
}

.source-item {
  display: flex;
  align-items: center;
  padding: 4px 0;
  cursor: pointer;
  color: var(--color-primary);
  font-size: 12px;
  border-radius: 4px;
  padding: 8px 12px;
  transition: all 0.15s ease;
  border: 1px solid transparent;

  [data-theme="dark"] & {
    color: var(--color-primary);
  }

  &:hover {
    background: var(--color-bg-secondary);
    text-decoration: none;
    border-color: var(--color-border);
  }
}

.message.user .source-item {
  color: var(--color-active);

  [data-theme="dark"] & {
    color: var(--color-active);
  }

  &:hover {
    color: var(--color-active);
    opacity: 0.8;
  }
}

.message.user .source-item {
  color: rgba(255, 255, 255, 0.9);
}

.source-item i {
  margin-right: 4px;
}

.source-item span {
  flex: 1;
}

.relevance {
  margin-left: 8px;
  color: var(--color-text-secondary);
  
  [data-theme="dark"] & {
    color: var(--color-text-secondary);
  }
}

.message.user .relevance {
  color: var(--color-text-secondary);
}

.typing-indicator {
  display: flex;
  gap: 4px;
  padding: 6px 0;
}

.typing-indicator span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--color-text-secondary);
  animation: typing 1.4s infinite;

  [data-theme="dark"] & {
    background: var(--color-text-secondary);
  }
}

.typing-indicator span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes typing {
  0%,
  60%,
  100% {
    transform: translateY(0);
    opacity: 0.7;
  }
  30% {
    transform: translateY(-10px);
    opacity: 1;
  }
}

.input-area {
  padding: 12px;
  background: var(--color-bg-primary);
  border-top: 1px solid var(--color-border);
  flex-shrink: 0;
  
  [data-theme="dark"] & {
    background: var(--color-bg-primary);
    border-top-color: var(--color-border);
  }
}

.input-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 8px;
}
</style>

