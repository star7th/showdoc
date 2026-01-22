<template>
  <div class="mobile-header" :class="{ 'hide-element': hideHeader }">
    <div class="header-wrap">
      <!-- 左侧：Logo 和项目名称 -->
      <div class="logo">
        <div class="logo-content">
          <img class="logo-img" src="@/assets/Logo.svg" alt="logo" />
          <span class="font-bold">{{ itemInfo?.item_name || '' }}</span>
        </div>
      </div>

      <!-- 右侧：目录按钮或退出全屏按钮 -->
      <div v-if="!isFullPage" class="cat-btn-div" @click="openDrawer">
        <div class="cat-btn">
          <i class="fas fa-folder-tree"></i> {{ $t('item.catalog') }}
        </div>
      </div>
      <!-- 全屏模式下的退出全屏按钮 -->
      <div v-else class="exit-fullscreen-btn" @click="$emit('exit-fullscreen')">
        <i class="far fa-compress"></i>
        <span>{{ $t('common.exit_fullscreen') }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * 移动端头部组件
 * - 显示 Logo 和项目名称
 * - 提供目录按钮，点击打开目录抽屉
 * - 支持滚动时隐藏（通过 hideHeader prop 控制）
 */
// Props
interface Props {
  itemInfo?: any
  searchItem?: (keyword: string) => void
  getPageContent?: (pageId: number) => void
  hideHeader?: boolean // 向下滚动时隐藏头部，向上滚动时显示
  isFullPage?: boolean // 是否为全屏模式
}

const props = withDefaults(defineProps<Props>(), {
  itemInfo: () => ({}),
  searchItem: () => {},
  getPageContent: () => {},
  hideHeader: false,
  isFullPage: false
})

// 使用 defineModel 双向绑定抽屉状态（Vue 3.4+ 特性）
// 父组件通过 v-model:drawerVisible 绑定
const drawerVisible = defineModel<boolean>('drawerVisible', { default: false })

// Emits
const emit = defineEmits<{
  exitFullscreen: []
}>()

// Methods
const openDrawer = () => {
  drawerVisible.value = true
}
</script>

<style scoped lang="scss">
.mobile-header {
  height: 60px;
  border-bottom: 1px solid var(--color-border);
  position: fixed;
  top: 0;
  width: 100%;
  z-index: 999;
  display: flex;
  justify-content: center;
  align-items: center;
  background: var(--color-bg-secondary);
  transition: opacity 0.15s ease;
}

.hide-element {
  opacity: 0;
  pointer-events: none;
}

.header-wrap {
  height: 40px;
  line-height: 40px;
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
}

.logo {
  margin-left: 20px;
  align-items: center;
}

.logo-content {
  display: flex;
  align-items: center;
}

.logo-img {
  width: 40px;
  height: 40px;
  margin-right: 5px;
}

.font-bold {
  font-size: 16px;
  font-weight: 600;
  color: var(--color-text-primary);
  max-width: 150px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.cat-btn-div {
  margin-right: 20px;
  background: var(--color-bg-primary);
  border-radius: 8px;
  cursor: pointer;
  padding-left: 15px;
  padding-right: 15px;
  color: var(--color-text-primary);
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 6px;
  box-shadow: var(--shadow-xs);
  transition: all 0.15s ease;

  &:hover {
    background-color: var(--color-bg-secondary);
    box-shadow: var(--shadow-sm);
  }

  i {
    font-size: 14px;
  }
}

// 退出全屏按钮
.exit-fullscreen-btn {
  margin-right: 20px;
  background-color: var(--color-bg-primary);
  border-radius: 8px;
  cursor: pointer;
  padding-left: 15px;
  padding-right: 15px;
  color: var(--color-text-primary);
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 6px;
  box-shadow: var(--shadow-xs);
  transition: all 0.15s ease;

  &:hover {
    background-color: var(--color-bg-secondary);
    box-shadow: var(--shadow-sm);
  }

  i {
    font-size: 14px;
  }
}
</style>

