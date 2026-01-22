<template>
  <div class="theme-toggle" @click="handleToggle">
    <!-- 默认插槽：自定义按钮样式 -->
    <slot>
      <!-- 默认内容：使用半圆图标表示主题切换 -->
      <i class="fas fa-circle-half-stroke theme-icon"></i>
    </slot>
  </div>
</template>

<script setup lang="ts">
import { useAppStore } from '@/store/app'

const appStore = useAppStore()

const handleToggle = () => {
  appStore.toggleTheme()
}

// 暴露给外部使用的数据和方法
defineExpose({
  appStore,
  handleToggle
})
</script>

<style lang="scss" scoped>
.theme-toggle {
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
  transition: background-color 0.15s ease;

  &:hover {
    background-color: rgba(0, 0, 0, 0.05);
  }

  [data-theme='dark'] &:hover {
    background-color: rgba(255, 255, 255, 0.1);
  }
}

.theme-icon {
  font-size: 20px;
  user-select: none;
}
</style>

