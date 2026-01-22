<template>
  <div class="modal-header text-default" :class="{ 'use-line': props.useLine }">
    <div class="header-left">
      <div v-if="props.icon" class="icon">
        <i :class="getIconClass(props.icon)"></i>
      </div>
      <span v-if="props.title" :class="{ marginLeftDefault: !props.icon }">{{ props.title }}</span>
      <slot name="left"></slot>
    </div>
    <div class="header-right">
      <slot name="right"></slot>
      <a-tooltip :mouseEnterDelay="0.3" :destroyTooltipOnHide="true" title="关闭">
        <div class="icon clickable" @click="emits('close')">
          <i :class="getIconClass(['fas', 'xmark'])"></i>
        </div>
      </a-tooltip>
    </div>
  </div>
</template>
<script setup lang="ts">
import { getIconClass } from '@/utils/icon';

const props = withDefaults(defineProps<{
  icon?: string[]
  title?: string
  useClose?: boolean
  useLine?: boolean
}>(), {
  useClose: true,
  useLine: true
})

const emits = defineEmits(['close'])

</script>
<style lang="scss" scoped>
.modal-header {
  display: flex;
  justify-content: space-between;
  height: 60px;
  padding: 0 24px; // 增加左右内边距，避免内容紧贴边缘
  user-select: none;

  &.use-line {
    border-bottom: 1px solid var(--color-interval);
  }

  .header-left,
  .header-right {
    display: flex;
    align-items: center;
  }

  :deep(.icon) {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 60px;
    -webkit-app-region: no-drag;
    
    // 关闭按钮增强（克制设计 - 正方形按钮）
    &.clickable {
      cursor: pointer;
      padding: 0;
      
      // 创建正方形的按钮区域
      i {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        font-size: 16px;
        color: var(--color-text-primary);
        border-radius: 6px;
        transition: background-color 0.15s ease;
        
        &:hover {
          background-color: var(--hover-overlay);
        }
      }
    }
  }
}
</style>