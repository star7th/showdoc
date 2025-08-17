export default {
  methods: {
    // 橡皮擦模式下的光标
    getEraserCursor() {
      return 'cell'
    },
    applyBrush() {
      if (!this.canvas) return
      this.canvas.isDrawingMode =
        this.isReadOnly || this.isInsertingText ? false : this.isDrawing
      if (!this.canvas.freeDrawingBrush) {
        this.canvas.freeDrawingBrush = new window.fabric.PencilBrush(
          this.canvas
        )
      }
      const color = this.isErasing ? '#ffffff' : this.brushColor
      this.canvas.freeDrawingBrush.color = color
      this.canvas.freeDrawingBrush.width = this.brushWidth
      try {
        this.canvas.freeDrawingCursor = this.isErasing
          ? this.getEraserCursor()
          : 'crosshair'
        const cursor = this.canvas.isDrawingMode
          ? this.isErasing
            ? this.getEraserCursor()
            : 'crosshair'
          : 'default'
        this.canvas.setCursor(cursor)
        this.canvas.requestRenderAll()
      } catch (e) {}
    },
    toggleDrawing() {
      if (this.isReadOnly) return
      this.isDrawing = !this.isDrawing
      this.pendingShapeType = null
      this.applyBrush()
      try {
        const cursor = this.isDrawing
          ? this.isErasing
            ? this.getEraserCursor()
            : 'crosshair'
          : 'default'
        this.canvas && this.canvas.setCursor(cursor)
        this.canvas && this.canvas.requestRenderAll()
      } catch (e) {}
    },
    toggleEraser() {
      if (this.isReadOnly) return
      this.isErasing = !this.isErasing
      if (this.isErasing) this.isDrawing = true
      this.pendingShapeType = null
      this.applyBrush()
      try {
        const cursor = this.isErasing ? this.getEraserCursor() : 'crosshair'
        this.canvas && this.canvas.setCursor(cursor)
        this.canvas && this.canvas.requestRenderAll()
      } catch (e) {}
    },
    toggleTextInsert() {
      if (this.isReadOnly) return
      this.isInsertingText = !this.isInsertingText
      if (this.isInsertingText) {
        this.isDrawing = false
        this.isErasing = false
        this.pendingShapeType = null
      } else {
        this.isDrawing = true
        this.isErasing = false
      }
      this.applyBrush()
      try {
        this.canvas &&
          this.canvas.setCursor(this.isInsertingText ? 'text' : 'default')
        this.canvas.requestRenderAll()
      } catch (e) {}
    },
    onCanvasMouseMove() {
      if (this.isReadOnly || !this.canvas || this.spacePressed || this.panning)
        return
      try {
        let cursor = 'default'
        if (this.pendingShapeType) cursor = 'crosshair'
        else if (this.isInsertingText) cursor = 'text'
        else if (this.isDrawing && this.isErasing)
          cursor = this.getEraserCursor()
        else if (this.isDrawing) cursor = 'crosshair'
        this.canvas.setCursor(cursor)
      } catch (e) {}
    },
    onCanvasMouseDown(opt) {
      if (this.isReadOnly || !this.canvas || this.spacePressed) return
      const evt = opt && opt.e
      const pointer = this.canvas.getPointer(evt)
      if (this.pendingShapeType) {
        this.insertShapeAtPosition &&
          this.insertShapeAtPosition(
            this.pendingShapeType,
            pointer.x,
            pointer.y
          )
        return
      }
      if (!this.isInsertingText) return
      const textbox = new window.fabric.Textbox('', {
        left: pointer.x,
        top: pointer.y,
        width: 300,
        fontSize: this.textFontSize,
        fill: this.textColor,
        editable: true,
        splitByGrapheme: true,
        cornerStyle: 'circle',
        transparentCorners: false
      })
      this.canvas.add(textbox)
      this.canvas.setActiveObject(textbox)
      this.canvas.requestRenderAll()
      try {
        textbox.enterEditing()
        textbox.selectionStart = 0
        textbox.selectionEnd = 0
      } catch (e) {}
      textbox.on('editing:exited', () => {
        this.isInsertingText = false
        this.isDrawing = false
        this.isErasing = false
        this.applyBrush()
        try {
          this.canvas && this.canvas.setCursor('default')
          this.canvas.requestRenderAll()
        } catch (e) {}
        this.onContentChanged && this.onContentChanged()
      })
      this.onContentChanged && this.onContentChanged()
    },
    deleteSelectedObjects() {
      if (!this.canvas || this.isReadOnly) return
      const activeObject = this.canvas.getActiveObject()
      if (!activeObject) return
      if (activeObject.type === 'activeSelection') {
        activeObject.forEachObject(obj => this.canvas.remove(obj))
        this.canvas.discardActiveObject()
      } else {
        this.canvas.remove(activeObject)
      }
      this.canvas.requestRenderAll()
      this.onContentChanged && this.onContentChanged()
    },
    applyReadOnlyMode() {
      if (!this.canvas) return
      this.isDrawing = false
      this.isErasing = false
      this.isInsertingText = false
      this.canvas.isDrawingMode = false
      this.canvas.selection = false
      this.canvas.skipTargetFind = true
      try {
        this.canvas.forEachObject(obj => {
          obj.selectable = false
          obj.evented = false
          if (obj.hasControls) obj.hasControls = false
        })
      } catch (e) {}
      this.canvas.discardActiveObject && this.canvas.discardActiveObject()
      this.canvas.requestRenderAll && this.canvas.requestRenderAll()
    }
  }
}
