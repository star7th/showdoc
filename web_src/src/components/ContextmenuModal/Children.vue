<template>
  <div v-if="props.show" class="children-container" ref="contextmenuRef" :style="{ top: `${top}px`, left: `${left}px` }"
    @click.stop @mouseenter="handleMouseEnter" @mouseleave="handleMouseLeave">
    <div class="contextmenu-item bgColor-select" v-for="(item, index) in props.list" :key="index" v-show="!item.hidden"
      @click="clickHandle(item)" @dblclick.stop @contextmenu.stop>
      <div class="line-container">
        <div class="item-container">
          <img class="img" v-if="item.img" :src="item.img">
          <div class="icon" v-if="item.icon" :style="item.iconStyle">
            <i :class="getIconClass(item.icon)"></i>
          </div>
          <div class="text">{{ item.text }}</div>
        </div>

        <div class="checked" v-if="item.checked">
          <i class="fas fa-check"></i>
        </div>
        <div class="arrow text-default" v-if="item.children && item.children.length">
          <i class="fas fa-angle-right"></i>
        </div>
      </div>
    </div>


  </div>
</template>
<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import type { PropType } from 'vue'
import type { ContextmenuModalItemInterface } from './index'
import { getIconClass } from '@/utils/icon'

const props = defineProps({
  show: Boolean,
  itemRef: Object as PropType<HTMLElement>,
  scrollOffset: Number,
  list: Object as PropType<ContextmenuModalItemInterface[]>
})

const emits = defineEmits(['close', 'mouseenter', 'mouseleave'])

const contextmenuRef = ref()
const top = ref(0)
const left = ref(0)

// 计算子菜单位置
function calculatePosition() {
  if (!props.itemRef || !contextmenuRef.value) return

  const itemRect = props.itemRef.getBoundingClientRect()
  const menuWidth = contextmenuRef.value.clientWidth
  const menuHeight = contextmenuRef.value.clientHeight
  const clientWidth = document.body.clientWidth
  const clientHeight = document.body.clientHeight

  // 默认在右侧显示
  let tempLeft = itemRect.right
  let tempTop = itemRect.top

  // 如果右侧空间不够，显示在左侧
  if (tempLeft + menuWidth > clientWidth - 5) {
    tempLeft = itemRect.left - menuWidth
  }

  // 如果下方空间不够，向上调整
  if (tempTop + menuHeight > clientHeight - 5) {
    tempTop = clientHeight - menuHeight - 5
  }

  // 确保不超出顶部
  if (tempTop < 5) {
    tempTop = 5
  }

  top.value = tempTop
  left.value = tempLeft
}

// 监听显示状态变化，显示时计算位置
watch(() => props.show, (newVal) => {
  if (newVal) {
    nextTick(() => {
      calculatePosition()
    })
  }
})

// 监听滚动偏移变化，重新计算位置
watch(() => props.scrollOffset, () => {
  if (props.show) {
    calculatePosition()
  }
})

// 鼠标进入子菜单
function handleMouseEnter() {
  emits('mouseenter')
}

// 鼠标离开子菜单
function handleMouseLeave() {
  emits('mouseleave')
}

function clickHandle(item: ContextmenuModalItemInterface) {
  if (item.children) return
  item.onclick && item.onclick()
  emits('close')
}
</script>
<style lang="scss" scoped>
.children-container {
  position: fixed;
  max-height: 80vh;
  border-radius: 8px;
  background-color: var(--color-default);
  border: 1px solid var(--color-interval);
  box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.1);
  transform-origin: left top;
  overflow-y: auto;
  z-index: 1001;

  &::-webkit-scrollbar {
    width: 6px;
  }

  &::-webkit-scrollbar-track {
    background: transparent;
  }

  &::-webkit-scrollbar-thumb {
    background: var(--color-interval);
    border-radius: 3px;
  }

  &::-webkit-scrollbar-thumb:hover {
    background: var(--color-border);
  }
}

.contextmenu-item {
  display: flex;
  align-items: center;
  padding: 0 10px;
  cursor: pointer;

  &:last-of-type .line-container {
    border: none;
  }

  .line-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    border-bottom: 1px solid var(--color-interval);
  }

  .item-container {
    display: flex;
    align-items: center;
    gap: 0.6em;
    height: 50px;
    padding: 0 10px 0 5px;

    .img {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      object-fit: cover;
      flex-shrink: 0;
    }

    .text {
      white-space: nowrap;
    }

    .icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 20px;
      height: 20px;
      font-size: 14px;
      flex-shrink: 0;
    }
  }

  .checked {
    font-size: 12px;
    margin: 0 10px;
    flex-shrink: 0;
  }

  .arrow {
    font-size: 12px;
    margin: 0 10px;
    flex-shrink: 0;
  }
}

// 暗黑主题适配
.children-container {
  [data-theme="dark"] & {
    .icon,
    .checked,
    .arrow {
      color: var(--color-text-primary);
    }
  }
}
</style>

