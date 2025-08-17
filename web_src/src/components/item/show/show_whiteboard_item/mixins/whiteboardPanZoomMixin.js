export default {
  data() {
    return {
      spacePressed: false,
      panning: false
    }
  },
  methods: {
    bindPanAndWheel() {
      if (!this.canvas) return
      window.addEventListener('keydown', this.onKeyDown)
      window.addEventListener('keyup', this.onKeyUp)
      this.canvas.on('mouse:down', () => {
        if (this.spacePressed) {
          this.panning = true
          this.canvas.setCursor('grab')
          this.canvas.renderAll()
        }
      })
      this.canvas.on('mouse:move', opt => {
        if (this.panning) {
          const evt = opt.e
          const delta = new window.fabric.Point(evt.movementX, evt.movementY)
          this.canvas.relativePan(delta)
        }
      })
      this.canvas.on('mouse:up', () => {
        this.panning = false
        this.canvas.setCursor('default')
        this.canvas.renderAll()
      })
      this.canvas.on('mouse:wheel', opt => {
        const evt = opt.e
        if (!evt.ctrlKey) return
        evt.preventDefault()
        evt.stopPropagation()
        let zoom = this.canvas.getZoom()
        zoom *= Math.pow(0.999, evt.deltaY)
        zoom = Math.max(0.25, Math.min(zoom, 4))
        const pointer = new window.fabric.Point(evt.offsetX, evt.offsetY)
        this.canvas.zoomToPoint(pointer, zoom)
      })
    },
    onKeyDown(e) {
      if (e.code === 'Space') {
        this.spacePressed = true
        if (this.canvas) this.canvas.isDrawingMode = false
      }
      if (e.code === 'Delete' || e.code === 'Backspace') {
        if (this.isInsertingText) return
        this.deleteSelectedObjects && this.deleteSelectedObjects()
      }
    },
    onKeyUp(e) {
      if (e.code === 'Space') {
        this.spacePressed = false
        if (this.canvas) {
          this.canvas.isDrawingMode = this.isReadOnly ? false : this.isDrawing
          try {
            const cursor = this.canvas.isDrawingMode
              ? this.isErasing
                ? this.getEraserCursor && this.getEraserCursor()
                : 'crosshair'
              : 'default'
            this.canvas.setCursor(cursor)
            this.canvas.requestRenderAll()
          } catch (e2) {}
        }
      }
    },
    zoomIn() {
      if (!this.canvas) return
      const zoom = Math.min(this.canvas.getZoom() * 1.1, 4)
      this.canvas.setZoom(zoom)
      this.canvas.requestRenderAll()
    },
    zoomOut() {
      if (!this.canvas) return
      const zoom = Math.max(this.canvas.getZoom() / 1.1, 0.25)
      this.canvas.setZoom(zoom)
      this.canvas.requestRenderAll()
    },
    zoomReset() {
      if (!this.canvas) return
      this.canvas.setViewportTransform([1, 0, 0, 1, 0, 0])
      this.canvas.requestRenderAll()
    }
  }
}
