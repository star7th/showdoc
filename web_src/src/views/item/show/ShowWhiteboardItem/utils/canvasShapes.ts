/**
 * 画布形状工具（矩形、圆形、三角形、箭头）
 */

export function useCanvasShapes() {
  /**
   * 在指定位置插入形状
   */
  const insertShapeAtPosition = (
    canvas: any,
    shapeType: string,
    x: number,
    y: number,
    brushColor: string,
    brushWidth: number,
    onContentChanged?: () => void
  ) => {
    if (!canvas) return

    let obj: any = null
    const fabric = (window as any).fabric

    switch (shapeType) {
      case 'rect': {
        const w = Math.max(60, brushWidth * 10)
        const h = Math.max(40, brushWidth * 7)
        obj = new fabric.Rect({
          left: x - w / 2,
          top: y - h / 2,
          width: w,
          height: h,
          fill: 'transparent',
          stroke: brushColor,
          strokeWidth: brushWidth
        })
        break
      }
      case 'circle': {
        const r = Math.max(30, brushWidth * 5)
        obj = new fabric.Circle({
          left: x - r,
          top: y - r,
          radius: r,
          fill: 'transparent',
          stroke: brushColor,
          strokeWidth: brushWidth
        })
        break
      }
      case 'triangle': {
        const w2 = Math.max(70, brushWidth * 12)
        const h2 = Math.max(60, brushWidth * 10)
        obj = new fabric.Triangle({
          left: x - w2 / 2,
          top: y - h2 / 2,
          width: w2,
          height: h2,
          fill: 'transparent',
          stroke: brushColor,
          strokeWidth: brushWidth
        })
        break
      }
      case 'arrow': {
        const len = Math.max(120, brushWidth * 20)
        const headSize = Math.max(10, brushWidth * 3)
        const lineEndX = len - headSize * 0.9
        const lineEndY = 0
        const line = new fabric.Line([0, 0, lineEndX, lineEndY], {
          stroke: brushColor,
          strokeWidth: brushWidth,
          selectable: false,
          evented: false
        })
        const head = new fabric.Triangle({
          left: lineEndX + headSize * 0.05,
          top: -headSize * 0.9,
          width: headSize * 2,
          height: headSize * 2,
          angle: 90,
          fill: brushColor,
          selectable: false,
          evented: false
        })
        obj = new fabric.Group([line, head], {
          left: x,
          top: y - brushWidth / 2
        })
        break
      }
    }

    if (obj) {
      obj.set({ cornerStyle: 'circle', transparentCorners: false })
      canvas.add(obj)
      canvas.setActiveObject(obj)
      canvas.requestRenderAll()

      if (onContentChanged) {
        onContentChanged()
      }
    }
  }

  return {
    insertShapeAtPosition
  }
}
