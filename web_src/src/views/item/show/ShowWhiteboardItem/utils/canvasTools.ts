/**
 * 画布工具（画笔、橡皮擦、文本等）
 */

export function useCanvasTools(canvas: any) {
  /**
   * 获取橡皮擦光标
   */
  const getEraserCursor = (): string => {
    return 'cell'
  }

  /**
   * 获取当前主题对应的橡皮擦颜色
   */
  const getEraserColor = (): string => {
    const isDarkTheme = document.documentElement.getAttribute('data-theme') === 'dark'
    return isDarkTheme ? '#2d2d2d' : '#ffffff'
  }

  /**
   * 应用画笔设置
   */
  const applyBrush = (
    isReadOnly: any,
    isDrawing: any,
    isErasing: any,
    isInsertingText: any,
    brushColor: any,
    brushWidth: any
  ) => {
    if (!canvas.value) return

    // 兼容 ref 和值
    const isReadOnlyValue = isReadOnly.value !== undefined ? isReadOnly.value : isReadOnly
    const isDrawingValue = isDrawing.value !== undefined ? isDrawing.value : isDrawing
    const isErasingValue = isErasing.value !== undefined ? isErasing.value : isErasing
    const isInsertingTextValue = isInsertingText.value !== undefined ? isInsertingText.value : isInsertingText
    const brushColorValue = brushColor.value !== undefined ? brushColor.value : brushColor
    const brushWidthValue = brushWidth.value !== undefined ? brushWidth.value : brushWidth

    canvas.value.isDrawingMode =
      isReadOnlyValue || isInsertingTextValue ? false : isDrawingValue

    if (!canvas.value.freeDrawingBrush) {
      canvas.value.freeDrawingBrush = new (window as any).fabric.PencilBrush(
        canvas.value
      )
    }

    const color = isErasingValue ? getEraserColor() : brushColorValue
    canvas.value.freeDrawingBrush.color = color
    canvas.value.freeDrawingBrush.width = brushWidthValue

    try {
      canvas.value.freeDrawingCursor = isErasingValue
        ? getEraserCursor()
        : 'crosshair'
      const cursor = canvas.value.isDrawingMode
        ? isErasingValue
          ? getEraserCursor()
          : 'crosshair'
        : 'default'
      canvas.value.setCursor(cursor)
      canvas.value.requestRenderAll()
    } catch (e) {}
  }

  /**
   * 删除选中的对象
   */
  const deleteSelectedObjects = () => {
    if (!canvas.value) return
    const activeObject = canvas.value.getActiveObject()
    if (!activeObject) return

    if (activeObject.type === 'activeSelection') {
      activeObject.forEachObject((obj: any) => canvas.value.remove(obj))
      canvas.value.discardActiveObject()
    } else {
      canvas.value.remove(activeObject)
    }
    canvas.value.requestRenderAll()
  }

  /**
   * 应用只读模式
   */
  const applyReadOnlyMode = () => {
    if (!canvas.value) return
    canvas.value.isDrawingMode = false
    canvas.value.selection = false
    canvas.value.skipTargetFind = true

    try {
      canvas.value.forEachObject((obj: any) => {
        obj.selectable = false
        obj.evented = false
        if (obj.hasControls) obj.hasControls = false
      })
    } catch (e) {}

    if (canvas.value.discardActiveObject) {
      canvas.value.discardActiveObject()
    }
    if (canvas.value.requestRenderAll) {
      canvas.value.requestRenderAll()
    }
  }

  return {
    applyBrush,
    deleteSelectedObjects,
    applyReadOnlyMode
  }
}
