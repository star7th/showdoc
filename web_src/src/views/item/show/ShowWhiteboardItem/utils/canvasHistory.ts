/**
 * 画布历史记录（撤销/重做）和自动保存
 */

import { ref } from 'vue'
import request from '@/utils/request'
import { message } from 'ant-design-vue'
import { unescapeHTML } from '@/models/page'

export function useCanvasHistory(
  canvas: any,
  pageId: any,
  itemInfo: any,
  options: {
    applyCustomSize?: (width: number, height: number) => void
    applyReadOnlyMode?: () => void
    onContentLoaded?: () => void
    onContentLoadedFromJSON?: () => void
  } = {}
) {
  const undoStack = ref<string[]>([])
  const redoStack = ref<string[]>([])
  const autoSaveTimer = ref<any>(null)
  const autoSaveLastAt = ref(0)

  /**
   * 序列化画布内容
   */
  const serialize = (): string => {
    if (!canvas.value) return '{}'
    const json = canvas.value.toJSON()
    // 移除背景色，以便加载时使用当前主题的背景色
    const { backgroundColor, ...jsonWithoutBg } = json
    const payload = {
      __canvasSize: {
        width: canvas.value.getWidth(),
        height: canvas.value.getHeight()
      },
      ...jsonWithoutBg
    }
    return JSON.stringify(payload)
  }

  /**
   * 保存到服务器
   */
  const save = async (silent = false): Promise<void> => {
    if (!pageId.value || !canvas.value) return

    try {
      await request(
        '/api/page/save',
        {
          page_id: pageId.value,
          page_title: itemInfo.value?.item_name,
          item_id: itemInfo.value?.item_id,
          is_urlencode: 1,
          page_content: encodeURIComponent(serialize())
        },
        'post',
        false,
        'form'
      )
      autoSaveLastAt.value = Date.now()
      if (!silent) {
        message.success('已保存')
      }
    } catch (error) {
      if (!silent) {
        message.error('保存失败')
      }
    }
  }

  /**
   * 调度自动保存
   */
  const scheduleAutoSave = () => {
    if (!itemInfo.value || !itemInfo.value.item_edit) return
    if (autoSaveTimer.value) {
      clearTimeout(autoSaveTimer.value)
      autoSaveTimer.value = null
    }

    const now = Date.now()
    const elapsed = now - autoSaveLastAt.value
    const autoSaveMinIntervalMs = 10000
    const autoSaveDebounceMs = 2000
    const throttleRemain = Math.max(autoSaveMinIntervalMs - elapsed, 0)
    const delay = Math.max(autoSaveDebounceMs, throttleRemain)

    autoSaveTimer.value = setTimeout(() => {
      save(true)
    }, delay)
  }

  /**
   * 内容变化时的处理
   */
  const onContentChanged = () => {
    pushUndoState()
    redoStack.value = []
    scheduleAutoSave()
  }

  /**
   * 保存撤销状态
   */
  const pushUndoState = () => {
    if (!canvas.value) return
    const snapshot = serialize()
    const max = 50
    undoStack.value.push(snapshot)
    if (undoStack.value.length > max) {
      undoStack.value.shift()
    }
  }

  /**
   * 撤销
   */
  const undo = () => {
    if (undoStack.value.length <= 1) return
    const current = undoStack.value.pop()
    redoStack.value.push(current!)
    const prev = undoStack.value[undoStack.value.length - 1]
    loadFromJSONString(prev)
  }

  /**
   * 重做
   */
  const redo = () => {
    if (redoStack.value.length === 0) return
    const next = redoStack.value.pop()
    if (next) {
      undoStack.value.push(next)
      loadFromJSONString(next)
    }
  }

  /**
   * 从 JSON 字符串加载画布内容
   */
  const loadFromJSONString = (str: string) => {
    try {
      const json = JSON.parse(str)
      if (json && json.__canvasSize) {
        const { width, height } = json.__canvasSize
        if (width && height && options.applyCustomSize) {
          options.applyCustomSize(width, height)
        }
      }
      canvas.value.loadFromJSON(json, () => {
        canvas.value.renderAll()
        if (itemInfo.value?.item_edit == 0 && options.applyReadOnlyMode) {
          options.applyReadOnlyMode()
        }
        if (options.onContentLoaded) {
          options.onContentLoaded()
        }
        if (options.onContentLoadedFromJSON) {
          options.onContentLoadedFromJSON()
        }
      })
    } catch (e) {}
  }

  /**
   * 从服务器加载页面内容
   */
  const loadPageContent = async () => {
    try {
      const data = await request(
        '/api/page/info',
        { page_id: pageId.value },
        'post',
        false,
        'form'
      )
      if (!canvas.value) return

      let content = data?.data?.page_content
      if (content) {
        try {
          const json = JSON.parse(unescapeHTML(content))
          if (json && json.__canvasSize) {
            const { width, height } = json.__canvasSize
            if (width && height && options.applyCustomSize) {
              options.applyCustomSize(width, height)
            }
          }
          canvas.value.loadFromJSON(json, () => {
            canvas.value.renderAll()
            // 只有在没有编辑权限时才应用只读模式
            if (itemInfo.value?.item_edit == 0 && options.applyReadOnlyMode) {
              options.applyReadOnlyMode()
            }
            if (options.onContentLoaded) {
              options.onContentLoaded()
            }
            pushUndoState()
          })
        } catch (e) {
          pushUndoState()
        }
      } else {
        pushUndoState()
      }
    } catch (error) {
      pushUndoState()
    }
  }

  return {
    undoStack,
    redoStack,
    onContentChanged,
    undo,
    redo,
    save,
    loadPageContent,
    serialize
  }
}

