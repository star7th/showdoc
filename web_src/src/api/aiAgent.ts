/**
 * AI Agent API 服务层
 * 封装所有 AI Agent 相关的 API 调用
 */
import request from '@/utils/request'
import { getServerHost } from '@/utils/system'

// ─── 类型定义 ─────────────────────────────────────────────

export interface AiChatMessage {
  id: number
  role: string
  content: string
  feedback?: number
  clientKey?: string
}

export interface AiChatSession {
  session_id: number
  title: string
  last_message_at: number
  message_count: number
}

export interface AiConfig {
  enabled: boolean
  welcome_message: string
  can_edit: boolean
  item_type: string
  is_guest?: boolean
  dialog_collapsed?: number
}

export interface SseRef {
  kind: string
  page_id?: number
  [key: string]: any
}

export interface SseEvent {
  type: 'text' | 'status' | 'ref' | 'edit' | 'changes' | 'error' | 'done' | 'rejected' | 'notice' | 'message_id'
  content?: string
  message?: string
  reason?: string
  code?: string
  ref?: SseRef
  operations?: any[]
  [key: string]: any
}

// ─── 内部工具 ─────────────────────────────────────────────

/** 从 localStorage 获取 user_token */
function getUserToken(): string {
  try {
    const str = localStorage.getItem('userinfo')
    if (str) {
      const info = JSON.parse(str)
      return info.user_token || ''
    }
  } catch {
    // ignore
  }
  return ''
}

/** 从 localStorage 获取或生成 guest_token（浏览器指纹） */
function getGuestToken(): string {
  const KEY = 'ai_guest_token'
  let token = localStorage.getItem(KEY)
  if (!token) {
    token = genUuid()
    localStorage.setItem(KEY, token)
  }
  return token
}

/**
 * 生成 RFC4122 v4 UUID。
 *
 * 优先使用 crypto.randomUUID()（安全上下文：HTTPS / localhost）。
 * 当处于非安全上下文（HTTP 自托管，如 http://192.168.x.x 或 hosts 本地域名）时
 * crypto.randomUUID 不可用，回退到基于 crypto.getRandomValues 的手写实现，
 * 最后再回退到 Math.random（兼容极老环境），避免抛错阻断游客 AI 请求。
 */
function genUuid(): string {
  try {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
      return crypto.randomUUID()
    }
  } catch {
    // 忽略，走兜底
  }

  try {
    if (typeof crypto !== 'undefined' && typeof crypto.getRandomValues === 'function') {
      const buf = new Uint8Array(16)
      crypto.getRandomValues(buf)
      buf[6] = (buf[6] & 0x0f) | 0x40 // version 4
      buf[8] = (buf[8] & 0x3f) | 0x80 // variant
      const h = (b: number) => b.toString(16).padStart(2, '0')
      const hex = Array.from(buf, h).join('')
      return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`
    }
  } catch {
    // 忽略，走兜底
  }

  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0
    const v = c === 'x' ? r : (r & 0x3) | 0x8
    return v.toString(16)
  })
}

/** 从 sessionStorage 获取 _item_pwd */
function getItemPwd(): string {
  return sessionStorage.getItem('_item_pwd') || ''
}

/** 向 params 注入公共字段（user_token / _item_pwd / guest_token） */
function withCommon<T extends Record<string, any>>(params: T): T & { user_token: string; _item_pwd?: string; guest_token?: string } {
  const userToken = getUserToken()
  const result: any = { ...params }

  if (userToken) {
    result.user_token = userToken
  } else {
    // 未登录用户：带 guest_token
    result.guest_token = getGuestToken()
  }

  const pwd = getItemPwd()
  if (pwd) {
    result._item_pwd = pwd
  }
  return result
}

/** 解包后端统一响应体 { error_code, data } → data */
function unwrapApi<T>(promise: Promise<any>): Promise<T> {
  return promise.then(res => res?.data ?? res)
}

// ─── 普通 JSON API ────────────────────────────────────────

/** 加载会话（含消息历史） */
export function loadSession(
  itemId: number,
  sessionId?: number,
): Promise<{ session_id: number; sessions: AiChatSession[]; messages: AiChatMessage[]; welcome_message: string; can_edit: boolean }> {
  const params: any = { item_id: itemId }
  if (sessionId) {
    params.session_id = sessionId
  }
  return unwrapApi(request('/api/agent/sessionLoad', withCommon(params)))
}

/** 新建会话 */
export function createSession(
  itemId: number,
): Promise<{ session_id: number }> {
  return unwrapApi(request('/api/agent/sessionNew', withCommon({ item_id: itemId })))
}

/** 重置会话 */
export function resetSession(sessionId: number): Promise<void> {
  return unwrapApi(request('/api/agent/sessionReset', withCommon({ session_id: sessionId })))
}

/** 获取会话列表 */
export function listSessions(
  itemId: number,
): Promise<{ sessions: AiChatSession[] }> {
  return unwrapApi(request('/api/agent/sessionList', withCommon({ item_id: itemId })))
}

/** 删除会话 */
export function deleteSession(sessionId: number): Promise<void> {
  return unwrapApi(request('/api/agent/sessionDelete', withCommon({ session_id: sessionId })))
}

/** 获取 AI 配置 */
export function getAiConfig(itemId?: number, msgAlert = true): Promise<AiConfig> {
  const params: any = {}
  if (itemId) {
    params.item_id = itemId
  }
  return unwrapApi(request('/api/agent/config', withCommon(params), 'post', msgAlert))
}

/** 提交反馈 */
export function sendFeedback(
  messageId: number,
  feedback: number,
): Promise<void> {
  return unwrapApi(request('/api/agent/feedback', withCommon({ msg_id: messageId, feedback })))
}

/** 取消 agent 生成 */
export function cancelAgent(sessionId: number, turnToken?: string): Promise<void> {
  const params: any = { session_id: sessionId }
  if (turnToken) params.turn_token = turnToken
  return unwrapApi(request('/api/agent/agentCancel', withCommon(params)))
}

// ─── SSE 流式 API ─────────────────────────────────────────

export interface SendAgentMessageParams {
  message: string
  sessionId: number
  itemId?: number
  pageId?: number
  editorContent?: string
  currentPage?: string
  regenerateFromMsgId?: number
  turnToken?: string
  onEvent: (event: SseEvent) => void
  onError: (error: Error) => void
  onDone: () => void
}

/**
 * 发送消息给 AI Agent，通过 SSE 流式接收响应。
 * 返回 { abort } 可随时中断。
 */
export function sendAgentMessage(params: SendAgentMessageParams): { abort: () => void } {
  const controller = new AbortController()

  const body: Record<string, any> = {
    message: params.message,
    session_id: params.sessionId,
  }

  const userToken = getUserToken()
  if (userToken) {
    body.user_token = userToken
  } else {
    body.guest_token = getGuestToken()
  }

  if (params.itemId != null) body.item_id = params.itemId
  if (params.pageId != null) body.page_id = params.pageId
  if (params.editorContent != null) body.editor_content = params.editorContent
  if (params.currentPage != null) body.current_page = params.currentPage
  if (params.regenerateFromMsgId != null) body.regenerate_from_msg_id = params.regenerateFromMsgId
  if (params.turnToken) body.turn_token = params.turnToken

  const pwd = getItemPwd()
  if (pwd) body._item_pwd = pwd

  const serverHost = getServerHost()
  const url = serverHost + '/api/agent/agent'

  // 异步执行，不 await
  ;(async () => {
    let reader: ReadableStreamDefaultReader<any> | null = null
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'text/event-stream',
          'Cache-Control': 'no-cache',
        },
        body: JSON.stringify(body),
        signal: controller.signal,
      })

      if (!res.ok) {
        throw new Error(`HTTP ${res.status}: ${res.statusText}`)
      }

      if (!res.body) {
        throw new Error('Response body is empty, server did not return a stream. Please try again later.')
      }

      reader = res.body.getReader()
      const decoder = new TextDecoder('utf-8')
      let buffer = ''

      while (true) {
        const { value, done } = await reader.read()
        if (done) break

        buffer += decoder.decode(value, { stream: true })

        // SSE 按双换行分割
        const parts = buffer.split('\n\n')
        buffer = parts.pop() || ''

        for (const part of parts) {
          if (!part.trim()) continue

          // 解析 SSE 字段：支持多行 data 和 event 字段
          let eventType = ''
          const dataLines: string[] = []

          for (const line of part.split('\n')) {
            if (line.startsWith('event:')) {
              eventType = line.slice(6).trim()
            } else if (line.startsWith('data:')) {
              dataLines.push(line.slice(5).trimStart())
            }
            // 忽略其他 SSE 字段（id:, retry:, 注释等）
          }

          if (dataLines.length === 0) continue
          const text = dataLines.join('\n')
          if (!text) continue

          // [DONE] 标记
          if (text === '[DONE]') {
            params.onEvent({ type: 'done' })
            params.onDone()
            return
          }

          try {
            const data = JSON.parse(text)
            if (eventType) data._sseEvent = eventType
            params.onEvent(data as SseEvent)

            if (data.type === 'rejected') {
              // 内容拦截，以错误形式通知调用方
              const e = new Error(data.message || '消息无法处理，请修改后重试')
              ;(e as any).type = 'rejected'
              params.onError(e)
              return
            }
            if (data.type === 'error') {
              // 带有 code 的分类错误事件：交给组件处理（显示提示），不终止流
              // 只有不含 code 的旧式 error 才视为致命错误
              if (data.code) {
                continue
              }
              params.onError(new Error(data.message || 'Unknown error'))
              return
            }
            if (data.type === 'done') {
              params.onDone()
              return
            }
          } catch {
            // 忽略 JSON 解析错误（部分数据）
          }
        }
      }

      // 流自然结束
      params.onDone()
    } catch (err: any) {
      if (err.name === 'AbortError') {
        // 用户主动取消，显式关闭流
        reader?.cancel().catch(() => {})
        return
      }
      params.onError(err instanceof Error ? err : new Error(String(err)))
    }
  })()

  return {
    abort: () => {
      controller.abort()
    },
  }
}
