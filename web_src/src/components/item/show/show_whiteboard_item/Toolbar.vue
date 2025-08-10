<template>
  <div class="toolbar">
    <div class="tool-left">
      <el-tooltip
        effect="dark"
        :content="$t('color') || '颜色'"
        placement="top"
      >
        <el-color-picker
          size="mini"
          :value="brushColor"
          :disabled="isReadOnly"
          @change="onBrushColorChange"
        />
      </el-tooltip>
      <div class="width-wrap">
        <span class="label">{{ $t('width') || '粗细' }}</span>
        <el-slider
          class="width-slider"
          :min="1"
          :max="50"
          :step="1"
          :show-input="true"
          input-size="mini"
          :value="brushWidth"
          :disabled="isReadOnly"
          @input="onBrushWidthInput"
        />
      </div>
    </div>

    <div class="tool-right">
      <!-- 撤销 / 重做 -->
      <el-tooltip effect="dark" :content="$t('undo') || '撤销'" placement="top">
        <el-button
          size="mini"
          plain
          @click="$emit('undo')"
          :disabled="isReadOnly"
        >
          <i class="far fa-rotate-left" />
          <span class="btn-text">{{ $t('undo') || '撤销' }}</span>
        </el-button>
      </el-tooltip>
      <el-tooltip effect="dark" :content="$t('redo') || '重做'" placement="top">
        <el-button
          size="mini"
          plain
          @click="$emit('redo')"
          :disabled="isReadOnly"
        >
          <i class="far fa-rotate-right" />
          <span class="btn-text">{{ $t('redo') || '重做' }}</span>
        </el-button>
      </el-tooltip>

      <span class="divider"></span>

      <!-- 绘制/选择 与 橡皮擦 -->
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
          @click="$emit('toggle-drawing')"
          :disabled="isReadOnly"
        >
          <i :class="isDrawing ? 'far fa-pen' : 'far fa-hand'" />
          <span class="btn-text">{{
            isDrawing ? $t('draw') || '绘制' : $t('select') || '选择'
          }}</span>
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
          @click="$emit('toggle-eraser')"
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
          @click="$emit('clear-canvas')"
          :disabled="isReadOnly"
        >
          <i class="far fa-trash-can" />
          <span class="btn-text">{{ $t('clear') || '清空画布' }}</span>
        </el-button>
      </el-tooltip>

      <span class="divider"></span>

      <!-- 文本样式 -->
      <el-popover placement="bottom" width="260" trigger="click">
        <div class="text-style-pop">
          <div
            class="row"
            style="display:flex;align-items:center;gap:8px;margin-bottom:8px;"
          >
            <span class="label" style="min-width:56px;">{{
              $t('font_size') || '字号'
            }}</span>
            <el-select
              size="mini"
              :value="textFontSize"
              style="width: 100px"
              :disabled="isReadOnly"
              @change="onTextFontSizeChange"
            >
              <el-option
                v-for="s in fontSizeOptions"
                :key="s"
                :label="s"
                :value="s"
              />
            </el-select>
          </div>
          <div class="row" style="display:flex;align-items:center;gap:8px;">
            <span class="label" style="min-width:56px;">{{
              $t('font_color') || '字体颜色'
            }}</span>
            <el-color-picker
              size="mini"
              :value="textColor"
              :disabled="isReadOnly"
              @change="onTextColorChange"
            />
          </div>
        </div>
        <el-button slot="reference" size="mini" plain :disabled="isReadOnly">
          <i class="far fa-font" />
          <span class="btn-text">{{ $t('text_style') || '文本样式' }}</span>
        </el-button>
      </el-popover>

      <!-- 插入文字 -->
      <el-tooltip
        effect="dark"
        :content="$t('insert_text') || '插入文字'"
        placement="top"
      >
        <el-button
          size="mini"
          :type="isInsertingText ? 'primary' : 'default'"
          plain
          :disabled="isReadOnly"
          @click="$emit('toggle-text-insert')"
        >
          <i class="far fa-i-cursor" />
          <span class="btn-text">{{ $t('insert_text') || '插入文字' }}</span>
        </el-button>
      </el-tooltip>

      <span class="divider"></span>

      <!-- 缩放与适屏 -->
      <el-tooltip
        effect="dark"
        :content="$t('zoom_out') || '缩小'"
        placement="top"
      >
        <el-button size="mini" plain @click="$emit('zoom-out')">
          <i class="far fa-magnifying-glass-minus" />
          <span class="btn-text">{{ $t('zoom_out') || '缩小' }}</span>
        </el-button>
      </el-tooltip>
      <el-tooltip
        effect="dark"
        :content="$t('zoom_in') || '放大'"
        placement="top"
      >
        <el-button size="mini" plain @click="$emit('zoom-in')">
          <i class="far fa-magnifying-glass-plus" />
          <span class="btn-text">{{ $t('zoom_in') || '放大' }}</span>
        </el-button>
      </el-tooltip>
      <el-tooltip effect="dark" :content="$t('fit') || '适屏'" placement="top">
        <el-button size="mini" plain @click="$emit('fit-to-viewport')">
          <i class="far fa-maximize" />
          <span class="btn-text">{{ $t('fit') || '适屏' }}</span>
        </el-button>
      </el-tooltip>
      <el-tooltip
        effect="dark"
        :content="$t('actual_size') || '1:1'"
        placement="top"
      >
        <el-button size="mini" plain @click="$emit('zoom-reset')">
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
          :disabled="isReadOnly"
          @click="triggerImageUpload"
        >
          <i class="far fa-file-image" />
          <span class="btn-text">{{ $t('insert_image') || '插入图片' }}</span>
        </el-button>
      </el-tooltip>
      <input
        ref="imgFile"
        type="file"
        accept="image/*"
        style="display:none"
        @change="$emit('import-image', $event)"
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
          @click="$emit('export-image')"
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
          @click="$emit('export-svg')"
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
          @click="$emit('export-json')"
          :disabled="isVisitor"
        >
          <i class="far fa-code" />
          <span class="btn-text">{{ $t('export_json') || '导出JSON' }}</span>
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
          :disabled="isReadOnly"
          @click="triggerImport"
        >
          <i class="far fa-file-arrow-up" />
          <span class="btn-text">{{ $t('import_json') || '导入JSON' }}</span>
        </el-button>
      </el-tooltip>
      <input
        ref="jsonFile"
        type="file"
        accept="application/json"
        style="display:none"
        @change="$emit('import-json', $event)"
      />
    </div>
  </div>
</template>

<script>
export default {
  name: 'WhiteboardToolbar',
  props: {
    isReadOnly: { type: Boolean, default: false },
    isVisitor: { type: Boolean, default: false },
    brushColor: { type: String, default: '#2c3e50' },
    brushWidth: { type: Number, default: 4 },
    isDrawing: { type: Boolean, default: true },
    isErasing: { type: Boolean, default: false },
    isInsertingText: { type: Boolean, default: false },
    textFontSize: { type: Number, default: 24 },
    textColor: { type: String, default: '#2c3e50' },
    fontSizeOptions: { type: Array, default: () => [] }
  },
  methods: {
    onBrushColorChange(val) {
      this.$emit('update:brushColor', val)
      this.$emit('apply-brush')
    },
    onBrushWidthInput(val) {
      this.$emit('update:brushWidth', val)
      this.$emit('apply-brush')
    },
    onTextFontSizeChange(val) {
      this.$emit('update:textFontSize', val)
      this.$emit('apply-text-font-size')
    },
    onTextColorChange(val) {
      this.$emit('update:textColor', val)
      this.$emit('apply-text-color')
    },
    triggerImageUpload() {
      if (this.$refs.imgFile) {
        this.$refs.imgFile.value = ''
        this.$refs.imgFile.click()
      }
    },
    triggerImport() {
      if (this.$refs.jsonFile) {
        this.$refs.jsonFile.value = ''
        this.$refs.jsonFile.click()
      }
    }
  }
}
</script>

<style scoped>
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
.btn-text {
  margin-left: 4px;
  display: none;
}
</style>
