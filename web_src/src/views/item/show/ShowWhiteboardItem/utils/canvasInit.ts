/**
 * 画布初始化相关逻辑
 */

import { ref, onBeforeUnmount } from 'vue'

export function useCanvasInit(itemInfo: any, pageId: any) {
  const canvas = ref<any>(null)
  const resizeObserver = ref<any>(null)

  /**
   * 获取视口画布高度
   * 占据视口的 90%，减去头部和工具栏的高度
   */
  const getViewportCanvasHeight = (): number => {
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0
    // 视口高度的 90%，减去头部(约60px)和工具栏(约60px)
    const h = viewportHeight * 0.9 - 120
    return Math.max(h, 480)
  }

  /**
   * 初始化画布
   */
  const initCanvas = (isReadOnly: boolean, onContentChanged?: () => void) => {
    if (!(window as any).fabric) return
    const el = document.getElementById('whiteboard-canvas')
    if (!el) return

    const parent = document.querySelector('#whiteboard-item .canvas-inner') || el.parentElement
    let width = Math.max(parent?.clientWidth || 0, 600)
    let height = Math.max(parent?.clientHeight || 0, 0)

    if (height <= 0) {
      height = getViewportCanvasHeight()
      try {
        if (parent) {
          (parent as HTMLElement).style.height = height + 'px'
        }
      } catch (e) {}
    } else {
      height = Math.max(height, 480)
    }

    // 根据主题获取画布背景色
    const isDarkTheme = document.documentElement.getAttribute('data-theme') === 'dark'
    const backgroundColor = isDarkTheme ? '#2d2d2d' : '#ffffff'

    canvas.value = new (window as any).fabric.Canvas('whiteboard-canvas', {
      isDrawingMode: !isReadOnly,
      width,
      height,
      selection: !isReadOnly,
      backgroundColor
    })

    // 绑定事件
    canvas.value.on('path:created', () => {
      if (onContentChanged) onContentChanged()
    })
    canvas.value.on('object:modified', () => {
      if (onContentChanged) onContentChanged()
    })
    canvas.value.on('object:removed', () => {
      if (onContentChanged) onContentChanged()
    })

    bindResize()
  }

  /**
   * 绑定窗口大小变化
   */
  const bindResize = () => {
    const wrap = document.querySelector('#whiteboard-item .canvas-wrap')
    const inner = document.querySelector('#whiteboard-item .canvas-inner')
    if (!wrap) return

    const resize = () => {
      if (!canvas.value) return
      let w = (inner && (inner as HTMLElement).clientWidth) || (wrap as HTMLElement).clientWidth
      let h = (inner && (inner as HTMLElement).clientHeight) || (wrap as HTMLElement).clientHeight

      // 如果高度太小或为0，使用视口高度的90%
      if (h <= 0 || h < 480) {
        h = getViewportCanvasHeight()
        try {
          if (inner) {
            (inner as HTMLElement).style.height = h + 'px'
          } else {
            (wrap as HTMLElement).style.height = h + 'px'
          }
        } catch (e) {}
      }

      if (w <= 0) {
        w = Math.max(
          document.documentElement.clientWidth || window.innerWidth || 0,
          600
        )
      }

      if (w > 0 && h > 0) {
        canvas.value.setWidth(w)
        canvas.value.setHeight(h)
        canvas.value.renderAll()
      }
    }

    // ResizeObserver polyfill
    if (window.ResizeObserver) {
      resizeObserver.value = new window.ResizeObserver(resize)
      resizeObserver.value.observe(wrap)
    } else {
      // Fallback
      const ResizeObserverPolyfill = class {
        cb: any
        constructor(cb: any) {
          this.cb = cb
        }
        observe() {
          window.addEventListener('resize', this.cb)
        }
        disconnect() {
          window.removeEventListener('resize', this.cb)
        }
      }
      resizeObserver.value = new ResizeObserverPolyfill(resize)
      resizeObserver.value.observe(wrap)
    }

    window.addEventListener('resize', resize)
    setTimeout(resize, 16)
    setTimeout(resize, 200)
    setTimeout(resize, 800)
  }

  /**
   * 清理
   */
  const cleanup = () => {
    try {
      if (resizeObserver.value && resizeObserver.value.disconnect) {
        resizeObserver.value.disconnect()
      }
    } catch (e) {}

    if (canvas.value) {
      canvas.value.dispose()
    }
  }

  onBeforeUnmount(cleanup)

  /**
   * 设置画布背景色
   */
  const setBackgroundColor = (color: string) => {
    if (!canvas.value) return
    canvas.value.setBackgroundColor(color, canvas.value.renderAll.bind(canvas.value))
  }

  return {
    canvas,
    initCanvas,
    getViewportCanvasHeight,
    setBackgroundColor,
    cleanup
  }
}

