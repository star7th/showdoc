<template>
  <div
    class="hello"
    @keydown.ctrl.83.prevent="save"
    @keydown.meta.83.prevent="save"
  >
    <Header v-if="!isFullscreen" :item_info="item_info">
      <HeaderRight
        :item_info="item_info"
        :save="save"
        :exportImage="exportImage"
        :clearCanvas="clearCanvas"
      />
    </Header>

    <div id="whiteboard-item">
      <Toolbar
        :isReadOnly="isReadOnly"
        :isVisitor="isVisitor"
        :brushColor.sync="brushColor"
        :brushWidth.sync="brushWidth"
        :isDrawing="isDrawing"
        :isErasing="isErasing"
        :isInsertingText="isInsertingText"
        :pendingShapeType="pendingShapeType"
        :textFontSize.sync="textFontSize"
        :textColor.sync="textColor"
        :fontSizeOptions="fontSizeOptions"
        :isFullscreen="isFullscreen"
        @undo="undo"
        @redo="redo"
        @toggle-drawing="toggleDrawing"
        @toggle-eraser="toggleEraser"
        @clear-canvas="clearCanvas"
        @apply-brush="applyBrush"
        @apply-text-font-size="applyTextFontSize"
        @apply-text-color="applyTextColor"
        @toggle-text-insert="toggleTextInsert"
        @toggle-fullscreen="toggleFullscreen"
        @zoom-out="zoomOut"
        @zoom-in="zoomIn"
        @fit-to-viewport="fitToViewport"
        @zoom-reset="zoomReset"
        @export-image="exportImage"
        @export-svg="exportSVG"
        @export-json="exportJSON"
        @import-image="onImportImage"
        @import-json="onImportJSON"
        @insert-rect="insertRect"
        @insert-circle="insertCircle"
        @insert-triangle="insertTriangle"
        @insert-arrow="insertArrow"
      />
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

/* 全屏模式样式 */
#whiteboard-item.fullscreen {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  z-index: 9999;
  background-color: #fff;
  margin-top: 0;
}

#whiteboard-item.fullscreen .toolbar {
  position: sticky;
  top: 0;
  z-index: 4;
}

#whiteboard-item.fullscreen .canvas-wrap {
  height: calc(100vh - 60px);
  overflow: hidden;
}

#whiteboard-item.fullscreen .canvas-inner {
  width: 100%;
  height: 100%;
}
</style>

<script>
import Header from '../Header'
import HeaderRight from './HeaderRight'
import Toolbar from './Toolbar.vue'
import whiteboardFullscreenMixin from './mixins/whiteboardFullscreenMixin'
import whiteboardHistoryMixin from './mixins/whiteboardHistoryMixin'
import whiteboardCanvasInitMixin from './mixins/whiteboardCanvasInitMixin'
import whiteboardSizeMixin from './mixins/whiteboardSizeMixin'
import whiteboardPanZoomMixin from './mixins/whiteboardPanZoomMixin'
import whiteboardToolsMixin from './mixins/whiteboardToolsMixin'
import whiteboardShapesMixin from './mixins/whiteboardShapesMixin'
import whiteboardImportExportMixin from './mixins/whiteboardImportExportMixin'

// script loader moved into mixin

export default {
  props: { item_info: '' },
  components: { Header, HeaderRight, Toolbar },
  mixins: [
    whiteboardFullscreenMixin,
    whiteboardHistoryMixin,
    whiteboardCanvasInitMixin,
    whiteboardSizeMixin,
    whiteboardPanZoomMixin,
    whiteboardToolsMixin,
    whiteboardShapesMixin,
    whiteboardImportExportMixin
  ],
  data() {
    return {
      page_id: '',
      canvas: null,
      isDrawing: true,
      isErasing: false,
      brushColor: '#2c3e50',
      brushWidth: 4,
      resizeObserver: null,
      // 文本插入模式
      isInsertingText: false,
      // 文本样式（默认用于新建；选中对象时用于修改）
      textFontSize: 24,
      textColor: '#2c3e50',
      fontSizeOptions: [
        12,
        14,
        16,
        18,
        20,
        24,
        28,
        32,
        36,
        48,
        64,
        80,
        96,
        120
      ],
      // 尺寸、平移缩放等迁移到 mixin
      // 形状插入模式
      pendingShapeType: null // 'rect', 'circle', 'triangle', 'arrow'
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
    onSelectionChanged() {
      if (!this.canvas) return
      const active =
        this.canvas.getActiveObject && this.canvas.getActiveObject()
      if (active && (active.type === 'textbox' || active.type === 'text')) {
        const size = Number(active.fontSize) || 24
        this.textFontSize = Math.max(1, Math.min(size, 300))
        if (active.fill) this.textColor = active.fill
      }
    },
    onSelectionCleared() {
      // 保留当前字号与颜色，作为后续新建文本的默认值
    },
    applyTextFontSize() {
      if (!this.canvas) return
      const active =
        this.canvas.getActiveObject && this.canvas.getActiveObject()
      if (active && (active.type === 'textbox' || active.type === 'text')) {
        try {
          active.set('fontSize', this.textFontSize)
          this.canvas.requestRenderAll()
          this.onContentChanged()
        } catch (e) {}
      }
    },
    applyTextColor() {
      if (!this.canvas) return
      const active =
        this.canvas.getActiveObject && this.canvas.getActiveObject()
      if (active && (active.type === 'textbox' || active.type === 'text')) {
        try {
          active.set('fill', this.textColor)
          this.canvas.requestRenderAll()
          this.onContentChanged()
        } catch (e) {}
      }
    }
    // 形状相关逻辑已迁移到 mixin
    // deps/static path/viewport height moved to mixin
    // canvas init and resize moved to mixin
    // ====== 尺寸持久化 ======
    // size persistence and drag resize moved to mixin
    // pan and wheel & key handlers moved to mixin
    // moved to tools mixin
    // 导入导出逻辑已迁移到 mixin
    // 只读逻辑已迁移到 mixin
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
    try {
      this.canvas &&
        this.canvas.off &&
        this.canvas.off('mouse:down', this.onCanvasMouseDown)
      this.canvas &&
        this.canvas.off &&
        this.canvas.off('mouse:move', this.onCanvasMouseMove)
    } catch (e) {}
    this.canvas && this.canvas.dispose && this.canvas.dispose()
    // 清理事件
    document.removeEventListener('mousemove', this.onResizing)
    window.removeEventListener('keydown', this.onKeyDown)
    window.removeEventListener('keyup', this.onKeyUp)
  }
}
</script>
