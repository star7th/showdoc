<template>
  <div class="language-toggle" @click="handleToggle">
    <!-- 默认插槽：自定义按钮样式 -->
    <slot>
      <!-- 默认内容：显示当前语言 -->
      <i class="fas fa-globe language-icon"></i>
      <span class="language-text">{{ currentLangText }}</span>
    </slot>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { detectBrowserLanguage } from '@/utils/language'

const { locale } = useI18n()

// 当前语言文本
const currentLangText = computed(() => {
  return locale.value === 'zh-CN' ? '中' : 'EN'
})

// 切换语言
const handleToggle = () => {
  locale.value = locale.value === 'zh-CN' ? 'en-US' : 'zh-CN'
  localStorage.setItem('locale', locale.value)
  // 更新 HTML lang 属性
  document.documentElement.lang = locale.value
}

// 初始化：自动判断语言
const initLanguage = () => {
  // 如果 localStorage 中已有语言设置，使用该设置
  const savedLocale = localStorage.getItem('locale')
  if (savedLocale) {
    locale.value = savedLocale
    document.documentElement.lang = savedLocale
    return
  }

  // 否则根据浏览器语言自动判断
  const detectedLocale = detectBrowserLanguage()
  locale.value = detectedLocale

  // 保存到 localStorage
  localStorage.setItem('locale', locale.value)
  document.documentElement.lang = locale.value
}

// 组件挂载时初始化语言
initLanguage()

// 暴露给外部使用的数据和方法
defineExpose({
  locale,
  handleToggle,
  initLanguage
})
</script>

<style lang="scss" scoped>
.language-toggle {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  padding: 6px 12px;
  border-radius: 4px;
  transition: background-color 0.15s ease;
  font-size: 14px;

  &:hover {
    background-color: rgba(0, 0, 0, 0.05);
  }

  [data-theme='dark'] &:hover {
    background-color: rgba(255, 255, 255, 0.1);
  }
}

.language-icon {
  font-size: 16px;
  user-select: none;
}

.language-text {
  user-select: none;
  font-weight: 500;
}
</style>

