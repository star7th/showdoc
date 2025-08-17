export default {
  data() {
    return {
      customCanvasSize: null,
      resizing: false,
      resizeStart: { x: 0, y: 0, width: 0, height: 0 }
    }
  },
  methods: {
    storageKeyForSize() {
      const itemId = (this.item_info && this.item_info.item_id) || '0'
      const pageId = this.page_id || '0'
      return `whiteboard:size:${itemId}:${pageId}`
    },
    loadSavedSize() {
      try {
        const raw = localStorage.getItem(this.storageKeyForSize())
        if (!raw) return null
        const obj = JSON.parse(raw)
        if (
          obj &&
          typeof obj.width === 'number' &&
          typeof obj.height === 'number'
        ) {
          return obj
        }
      } catch (e) {}
      return null
    },
    saveCustomSize(width, height) {
      try {
        localStorage.setItem(
          this.storageKeyForSize(),
          JSON.stringify({ width, height })
        )
      } catch (e) {}
    },
    clearSavedSize() {
      try {
        localStorage.removeItem(this.storageKeyForSize())
      } catch (e) {}
    },
    applyCustomSize(width, height) {
      const inner = document.querySelector('#whiteboard-item .canvas-inner')
      const wrap = document.querySelector('#whiteboard-item .canvas-wrap')
      if (!inner || !this.canvas) return
      const min = 480
      const max = 10000
      const w = Math.max(min, Math.min(width || 0, max))
      const h = Math.max(min, Math.min(height || 0, max))
      inner.style.width = w + 'px'
      inner.style.height = h + 'px'
      if (wrap) wrap.scrollLeft = wrap.scrollWidth
      this.canvas.setWidth(w)
      this.canvas.setHeight(h)
      this.canvas.renderAll()
    },
    fitToViewport() {
      const inner = document.querySelector('#whiteboard-item .canvas-inner')
      const wrap = document.querySelector('#whiteboard-item .canvas-wrap')
      if (!inner || !wrap || !this.canvas) return
      inner.style.width = ''
      inner.style.height = ''
      this.customCanvasSize = null
      this.clearSavedSize()
      const w = inner.clientWidth || wrap.clientWidth
      const h =
        inner.clientHeight ||
        wrap.clientHeight ||
        this.getViewportCanvasHeight()
      this.canvas.setWidth(w)
      this.canvas.setHeight(Math.max(h, 480))
      this.zoomReset && this.zoomReset()
      this.canvas.renderAll()
    },
    onResizeHandleDown(e) {
      if (this.isReadOnly) return
      const inner = document.querySelector('#whiteboard-item .canvas-inner')
      if (!inner) return
      this.resizing = true
      this.resizeStart = {
        x: e.clientX,
        y: e.clientY,
        width: inner.clientWidth,
        height: inner.clientHeight
      }
      document.addEventListener('mousemove', this.onResizing)
      document.addEventListener('mouseup', this.onResizeHandleUp, {
        once: true
      })
    },
    onResizing(e) {
      if (!this.resizing) return
      const dx = (e.clientX - this.resizeStart.x) * 2.5
      const dy = (e.clientY - this.resizeStart.y) * 2.5
      const newW = Math.round(this.resizeStart.width + dx)
      const newH = Math.round(this.resizeStart.height + dy)
      this.customCanvasSize = { width: newW, height: newH }
      this.applyCustomSize(newW, newH)
    },
    onResizeHandleUp() {
      if (!this.resizing) return
      this.resizing = false
      document.removeEventListener('mousemove', this.onResizing)
      if (this.customCanvasSize) {
        this.saveCustomSize(
          this.customCanvasSize.width,
          this.customCanvasSize.height
        )
      }
    }
  }
}
