<template>
  <div
    v-if="showWidget"
    class="ai-chat-widget"
    :class="{ collapsed: isCollapsed }"
  >
    <!-- 折叠状态：只显示按钮 -->
    <div v-if="isCollapsed" class="chat-button" @click="toggleCollapse">
      <i class="el-icon-chat-dot-round"></i>
      <span v-if="indexStatus === 'indexing'" class="pulse"></span>
    </div>

    <!-- 展开状态：显示完整对话框 -->
    <div v-else class="chat-window">
      <!-- 标题栏 -->
      <div class="chat-header">
        <div class="header-title">
          <i class="el-icon-chat-dot-round"></i>
          <span>{{ dialogTitle }}</span>
        </div>
        <div class="header-actions">
          <i
            class="el-icon-minus"
            @click="toggleCollapse"
            :title="$t('minimize')"
          ></i>
          <i class="el-icon-close" @click="closeChat" :title="$t('close')"></i>
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
                    ? 'el-icon-user'
                    : 'el-icon-chat-dot-round'
                "
              ></i>
            </div>
            <div class="message-content">
              <div
                class="message-text"
                v-html="formatMessage(message.content)"
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
                  <i class="el-icon-document"></i>
                  <span>{{ source.page_title }}</span>
                  <span class="relevance"
                    >相关度: {{ formatRelevance(source.relevance) }}</span
                  >
                  <i class="el-icon-view"></i>
                </div>
              </div>
            </div>
          </div>
          <!-- 正在输入指示器 -->
          <div v-if="isLoading" class="message assistant">
            <div class="message-avatar">
              <i class="el-icon-chat-dot-round"></i>
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
          <el-input
            v-model="inputText"
            type="textarea"
            :rows="3"
            :placeholder="$t('ai_input_placeholder')"
            @keydown.native="handleKeydown"
          ></el-input>
          <div class="input-actions">
            <el-button
              type="primary"
              :loading="isLoading"
              @click="sendMessage"
              :disabled="!inputText.trim()"
            >
              {{ $t('send') }}
            </el-button>
            <el-button @click="clearHistory">{{
              $t('clear_history')
            }}</el-button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import request from '@/request'
// marked 3.x 使用默认导出
const marked = require('marked')

export default {
  name: 'AiChatDialog',
  props: {
    item_id: {
      type: Number,
      required: true
    },
    item_name: {
      type: String,
      default: ''
    },
    visible: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      showWidget: false, // 是否显示整个组件
      isCollapsed: true, // 是否折叠
      messages: [],
      inputText: '',
      isLoading: false,
      conversationId: null,
      abortController: null, // 用于取消 fetch 请求
      aiConfig: {
        dialog_collapsed: true,
        welcome_message: ''
      },
      indexStatus: 'unknown' // 索引状态
    }
  },
  computed: {
    dialogTitle() {
      return (
        (this.item_name || this.$t('project')) +
        ' - ' +
        this.$t('ai_chat_assistant')
      )
    }
  },
  watch: {
    item_id(newVal) {
      if (newVal) {
        this.initAiWidget()
      }
    }
  },
  mounted() {
    // 初始化时先检查项目是否开启了 AI，只有开启了才检查索引状态
    if (this.item_id) {
      this.initAiWidget()
    }
  },
  methods: {
    async initAiWidget() {
      // 先检查项目是否开启了 AI 知识库功能
      try {
        const configRes = await request('/api/item/getAiKnowledgeBaseConfig', {
          item_id: this.item_id
        })

        if (configRes.error_code === 0 && configRes.data) {
          this.aiConfig = configRes.data
          // 只有项目开启了 AI 知识库功能，才检查索引状态
          if (configRes.data.enabled) {
            await this.checkIndexStatus()
          } else {
            // 项目未开启 AI，不显示组件
            this.showWidget = false
          }
        } else {
          // 获取配置失败，不显示组件
          this.showWidget = false
        }
      } catch (error) {
        console.error('加载AI配置失败:', error)
        this.showWidget = false
      }
    },
    async checkIndexStatus() {
      try {
        const res = await request('/api/ai/getIndexStatus', {
          item_id: this.item_id
        })

        if (res.error_code === 0 && res.data) {
          this.indexStatus = res.data.status || 'unknown'
          // 只有在系统级配置了且项目级启用了的情况下才显示组件
          if (res.data.status === 'not_configured') {
            const message = res.data.message || ''
            if (
              message.indexOf('AI 服务未配置') > -1 ||
              message.indexOf('未启用 AI 知识库功能') > -1
            ) {
              this.showWidget = false
            } else {
              this.showWidget = false
            }
          } else {
            // 其他状态（indexed, indexing, error, unknown），显示组件
            this.showWidget = true
            // 设置初始折叠状态
            this.isCollapsed = this.aiConfig.dialog_collapsed > 0
            if (!this.isCollapsed) {
              this.initChat()
            }
          }
        } else {
          this.showWidget = false
        }
      } catch (error) {
        console.error('检查索引状态失败:', error)
        this.showWidget = false
      }
    },
    async loadAiConfig() {
      try {
        const res = await request('/api/item/getAiKnowledgeBaseConfig', {
          item_id: this.item_id
        })
        if (res.error_code === 0 && res.data) {
          this.aiConfig = res.data
        }
      } catch (error) {
        console.error('加载AI配置失败:', error)
      }
    },
    toggleCollapse() {
      this.isCollapsed = !this.isCollapsed
      if (!this.isCollapsed && this.messages.length === 0) {
        // 展开时如果还没有消息，初始化聊天
        this.initChat()
      }
      if (!this.isCollapsed) {
        // 展开后滚动到底部
        this.$nextTick(() => {
          this.scrollToBottom()
        })
      }
    },
    closeChat() {
      this.cancelRequest()
      // 关闭时折叠，而不是隐藏整个组件
      this.isCollapsed = true
      this.$emit('update:visible', false)
    },
    initChat() {
      // 清空消息（如果是重新打开）
      this.messages = []

      // 添加欢迎消息（如果有配置的欢迎语，使用配置的；否则使用默认的）
      const welcomeMessage =
        this.aiConfig.welcome_message || this.$t('ai_welcome_message_default')
      if (welcomeMessage) {
        this.messages.push({
          role: 'assistant',
          content: welcomeMessage,
          sources: []
        })
      }

      // 滚动到底部
      this.$nextTick(() => {
        this.scrollToBottom()
      })
    },
    handleKeydown(event) {
      // 支持 Ctrl+Enter (Windows/Linux) 或 Cmd+Enter (Mac) 发送消息
      if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
        event.preventDefault()
        this.sendMessage()
      }
    },
    async sendMessage() {
      if (!this.inputText.trim() || this.isLoading) {
        return
      }

      const question = this.inputText.trim()
      this.inputText = ''

      // 添加用户消息
      this.messages.push({
        role: 'user',
        content: question,
        sources: []
      })

      // 添加空的助手消息（用于流式填充）
      const assistantMessage = {
        role: 'assistant',
        content: '',
        sources: []
      }
      this.messages.push(assistantMessage)

      this.isLoading = true
      this.scrollToBottom()

      try {
        // 使用 SSE 流式接收
        await this.sendMessageStream(question, assistantMessage)
      } catch (error) {
        console.error('发送消息失败:', error)
        assistantMessage.content = this.$t('ai_error_message')
        this.$message.error(this.$t('ai_error_message'))
      } finally {
        this.isLoading = false
        this.scrollToBottom()
      }
    },
    async sendMessageStream(question, assistantMessage) {
      return new Promise(async (resolve, reject) => {
        try {
          // 取消之前的请求（如果有）
          this.cancelRequest()

          // 创建新的 AbortController
          this.abortController = new AbortController()

          // 构建请求参数
          const params = {
            item_id: this.item_id,
            question: question,
            stream: 1
          }
          if (this.conversationId) {
            params.conversation_id = this.conversationId
          }

          // 获取用户 token（用于认证）
          const userinfostr = localStorage.getItem('userinfo')
          if (userinfostr) {
            try {
              const userinfo = JSON.parse(userinfostr)
              if (userinfo && userinfo.user_token) {
                params.user_token = userinfo.user_token
              }
            } catch (e) {
              console.warn('解析 userinfo 失败:', e)
            }
          }

          // 构建请求 URL
          const url = DocConfig.server + '/api/ai/chat'

          // 使用 fetch 接收流式响应（POST 请求，参数放在 body 中）
          const res = await fetch(url, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'text/event-stream',
              'Cache-Control': 'no-cache'
            },
            body: JSON.stringify(params),
            signal: this.abortController.signal // 支持取消
          })

          if (!res.ok) {
            throw new Error(`HTTP ${res.status}: ${res.statusText}`)
          }

          // 创建 reader 和 decoder
          const reader = res.body.getReader()
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
                    this.scrollToBottom()
                  } else if (data.type === 'sources') {
                    assistantMessage.sources = data.sources || []
                  } else if (data.type === 'done') {
                    if (data.conversation_id) {
                      this.conversationId = data.conversation_id
                    }
                    resolve()
                    return
                  } else if (data.type === 'error') {
                    assistantMessage.content =
                      data.message || this.$t('ai_error_message')
                    reject(new Error(data.message || 'AI 服务错误'))
                    return
                  }
                } catch (parseError) {
                  // 忽略 JSON 解析错误（可能是部分数据）
                }
              }

              // 继续读取下一个 chunk
              await readChunk()
            } catch (error) {
              console.error('读取流式数据失败:', error)
              if (assistantMessage.content === '') {
                assistantMessage.content = this.$t('ai_error_message')
              }
              reject(error)
            }
          }

          // 开始读取
          await readChunk()
        } catch (error) {
          console.error('发送消息失败:', error)
          if (assistantMessage.content === '') {
            assistantMessage.content = this.$t('ai_error_message')
          }
          reject(error)
        }
      })
    },
    formatMessage(content) {
      if (!content) return ''
      // 使用 marked 渲染 Markdown
      return marked(content)
    },
    formatRelevance(relevance) {
      if (!relevance) return ''
      const stars = Math.round(relevance * 5)
      return '★'.repeat(stars) + '☆'.repeat(5 - stars)
    },
    goToSource(source) {
      if (source.page_id && this.item_id) {
        // 使用 Vue Router 的 resolve 方法处理路由跳转，自动适配不同的路由模式
        const routePath = `/${this.item_id}/${source.page_id}`
        const href = this.$router.resolve(routePath).href
        window.open(href, '_blank')
      }
    },
    clearHistory() {
      this.messages = []
      this.conversationId = null
      this.initChat()
    },
    scrollToBottom() {
      this.$nextTick(() => {
        const container = this.$refs.messagesContainer
        if (container) {
          container.scrollTop = container.scrollHeight
        }
      })
    },
    cancelRequest() {
      if (this.abortController) {
        this.abortController.abort()
        this.abortController = null
      }
    }
  },
  beforeDestroy() {
    this.cancelRequest()
  }
}
</script>

<style scoped>
.ai-chat-widget {
  position: fixed;
  right: 20px;
  bottom: 20px;
  z-index: 1000;
  transition: all 0.3s ease;
}

/* 折叠状态：只显示按钮 */
.ai-chat-widget.collapsed .chat-button {
  width: 56px;
  height: 56px;
  background: #343a40;
  border-radius: 50%;
  box-shadow: 0 2px 8px rgba(52, 58, 64, 0.25);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  transition: all 0.3s;
}

/* 桌面端 hover 效果 */
@media (hover: hover) and (pointer: fine) {
  .ai-chat-widget.collapsed .chat-button:hover {
    background: #495057;
    box-shadow: 0 4px 12px rgba(52, 58, 64, 0.35);
    transform: scale(1.05);
  }

  .header-actions i:hover {
    opacity: 0.8;
  }

  .source-item:hover {
    text-decoration: underline;
  }
}

.ai-chat-widget.collapsed .chat-button i {
  font-size: 24px;
  color: #fff;
}

/* 展开状态：显示完整对话框 */
.chat-window {
  width: 400px;
  height: 600px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
  border: 1px solid rgba(0, 0, 0, 0.08);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* 移动端适配 */
@media (max-width: 768px) {
  .ai-chat-widget {
    right: 12px;
    bottom: 12px;
  }

  /* 移动端按钮稍小一些 */
  .ai-chat-widget.collapsed .chat-button {
    width: 50px;
    height: 50px;
    /* 移动端移除 hover 效果，使用 active 状态 */
  }

  .ai-chat-widget.collapsed .chat-button:active {
    background: #495057;
    transform: scale(0.95);
  }

  .ai-chat-widget.collapsed .chat-button i {
    font-size: 22px;
  }

  /* 移动端对话框全屏 */
  .chat-window {
    width: calc(100vw - 24px);
    height: calc(100vh - 24px);
    max-width: 100vw;
    max-height: 100vh;
    border-radius: 12px 12px 0 0;
    right: 12px;
    bottom: 12px;
    position: fixed;
    /* 防止移动端点击穿透 */
    touch-action: pan-y;
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
    /* 移动端触摸反馈 */
    -webkit-tap-highlight-color: rgba(255, 255, 255, 0.2);
    tap-highlight-color: rgba(255, 255, 255, 0.2);
  }

  .header-actions i:active {
    opacity: 0.6;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
  }

  /* 移动端消息区域优化 */
  .messages {
    padding: 16px 12px;
    font-size: 14px;
    /* 移动端优化滚动 */
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
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
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    /* 防止移动端键盘弹出时布局问题 */
    position: relative;
  }

  .input-area /deep/ .el-textarea__inner {
    font-size: 14px;
    line-height: 1.5;
    padding: 10px 12px;
    min-height: 60px;
    resize: none;
  }

  .input-actions {
    margin-top: 12px;
    gap: 10px;
    display: flex;
  }

  .input-actions .el-button {
    flex: 1;
    padding: 12px 0;
    font-size: 14px;
    min-height: 44px;
    /* 移动端按钮触摸优化 */
    -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1);
    tap-highlight-color: rgba(0, 0, 0, 0.1);
  }

  /* 移动端表格优化 */
  .message-text /deep/ table {
    font-size: 12px;
    display: block;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .message-text /deep/ table th,
  .message-text /deep/ table td {
    padding: 8px 6px;
    white-space: nowrap;
  }

  /* 移动端代码块优化 */
  .message-text /deep/ pre {
    font-size: 12px;
    padding: 10px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  /* 移动端引用来源优化 */
  .source-item {
    padding: 8px 0;
    font-size: 13px;
    min-height: 44px;
    display: flex;
    align-items: center;
    /* 移动端触摸反馈 */
    -webkit-tap-highlight-color: rgba(0, 123, 255, 0.1);
    tap-highlight-color: rgba(0, 123, 255, 0.1);
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
    height: calc(100vh - 16px);
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
  background: #343a40;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 12px;
  flex-shrink: 0;
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
  transition: opacity 0.2s;
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
  background: #f9f9f9;
  font-size: 13px;
  -webkit-overflow-scrolling: touch; /* iOS 平滑滚动 */
  overscroll-behavior: contain; /* 防止滚动穿透 */
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
  background: #343a40;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin: 0 8px;
}

.message.user .message-avatar {
  background: #007bff;
}

.message-avatar i {
  color: #fff;
  font-size: 16px;
}

.message-content {
  max-width: 70%;
  background: #fff;
  padding: 9px 14px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(0, 0, 0, 0.05);
  overflow-x: auto;
}

.message.user .message-content {
  background: #007bff;
  color: #fff;
  border-color: transparent;
}

.message-text {
  line-height: 1.5;
  word-wrap: break-word;
}

.message-text /deep/ pre {
  background: #f9f9f9;
  padding: 8px 10px;
  border-radius: 6px;
  overflow-x: auto;
  border: 1px solid rgba(0, 0, 0, 0.05);
  margin: 6px 0;
}

.message.user .message-text /deep/ pre {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.2);
}

/* 表格样式 */
.message-text /deep/ table {
  width: 100%;
  border-collapse: collapse;
  margin: 8px 0;
  font-size: 13px;
  min-width: 100%;
  display: table;
}

.message-text /deep/ table thead {
  background: #f5f5f5;
}

.message.user .message-text /deep/ table thead {
  background: rgba(255, 255, 255, 0.15);
}

.message-text /deep/ table th,
.message-text /deep/ table td {
  padding: 6px 10px;
  border: 1px solid rgba(0, 0, 0, 0.1);
  text-align: left;
}

.message.user .message-text /deep/ table th,
.message.user .message-text /deep/ table td {
  border-color: rgba(255, 255, 255, 0.2);
}

.message-text /deep/ table tbody tr:nth-child(even) {
  background: #fafafa;
}

.message.user .message-text /deep/ table tbody tr:nth-child(even) {
  background: rgba(255, 255, 255, 0.05);
}

.message-sources {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.message.user .message-sources {
  border-top-color: rgba(255, 255, 255, 0.25);
}

.source-item {
  display: flex;
  align-items: center;
  padding: 4px 0;
  cursor: pointer;
  color: #007bff;
  font-size: 12px;
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
  color: #909399;
}

.message.user .relevance {
  color: rgba(255, 255, 255, 0.7);
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
  background: #343a40;
  animation: typing 1.4s infinite;
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
  background: #fff;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
  flex-shrink: 0;
}

.input-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 8px;
}

.pulse {
  position: absolute;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: rgba(52, 58, 64, 0.4);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  100% {
    transform: scale(1.5);
    opacity: 0;
  }
}
</style>

