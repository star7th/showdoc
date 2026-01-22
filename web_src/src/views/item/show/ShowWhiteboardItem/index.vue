<template>
  <div
    id="whiteboard-item"
    :class="{ fullscreen: isFullscreen }"
    @keydown.ctrl.83.prevent="handleSave"
    @keydown.meta.83.prevent="handleSave"
  >
    <!-- 头部 -->
    <div v-if="!isFullscreen" class="item-header">
      <ItemHeader :itemInfo="itemInfo">
        <template #right>
          <WhiteboardHeaderRight
            :itemInfo="itemInfo"
            :pageId="pageId"
            @save="handleSave"
            @exportImage="exportImage"
            @clearCanvas="clearCanvas"
          />
        </template>
      </ItemHeader>
    </div>

    <!-- 工具栏 -->
    <Toolbar
      :isReadOnly="isReadOnly"
      :isVisitor="isVisitor"
      :brushColor="brushColor"
      :brushWidth="brushWidth"
      :isDrawing="isDrawing"
      :isErasing="isErasing"
      :isInsertingText="isInsertingText"
      :pendingShapeType="pendingShapeType"
      :textFontSize="textFontSize"
      :textColor="textColor"
      :fontSizeOptions="fontSizeOptions"
      :isFullscreen="isFullscreen"
      @update:brushColor="brushColor = $event"
      @update:brushWidth="brushWidth = $event"
      @update:textFontSize="textFontSize = $event"
      @update:textColor="textColor = $event"
      @toggle-drawing="toggleDrawing"
      @toggle-eraser="toggleEraser"
      @toggle-text-insert="toggleTextInsert"
      @insert-rect="insertRect"
      @insert-circle="insertCircle"
      @insert-triangle="insertTriangle"
      @insert-arrow="insertArrow"
      @undo="undo"
      @redo="redo"
      @clear-canvas="clearCanvas"
      @export-image="exportImage"
      @export-svg="exportSVG"
      @export-json="exportJSON"
      @import-image="importImage"
      @import-json="importJSON"
      @zoom-out="zoomOut"
      @zoom-in="zoomIn"
      @fit-to-viewport="fitToViewport"
      @zoom-reset="zoomReset"
      @toggle-fullscreen="toggleFullscreen"
      @apply-brush="applyBrush"
      @apply-text-font-size="applyTextFontSize"
      @apply-text-color="applyTextColor"
    />

    <!-- 画布区域 -->
    <div class="canvas-wrap">
      <div class="canvas-inner">
        <canvas id="whiteboard-canvas"></canvas>
        <a-tooltip
          v-if="!isReadOnly"
          :title="$t('item.whiteboard_drag_resize') || '拖动调整尺寸'"
          placement="top"
        >
          <div
            class="resize-handle"
            @mousedown.prevent="onResizeHandleDown"
          ></div>
        </a-tooltip>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { useAppStore } from '@/store/app'
import ItemHeader from '../../components/ItemHeader.vue'
import WhiteboardHeaderRight from './HeaderRight.vue'
import Toolbar from './Toolbar.vue'
import { useFabricLoader } from './utils/fabricLoader'
import { useCanvasInit } from './utils/canvasInit'
import { useCanvasHistory } from './utils/canvasHistory'
import { useCanvasTools } from './utils/canvasTools'
import { useCanvasShapes } from './utils/canvasShapes'
import { useCanvasPanZoom } from './utils/canvasPanZoom'
import { useCanvasSize } from './utils/canvasSize'
import { useCanvasImportExport } from './utils/canvasImportExport'

interface Props {
  itemInfo: any
}

const props = defineProps<Props>()
const router = useRouter()
const { t } = useI18n()
const appStore = useAppStore()

// Fabric.js 加载
const { isLoaded: fabricLoaded, loadFabric } = useFabricLoader()

// 画布
const { canvas, initCanvas, setBackgroundColor } = useCanvasInit(
  computed(() => props.itemInfo),
  ref('')
)

// 页面信息
const pageId = ref('')

// 只读状态
const isReadOnly = computed(() => !(props.itemInfo && props.itemInfo.item_edit == 1))
const isVisitor = computed(() => !(props.itemInfo && props.itemInfo.is_login == 1))

// 全屏状态
const isFullscreen = ref(false)

// 画笔状态 - 根据主题设置默认颜色
const getDefaultBrushColor = () => appStore.theme === 'dark' ? '#e0e0e0' : '#2c3e50'
const getDefaultTextColor = () => appStore.theme === 'dark' ? '#e0e0e0' : '#2c3e50'

const isDrawing = ref(true)
const isErasing = ref(false)
const brushColor = ref(getDefaultBrushColor())
const brushWidth = ref(4)

// 文本插入状态
const isInsertingText = ref(false)
const textFontSize = ref(24)
const textColor = ref(getDefaultTextColor())
const fontSizeOptions = [
  12, 14, 16, 18, 20, 24, 28, 32, 36, 48, 64, 80, 96, 120
]

// 监听主题变化，切换画布背景色和默认颜色
watch(() => appStore.theme, (newTheme) => {
  if (!canvas.value) return

  // 切换画布背景色
  const bgColor = newTheme === 'dark' ? '#2d2d2d' : '#ffffff'
  setBackgroundColor(bgColor)

  // 更新画笔和文字颜色（如果当前使用的是旧主题的默认颜色）
  const lightDefaultColor = '#2c3e50'
  const darkDefaultColor = '#e0e0e0'
  const newBrushColor = newTheme === 'dark' ? darkDefaultColor : lightDefaultColor
  const newTextColor = newTheme === 'dark' ? darkDefaultColor : lightDefaultColor
  const oldBrushColor = newTheme === 'dark' ? lightDefaultColor : darkDefaultColor
  const oldTextColor = newTheme === 'dark' ? lightDefaultColor : darkDefaultColor

  // 如果当前颜色是旧主题的默认值，则更新为新主题的默认值
  if (brushColor.value === oldBrushColor) {
    brushColor.value = newBrushColor
  }
  if (textColor.value === oldTextColor) {
    textColor.value = newTextColor
  }

  // 重新应用画笔设置（包括更新橡皮擦颜色）
  applyBrush(
    isReadOnly.value,
    isDrawing.value,
    isErasing.value,
    isInsertingText.value,
    brushColor.value,
    brushWidth.value
  )

  // 请求重新渲染画布，确保背景色立即生效
  canvas.value.requestRenderAll()
})

// 形状插入状态
const pendingShapeType = ref<string | null>(null)

// 画布尺寸管理
const {
  customCanvasSize,
  loadSavedSize,
  applyCustomSize,
  onResizeHandleDown,
  fitToViewport
} = useCanvasSize(
  computed(() => props.itemInfo),
  pageId,
  canvas
)

// 画布工具
const {
  applyBrush,
  deleteSelectedObjects,
  applyReadOnlyMode
} = useCanvasTools(canvas)

// 形状工具
const { insertShapeAtPosition } = useCanvasShapes()

// 平移缩放
const { bindPanAndWheel, zoomIn, zoomOut, zoomReset } = useCanvasPanZoom(
  canvas,
  isDrawing,
  isErasing
)

// 导入导出
const {
  exportImage,
  exportSVG,
  exportJSON,
  importJSON,
  importImage,
  clearCanvas
} = useCanvasImportExport(canvas, computed(() => props.itemInfo), () => '')

// 历史记录和保存
const { undo, redo, onContentChanged, save, loadPageContent } = useCanvasHistory(
  canvas,
  pageId,
  computed(() => props.itemInfo),
  {
    applyCustomSize: (width: number, height: number) => {
      customCanvasSize.value = { width, height }
      applyCustomSize(width, height)
    },
    applyReadOnlyMode: () => {
      applyReadOnlyMode()
    },
    onContentLoaded: () => {
      // 加载完内容后重新应用画笔设置，恢复可编辑状态
      applyBrush(
        isReadOnly.value,
        isDrawing.value,
        isErasing.value,
        isInsertingText.value,
        brushColor.value,
        brushWidth.value
      )
      // 重新应用当前主题的背景色
      const bgColor = appStore.theme === 'dark' ? '#2d2d2d' : '#ffffff'
      setBackgroundColor(bgColor)
    },
    onContentLoadedFromJSON: () => {
      // 从 JSON 加载内容后（撤销/重做/加载页面），重新应用主题背景色
      const bgColor = appStore.theme === 'dark' ? '#2d2d2d' : '#ffffff'
      setBackgroundColor(bgColor)
    }
  }
)

// 切换绘制模式
const toggleDrawing = () => {
  if (isReadOnly.value) return
  isDrawing.value = !isDrawing.value
  isErasing.value = false
  isInsertingText.value = false
  pendingShapeType.value = null
  applyBrush()
}

// 切换橡皮擦模式
const toggleEraser = () => {
  if (isReadOnly.value) return
  isErasing.value = !isErasing.value
  if (isErasing.value) {
    isDrawing.value = true
  }
  isInsertingText.value = false
  pendingShapeType.value = null
  applyBrush()
}

// 切换文本插入模式
const toggleTextInsert = () => {
  if (isReadOnly.value) return
  isInsertingText.value = !isInsertingText.value
  if (isInsertingText.value) {
    isDrawing.value = false
    isErasing.value = false
    pendingShapeType.value = null
  } else {
    isDrawing.value = true
    isErasing.value = false
  }
  applyBrush()
}

// 切换全屏
const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
  if (isFullscreen.value) {
    document.documentElement.requestFullscreen()
  } else {
    document.exitFullscreen()
  }
}

// 保存快捷键
const handleSave = async () => {
  if (isReadOnly.value) {
    message.warning(t('item.whiteboard_no_edit_permission') || '没有编辑权限')
    return
  }
  await save()
}

// 选择变化时更新文本属性
const onSelectionChanged = () => {
  if (!canvas.value) return
  const active = canvas.value.getActiveObject?.()
  if (active && (active.type === 'textbox' || active.type === 'text')) {
    const size = Number(active.fontSize) || 24
    textFontSize.value = Math.max(1, Math.min(size, 300))
    if (active.fill) textColor.value = active.fill
  }
}

// 初始化
onMounted(async () => {
  // 确保主题已初始化
  appStore.initTheme()

  // 默认选中第一个页面
  pageId.value =
    (props.itemInfo?.menu?.pages?.[0]?.page_id) || ''

  // 动态加载 Fabric.js 并初始化
  await nextTick()
  await loadFabric()

  if (fabricLoaded.value) {
    // 等待布局完成后再初始化，避免 0 宽高
    setTimeout(async () => {
      initCanvas(isReadOnly.value, onContentChanged)
      applyBrush(
        isReadOnly.value,
        isDrawing.value,
        isErasing.value,
        isInsertingText.value,
        brushColor.value,
        brushWidth.value
      )

      // 应用持久化尺寸（如果有）
      const saved = loadSavedSize()
      if (saved) {
        customCanvasSize.value = saved
        applyCustomSize(saved.width, saved.height)
      }

      // 绑定平移与滚轮缩放
      bindPanAndWheel()

      // 绑定选择事件
      if (canvas.value) {
        canvas.value.on('selection:created', onSelectionChanged)
        canvas.value.on('selection:updated', onSelectionChanged)
        canvas.value.on('mouse:down', handleCanvasMouseDown)
      }

      // 绑定键盘事件
      window.addEventListener('keydown', handleKeyDown)
      window.addEventListener('keyup', handleKeyUp)

      // 加载页面内容
      await loadPageContent()
    }, 0)
  }
})

// 画布鼠标按下事件
const handleCanvasMouseDown = (opt: any) => {
  if (isReadOnly.value || !canvas.value) return
  const evt = opt?.e
  const pointer = canvas.value.getPointer(evt)

  if (pendingShapeType.value) {
    insertShapeAtPosition(
      canvas.value,
      pendingShapeType.value,
      pointer.x,
      pointer.y,
      brushColor.value,
      brushWidth.value,
      onContentChanged
    )
    return
  }

  if (!isInsertingText.value) return
  
  const fabric = (window as any).fabric
  const textbox = new fabric.Textbox('', {
    left: pointer.x,
    top: pointer.y,
    width: 300,
    fontSize: textFontSize.value,
    fill: textColor.value,
    editable: true,
    splitByGrapheme: true,
    cornerStyle: 'circle',
    transparentCorners: false
  })
  
  canvas.value.add(textbox)
  canvas.value.setActiveObject(textbox)
  canvas.value.requestRenderAll()
  
  try {
    textbox.enterEditing()
    textbox.selectionStart = 0
    textbox.selectionEnd = 0
  } catch (e) {}
  
  textbox.on('editing:exited', () => {
    isInsertingText.value = false
    isDrawing.value = false
    isErasing.value = false
    applyBrush(
      isReadOnly.value,
      isDrawing.value,
      isErasing.value,
      isInsertingText.value,
      brushColor.value,
      brushWidth.value
    )
    try {
      if (canvas.value) {
        canvas.value.setCursor('default')
        canvas.value.requestRenderAll()
      }
    } catch (e) {}
    onContentChanged()
  })
  
  onContentChanged()
}

// 键盘按下
const handleKeyDown = (e: KeyboardEvent) => {
  if (e.code === 'Delete' || e.code === 'Backspace') {
    if (isInsertingText.value) return
    deleteSelectedObjects()
  }
}

// 键盘抬起
const handleKeyUp = (e: KeyboardEvent) => {
  // 在 useCanvasPanZoom 中处理空格键
}

// 文本样式应用
const applyTextFontSize = () => {
  if (!canvas.value) return
  const active = canvas.value.getActiveObject?.()
  if (active && (active.type === 'textbox' || active.type === 'text')) {
    try {
      active.set('fontSize', textFontSize.value)
      canvas.value.requestRenderAll()
      onContentChanged()
    } catch (e) {}
  }
}

const applyTextColor = () => {
  if (!canvas.value) return
  const active = canvas.value.getActiveObject?.()
  if (active && (active.type === 'textbox' || active.type === 'text')) {
    try {
      active.set('fill', textColor.value)
      canvas.value.requestRenderAll()
      onContentChanged()
    } catch (e) {}
  }
}

// 插入形状
const insertRect = () => {
  pendingShapeType.value = 'rect'
  isDrawing.value = false
  isErasing.value = false
  isInsertingText.value = false
  applyBrush(
    isReadOnly.value,
    isDrawing.value,
    isErasing.value,
    isInsertingText.value,
    brushColor.value,
    brushWidth.value
  )
  try {
    if (canvas.value) {
      canvas.value.setCursor('crosshair')
    }
  } catch (e) {}
}

const insertCircle = () => {
  pendingShapeType.value = 'circle'
  isDrawing.value = false
  isErasing.value = false
  isInsertingText.value = false
  applyBrush(
    isReadOnly.value,
    isDrawing.value,
    isErasing.value,
    isInsertingText.value,
    brushColor.value,
    brushWidth.value
  )
  try {
    if (canvas.value) {
      canvas.value.setCursor('crosshair')
    }
  } catch (e) {}
}

const insertTriangle = () => {
  pendingShapeType.value = 'triangle'
  isDrawing.value = false
  isErasing.value = false
  isInsertingText.value = false
  applyBrush(
    isReadOnly.value,
    isDrawing.value,
    isErasing.value,
    isInsertingText.value,
    brushColor.value,
    brushWidth.value
  )
  try {
    if (canvas.value) {
      canvas.value.setCursor('crosshair')
    }
  } catch (e) {}
}

const insertArrow = () => {
  pendingShapeType.value = 'arrow'
  isDrawing.value = false
  isErasing.value = false
  isInsertingText.value = false
  applyBrush(
    isReadOnly.value,
    isDrawing.value,
    isErasing.value,
    isInsertingText.value,
    brushColor.value,
    brushWidth.value
  )
  try {
    if (canvas.value) {
      canvas.value.setCursor('crosshair')
    }
  } catch (e) {}
}

// 清理
onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeyDown)
  window.removeEventListener('keyup', handleKeyUp)
  
  if (canvas.value) {
    try {
      canvas.value.off('mouse:down', handleCanvasMouseDown)
      canvas.value.off('selection:created', onSelectionChanged)
      canvas.value.off('selection:updated', onSelectionChanged)
      canvas.value.dispose()
    } catch (e) {}
  }
})
</script>

<style scoped lang="scss">
#whiteboard-item {
  display: flex;
  flex-direction: column;
  min-height: calc(100vh - 80px);

  // 非全屏模式下，Header是fixed定位的，不需要额外margin
  &.fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 9999;
    background-color: var(--color-bg-primary);
    margin-top: 0;
    min-height: 100vh;

    .whiteboard-toolbar {
      position: sticky;
      top: 0;
      z-index: 4;
    }

    .canvas-wrap {
      height: calc(100vh - 60px);
      overflow: hidden;
      flex: 1;
    }

    .canvas-inner {
      width: 100%;
      height: 100%;
    }
  }
}

// 工具栏样式
.whiteboard-toolbar {
  position: sticky;
  top: 90px;
  z-index: 3;
  flex-shrink: 0;
}

.canvas-wrap {
  position: relative;
  width: 100%;
  min-width: 0;
  min-height: 480px;
  overflow: auto;
  flex: 1;

  // 美化滚动条
  &::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }
  &::-webkit-scrollbar-thumb {
    background: var(--scrollbar-thumb);
    border-radius: 4px;
  }
  &::-webkit-scrollbar-track {
    background: var(--color-bg-secondary);
  }
}

.canvas-inner {
  border: 1px solid var(--color-border);
  border-radius: 8px;
  overflow: hidden;
  background: var(--color-bg-primary);
  display: block;
  min-width: 100%;
  position: relative;
  min-height: 480px;
}

#whiteboard-canvas {
  display: block;
  width: 100%;
  height: 100%;
}

.resize-handle {
  position: absolute;
  right: 2px;
  bottom: 2px;
  width: 20px;
  height: 20px;
  cursor: nwse-resize;
  background: linear-gradient(135deg, transparent 50%, var(--color-active) 50%);
  border-radius: 4px;
  opacity: 0.6;
  transition: opacity 0.15s ease;

  &:hover {
    opacity: 1;
    box-shadow: var(--shadow-xs);
  }
}
</style>
