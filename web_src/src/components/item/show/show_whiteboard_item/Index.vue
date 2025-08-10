<template>
  <div
    class="hello"
    @keydown.ctrl.83.prevent="save"
    @keydown.meta.83.prevent="save"
  >
    <Header :item_info="item_info">
      <HeaderRight
        :item_info="item_info"
        :save="save"
        :exportImage="exportImage"
        :clearCanvas="clearCanvas"
      />
    </Header>

    <div id="whiteboard-item">
      <div class="toolbar">
        <div class="tool-left">
          <el-tooltip
            effect="dark"
            :content="$t('color') || '颜色'"
            placement="top"
          >
            <el-color-picker
              size="mini"
              v-model="brushColor"
              :disabled="isReadOnly"
              @change="applyBrush"
            />
          </el-tooltip>
          <div class="width-wrap">
            <span class="label">{{ $t('width') || '粗细' }}</span>
            <el-slider
              class="width-slider"
              :min="1"
              :max="50"
              :step="1"
              v-model="brushWidth"
              @input="applyBrush"
              :show-input="true"
              input-size="mini"
              :disabled="isReadOnly"
            />
          </div>
        </div>
        <div class="tool-right">
          <!-- 撤销 / 重做 -->
          <el-tooltip
            effect="dark"
            :content="$t('undo') || '撤销'"
            placement="top"
          >
            <el-button size="mini" plain @click="undo" :disabled="isReadOnly">
              <i class="far fa-rotate-left" />
              <span class="btn-text">{{ $t('undo') || '撤销' }}</span>
            </el-button>
          </el-tooltip>
          <el-tooltip
            effect="dark"
            :content="$t('redo') || '重做'"
            placement="top"
          >
            <el-button size="mini" plain @click="redo" :disabled="isReadOnly">
              <i class="far fa-rotate-right" />
              <span class="btn-text">{{ $t('redo') || '重做' }}</span>
            </el-button>
          </el-tooltip>

          <span class="divider"></span>

          <!-- 绘制/选择 切换 与 橡皮擦 -->
          <el-tooltip
            effect="dark"
            :content="
              isDrawing
                ? $t('drawing_on') || '绘制中'
                : $t('drawing_off') || '停止绘制'
            "
            placement="top"
          >
            <el-button
              size="mini"
              :type="isDrawing ? 'primary' : 'default'"
              plain
              @click="toggleDrawing"
              :disabled="isReadOnly"
            >
              <i :class="isDrawing ? 'far fa-pen' : 'far fa-hand'" />
              <span class="btn-text">
                {{ isDrawing ? $t('draw') || '绘制' : $t('select') || '选择' }}
              </span>
            </el-button>
          </el-tooltip>
          <el-tooltip
            effect="dark"
            :content="$t('eraser') || '橡皮擦'"
            placement="top"
          >
            <el-button
              size="mini"
              :type="isErasing ? 'warning' : 'default'"
              plain
              @click="toggleEraser"
              :disabled="isReadOnly"
            >
              <i class="far fa-eraser" />
              <span class="btn-text">{{ $t('eraser') || '橡皮擦' }}</span>
            </el-button>
          </el-tooltip>

          <el-tooltip
            effect="dark"
            :content="$t('clear') || '清空画布'"
            placement="top"
          >
            <el-button
              size="mini"
              type="danger"
              plain
              @click="clearCanvas"
              :disabled="isReadOnly"
            >
              <i class="far fa-trash-can" />
              <span class="btn-text">{{ $t('clear') || '清空画布' }}</span>
            </el-button>
          </el-tooltip>

          <span class="divider"></span>

          <!-- 缩放 -->
          <el-tooltip
            effect="dark"
            :content="$t('zoom_out') || '缩小'"
            placement="top"
          >
            <el-button size="mini" plain @click="zoomOut">
              <i class="far fa-magnifying-glass-minus" />
              <span class="btn-text">{{ $t('zoom_out') || '缩小' }}</span>
            </el-button>
          </el-tooltip>
          <el-tooltip
            effect="dark"
            :content="$t('zoom_in') || '放大'"
            placement="top"
          >
            <el-button size="mini" plain @click="zoomIn">
              <i class="far fa-magnifying-glass-plus" />
              <span class="btn-text">{{ $t('zoom_in') || '放大' }}</span>
            </el-button>
          </el-tooltip>

          <!-- 适屏 / 1:1 -->
          <el-tooltip
            effect="dark"
            :content="$t('fit') || '适屏'"
            placement="top"
          >
            <el-button size="mini" plain @click="fitToViewport">
              <i class="far fa-maximize" />
              <span class="btn-text">{{ $t('fit') || '适屏' }}</span>
            </el-button>
          </el-tooltip>
          <el-tooltip
            effect="dark"
            :content="$t('actual_size') || '1:1'"
            placement="top"
          >
            <el-button size="mini" plain @click="zoomReset">
              <i class="far fa-minimize" />
              <span class="btn-text">{{ $t('actual_size') || '1:1' }}</span>
            </el-button>
          </el-tooltip>

          <span class="divider"></span>

          <!-- 插入图片 -->
          <el-tooltip
            effect="dark"
            :content="$t('insert_image') || '插入图片'"
            placement="top"
          >
            <el-button
              size="mini"
              plain
              :disabled="!item_info.item_edit"
              @click="triggerImageUpload"
            >
              <i class="far fa-file-image" />
              <span class="btn-text">{{
                $t('insert_image') || '插入图片'
              }}</span>
            </el-button>
          </el-tooltip>
          <input
            ref="imgFile"
            type="file"
            accept="image/*"
            style="display:none"
            @change="onImportImage"
          />

          <span class="divider"></span>

          <!-- 导出/导入 -->
          <el-tooltip
            effect="dark"
            :content="$t('export') || '导出 PNG'"
            placement="top"
          >
            <el-button
              size="mini"
              plain
              @click="exportImage"
              :disabled="isVisitor"
            >
              <i class="far fa-image" />
              <span class="btn-text">{{ $t('export_png') || '导出PNG' }}</span>
            </el-button>
          </el-tooltip>
          <el-tooltip
            effect="dark"
            :content="$t('export_svg') || '导出 SVG'"
            placement="top"
          >
            <el-button
              size="mini"
              plain
              @click="exportSVG"
              :disabled="isVisitor"
            >
              <i class="far fa-file-code" />
              <span class="btn-text">{{ $t('export_svg') || '导出SVG' }}</span>
            </el-button>
          </el-tooltip>
          <el-tooltip
            effect="dark"
            :content="$t('export_json') || '导出 JSON'"
            placement="top"
          >
            <el-button
              size="mini"
              plain
              @click="exportJSON"
              :disabled="isVisitor"
            >
              <i class="far fa-code" />
              <span class="btn-text">{{
                $t('export_json') || '导出JSON'
              }}</span>
            </el-button>
          </el-tooltip>
          <el-tooltip
            effect="dark"
            :content="$t('import') || '导入 JSON'"
            placement="top"
          >
            <el-button
              size="mini"
              plain
              :disabled="!item_info.item_edit"
              @click="triggerImport"
            >
              <i class="far fa-file-arrow-up" />
              <span class="btn-text">{{
                $t('import_json') || '导入JSON'
              }}</span>
            </el-button>
          </el-tooltip>
          <input
            ref="jsonFile"
            type="file"
            accept="application/json"
            style="display:none"
            @change="onImportJSON"
          />
        </div>
      </div>
      <div class="canvas-wrap">
        <div class="canvas-inner">
          <canvas id="whiteboard-canvas"></canvas>
          <el-tooltip
            effect="dark"
            :content="$t('drag_resize') || '拖动调整尺寸'"
            placement="top"
          >
            <div
              v-if="!isReadOnly"
              class="resize-handle"
              @mousedown.prevent="onResizeHandleDown"
            ></div>
          </el-tooltip>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
#whiteboard-item {
  margin-top: 90px;
}
.toolbar {
  position: sticky;
  top: 90px;
  z-index: 3;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 8px 12px;
  background: #fff;
  border-bottom: 1px solid #f0f0f0;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}
.canvas-wrap {
  position: relative;
  width: 100%;
  min-width: 0; /* 避免flex布局下不收缩 */
}
.canvas-inner {
  border: 1px solid #eee;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
  /* 保证大尺寸时可以横向滚动 */
  display: inline-block;
  min-width: 100%;
}
.tool-left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.tool-right {
  display: flex;
  align-items: center;
  gap: 8px;
}
.divider {
  display: inline-block;
  width: 1px;
  height: 18px;
  background: #eee;
  margin: 0 4px;
}
.width-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
}
.width-wrap .label {
  font-size: 12px;
  color: #666;
}
.width-slider {
  width: 200px;
}
.width-slider >>> .el-slider__button {
  width: 10px;
  height: 10px;
}
.width-slider >>> .el-input__inner {
  height: 24px;
  line-height: 24px;
}
.canvas-wrap {
  height: calc(100vh - 150px);
  overflow: auto;
  /* 美化滚动条 */
  &::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }
  &::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 4px;
  }
  &::-webkit-scrollbar-track {
    background: #f5f5f5;
  }
}
.canvas-wrap,
#whiteboard-canvas {
  width: 100%;
}
.canvas-wrap {
  min-height: 480px;
}
#whiteboard-canvas {
  display: block;
  height: 100%;
}
.canvas-inner {
  position: relative; /* 以便定位拖拽柄 */
}
.resize-handle {
  position: absolute;
  right: 2px;
  bottom: 2px;
  width: 20px;
  height: 20px;
  cursor: nwse-resize;
  background: linear-gradient(135deg, transparent 0 50%, #409eff 50% 100%);
  border-radius: 4px;
  opacity: 0.6;
  transition: opacity 0.2s;
}
.resize-handle:hover {
  opacity: 1;
  box-shadow: 0 0 3px rgba(0, 0, 0, 0.2);
}
.ml-1 {
  margin-left: 6px;
}
.ml-3 {
  margin-left: 12px;
}
.btn-text {
  margin-left: 4px;
  display: none;
}
</style>

<script>
import Header from '../Header'
import HeaderRight from './HeaderRight'
import { unescapeHTML } from '@/models/page'

if (typeof window !== 'undefined') {
  var $s = require('scriptjs')
}

export default {
  props: { item_info: '' },
  components: { Header, HeaderRight },
  data() {
    return {
      page_id: '',
      canvas: null,
      isDrawing: true,
      isErasing: false,
      brushColor: '#2c3e50',
      brushWidth: 4,
      undoStack: [],
      redoStack: [],
      resizeObserver: null,
      // 用户自定义画布尺寸
      customCanvasSize: null, // { width, height }
      // 拖拽尺寸
      resizing: false,
      resizeStart: { x: 0, y: 0, width: 0, height: 0 },
      // 平移/滚轮缩放
      spacePressed: false,
      panning: false,
      // autosave
      autoSaveTimer: null,
      autoSaveDebounceMs: 2000,
      autoSaveMinIntervalMs: 10000,
      autoSaveLastAt: 0
    }
  },
  computed: {
    isReadOnly() {
      return !(this.item_info && this.item_info.item_edit)
    },
    isVisitor() {
      return !(this.item_info && this.item_info.is_login)
    }
  },
  methods: {
    loadDeps(cb) {
      // 仅从本地 staticPath 加载（不回退 CDN）
      const url = `${this.getStaticPath()}whiteboard/fabric.min.js`
      $s([url], () => {
        cb && cb()
      })
    },
    getStaticPath() {
      // 优先使用全局配置里的 staticPath，兜底 /static/
      try {
        return window.DocConfig && window.DocConfig.staticPath
          ? window.DocConfig.staticPath
          : '/static/'
      } catch (e) {
        return '/static/'
      }
    },
    getViewportCanvasHeight() {
      const h =
        (window.innerHeight || document.documentElement.clientHeight || 0) - 140
      return Math.max(h, 480)
    },
    getPageContent() {
      this.request('/api/page/info', { page_id: this.page_id }).then(data => {
        if (!this.canvas) return
        let content = data && data.data && data.data.page_content
        if (content) {
          try {
            const json = JSON.parse(unescapeHTML(content))
            // 当内容里包含保存的画布尺寸时，先应用尺寸，确保游客也能看到完整区域
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
            // ignore
            this.pushUndoState()
          }
        } else {
          this.pushUndoState()
        }
      })
    },
    initCanvas() {
      if (!window.fabric) return
      const el = document.getElementById('whiteboard-canvas')
      if (!el) return
      const parent =
        document.querySelector('#whiteboard-item .canvas-inner') ||
        el.parentElement
      let width = Math.max(parent.clientWidth || 0, 600)
      let height = Math.max(parent.clientHeight || 0, 0)
      if (height <= 0) {
        // 父容器尚未计算出高度，使用视口高度兜底，并直接设置容器高度
        height = this.getViewportCanvasHeight()
        try {
          parent.style.height = height + 'px'
        } catch (e) {}
      } else {
        height = Math.max(height, 480)
      }
      this.canvas = new window.fabric.Canvas('whiteboard-canvas', {
        isDrawingMode: !this.isReadOnly,
        width,
        height,
        selection: !this.isReadOnly,
        backgroundColor: '#ffffff'
      })
      this.applyBrush()
      this.canvas.on('path:created', () => {
        this.onContentChanged()
      })
      this.canvas.on('object:modified', () => this.onContentChanged())
      this.canvas.on('object:removed', () => this.onContentChanged())

      // 自适应窗口尺寸
      this.bindResize()
      // 应用只读态
      if (this.isReadOnly) this.applyReadOnlyMode()
    },
    bindResize() {
      const wrap = document.querySelector('#whiteboard-item .canvas-wrap')
      const inner = document.querySelector('#whiteboard-item .canvas-inner')
      if (!wrap) return
      const resize = () => {
        if (!this.canvas) return
        let w = (inner && inner.clientWidth) || wrap.clientWidth
        let h = (inner && inner.clientHeight) || wrap.clientHeight
        if (h <= 0) {
          h = this.getViewportCanvasHeight()
          try {
            if (inner) inner.style.height = h + 'px'
            else wrap.style.height = h + 'px'
          } catch (e) {}
        }
        if (w <= 0) {
          w = Math.max(
            document.documentElement.clientWidth || window.innerWidth || 0,
            600
          )
        }
        if (w > 0 && h > 0) {
          this.canvas.setWidth(w)
          this.canvas.setHeight(h)
          this.canvas.renderAll()
        }
      }
      this.resizeObserver = new (window.ResizeObserver ||
        class {
          constructor(cb) {
            this.cb = cb
          }
          observe() {
            window.addEventListener('resize', this.cb)
          }
          disconnect() {
            window.removeEventListener('resize', this.cb)
          }
        })(resize)
      this.resizeObserver.observe(wrap)
      window.addEventListener('resize', resize)
      setTimeout(resize, 16)
      setTimeout(resize, 200)
      setTimeout(resize, 800)
    },
    // ====== 尺寸持久化 ======
    storageKeyForSize() {
      const itemId = (this.item_info && this.item_info.item_id) || '0'
      const pageId = this.page_id || '0'
      return `whiteboard:size:${itemId}:${pageId}`
    },
    loadSavedSize() {
      try {
        const raw = localStorage.getItem(this.storageKeyForSize())
        if (!raw) return null
        const obj = JSON.parse(raw)
        if (
          obj &&
          typeof obj.width === 'number' &&
          typeof obj.height === 'number'
        ) {
          return obj
        }
      } catch (e) {}
      return null
    },
    saveCustomSize(width, height) {
      try {
        localStorage.setItem(
          this.storageKeyForSize(),
          JSON.stringify({ width, height })
        )
      } catch (e) {}
    },
    clearSavedSize() {
      try {
        localStorage.removeItem(this.storageKeyForSize())
      } catch (e) {}
    },
    applyCustomSize(width, height) {
      const inner = document.querySelector('#whiteboard-item .canvas-inner')
      const wrap = document.querySelector('#whiteboard-item .canvas-wrap')
      if (!inner || !this.canvas) return
      const min = 480
      const max = 10000
      const w = Math.max(min, Math.min(width || 0, max))
      const h = Math.max(min, Math.min(height || 0, max))
      inner.style.width = w + 'px'
      inner.style.height = h + 'px'
      if (wrap) wrap.scrollLeft = wrap.scrollWidth
      this.canvas.setWidth(w)
      this.canvas.setHeight(h)
      this.canvas.renderAll()
    },
    // 适屏（清除记忆，回到容器尺寸）
    fitToViewport() {
      const inner = document.querySelector('#whiteboard-item .canvas-inner')
      const wrap = document.querySelector('#whiteboard-item .canvas-wrap')
      if (!inner || !wrap || !this.canvas) return
      inner.style.width = ''
      inner.style.height = ''
      this.customCanvasSize = null
      this.clearSavedSize()
      const w = inner.clientWidth || wrap.clientWidth
      const h =
        inner.clientHeight ||
        wrap.clientHeight ||
        this.getViewportCanvasHeight()
      this.canvas.setWidth(w)
      this.canvas.setHeight(Math.max(h, 480))
      this.zoomReset()
      this.canvas.renderAll()
    },
    // ====== 右下角拖拽柄 ======
    onResizeHandleDown(e) {
      if (this.isReadOnly) return
      const inner = document.querySelector('#whiteboard-item .canvas-inner')
      if (!inner) return
      this.resizing = true
      this.resizeStart = {
        x: e.clientX,
        y: e.clientY,
        width: inner.clientWidth,
        height: inner.clientHeight
      }
      document.addEventListener('mousemove', this.onResizing)
      document.addEventListener('mouseup', this.onResizeHandleUp, {
        once: true
      })
    },
    onResizing(e) {
      if (!this.resizing) return
      // 增加拖拽灵敏度，变化幅度扩大1.5倍
      const dx = (e.clientX - this.resizeStart.x) * 2.5
      const dy = (e.clientY - this.resizeStart.y) * 2.5
      const newW = Math.round(this.resizeStart.width + dx)
      const newH = Math.round(this.resizeStart.height + dy)
      this.customCanvasSize = { width: newW, height: newH }
      this.applyCustomSize(newW, newH)
    },
    onResizeHandleUp() {
      if (!this.resizing) return
      this.resizing = false
      document.removeEventListener('mousemove', this.onResizing)
      if (this.customCanvasSize) {
        this.saveCustomSize(
          this.customCanvasSize.width,
          this.customCanvasSize.height
        )
      }
    },
    // ====== 平移与滚轮缩放 ======
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
    },
    onKeyUp(e) {
      if (e.code === 'Space') {
        this.spacePressed = false
        if (this.canvas) {
          this.canvas.isDrawingMode = this.isReadOnly ? false : this.isDrawing
        }
      }
    },
    applyBrush() {
      if (!this.canvas) return
      // 只读模式强制关闭绘制
      this.canvas.isDrawingMode = this.isReadOnly ? false : this.isDrawing
      if (!this.canvas.freeDrawingBrush) {
        this.canvas.freeDrawingBrush = new window.fabric.PencilBrush(
          this.canvas
        )
      }
      const color = this.isErasing ? '#ffffff' : this.brushColor
      this.canvas.freeDrawingBrush.color = color
      this.canvas.freeDrawingBrush.width = this.brushWidth
    },
    toggleDrawing() {
      if (this.isReadOnly) return
      this.isDrawing = !this.isDrawing
      this.applyBrush()
    },
    toggleEraser() {
      if (this.isReadOnly) return
      this.isErasing = !this.isErasing
      // 开启橡皮擦时强制进入绘制模式
      if (this.isErasing) this.isDrawing = true
      this.applyBrush()
    },
    clearCanvas() {
      if (this.isReadOnly) return
      if (!this.canvas) return
      this.canvas.clear()
      this.canvas.setBackgroundColor(
        '#ffffff',
        this.canvas.renderAll.bind(this.canvas)
      )
      this.onContentChanged()
    },
    exportImage() {
      if (this.isVisitor) return
      if (!this.canvas) return
      const dataURL = this.canvas.toDataURL({ format: 'png', quality: 1 })
      const a = document.createElement('a')
      a.href = dataURL
      a.download = 'showdoc-whiteboard.png'
      a.click()
    },
    exportSVG() {
      if (this.isVisitor) return
      if (!this.canvas) return
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
      if (this.isVisitor) return
      if (!this.canvas) return
      const json = this.serialize()
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
          this.loadFromJSONString(str)
          this.onContentChanged()
          this.$message.success(this.$t('op_success') || '操作成功')
        } catch (err) {
          this.$message.error(this.$t('upload_failed_error') || '导入失败')
        }
      }
      reader.readAsText(file)
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
              this.onContentChanged()
            },
            { crossOrigin: 'anonymous' }
          )
        }
        imgEl.src = dataURL
      }
      reader.readAsDataURL(file)
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
      // 变更后推进撤销栈，清空重做栈
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
      } catch (e) {
        /* ignore */
      }
    },
    applyReadOnlyMode() {
      if (!this.canvas) return
      this.isDrawing = false
      this.isErasing = false
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
  },
  mounted() {
    // 默认选中第一个页面
    this.page_id =
      (this.item_info.menu &&
        this.item_info.menu.pages &&
        this.item_info.menu.pages[0] &&
        this.item_info.menu.pages[0].page_id) ||
      ''
    // 动态加载 Fabric 并初始化
    this.$nextTick(() => {
      this.loadDeps(() => {
        // 等待布局完成后再初始化，避免 0 宽高
        setTimeout(() => {
          this.initCanvas()
          // 应用持久化尺寸（如果有）
          const saved = this.loadSavedSize()
          if (saved) {
            this.customCanvasSize = saved
            this.applyCustomSize(saved.width, saved.height)
          }
          this.getPageContent()
          // 绑定平移与滚轮缩放
          this.bindPanAndWheel()
        }, 0)
      })
    })
  },
  beforeDestroy() {
    this.$message.closeAll()
    try {
      this.resizeObserver &&
        this.resizeObserver.disconnect &&
        this.resizeObserver.disconnect()
    } catch (e) {}
    this.canvas && this.canvas.dispose && this.canvas.dispose()
    // 清理事件
    document.removeEventListener('mousemove', this.onResizing)
    window.removeEventListener('keydown', this.onKeyDown)
    window.removeEventListener('keyup', this.onKeyUp)
  }
}
</script>
