/**
 * 画布尺寸管理
 */

import { ref } from 'vue'

export function useCanvasSize(itemInfo: any, pageId: any, canvas: any) {
  const customCanvasSize = ref<{ width: number; height: number } | null>(null)
  const resizing = ref(false)
  const resizeStart = ref({ x: 0, y: 0, width: 0, height: 0 })

  /**
   * 获取存储的键名
   */
  const storageKeyForSize = (): string => {
    const itemId = itemInfo.value?.item_id || '0'
    const pId = pageId.value || '0'
    return `whiteboard:size:${itemId}:${pId}`
  }

  /**
   * 加载保存的尺寸
   */
  const loadSavedSize = () => {
    try {
      const raw = localStorage.getItem(storageKeyForSize())
      if (!raw) return null
      const obj = JSON.parse(raw)
      if (obj && typeof obj.width === 'number' && typeof obj.height === 'number') {
        return obj
      }
    } catch (e) {}
    return null
  }

  /**
   * 保存自定义尺寸
   */
  const saveCustomSize = (width: number, height: number) => {
    try {
      localStorage.setItem(
        storageKeyForSize(),
        JSON.stringify({ width, height })
      )
    } catch (e) {}
  }

  /**
   * 清除保存的尺寸
   */
  const clearSavedSize = () => {
    try {
      localStorage.removeItem(storageKeyForSize())
    } catch (e) {}
  }

  /**
   * 应用自定义尺寸
   */
  const applyCustomSize = (width: number, height: number) => {
    const inner = document.querySelector('#whiteboard-item .canvas-inner')
    const wrap = document.querySelector('#whiteboard-item .canvas-wrap')

    if (!inner || !canvas.value) return

    const min = 480
    const max = 10000
    const w = Math.max(min, Math.min(width || 0, max))
    const h = Math.max(min, Math.min(height || 0, max))

    ;(inner as HTMLElement).style.width = w + 'px'
    ;(inner as HTMLElement).style.height = h + 'px'

    if (wrap) {
      (wrap as HTMLElement).scrollLeft = (wrap as HTMLElement).scrollWidth
    }

    canvas.value.setWidth(w)
    canvas.value.setHeight(h)
    canvas.value.renderAll()
  }

  /**
   * 拖拽手柄按下
   */
  const onResizeHandleDown = (e: MouseEvent) => {
    const inner = document.querySelector('#whiteboard-item .canvas-inner')
    if (!inner) return

    resizing.value = true
    resizeStart.value = {
      x: e.clientX,
      y: e.clientY,
      width: (inner as HTMLElement).clientWidth,
      height: (inner as HTMLElement).clientHeight
    }

    document.addEventListener('mousemove', onResizing)
    document.addEventListener('mouseup', onResizeHandleUp, { once: true })
  }

  /**
   * 拖拽中
   */
  const onResizing = (e: MouseEvent) => {
    if (!resizing.value) return

    const dx = (e.clientX - resizeStart.value.x) * 2.5
    const dy = (e.clientY - resizeStart.value.y) * 2.5
    const newW = Math.round(resizeStart.value.width + dx)
    const newH = Math.round(resizeStart.value.height + dy)

    customCanvasSize.value = { width: newW, height: newH }
    applyCustomSize(newW, newH)
  }

  /**
   * 拖拽结束
   */
  const onResizeHandleUp = () => {
    if (!resizing.value) return

    resizing.value = false
    document.removeEventListener('mousemove', onResizing)

    if (customCanvasSize.value) {
      saveCustomSize(
        customCanvasSize.value.width,
        customCanvasSize.value.height
      )
    }
  }

  /**
   * 适屏显示
   */
  const fitToViewport = () => {
    const inner = document.querySelector('#whiteboard-item .canvas-inner')
    const wrap = document.querySelector('#whiteboard-item .canvas-wrap')
    if (!inner || !wrap || !canvas.value) return

    ;(inner as HTMLElement).style.width = ''
    ;(inner as HTMLElement).style.height = ''
    customCanvasSize.value = null
    clearSavedSize()

    const w = (inner as HTMLElement).clientWidth || (wrap as HTMLElement).clientWidth
    const h = Math.max(
      (inner as HTMLElement).clientHeight ||
        (wrap as HTMLElement).clientHeight ||
        window.innerHeight - 150,
      480
    )

    canvas.value.setWidth(w)
    canvas.value.setHeight(h)
    canvas.value.renderAll()
  }

  return {
    customCanvasSize,
    resizing,
    loadSavedSize,
    saveCustomSize,
    clearSavedSize,
    applyCustomSize,
    onResizeHandleDown,
    onResizeHandleUp,
    fitToViewport
  }
}

