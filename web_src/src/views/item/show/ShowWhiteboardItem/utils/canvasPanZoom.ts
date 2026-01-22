/**
 * 画布平移和缩放
 */

import { ref } from 'vue'

export function useCanvasPanZoom(canvas: any, isDrawing: any, isErasing: any) {
  const spacePressed = ref(false)
  const panning = ref(false)

  /**
   * 绑定平移和滚轮缩放
   */
  const bindPanAndWheel = () => {
    if (!canvas.value) return

    window.addEventListener('keydown', onKeyDown)
    window.addEventListener('keyup', onKeyUp)

    canvas.value.on('mouse:down', () => {
      if (spacePressed.value) {
        panning.value = true
        canvas.value.setCursor('grab')
        canvas.value.renderAll()
      }
    })

    canvas.value.on('mouse:move', (opt: any) => {
      if (panning.value) {
        const evt = opt.e
        const delta = new (window as any).fabric.Point(evt.movementX, evt.movementY)
        canvas.value.relativePan(delta)
      }
    })

    canvas.value.on('mouse:up', () => {
      panning.value = false
      canvas.value.setCursor('default')
      canvas.value.renderAll()
    })

    canvas.value.on('mouse:wheel', (opt: any) => {
      const evt = opt.e
      if (!evt.ctrlKey) return
      evt.preventDefault()
      evt.stopPropagation()

      let zoom = canvas.value.getZoom()
      zoom *= Math.pow(0.999, evt.deltaY)
      zoom = Math.max(0.25, Math.min(zoom, 4))

      const pointer = new (window as any).fabric.Point(evt.offsetX, evt.offsetY)
      canvas.value.zoomToPoint(pointer, zoom)
    })
  }

  /**
   * 键盘按下事件
   */
  const onKeyDown = (e: KeyboardEvent) => {
    if (e.code === 'Space') {
      spacePressed.value = true
      if (canvas.value) {
        canvas.value.isDrawingMode = false
      }
    }
    if (e.code === 'Delete' || e.code === 'Backspace') {
      // TODO: 删除选中的对象
    }
  }

  /**
   * 键盘抬起事件
   */
  const onKeyUp = (e: KeyboardEvent) => {
    if (e.code === 'Space') {
      spacePressed.value = false
      if (canvas.value) {
        canvas.value.isDrawingMode = isDrawing.value
        try {
          const cursor = canvas.value.isDrawingMode
            ? isErasing.value
              ? 'cell'
              : 'crosshair'
            : 'default'
          canvas.value.setCursor(cursor)
          canvas.value.requestRenderAll()
        } catch (e2) {}
      }
    }
  }

  /**
   * 放大
   */
  const zoomIn = () => {
    if (!canvas.value) return
    const zoom = Math.min(canvas.value.getZoom() * 1.1, 4)
    canvas.value.setZoom(zoom)
    canvas.value.requestRenderAll()
  }

  /**
   * 缩小
   */
  const zoomOut = () => {
    if (!canvas.value) return
    const zoom = Math.max(canvas.value.getZoom() / 1.1, 0.25)
    canvas.value.setZoom(zoom)
    canvas.value.requestRenderAll()
  }

  /**
   * 重置缩放
   */
  const zoomReset = () => {
    if (!canvas.value) return
    canvas.value.setViewportTransform([1, 0, 0, 1, 0, 0])
    canvas.value.requestRenderAll()
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

    const w = (inner as HTMLElement).clientWidth || (wrap as HTMLElement).clientWidth
    const h =
      (inner as HTMLElement).clientHeight ||
      (wrap as HTMLElement).clientHeight ||
      480

    canvas.value.setWidth(w)
    canvas.value.setHeight(Math.max(h, 480))
    zoomReset()
    canvas.value.renderAll()
  }

  return {
    bindPanAndWheel,
    zoomIn,
    zoomOut,
    zoomReset,
    fitToViewport,
    spacePressed,
    panning
  }
}

