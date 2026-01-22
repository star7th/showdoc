<template>
  <div class="show-single-page-item" :class="{ 'mobile-view': isMobile }">
    <!-- 移动端占位元素（隐藏头部时防止内容跳变） -->
    <div v-if="isMobile" class="pos"></div>

    <!-- PC 头部 -->
    <ItemHeader v-if="!isMobile" :item-info="itemInfo">
      <template #right>
        <ItemHeaderRight
          :page-id="pageId"
          :item-info="itemInfo"
          :page-info="pageInfo"
          @edit-page="handleEdit"
        />
      </template>
    </ItemHeader>

    <!-- 移动端头部 -->
    <div v-if="isMobile" class="mobile-header" :class="{ 'hide-element': hideHeader }">
      <div class="header-wrap">
        <!-- 左侧：Logo 和项目名称 -->
        <div class="logo">
          <div class="logo-content">
            <img class="logo-img" src="@/assets/Logo.svg" alt="logo" />
            <span class="font-bold">{{ itemInfo?.item_name || '' }}</span>
          </div>
        </div>

        <!-- 右侧：返回按钮或退出全屏按钮 -->
        <div v-if="!isFullPage" class="back-btn" @click="goBack">
          <i class="far fa-arrow-left"></i> {{ $t('common.back') }}
        </div>
        <div v-else class="exit-fullscreen-btn" @click="exitFullscreen">
          <i class="far fa-compress"></i>
          <span>{{ $t('common.exit_fullscreen') }}</span>
        </div>
      </div>
    </div>

    <!-- 内容容器 -->
    <div class="content-container" :class="{ 'mobile-content': isMobile }">
      <!-- 页面内容区 -->
      <div class="page-wrapper">
        <!-- 页面标题 -->
        <div class="page-title-box">
          <h2 id="doc-title" class="page-title">{{ pageTitle }}</h2>
          <i
            v-if="!isMobile && !isFullPage"
            :class="isFullPage ? 'far fa-compress' : 'far fa-expand'"
            class="full-page-icon"
            @click="toggleFullPage"
          />
        </div>

        <!-- 全屏模式下的取消全屏按钮（固定定位） -->
        <div v-if="isFullPage" class="fullscreen-cancel-btn" @click="toggleFullPage">
          <i class="far fa-compress"></i>
          <span>{{ $t('common.exit_fullscreen') }}</span>
        </div>

        <!-- Markdown 内容 -->
        <div id="page_md_content" class="page_content_main">
          <EditormdEditor
            v-if="content"
            :key="`${pageId}-${isFullPage}`"
            v-model="content"
            mode="preview"
            :readonly="itemInfo?.item_edit != 1"
            @task-toggle="handleTaskToggle"
          />
          <div v-else class="loading">
            <a-spin size="large" :tip="$t('common.loading')" />
          </div>
        </div>

        <!-- 附件图标 -->
        <div class="attachment-icon" v-if="attachmentCount">
          <i class="far fa-paperclip" @click="showAttachmentModal = true"></i>
        </div>
      </div>
    </div>

    <!-- TOC 目录侧边栏（仅 PC 端显示，固定在右侧） -->
    <div class="toc-side" v-if="pageId && !isMobile && !isFullPage">
      <Toc />
    </div>

    <!-- 回到顶部按钮 -->
    <CommonTop :right="isMobile ? 20 : 40" :bottom="isMobile ? 20 : 40" />

    <!-- 附件列表弹窗 -->
    <AttachmentModal
      v-if="showAttachmentModal"
      :item-id="pageInfo?.item_id"
      :page-id="pageId"
      @close="showAttachmentModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, onUnmounted, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import request from '@/utils/request'
import { renderPageContent } from '@/models/page'
import { toggleNthTaskCheckbox } from '@/models/markdown'
import ItemHeader from '../../components/ItemHeader.vue'
import ItemHeaderRight from './HeaderRight.vue'
// 引入 ShowDoc 编辑器适配器（包装底层 EditormdEditor 组件）
// 适配器提供了 ShowDoc 特定的默认配置和事件处理
import EditormdEditor from '@/components/EditormdEditor/ShowdocAdapter.vue'
import CommonTop from '@/components/CommonTop.vue'
import Toc from '@/components/Toc.vue'
import EditPageModal from '@/views/modals/page/EditPageModal/index'
import AttachmentModal from '@/views/modals/page/AttachmentModal/index'

// Props
interface Props {
  itemInfo: any
}

const props = defineProps<Props>()

// Composables
const { t } = useI18n()

// Refs
const content = ref('')
const pageId = ref<number>(0)
const pageTitle = ref('')
const catId = ref<number>(0)
const attachmentCount = ref<number>(0)
const pageInfo = ref<any>(null)
const showAttachmentModal = ref(false)
const isFullPage = ref(false) // 全屏模式
let taskSaveTimer: number | null = null

// Computed
// 判断是否为移动设备或全屏模式
const isMobile = computed(() => {
  return window.innerWidth < 1000 || isFullPage.value
})

// 移动端滚动控制头部显示/隐藏
const hideHeader = ref(false)
const _lastScrollTop = ref(0)

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

// Methods
const getPageContent = async (requestedPageId?: number) => {
  if (!requestedPageId && !props.itemInfo?.menu?.pages?.[0]?.page_id) {
    return
  }

  const targetPageId = requestedPageId || props.itemInfo?.menu?.pages?.[0]?.page_id
  if (!targetPageId) return

  try {
    const data = await request('/api/page/info', {
      page_id: targetPageId,
    })

    content.value = renderPageContent(data.data.page_content)
    pageTitle.value = data.data.page_title
    catId.value = data.data.cat_id
    attachmentCount.value = data.data.attachment_count || 0
    pageInfo.value = data.data

    // 切换变量让它重新加载
    pageId.value = 0
    await nextTick()
    pageId.value = targetPageId as number

    // 更新页面标题
    document.title = pageTitle.value + '--ShowDoc'
  } catch (error) {
    console.error('获取页面内容失败:', error)
    content.value = '### ' + t('common.load_failed')
  }
}

const handleTaskToggle = async ({ index, checked }: { index: number, checked: boolean }) => {
  if (props.itemInfo?.item_edit != 1) return

  content.value = toggleNthTaskCheckbox(content.value, index, checked)

  // 防抖保存
  if (taskSaveTimer) {
    clearTimeout(taskSaveTimer)
  }
  taskSaveTimer = window.setTimeout(async () => {
    try {
      await request(
        '/api/page/save',
        {
          page_id: pageId.value,
          item_id: props.itemInfo.item_id,
          cat_id: catId.value,
          page_title: pageTitle.value,
          is_urlencode: 1,
          page_content: encodeURIComponent(content.value),
        },
        'post',
        false
      )
    } catch (error) {
      console.error('保存失败:', error)
    }
  }, 800)
}

const handleEdit = async () => {
  if (pageId.value && props.itemInfo?.item_id) {
    const result = await EditPageModal({
      itemId: props.itemInfo.item_id,
      editPageId: pageId.value,
      copyPageId: 0
    })
    if (result) {
      // 编辑完成后重新加载页面内容
      await getPageContent(pageId.value)
    }
  }
}

const goBack = () => {
  window.location.href = '/item/index'
}

// 退出全屏（刷新页面）
const exitFullscreen = () => {
  window.location.reload()
}

const toggleFullPage = () => {
  isFullPage.value = !isFullPage.value
}

const checkMobileAndAdapt = () => {
  // 移动端适配逻辑可以在这里添加
  // 主要通过 CSS 类名 .mobile-view 和 .mobile-content 来控制
  if (!isMobile.value) {
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

// 生命周期
onMounted(async () => {
  await getPageContent()
  checkMobileAndAdapt()

  // PC 端初始化 TOC 位置（多次重试，确保 TOC 已渲染）
  if (!isMobile.value) {
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

  // 监听窗口大小变化
  window.addEventListener('resize', checkMobileAndAdapt)

  // 移动端添加滚动监听
  window.addEventListener('scroll', handleScroll)
})

onBeforeUnmount(() => {
  // 清理定时器
  if (taskSaveTimer) {
    clearTimeout(taskSaveTimer)
  }

  // 移除事件监听
  window.removeEventListener('resize', checkMobileAndAdapt)
})

onUnmounted(() => {
  // 移除滚动监听
  window.removeEventListener('scroll', handleScroll)
})
</script>

<style lang="scss" scoped>
.show-single-page-item {
  min-height: 100vh;
  background-color: var(--color-bg-secondary);

  &.mobile-view {
    background-color: var(--color-bg-primary);
  }
}

// PC 头部样式已由 ItemHeader 组件定义，此处无额外样式

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
.content-container {
  width: 100%;
  min-height: calc(100vh - 160px);
  position: relative;

  &.mobile-content {
    min-height: calc(100vh - 70px);
  }
}

// 页面内容区（克制设计）
.page-wrapper {
  width: 850px;
  max-width: 850px;
  background-color: var(--color-bg-primary);
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  padding: 32px;
  min-height: 500px;
  margin: 110px auto 40px;
  transition: all 0.15s ease;
}

.mobile-content .page-wrapper {
  width: 95%;
  max-width: 95%;
  margin: 60px auto 20px;
  border-radius: 0;
  box-shadow: none;
  padding: 10px;
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

.mobile-content .toc-side {
  display: none;
}

// 页面标题（克制设计）
.page-title-box {
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

  .full-page-icon {
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
.page-content {
  min-height: 400px;

  .loading {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 100px 0;
  }
}

// 附件图标（克制设计）
.attachment-icon {
  position: absolute;
  top: 24px;
  right: 48px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg-secondary);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;

  &:hover {
    background: var(--hover-overlay);
    color: var(--color-active);
  }

  i {
    font-size: 16px;
    color: var(--color-text-secondary);
    transition: color 0.15s ease;
  }

  &:hover i {
    color: var(--color-active);
  }
}

// 移动端适配
.mobile-view {
  .page-wrapper {
    width: 95%;
    max-width: 95%;
    margin: 60px auto 20px;
    padding: 10px;
    border-radius: 0;
    box-shadow: none;
  }

  .page-title-box {
    height: auto;
    padding: 10px 0;

    #doc-title {
      font-size: 20px;
    }
  }

  .page_content_main {
    padding: 10px 5px 90px 5px;
  }

  .attachment-icon {
    top: 20px;
    right: 15px;
  }
}

// 移动端占位元素样式
.pos {
  height: 60px;
  width: 100%;
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

// Markdown 样式已在 EditormdEditor/themes/base.css 中统一定义
// 无需重复定义，保持样式一致性
</style>
