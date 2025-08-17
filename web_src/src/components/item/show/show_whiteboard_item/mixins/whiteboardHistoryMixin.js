import { unescapeHTML } from '@/models/page'

export default {
  data() {
    return {
      undoStack: [],
      redoStack: [],
      autoSaveTimer: null,
      autoSaveDebounceMs: 2000,
      autoSaveMinIntervalMs: 10000,
      autoSaveLastAt: 0
    }
  },
  methods: {
    getPageContent() {
      this.request('/api/page/info', { page_id: this.page_id }).then(data => {
        if (!this.canvas) return
        let content = data && data.data && data.data.page_content
        if (content) {
          try {
            const json = JSON.parse(unescapeHTML(content))
            if (json && json.__canvasSize) {
              const { width, height } = json.__canvasSize
              if (width && height) {
                this.customCanvasSize = { width, height }
                this.applyCustomSize(width, height)
              }
            }
            this.canvas.loadFromJSON(json, () => {
              this.canvas.renderAll()
              if (this.isReadOnly) this.applyReadOnlyMode()
              this.pushUndoState()
            })
          } catch (e) {
            this.pushUndoState()
          }
        } else {
          this.pushUndoState()
        }
      })
    },
    serialize() {
      if (!this.canvas) return '{}'
      const json = this.canvas.toJSON()
      const payload = {
        __canvasSize: {
          width: this.canvas ? this.canvas.getWidth() : 0,
          height: this.canvas ? this.canvas.getHeight() : 0
        },
        ...json
      }
      return JSON.stringify(payload)
    },
    save(silent = false) {
      if (this.isReadOnly) {
        this.$message.warning(this.$t('no_edit_permission') || '没有编辑权限')
        return
      }
      if (!this.page_id || !this.canvas) return
      this.request('/api/page/save', {
        page_id: this.page_id,
        page_title: this.item_info.item_name,
        item_id: this.item_info.item_id,
        is_urlencode: 1,
        page_content: encodeURIComponent(this.serialize())
      }).then(() => {
        this.autoSaveLastAt = Date.now()
        if (!silent) {
          this.$message.success(this.$t('save_success') || '已保存')
        }
      })
    },
    scheduleAutoSave() {
      if (!this.item_info || !this.item_info.item_edit) return
      if (this.autoSaveTimer) {
        clearTimeout(this.autoSaveTimer)
        this.autoSaveTimer = null
      }
      const now = Date.now()
      const elapsed = now - (this.autoSaveLastAt || 0)
      const throttleRemain = Math.max(this.autoSaveMinIntervalMs - elapsed, 0)
      const delay = Math.max(this.autoSaveDebounceMs, throttleRemain)
      this.autoSaveTimer = setTimeout(() => {
        this.save(true)
      }, delay)
    },
    onContentChanged() {
      if (this.isReadOnly) return
      this.pushUndoState()
      this.redoStack = []
      this.scheduleAutoSave()
    },
    pushUndoState() {
      if (!this.canvas) return
      const snapshot = this.serialize()
      const max = 50
      this.undoStack.push(snapshot)
      if (this.undoStack.length > max) this.undoStack.shift()
    },
    undo() {
      if (this.undoStack.length <= 1) return
      const current = this.undoStack.pop()
      this.redoStack.push(current)
      const prev = this.undoStack[this.undoStack.length - 1]
      this.loadFromJSONString(prev)
    },
    redo() {
      if (this.redoStack.length === 0) return
      const next = this.redoStack.pop()
      this.undoStack.push(next)
      this.loadFromJSONString(next)
    },
    loadFromJSONString(str) {
      try {
        const json = JSON.parse(str)
        if (json && json.__canvasSize) {
          const { width, height } = json.__canvasSize
          this.customCanvasSize = { width, height }
          this.applyCustomSize(width, height)
        }
        this.canvas.loadFromJSON(json, () => {
          this.canvas.renderAll()
          if (this.isReadOnly) this.applyReadOnlyMode()
        })
      } catch (e) {}
    }
  }
}
