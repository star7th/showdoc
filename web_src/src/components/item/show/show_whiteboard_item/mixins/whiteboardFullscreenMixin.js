export default {
  data() {
    return {
      isFullscreen: false
    }
  },
  methods: {
    toggleFullscreen() {
      if (this.isFullscreen) {
        this.exitFullscreen()
      } else {
        this.enterFullscreen()
      }
    },
    enterFullscreen() {
      const whiteboardContainer = document.getElementById('whiteboard-item')
      if (!whiteboardContainer) return
      whiteboardContainer.classList.add('fullscreen')
      this.isFullscreen = true
      this.$nextTick(() => {
        this.adjustCanvasForFullscreen()
      })
      document.addEventListener('keydown', this.onFullscreenKeydown)
    },
    exitFullscreen() {
      const whiteboardContainer = document.getElementById('whiteboard-item')
      if (!whiteboardContainer) return
      whiteboardContainer.classList.remove('fullscreen')
      this.isFullscreen = false
      this.$nextTick(() => {
        this.adjustCanvasForFullscreen()
      })
      document.removeEventListener('keydown', this.onFullscreenKeydown)
    },
    adjustCanvasForFullscreen() {
      if (!this.canvas) return
      const whiteboardContainer = document.getElementById('whiteboard-item')
      const canvasWrap =
        whiteboardContainer && whiteboardContainer.querySelector('.canvas-wrap')
      if (!canvasWrap) return
      if (this.isFullscreen) {
        const width = window.innerWidth
        const height = window.innerHeight - 60
        this.canvas.setWidth(width)
        this.canvas.setHeight(height)
        this.canvas.renderAll()
      } else {
        if (this.customCanvasSize) {
          this.applyCustomSize(
            this.customCanvasSize.width,
            this.customCanvasSize.height
          )
        } else {
          this.fitToViewport()
        }
      }
    },
    onFullscreenKeydown(e) {
      if (e.key === 'Escape' && this.isFullscreen) {
        this.exitFullscreen()
      }
    }
  },
  beforeDestroy() {
    document.removeEventListener('keydown', this.onFullscreenKeydown)
    if (this.isFullscreen) {
      try {
        this.exitFullscreen()
      } catch (e) {}
    }
  }
}
