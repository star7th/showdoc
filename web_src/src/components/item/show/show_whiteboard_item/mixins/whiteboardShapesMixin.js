export default {
  methods: {
    addObjectCommon(obj) {
      if (!this.canvas || !obj) return
      try {
        this.isDrawing = false
        this.isErasing = false
        this.isInsertingText = false
        this.pendingShapeType = null
        this.applyBrush && this.applyBrush()
        obj.set({ cornerStyle: 'circle', transparentCorners: false })
        this.canvas.add(obj)
        this.canvas.setActiveObject(obj)
        this.canvas.requestRenderAll()
        this.onContentChanged && this.onContentChanged()
      } catch (e) {}
    },
    insertRect() {
      if (this.isReadOnly || !this.canvas) return
      this.pendingShapeType = 'rect'
      this.isDrawing = false
      this.isErasing = false
      this.isInsertingText = false
      this.applyBrush && this.applyBrush()
      try {
        this.canvas.setCursor('crosshair')
      } catch (e) {}
    },
    insertCircle() {
      if (this.isReadOnly || !this.canvas) return
      this.pendingShapeType = 'circle'
      this.isDrawing = false
      this.isErasing = false
      this.isInsertingText = false
      this.applyBrush && this.applyBrush()
      try {
        this.canvas.setCursor('crosshair')
      } catch (e) {}
    },
    insertTriangle() {
      if (this.isReadOnly || !this.canvas) return
      this.pendingShapeType = 'triangle'
      this.isDrawing = false
      this.isErasing = false
      this.isInsertingText = false
      this.applyBrush && this.applyBrush()
      try {
        this.canvas.setCursor('crosshair')
      } catch (e) {}
    },
    insertArrow() {
      if (this.isReadOnly || !this.canvas) return
      this.pendingShapeType = 'arrow'
      this.isDrawing = false
      this.isErasing = false
      this.isInsertingText = false
      this.applyBrush && this.applyBrush()
      try {
        this.canvas.setCursor('crosshair')
      } catch (e) {}
    },
    insertShapeAtPosition(shapeType, x, y) {
      if (!this.canvas) return
      let obj = null
      switch (shapeType) {
        case 'rect': {
          const w = Math.max(60, this.brushWidth * 10)
          const h = Math.max(40, this.brushWidth * 7)
          obj = new window.fabric.Rect({
            left: x - w / 2,
            top: y - h / 2,
            width: w,
            height: h,
            fill: 'transparent',
            stroke: this.brushColor,
            strokeWidth: this.brushWidth
          })
          break
        }
        case 'circle': {
          const r = Math.max(30, this.brushWidth * 5)
          obj = new window.fabric.Circle({
            left: x - r,
            top: y - r,
            radius: r,
            fill: 'transparent',
            stroke: this.brushColor,
            strokeWidth: this.brushWidth
          })
          break
        }
        case 'triangle': {
          const w2 = Math.max(70, this.brushWidth * 12)
          const h2 = Math.max(60, this.brushWidth * 10)
          obj = new window.fabric.Triangle({
            left: x - w2 / 2,
            top: y - h2 / 2,
            width: w2,
            height: h2,
            fill: 'transparent',
            stroke: this.brushColor,
            strokeWidth: this.brushWidth
          })
          break
        }
        case 'arrow': {
          const len = Math.max(120, this.brushWidth * 20)
          const headSize = Math.max(10, this.brushWidth * 3)
          const lineEndX = len - headSize * 0.9
          const lineEndY = 0
          const line = new window.fabric.Line([0, 0, lineEndX, lineEndY], {
            stroke: this.brushColor,
            strokeWidth: this.brushWidth,
            selectable: false,
            evented: false
          })
          const head = new window.fabric.Triangle({
            left: lineEndX + headSize * 0.05,
            top: -headSize * 0.9,
            width: headSize * 2,
            height: headSize * 2,
            angle: 90,
            fill: this.brushColor,
            selectable: false,
            evented: false
          })
          obj = new window.fabric.Group([line, head], {
            left: x,
            top: y - this.brushWidth / 2
          })
          break
        }
      }
      if (obj) this.addObjectCommon(obj)
    }
  }
}
