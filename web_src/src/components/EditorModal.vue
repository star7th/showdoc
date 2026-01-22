<template>
  <Teleport to="body">
    <Transition name="editor-modal">
      <div v-if="show" class="editor-modal" @wheel.stop @click.self="handleMaskClick">
        <div class="editor-modal-content">
          <slot></slot>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { watch, onBeforeUnmount } from 'vue'

interface Props {
  show?: boolean
  maskClosable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  show: false,
  maskClosable: false
})

const emits = defineEmits(['close'])

// 监听弹窗显示状态，控制 body 滚动
watch(() => props.show, (newVal) => {
  if (newVal) {
    // 弹窗打开时，禁用 body 滚动
    document.body.style.overflow = 'hidden'
  } else {
    // 弹窗关闭时，恢复 body 滚动
    document.body.style.overflow = ''
  }
})

onBeforeUnmount(() => {
  // 组件卸载时恢复 body 滚动
  document.body.style.overflow = ''
})

const handleMaskClick = () => {
  if (props.maskClosable) {
    emits('close')
  }
}
</script>

<style lang="scss" scoped>
.editor-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding-top: 10px;
  padding-bottom: 10px;
  overflow: auto;
  background-color: var(--color-inactive);
  z-index: 1000;
  user-select: none;
}

.editor-modal-content {
  width: 98%;
  max-width: 1920px;
  height: calc(100vh - 20px);
  background-color: var(--color-bg-primary);
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  user-select: text;
  overflow: hidden;
}

// 过渡动画
.editor-modal-enter-active,
.editor-modal-leave-active {
  transition: opacity 200ms ease-in-out;

  .editor-modal-content {
    transition: transform 200ms ease-in-out;
  }
}

.editor-modal-enter-from,
.editor-modal-leave-to {
  opacity: 0;

  .editor-modal-content {
    transform: scale(0.9) translateY(-10px);
  }
}

.editor-modal-enter-to,
.editor-modal-leave-from {
  opacity: 1;

  .editor-modal-content {
    transform: scale(1) translateY(0);
  }
}
</style>

