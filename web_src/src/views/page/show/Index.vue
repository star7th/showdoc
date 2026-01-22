<template>
  <div class="page-show" :class="{ 'mobile-view': isMobile || isFullPage }">
    <!-- 移动端占位元素（隐藏头部时防止内容跳变） -->
    <div v-if="isMobile" class="pos"></div>

    <!-- 移动端头部 -->
    <div v-if="isMobile" class="mobile-header" :class="{ 'hide-element': hideHeader }">
      <div class="header-wrap">
        <!-- 左侧：Logo 和页面标题 -->
        <div class="logo">
          <div class="logo-content">
            <img class="logo-img" src="@/assets/Logo.svg" alt="logo" />
            <span class="font-bold">{{ pageTitle || t('page.show.title') }}</span>
          </div>
        </div>

        <!-- 右侧：返回按钮或退出全屏按钮 -->
        <div v-if="!isFullPage" class="back-btn" @click="goHome">
          <i class="fas fa-home"></i> {{ $t('common.home') }}
        </div>
        <div v-else class="exit-fullscreen-btn" @click="exitFullscreen">
          <i class="fas fa-compress"></i>
          <span>{{ t('common.exit_fullscreen') }}</span>
        </div>
      </div>
    </div>

    <!-- 内容容器 -->
    <div class="content-wrapper" :class="{ 'mobile-wrapper': isMobile }">
      <!-- 文档容器 -->
      <div class="doc-container" id="doc-container">
        <!-- 页面标题 -->
        <div class="doc-title-box">
          <h2 id="doc-title">{{ pageTitle }}</h2>
          <i
            v-if="!isMobile && showFullPageBtn && !isFullPage"
            :class="isFullPage ? 'fas fa-compress' : 'fas fa-expand'"
            id="full-page"
            @click="toggleFullPage"
          ></i>
        </div>


        <!-- 页面内容 -->
        <div id="page_md_content" class="page_content_main">
          <!-- Markdown 内容渲染（纯预览模式） -->
          <EditormdEditor
            v-if="pageId && content"
            :key="editorKey"
            v-model="content"
            mode="preview"
          />
        </div>

        <!-- 加载失败/页面不存在 -->
        <div v-if="errorMessage" class="error-message">
          <i class="fas fa-exclamation-circle"></i>
          <span>{{ errorMessage }}</span>
        </div>
      </div>
    </div>

    <!-- TOC 目录 (PC 端，固定在右侧) -->
    <div class="toc-side" v-if="pageId && content && !isMobile && showToc && !isFullPage">
      <Toc />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
// 引入 ShowDoc 编辑器适配器（包装底层 EditormdEditor 组件）
// 适配器提供了 ShowDoc 特定的默认配置和事件处理
import EditormdEditor from '@/components/EditormdEditor/ShowdocAdapter.vue'
import Toc from '@/components/Toc.vue'
import { renderPageContent, unescapeHTML } from '@/models/page'
import request from '@/utils/request'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const isMobile = ref(false)
const content = ref('')
const pageTitle = ref('')
const pageId = ref(0)
const itemId = ref(0)
const catId = ref(0)
const isRunapi = ref(false)
const showFullPageBtn = ref(true)
const showToc = ref(true)
const errorMessage = ref('')
const isFullPage = ref(false) // 全屏模式
const editorKey = ref(0) // 用于强制重新挂载编辑器
const hideHeader = ref(false) // 移动端隐藏头部
const _lastScrollTop = ref(0) // 记录上次滚动位置

// 判断是否为移动设备或全屏模式
const checkMobile = () => {
  const width = window.innerWidth
  isMobile.value = width <= 768 || width < 1000 || isFullPage.value
}

// 移动端滚动控制头部显示/隐藏
const handleScroll = () => {
  if (!isMobile.value) return

  const scrollTop =
    document.documentElement.scrollTop ||
    document.body.scrollTop ||
    window.pageYOffset

  // 判断滚动的方向
  if (scrollTop < 100 || scrollTop < _lastScrollTop.value) {
    // 向上滚动时或顶部100px内显示头部
    hideHeader.value = false
  } else {
    // 向下滚动时隐藏头部
    hideHeader.value = true
  }

  _lastScrollTop.value = scrollTop
}

// 获取页面内容
const getPageContent = async () => {
  const page_id = route.params.page_id as string
  const unique_key = route.params.unique_key as string

  if (!page_id && !unique_key) {
    errorMessage.value = t('page.show.page_not_found')
    return
  }

  try {
    const url = unique_key ? '/api/page/infoByKey' : '/api/page/info'
    const params = {
      page_id: page_id,
      unique_key: unique_key
    }
    const data = await request(url, params, 'post', false)

    if (data.error_code === 0) {
      // Runapi 判定与权限
      const raw = data.data.page_content || ''
      isRunapi.value = false
      try {
        const obj = JSON.parse(unescapeHTML(raw))
        isRunapi.value = !!(obj && obj.info && obj.info.url)
      } catch (e) {}

      content.value = renderPageContent(raw)
      pageTitle.value = data.data.page_title
      pageId.value = data.data.page_id
      itemId.value = data.data.item_id || 0
      catId.value = data.data.cat_id || 0
      errorMessage.value = ''

      // 更新页面标题
      document.title = pageTitle.value + '--ShowDoc'

      // 更新 meta description
      const metaDescription = document.querySelector('meta[name="description"]')
      if (metaDescription) {
        metaDescription.setAttribute('content', pageTitle.value)
      }

    } else if (data.error_code === 10104 && !unique_key) {
      // 需要输入验证码，跳转到验证码页面
      router.replace({
        path: '/captcha/index',
        query: {
          page_id: page_id,
          unique_key: unique_key,
          type: 'visit_page',
          redirect: route.fullPath
        }
      })
    } else if ((data.error_code === 10307 || data.error_code === 10303) && !unique_key) {
      // 需要输入密码
      router.replace({
        path: '/item/password/0',
        query: {
          page_id: page_id,
          redirect: route.fullPath
        }
      })
    } else {
      errorMessage.value = data.error_message || t('page.show.load_failed')
    }
  } catch (error) {
    errorMessage.value = t('page.show.load_failed')
  }
}

// 全屏切换
const toggleFullPage = () => {
  isFullPage.value = !isFullPage.value

  // 重新检查移动端状态（因为 checkMobile 会考虑 isFullPage）
  checkMobile()

  // 进入全屏模式相当于切换到移动端视图
  // 退出全屏模式恢复 PC 端视图
  editorKey.value++ // 强制重新挂载编辑器
}

// 返回首页
const goHome = () => {
  window.location.href = '/'
}

// 退出全屏（刷新页面）
const exitFullscreen = () => {
  window.location.reload()
}

// 窗口大小变化监听
const handleResize = () => {
  checkMobile()
  if (isMobile.value) {
    showFullPageBtn.value = false
    showToc.value = false
    const docContainer = document.getElementById('doc-container')
    if (docContainer) {
      docContainer.style.width = '95%'
      docContainer.style.padding = '5px'
    }
  } else {
    // PC 端动态计算 TOC 位置，使其处于右侧空白区域中间
    updateTocPosition()
  }
}

// 更新 TOC 位置
const updateTocPosition = () => {
  const tocSide = document.querySelector('.toc-side')
  const tocList = document.querySelector('.toc-list')
  if (!tocSide || !tocList) return

  // 计算内容区右边缘位置
  // 内容区宽度 850px，居中显示
  // 内容区左边缘 = (screenWidth - 850) / 2
  // 内容区右边缘 = (screenWidth - 850) / 2 + 850
  // TOC 左边 = 内容区右边缘 + 40px 间距
  const screenWidth = window.innerWidth
  const contentRightEdge = (screenWidth - 850) / 2 + 850
  const leftDistance = contentRightEdge + 40

  console.log('屏幕宽度:', screenWidth)
  console.log('内容区右边缘:', contentRightEdge)
  console.log('TOC left值:', leftDistance)

  tocSide.setAttribute('style', `right: auto; left: ${leftDistance}px;`)
}

onMounted(() => {
  checkMobile()
  getPageContent()

  // 移动端适配
  if (isMobile.value) {
    showFullPageBtn.value = false
    showToc.value = false
  } else {
    // PC 端初始化 TOC 位置（多次重试，确保 TOC 已渲染）
    const tryUpdateToc = (delay: number) => {
      setTimeout(() => {
        updateTocPosition()
      }, delay)
    }
    tryUpdateToc(500)
    tryUpdateToc(1000)
    tryUpdateToc(1500)
    tryUpdateToc(2000)
  }

  window.addEventListener('resize', handleResize)
  // 移动端添加滚动监听
  window.addEventListener('scroll', handleScroll)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
  window.removeEventListener('scroll', handleScroll)
  document.title = 'ShowDoc'
})
</script>

<style scoped lang="scss">
.page-show {
  min-height: 100vh;
  background-color: var(--bg-color);
  padding-top: 60px;
  padding-bottom: 40px;

  &.mobile-view {
    padding-top: 50px;
    padding-bottom: 20px;
  }
}

// 移动端头部
.mobile-header {
  height: 60px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
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

// 返回按钮（克制设计）
.back-btn {
  margin-right: 20px;
  background: var(--color-bg-primary);
  border-radius: 6px;
  cursor: pointer;
  padding: 0 15px;
  height: 36px;
  color: var(--color-text-primary);
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.15s ease;

  &:hover {
    background: var(--hover-overlay);
  }

  i {
    font-size: 14px;
  }
}

// 退出全屏按钮（克制设计）
.exit-fullscreen-btn {
  margin-right: 20px;
  background: var(--color-bg-primary);
  border-radius: 6px;
  cursor: pointer;
  padding: 0 15px;
  height: 36px;
  color: var(--color-text-primary);
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.15s ease;

  &:hover {
    background: var(--hover-overlay);
  }

  i {
    font-size: 14px;
  }
}

// 内容容器
.content-wrapper {
  width: 100%;
  min-height: calc(100vh - 100px);
  position: relative;

  &.mobile-wrapper {
    min-height: calc(100vh - 70px);
  }
}

// 文档容器（克制设计）
.doc-container {
  width: 850px;
  max-width: 850px;
  background-color: var(--color-bg-primary);
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  padding: 32px;
  min-height: 500px;
  margin: 0 auto;
  transition: all 0.15s ease;
}

// TOC 侧边栏（固定在右侧）
.toc-side {
  position: fixed; // 固定定位
  left: auto; // 初始值，会被 JavaScript 动态更新
  top: 100px;
  width: 260px;
  z-index: 10;
  transition: left 0.15s ease; // 添加过渡动画
}

// 页面标题（克制设计）
.doc-title-box {
  position: relative;
  padding: 0 0 20px 0;
  margin-bottom: 20px;
  border-bottom: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  justify-content: center;

  #doc-title {
    font-size: 24px;
    font-weight: 600;
    color: var(--color-text-primary);
    margin: 0;
    padding: 0;
  }

  #full-page {
    position: absolute;
    top: 4px;
    right: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: var(--color-text-secondary);
    background: var(--color-bg-secondary);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;

    &:hover {
      background: var(--hover-overlay);
      color: var(--color-active);
    }
  }
}

// 页面内容
#page_md_content {
  width: 100%;
  color: var(--color-text-primary);
  overflow: hidden;
}

.page_content_main {
  padding: 10px 10px 90px 10px;
  min-height: 400px;
}

// 错误提示（克制设计）
.error-message {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 40px 20px;
  color: var(--color-text-secondary);
  font-size: 14px;

  .fas {
    font-size: 24px;
  }
}

// Markdown 样式已在 EditormdEditor/themes/base.css 中统一定义
// 无需重复定义，保持样式一致性

// 移动端适配
.mobile-view {
  .doc-container {
    width: 95%;
    max-width: 95%;
    margin: 0 auto;
    padding: 10px;
    border-radius: 0;
    box-shadow: none;
  }

  .doc-title-box {
    height: auto;
    padding: 10px 0;

    #doc-title {
      font-size: 20px;
    }

    #full-page {
      display: none;
    }
  }

  .page_content_main {
    padding: 5px;
  }
}

// 移动端占位元素样式
.pos {
  height: 60px;
  width: 100%;
}
</style>
