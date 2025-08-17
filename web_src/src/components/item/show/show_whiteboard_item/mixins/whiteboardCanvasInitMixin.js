export default {
  methods: {
    loadDeps(cb) {
      const url = `${this.getStaticPath()}whiteboard/fabric.min.js`
      if (typeof window !== 'undefined' && window.$script) {
        window.$script([url], () => cb && cb())
        return
      }
      try {
        const $s = require('scriptjs')
        $s([url], () => cb && cb())
      } catch (e) {
        // ignore
      }
    },
    getStaticPath() {
      try {
        return window.DocConfig && window.DocConfig.staticPath
          ? window.DocConfig.staticPath
          : '/static/'
      } catch (e) {
        return '/static/'
      }
    },
    getViewportCanvasHeight() {
      const h =
        (window.innerHeight || document.documentElement.clientHeight || 0) - 140
      return Math.max(h, 480)
    },
    initCanvas() {
      if (!window.fabric) return
      const el = document.getElementById('whiteboard-canvas')
      if (!el) return
      const parent =
        document.querySelector('#whiteboard-item .canvas-inner') ||
        el.parentElement
      let width = Math.max(parent.clientWidth || 0, 600)
      let height = Math.max(parent.clientHeight || 0, 0)
      if (height <= 0) {
        height = this.getViewportCanvasHeight()
        try {
          parent.style.height = height + 'px'
        } catch (e) {}
      } else {
        height = Math.max(height, 480)
      }
      this.canvas = new window.fabric.Canvas('whiteboard-canvas', {
        isDrawingMode: !this.isReadOnly,
        width,
        height,
        selection: !this.isReadOnly,
        backgroundColor: '#ffffff'
      })
      this.applyBrush && this.applyBrush()
      this.canvas.on('mouse:down', this.onCanvasMouseDown)
      this.canvas.on('mouse:move', this.onCanvasMouseMove)
      this.canvas.on('path:created', () => {
        this.onContentChanged && this.onContentChanged()
      })
      this.canvas.on(
        'object:modified',
        () => this.onContentChanged && this.onContentChanged()
      )
      this.canvas.on(
        'object:removed',
        () => this.onContentChanged && this.onContentChanged()
      )
      this.canvas.on('selection:created', this.onSelectionChanged)
      this.canvas.on('selection:updated', this.onSelectionChanged)
      this.canvas.on('selection:cleared', this.onSelectionCleared)
      this.bindResize()
      if (this.isReadOnly) this.applyReadOnlyMode && this.applyReadOnlyMode()
    },
    bindResize() {
      const wrap = document.querySelector('#whiteboard-item .canvas-wrap')
      const inner = document.querySelector('#whiteboard-item .canvas-inner')
      if (!wrap) return
      const resize = () => {
        if (!this.canvas) return
        let w = (inner && inner.clientWidth) || wrap.clientWidth
        let h = (inner && inner.clientHeight) || wrap.clientHeight
        if (h <= 0) {
          h = this.getViewportCanvasHeight()
          try {
            if (inner) inner.style.height = h + 'px'
            else wrap.style.height = h + 'px'
          } catch (e) {}
        }
        if (w <= 0) {
          w = Math.max(
            document.documentElement.clientWidth || window.innerWidth || 0,
            600
          )
        }
        if (w > 0 && h > 0) {
          this.canvas.setWidth(w)
          this.canvas.setHeight(h)
          this.canvas.renderAll()
        }
      }
      this.resizeObserver = new (window.ResizeObserver ||
        class {
          constructor(cb) {
            this.cb = cb
          }
          observe() {
            window.addEventListener('resize', this.cb)
          }
          disconnect() {
            window.removeEventListener('resize', this.cb)
          }
        })(resize)
      this.resizeObserver.observe(wrap)
      window.addEventListener('resize', resize)
      setTimeout(resize, 16)
      setTimeout(resize, 200)
      setTimeout(resize, 800)
    }
  }
}
