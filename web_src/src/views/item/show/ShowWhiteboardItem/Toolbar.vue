<template>
  <div class="whiteboard-toolbar">
    <div class="tool-left">
      <!-- 绘制核心工具 -->
      <a-tooltip :title="$t('item.whiteboard_color') || '颜色'">
        <a-color-picker
          :value="brushColor"
          size="small"
          :disabled="isReadOnly"
          @change="onBrushColorChange"
        />
      </a-tooltip>

      <div class="width-wrap">
        <span class="label">{{ $t('item.whiteboard_width') || '粗细' }}</span>
        <a-slider
          class="width-slider"
          :min="1"
          :max="50"
          :step="1"
          :value="brushWidth"
          :disabled="isReadOnly"
          @change="onBrushWidthChange"
        />
        <a-input-number
          :value="brushWidth"
          :min="1"
          :max="50"
          size="small"
          :disabled="isReadOnly"
          style="width: 60px"
          @change="onBrushWidthChange"
        />
      </div>

      <span class="divider"></span>

      <!-- 绘制/选择 与 橡皮擦 -->
      <a-tooltip
        :title="isDrawing ? ($t('item.whiteboard_drawing_on') || '绘制中') : ($t('item.whiteboard_drawing_off') || '停止绘制')"
      >
        <a-button
          size="small"
          :type="isDrawing ? 'primary' : 'default'"
          :disabled="isReadOnly"
          @click="$emit('toggle-drawing')"
        >
          <template #icon>
            <i :class="isDrawing ? 'fa-solid fa-pen' : 'fa-solid fa-hand-pointer'" />
          </template>
          <span class="btn-text">{{
            isDrawing ? ($t('item.whiteboard_draw') || '绘制') : ($t('item.whiteboard_select') || '选择')
          }}</span>
        </a-button>
      </a-tooltip>

      <a-tooltip :title="$t('item.whiteboard_eraser') || '橡皮擦'">
        <a-button
          size="small"
          :type="isErasing ? 'primary' : 'default'"
          :disabled="isReadOnly"
          @click="$emit('toggle-eraser')"
        >
          <template #icon>
            <i class="fa-solid fa-eraser" />
          </template>
          <span class="btn-text">{{ $t('item.whiteboard_eraser') || '橡皮擦' }}</span>
        </a-button>
      </a-tooltip>
    </div>

    <div class="tool-right">
      <!-- 撤销 / 重做 -->
      <a-tooltip :title="$t('common.undo') || '撤销'">
        <a-button size="small" :disabled="isReadOnly" @click="$emit('undo')">
          <template #icon>
            <i class="fa-solid fa-rotate-left" />
          </template>
          <span class="btn-text">{{ $t('common.undo') || '撤销' }}</span>
        </a-button>
      </a-tooltip>

      <a-tooltip :title="$t('common.redo') || '重做'">
        <a-button size="small" :disabled="isReadOnly" @click="$emit('redo')">
          <template #icon>
            <i class="fa-solid fa-rotate-right" />
          </template>
          <span class="btn-text">{{ $t('common.redo') || '重做' }}</span>
        </a-button>
      </a-tooltip>

      <span class="divider"></span>

      <!-- 插入文字 -->
      <a-tooltip :title="$t('item.whiteboard_insert_text') || '插入文字'">
        <a-button
          size="small"
          :type="isInsertingText ? 'primary' : 'default'"
          :disabled="isReadOnly"
          @click="$emit('toggle-text-insert')"
        >
          <template #icon>
            <i class="fa-solid fa-i-cursor" />
          </template>
          <span class="btn-text">{{ $t('item.whiteboard_insert_text') || '插入文字' }}</span>
        </a-button>
      </a-tooltip>

      <!-- 文本样式 -->
      <a-popover placement="bottom" trigger="click" :overlayStyle="{ width: '260px' }">
        <template #content>
          <div class="text-style-pop">
            <div class="row" style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
              <span class="label" style="min-width: 56px;">{{ $t('item.whiteboard_font_size') || '字号' }}</span>
              <a-select
                :value="textFontSize"
                size="small"
                style="width: 100px"
                :disabled="isReadOnly"
                @change="onTextFontSizeChange"
              >
                <a-select-option v-for="s in fontSizeOptions" :key="s" :value="s">
                  {{ s }}
                </a-select-option>
              </a-select>
            </div>
            <div class="row" style="display: flex; align-items: center; gap: 8px;">
              <span class="label" style="min-width: 56px;">{{ $t('item.whiteboard_font_color') || '字体颜色' }}</span>
              <a-color-picker
                :value="textColor"
                size="small"
                :disabled="isReadOnly"
                @change="onTextColorChange"
              />
            </div>
          </div>
        </template>
        <a-button size="small" :disabled="isReadOnly">
          <template #icon>
            <i class="fa-solid fa-font" />
          </template>
          <span class="btn-text">{{ $t('item.whiteboard_text_style') || '文本样式' }}</span>
        </a-button>
      </a-popover>

      <span class="divider"></span>

      <!-- 插入形状 -->
      <a-popover placement="bottom" trigger="click" :overlayStyle="{ width: '480px' }">
        <template #content>
          <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center; padding: 12px;">
            <a-button
              size="small"
              :type="pendingShapeType === 'rect' ? 'primary' : 'default'"
              :disabled="isReadOnly"
              @click="$emit('insert-rect')"
              style="min-width: 80px; height: 40px;"
            >
              <i class="fa-regular fa-square" style="font-size: 16px; margin-right: 6px;" />
              {{ $t('item.whiteboard_shape_rect') || '方形' }}
            </a-button>
            <a-button
              size="small"
              :type="pendingShapeType === 'circle' ? 'primary' : 'default'"
              :disabled="isReadOnly"
              @click="$emit('insert-circle')"
              style="min-width: 80px; height: 40px;"
            >
              <i class="fa-regular fa-circle" style="font-size: 16px; margin-right: 6px;" />
              {{ $t('item.whiteboard_shape_circle') || '圆形' }}
            </a-button>
            <a-button
              size="small"
              :type="pendingShapeType === 'triangle' ? 'primary' : 'default'"
              :disabled="isReadOnly"
              @click="$emit('insert-triangle')"
              style="min-width: 80px; height: 40px;"
            >
              <i class="fa-solid fa-caret-up" style="font-size: 16px; margin-right: 6px;" />
              {{ $t('item.whiteboard_shape_triangle') || '三角形' }}
            </a-button>
            <a-button
              size="small"
              :type="pendingShapeType === 'arrow' ? 'primary' : 'default'"
              :disabled="isReadOnly"
              @click="$emit('insert-arrow')"
              style="min-width: 80px; height: 40px;"
            >
              <i class="fa-solid fa-arrow-right" style="font-size: 16px; margin-right: 6px;" />
              {{ $t('item.whiteboard_shape_arrow') || '箭头' }}
            </a-button>
          </div>
        </template>
        <a-button
          size="small"
          :type="pendingShapeType ? 'primary' : 'default'"
          :disabled="isReadOnly"
        >
          <template #icon>
            <i class="fa-solid fa-draw-polygon" />
          </template>
          <span class="btn-text">{{ $t('item.whiteboard_insert_shape') || '插入形状' }}</span>
        </a-button>
      </a-popover>

      <!-- 插入图片 -->
      <a-tooltip :title="$t('item.whiteboard_insert_image') || '插入图片'">
        <a-button size="small" :disabled="isReadOnly" @click="triggerImageUpload">
          <template #icon>
            <i class="fa-solid fa-image" />
          </template>
          <span class="btn-text">{{ $t('item.whiteboard_insert_image') || '插入图片' }}</span>
        </a-button>
      </a-tooltip>
      <input
        ref="imgFile"
        type="file"
        accept="image/*"
        style="display: none"
        @change="$emit('import-image', $event)"
      />

      <span class="divider"></span>

      <!-- 缩放与适屏 -->
      <a-tooltip :title="$t('item.whiteboard_zoom_out') || '缩小'">
        <a-button size="small" @click="$emit('zoom-out')">
          <template #icon>
            <i class="fa-solid fa-magnifying-glass-minus" />
          </template>
          <span class="btn-text">{{ $t('item.whiteboard_zoom_out') || '缩小' }}</span>
        </a-button>
      </a-tooltip>

      <a-tooltip :title="$t('item.whiteboard_zoom_in') || '放大'">
        <a-button size="small" @click="$emit('zoom-in')">
          <template #icon>
            <i class="fa-solid fa-magnifying-glass-plus" />
          </template>
          <span class="btn-text">{{ $t('item.whiteboard_zoom_in') || '放大' }}</span>
        </a-button>
      </a-tooltip>

      <a-tooltip :title="$t('item.whiteboard_fit') || '适屏'">
        <a-button size="small" @click="$emit('fit-to-viewport')">
          <template #icon>
            <i class="fa-solid fa-maximize" />
          </template>
          <span class="btn-text">{{ $t('item.whiteboard_fit') || '适屏' }}</span>
        </a-button>
      </a-tooltip>

      <a-tooltip :title="$t('item.whiteboard_actual_size') || '1:1'">
        <a-button size="small" @click="$emit('zoom-reset')">
          <template #icon>
            <i class="fa-solid fa-compress" />
          </template>
          <span class="btn-text">{{ $t('item.whiteboard_actual_size') || '1:1' }}</span>
        </a-button>
      </a-tooltip>

      <!-- 全屏模式 -->
      <a-tooltip :title="isFullscreen ? ($t('item.whiteboard_exit_fullscreen') || '退出全屏') : ($t('item.whiteboard_fullscreen') || '全屏编辑')">
        <a-button
          size="small"
          :type="isFullscreen ? 'primary' : 'default'"
          @click="$emit('toggle-fullscreen')"
        >
          <template #icon>
            <i :class="isFullscreen ? 'fa-solid fa-compress' : 'fa-solid fa-expand'" />
          </template>
          <span class="btn-text">{{
            isFullscreen
              ? ($t('item.whiteboard_exit_fullscreen') || '退出全屏')
              : ($t('item.whiteboard_fullscreen') || '全屏编辑')
          }}</span>
        </a-button>
      </a-tooltip>

      <span class="divider"></span>

      <!-- 导出/导入 -->
      <a-tooltip :title="$t('item.whiteboard_export_png') || '导出 PNG'">
        <a-button size="small" @click="$emit('export-image')">
          <template #icon>
            <i class="fa-solid fa-image" />
          </template>
          <span class="btn-text">{{ $t('item.whiteboard_export_png') || '导出PNG' }}</span>
        </a-button>
      </a-tooltip>

      <a-tooltip :title="$t('item.whiteboard_export_svg') || '导出 SVG'">
        <a-button size="small" @click="$emit('export-svg')">
          <template #icon>
            <i class="fa-solid fa-file-code" />
          </template>
          <span class="btn-text">{{ $t('item.whiteboard_export_svg') || '导出SVG' }}</span>
        </a-button>
      </a-tooltip>

      <a-tooltip :title="$t('item.whiteboard_export_json') || '导出 JSON'">
        <a-button size="small" @click="$emit('export-json')">
          <template #icon>
            <i class="fa-solid fa-code" />
          </template>
          <span class="btn-text">{{ $t('item.whiteboard_export_json') || '导出JSON' }}</span>
        </a-button>
      </a-tooltip>

      <a-tooltip :title="$t('item.whiteboard_import_json') || '导入 JSON'">
        <a-button size="small" :disabled="isReadOnly" @click="triggerImport">
          <template #icon>
            <i class="fa-solid fa-file-import" />
          </template>
          <span class="btn-text">{{ $t('item.whiteboard_import_json') || '导入JSON' }}</span>
        </a-button>
      </a-tooltip>
      <input
        ref="jsonFile"
        type="file"
        accept="application/json"
        style="display: none"
        @change="$emit('import-json', $event)"
      />

      <span class="divider"></span>

      <!-- 清空画布 -->
      <a-tooltip :title="$t('item.whiteboard_clear') || '清空画布'">
        <a-button
          size="small"
          danger
          :disabled="isReadOnly"
          @click="$emit('clear-canvas')"
        >
          <template #icon>
            <i class="fa-solid fa-trash-can" />
          </template>
          <span class="btn-text">{{ $t('item.whiteboard_clear') || '清空画布' }}</span>
        </a-button>
      </a-tooltip>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Props {
  isReadOnly: boolean
  brushColor: string
  brushWidth: number
  isDrawing: boolean
  isErasing: boolean
  isInsertingText: boolean
  pendingShapeType: string | null
  textFontSize: number
  textColor: string
  fontSizeOptions: number[]
  isFullscreen: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:brushColor': [value: string]
  'update:brushWidth': [value: number]
  'update:textFontSize': [value: number]
  'update:textColor': [value: string]
  'toggle-drawing': []
  'toggle-eraser': []
  'toggle-text-insert': []
  'insert-rect': []
  'insert-circle': []
  'insert-triangle': []
  'insert-arrow': []
  'undo': []
  'redo': []
  'clear-canvas': []
  'export-image': []
  'export-svg': []
  'export-json': []
  'import-image': [event: Event]
  'import-json': [event: Event]
  'zoom-out': []
  'zoom-in': []
  'fit-to-viewport': []
  'zoom-reset': []
  'toggle-fullscreen': []
  'apply-brush': []
  'apply-text-font-size': []
  'apply-text-color': []
}>()

const imgFile = ref<HTMLInputElement | null>(null)
const jsonFile = ref<HTMLInputElement | null>(null)

const onBrushColorChange = (val: string) => {
  emit('update:brushColor', val)
  emit('apply-brush')
}

const onBrushWidthChange = (val: number) => {
  emit('update:brushWidth', val)
  emit('apply-brush')
}

const onTextFontSizeChange = (val: number) => {
  emit('update:textFontSize', val)
  emit('apply-text-font-size')
}

const onTextColorChange = (val: string) => {
  emit('update:textColor', val)
  emit('apply-text-color')
}

const triggerImageUpload = () => {
  if (imgFile.value) {
    imgFile.value.value = ''
    imgFile.value.click()
  }
}

const triggerImport = () => {
  if (jsonFile.value) {
    jsonFile.value.value = ''
    jsonFile.value.click()
  }
}
</script>

<style scoped lang="scss">
.whiteboard-toolbar {
  position: sticky;
  top: 90px;
  z-index: 3;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 8px 12px;
  background: var(--color-bg-primary);
  border-bottom: 1px solid var(--color-border);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
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
  background: var(--color-border);
  margin: 0 4px;
}

.width-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
}

.width-wrap .label {
  font-size: 12px;
  color: var(--color-text-secondary);
}

.width-slider {
  width: 200px;
}

.btn-text {
  margin-left: 4px;
  display: none;
}
</style>

