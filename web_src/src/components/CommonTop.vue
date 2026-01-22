<template>
  <transition name="fade-up">
    <div
      v-show="visible"
      class="common-top"
      :style="{ right: `${right}px`, bottom: `${bottom}px` }"
      @click="scrollToTop"
    >
      <i class="fas fa-arrow-up"></i>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

interface Props {
  // 滚动多少距离后显示（单位：px）
  visibilityHeight?: number
  // 距离右侧的位置（单位：px）
  right?: number
  // 距离底部的位置（单位：px）
  bottom?: number
  // 滚动容器选择器（默认为 window）
  target?: string | (() => Element | Window)
}

const props = withDefaults(defineProps<Props>(), {
  visibilityHeight: 100,
  right: 40,
  bottom: 40,
  target: () => window
})

const visible = ref(false)

// 获取滚动容器
const getScrollContainer = () => {
  if (typeof props.target === 'function') {
    return props.target()
  } else if (typeof props.target === 'string') {
    return document.querySelector(props.target) || window
  }
  return window
}

// 滚动到顶部
const scrollToTop = () => {
  const container = getScrollContainer()
  
  if (container === window) {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    })
  } else {
    const el = container as Element
    el.scrollTo({
      top: 0,
      behavior: 'smooth'
    })
  }
}

// 处理滚动事件
const handleScroll = () => {
  const container = getScrollContainer()
  let scrollTop = 0
  
  if (container === window) {
    scrollTop = window.pageYOffset || document.documentElement.scrollTop
  } else {
    scrollTop = (container as Element).scrollTop
  }
  
  visible.value = scrollTop >= props.visibilityHeight
}

onMounted(() => {
  const container = getScrollContainer()
  container.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  const container = getScrollContainer()
  container.removeEventListener('scroll', handleScroll)
})
</script>

<style lang="scss" scoped>
.common-top {
  position: fixed;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  background-color: var(--color-bg-primary);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  box-shadow: var(--shadow-xs);
  cursor: pointer;
  transition: all 0.15s ease;
  z-index: 999;
  user-select: none;

  i {
    color: var(--color-primary);
    font-size: 18px;
  }

  &:hover {
    box-shadow: var(--shadow-sm);
    border-color: var(--color-active);
    background: var(--hover-overlay);

    i {
      color: var(--color-active);
    }
  }

  &:active {
    box-shadow: var(--shadow-xs);
  }
}

// 渐入动画
.fade-up-enter-active,
.fade-up-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.fade-up-enter-from,
.fade-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}
</style>

