<!--
  ShowDoc Editormd 编辑器适配器

  作用：
  - 包装底层 EditormdEditor 组件，提供 ShowDoc 特定的配置
  - 统一管理 ShowDoc 的编辑器功能开关、工具栏、主题
  - 处理 v-model 双向绑定，确保内容正确同步
  - 兼容 VditorEditor 的接口，方便项目切换

  架构说明：
  - 底层组件：EditormdEditor（通用编辑器）
  - 适配器层：ShowdocAdapter（ShowDoc 特定配置）
  - 业务层：EditPageModal 等业务组件

  使用方式：
  <ShowdocAdapter v-model="content" mode="editor" height="70vh" />

  可选配置：
  - features: 覆盖默认的功能配置
  - toolbar: 覆盖默认的工具栏配置
  - theme: 覆盖默认的主题配置
  - upload: 覆盖默认的上传配置

  特点：
  - 支持自动插入 TOC
  - 支持 Mermaid、PlantUML、Mindmap、视频
  - 主题自适应（跟随全局主题）
  - 工具栏与旧版保持一致
-->
<template>
  <div class="editormd-editor-adapter showdoc">
    <div class="editor-wrapper">
      <EditormdEditor
        ref="editormdEditorRef"
        v-model="content"
        :id="editorId"
        :mode="mode"
        :height="height"
        :taskToggle="taskToggle"
        :keyword="keyword"
        :editorPath="editorPath"
        :features="features"
        :theme="theme"
        :toolbar="toolbar"
        :upload="uploadConfig"
        @load="handleLoad"
        @change="handleChange"
        @task-toggle="handleTaskToggle"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import type { EditormdEditorExpose, ThemeConfig } from './types'
import EditormdEditor from './index.vue'
import {
  DEFAULT_FEATURE_CONFIG,
  DEFAULT_TOOLBAR,
  DEFAULT_AUTO_INSERT_TOC_MODE,
} from './constants'

// ============================================
// Props 定义
// ============================================
interface Props {
  // v-model 绑定的内容
  modelValue: string
  // 编辑器 ID
  id?: string
  // 编辑器模式：editor(编辑)、preview(预览)、html(纯 HTML 渲染)
  mode?: 'editor' | 'preview' | 'html'
  // 编辑器高度
  height?: string
  // 允许在阅读模式下切换任务列表复选框
  taskToggle?: boolean
  // 关键字高亮
  keyword?: string
  // 编辑器路径
  editorPath?: string
  // 【可选】覆盖默认的功能配置
  features?: {
    syncScroll?: boolean
    toc?: boolean
    autoInsertToc?: boolean
    fullscreen?: boolean
    mermaid?: boolean
    plantuml?: boolean
    mindmap?: boolean
    flowchart?: boolean
    sequenceDiagram?: boolean
    tex?: boolean
    taskList?: boolean
    codeHighlight?: boolean
    imageUpload?: boolean
    video?: boolean
  }
  // 【可选】覆盖默认的主题配置
  theme?: ThemeConfig
  // 【可选】覆盖默认的工具栏配置
  toolbar?: any[]
  // 【可选】覆盖默认的上传配置
  upload?: {
    handler?: (files: File[]) => Promise<{ url: string }[]>
    maxFileSize?: number
    accept?: string
    multiple?: boolean
  }
}

// 默认值
const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  // 使用随机 ID，避免与页面上的其他编辑器实例（如预览区）冲突
  id: () => `editor-md-${Math.random().toString(36).substr(2, 9)}`,
  mode: 'editor',
  height: '70vh',
  taskToggle: true,
  keyword: '',
  editorPath: '',
  features: () => ({}),
  theme: () => ({ editor: 'light', preview: 'light' }),
  toolbar: () => DEFAULT_TOOLBAR,
})

// ============================================
// Emits 定义
// ============================================
const emit = defineEmits<{
  // v-model 更新事件
  (e: 'update:modelValue', value: string): void
  // 内容变化事件（实时）
  (e: 'change', value: string): void
  // 内容变化事件（防抖）
  (e: 'input', value: string): void
  // 编辑器加载完成
  (e: 'load', editor: any): void
  // 任务列表切换
  (e: 'task-toggle', payload: { index: number; checked: boolean }): void
}>()

// ============================================
// 内部状态
// ============================================
const editormdEditorRef = ref<InstanceType<typeof EditormdEditor>>()
const content = ref(props.modelValue)

// 生成编辑器 ID
const editorId = computed(() => props.id)

// 合并功能配置
const features = computed(() => {
  return {
    ...DEFAULT_FEATURE_CONFIG,
    autoInsertToc: true, // 默认自动插入 TOC
    ...props.features,
  }
})

// 主题配置（跟随全局主题）
const theme = computed<ThemeConfig>(() => {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark'
  return {
    editor: isDark ? 'dark' : 'light',
    preview: isDark ? 'dark' : 'light',
    ...props.theme,
  }
})

// ============================================
// 事件处理
// ============================================

// 编辑器加载完成
const handleLoad = (editor: any) => {
  emit('load', editor)
}

// 内容变化（实时）
const handleChange = (value: string) => {
  emit('update:modelValue', value)
  emit('change', value)
  emit('input', value)
}

// 任务列表切换
const handleTaskToggle = (payload: { index: number; checked: boolean }) => {
  emit('task-toggle', payload)
}

// ============================================
// 监听外部内容变化
// ============================================
watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue !== content.value) {
      content.value = newValue
    }
  }
)

// ============================================
// 监听主题变化，动态更新编辑器主题
// ============================================
watch(
  () => document.documentElement.getAttribute('data-theme'),
  (newTheme) => {
    const isDark = newTheme === 'dark'
    // Editormd 不支持动态切换主题，需要重新初始化
    // 这里暂不实现，如果需要可以通过重新挂载组件来实现
    console.log('Theme changed to:', isDark ? 'dark' : 'light')
  }
)

// ============================================
// 暴露的方法（兼容 VditorEditor 接口）
// ============================================
const getValue = () => {
  return editormdEditorRef.value?.getMarkdown() || ''
}

const setValue = (value: string) => {
  editormdEditorRef.value?.setValue(value)
}

const insertValue = (value: string) => {
  editormdEditorRef.value?.insertValue(value)
}

const getSelection = () => {
  return editormdEditorRef.value?.getSelection() || ''
}

const clear = () => {
  editormdEditorRef.value?.clear()
}

const setCursor = (position: { line: number; ch: number }) => {
  editormdEditorRef.value?.setCursor(position)
}

const focus = () => {
  // 通过 CodeMirror 实例获取焦点
  const instance = editormdEditorRef.value?.getInstance()
  if (instance?.editor) {
    // instance.editor 是 CodeMirror 实例
    instance.editor.focus()
  }
}

const blur = () => {
  // Editormd 没有直接的 blur 方法
  console.log('Editormd blur not implemented')
}

const preview = () => {
  return editormdEditorRef.value?.preview()
}

const unwatch = () => {
  return editormdEditorRef.value?.unwatch()
}

const watchEditor = () => {
  return editormdEditorRef.value?.watch()
}

const getInstance = () => {
  return editormdEditorRef.value?.getInstance()
}

// ============================================
// 暴露给父组件
// ============================================
defineExpose({
  getValue,
  setValue,
  insertValue,
  getSelection,
  clear,
  setCursor,
  focus,
  blur,
  preview,
  unwatch,
  watch: watchEditor,
  getInstance,
})
</script>

<style scoped>
.editormd-editor-adapter {
  width: 100%;
}

.editormd-editor-adapter.showdoc .editor-wrapper {
  width: 100%;
}
</style>
