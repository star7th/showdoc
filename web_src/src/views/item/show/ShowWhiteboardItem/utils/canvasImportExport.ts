/**
 * 画布导入导出功能
 */

import { ref } from 'vue'

export function useCanvasImportExport(canvas: any, itemInfo: any, serialize: () => string) {
  const imgFile = ref<HTMLInputElement | null>(null)
  const jsonFile = ref<HTMLInputElement | null>(null)

  /**
   * 清空画布
   */
  const clearCanvas = () => {
    if (!canvas.value) return
    canvas.value.clear()
    canvas.value.setBackgroundColor('#ffffff', canvas.value.renderAll.bind(canvas.value))
  }

  /**
   * 导出为 PNG
   */
  const exportImage = () => {
    if (!canvas.value) return
    const dataURL = canvas.value.toDataURL({ format: 'png', quality: 1 })
    const a = document.createElement('a')
    a.href = dataURL
    a.download = 'showdoc-whiteboard.png'
    a.click()
  }

  /**
   * 导出为 SVG
   */
  const exportSVG = () => {
    if (!canvas.value) return
    try {
      const svg = canvas.value.toSVG()
      const blob = new Blob([svg], { type: 'image/svg+xml' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = 'showdoc-whiteboard.svg'
      a.click()
      URL.revokeObjectURL(url)
    } catch (e) {}
  }

  /**
   * 导出为 JSON
   */
  const exportJSON = () => {
    const json = serialize()
    const blob = new Blob([json], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'showdoc-whiteboard.json'
    a.click()
    URL.revokeObjectURL(url)
  }

  /**
   * 导入 JSON
   */
  const importJSON = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (!file) return

    const reader = new FileReader()
    reader.onload = () => {
      try {
        const str = String(reader.result || '')
        // TODO: 调用 loadFromJSONString
        // Message.success('操作成功')
      } catch (err) {
        // Message.error('导入失败')
      }
    }
    reader.readAsText(file)
  }

  /**
   * 导入图片
   */
  const importImage = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (!file || !canvas.value) return

    const reader = new FileReader()
    reader.onload = () => {
      const dataURL = reader.result
      const imgEl = new Image()
      imgEl.onload = () => {
        const maxW = canvas.value.getWidth()
        const maxH = canvas.value.getHeight()
        const ratio = Math.min(maxW / imgEl.width, maxH / imgEl.height, 1)

        const fabric = (window as any).fabric
        fabric.Image.fromURL(
          dataURL as string,
          (img: any) => {
            img.set({
              left: (maxW - imgEl.width * ratio) / 2,
              top: (maxH - imgEl.height * ratio) / 2,
              selectable: true,
              hasControls: true,
              cornerStyle: 'circle',
              transparentCorners: false
            })
            img.scale(ratio)
            canvas.value.add(img)
            canvas.value.setActiveObject(img)
            canvas.value.requestRenderAll()
          },
          { crossOrigin: 'anonymous' }
        )
      }
      imgEl.src = dataURL as string
    }
    reader.readAsDataURL(file)
  }

  /**
   * 触发图片上传
   */
  const triggerImageUpload = () => {
    if (imgFile.value) {
      imgFile.value.value = ''
      imgFile.value.click()
    }
  }

  /**
   * 触发 JSON 导入
   */
  const triggerImport = () => {
    if (jsonFile.value) {
      jsonFile.value.value = ''
      jsonFile.value.click()
    }
  }

  return {
    imgFile,
    jsonFile,
    clearCanvas,
    exportImage,
    exportSVG,
    exportJSON,
    importJSON,
    importImage,
    triggerImageUpload,
    triggerImport
  }
}

