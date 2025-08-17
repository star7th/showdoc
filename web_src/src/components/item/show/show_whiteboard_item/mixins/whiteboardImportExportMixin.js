export default {
  methods: {
    clearCanvas() {
      if (this.isReadOnly || !this.canvas) return
      this.canvas.clear()
      this.canvas.setBackgroundColor(
        '#ffffff',
        this.canvas.renderAll.bind(this.canvas)
      )
      this.onContentChanged && this.onContentChanged()
    },
    exportImage() {
      if (this.isVisitor || !this.canvas) return
      const dataURL = this.canvas.toDataURL({ format: 'png', quality: 1 })
      const a = document.createElement('a')
      a.href = dataURL
      a.download = 'showdoc-whiteboard.png'
      a.click()
    },
    exportSVG() {
      if (this.isVisitor || !this.canvas) return
      try {
        const svg = this.canvas.toSVG()
        const blob = new Blob([svg], { type: 'image/svg+xml' })
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = 'showdoc-whiteboard.svg'
        a.click()
        URL.revokeObjectURL(url)
      } catch (e) {}
    },
    exportJSON() {
      if (this.isVisitor || !this.canvas) return
      const json = this.serialize ? this.serialize() : '{}'
      const blob = new Blob([json], { type: 'application/json' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = 'showdoc-whiteboard.json'
      a.click()
      URL.revokeObjectURL(url)
    },
    triggerImport() {
      if (this.$refs.jsonFile) {
        this.$refs.jsonFile.value = ''
        this.$refs.jsonFile.click()
      }
    },
    onImportJSON(e) {
      const file = e && e.target && e.target.files && e.target.files[0]
      if (!file) return
      const reader = new FileReader()
      reader.onload = () => {
        try {
          const str = String(reader.result || '')
          this.loadFromJSONString && this.loadFromJSONString(str)
          this.onContentChanged && this.onContentChanged()
          this.$message.success(this.$t('op_success') || '操作成功')
        } catch (err) {
          this.$message.error(this.$t('upload_failed_error') || '导入失败')
        }
      }
      reader.readAsText(file)
    },
    triggerImageUpload() {
      if (this.$refs.imgFile) {
        this.$refs.imgFile.value = ''
        this.$refs.imgFile.click()
      }
    },
    onImportImage(e) {
      const file = e && e.target && e.target.files && e.target.files[0]
      if (!file || !this.canvas) return
      const reader = new FileReader()
      reader.onload = () => {
        const dataURL = reader.result
        const imgEl = new Image()
        imgEl.onload = () => {
          const maxW = this.canvas.getWidth()
          const maxH = this.canvas.getHeight()
          const ratio = Math.min(maxW / imgEl.width, maxH / imgEl.height, 1)
          window.fabric.Image.fromURL(
            dataURL,
            img => {
              img.set({
                left: (maxW - imgEl.width * ratio) / 2,
                top: (maxH - imgEl.height * ratio) / 2,
                selectable: true,
                hasControls: true,
                cornerStyle: 'circle',
                transparentCorners: false
              })
              img.scale(ratio)
              this.canvas.add(img)
              this.canvas.setActiveObject(img)
              this.canvas.requestRenderAll()
              this.onContentChanged && this.onContentChanged()
            },
            { crossOrigin: 'anonymous' }
          )
        }
        imgEl.src = dataURL
      }
      reader.readAsDataURL(file)
    }
  }
}
