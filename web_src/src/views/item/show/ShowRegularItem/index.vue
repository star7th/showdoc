<template>
  <div :class="['show-regular-item', device]" v-if="showComp">
    <!-- 移动端占位元素（隐藏头部时防止内容跳变） -->
    <div v-if="showMobileHeader" class="pos"></div>

    <!-- PC端头部 -->
    <ItemHeader v-if="showPCHeader" :item-info="itemInfo">
      <template #right>
        <ItemHeaderRight
          :page-id="currentPageId"
          :item-info="itemInfo"
          :page-info="pageInfo"
          :search-item="handleSearch"
        />
      </template>
    </ItemHeader>

    <!-- 移动端头部 -->
    <MobileHeader
      v-if="showMobileHeader"
      v-model:drawerVisible="drawerVisible"
      :item-info="itemInfo"
      :search-item="handleSearch"
      :get-page-content="handleGetPageContent"
      :hide-header="hideHeader"
      :is-full-page="isFullPage"
      @exit-fullscreen="exitFullscreen"
    />

    <!-- 
      移动端目录抽屉
      注意：Ant Design Vue 3.x 使用 :visible 属性控制显示
      不要使用 v-model:open（那是 4.x 的语法）
    -->
    <a-drawer
      :visible="drawerVisible"
      @close="drawerVisible = false"
      placement="right"
      width="80%"
      :closable="true"
      :mask-closable="true"
      title="目录"
    >
      <div class="drawer-content">
        <CatalogTree
          ref="catalogTreeRef"
          :keyword="searchKeyword"
          :item-info="itemInfo"
          :get-page-content="handleGetPageContent"
          :search-item="handleSearch"
          @reload-item="handleReloadItem"
        />

        <CatalogActions
          v-if="itemInfo && isItemEditable"
          :item-id="itemInfo.item_id"
          :page-id="currentPageId"
          :page-info="pageInfo"
          :expand-all="expandAllCatalogs"
          :collapse-all="collapseAllCatalogs"
          :search-item="handleSearch"
          @reload-item="handleReloadItem"
        />
      </div>
    </a-drawer>

    <!-- 主体内容 -->
    <div class="doc-container" id="doc-container">
      <!-- 左侧目录区 -->
      <div id="left-side" v-if="showLeftSide">
        <CatalogTree
          ref="catalogTreeRef"
          :keyword="searchKeyword"
          :item-info="itemInfo"
          :get-page-content="handleGetPageContent"
          :search-item="handleSearch"
          @reload-item="handleReloadItem"
        />

        <CatalogActions
          v-if="itemInfo && isItemEditable"
          :item-id="itemInfo.item_id"
          :page-id="currentPageId"
          :page-info="pageInfo"
          :expand-all="expandAllCatalogs"
          :collapse-all="collapseAllCatalogs"
          :search-item="handleSearch"
          @reload-item="handleReloadItem"
        />
      </div>

      <!-- 中间内容区 -->
      <div id="content-side">
        <div id="p-content">
          <!-- 搜索面包屑（仅在搜索时显示） -->
          <SearchBreadcrumb
            v-if="searchKeyword && pageInfo"
            :page-info="pageInfo"
            :item-info="itemInfo"
            :keyword="searchKeyword"
          />

          <!-- 页面标题 -->
          <div class="doc-title-box">
            <span class="doc-title">{{ pageTitle }}</span>
            <!-- 草稿标签 -->
            <a-tooltip v-if="pageInfo && pageInfo.is_draft == 1" :title="$t('page.draft_tooltip')">
              <a-tag color="orange" class="draft-tag">{{ $t('page.draft') }}</a-tag>
            </a-tooltip>
            <span class="doc-actions">
              <i
                v-if="attachmentCount"
                class="far fa-paperclip attachment-icon"
                @click="handleShowAttachment"
              />
              <i
                v-if="currentPageId && !isMobile()"
                :class="isFullPage ? 'far fa-compress' : 'far fa-expand'"
                class="full-page-icon"
                @click="toggleFullPage"
              />
            </span>
          </div>

          <!-- 页面内容 -->
          <div class="doc-body">
            <div class="page-content-main" id="page-content-main">
              <!-- Markdown 内容渲染（纯预览模式） -->
              <EditormdEditor
                v-if="currentPageId"
                :key="`${currentPageId}-${isFullPage}`"
                v-model="pageContent"
                mode="preview"
                @task-toggle="handleTaskToggle"
              />

              <!-- 空项目提示 -->
              <div v-else-if="emptyItem" class="empty-tips">
                <div class="icon">
                  <i class="far fa-exclamation-triangle"></i>
                </div>
                <div class="text">
                  <p>当前项目是空的，你可以点击左下方的 + 以手动添加页面。</p>
                </div>
              </div>
            </div>

            <!-- 页面反馈 -->
            <div id="feedback-area">
              <PageFeedback
                v-if="currentPageId && itemInfo && !isMobile()"
                :page-id="currentPageId"
                :item-info="itemInfo"
              />
            </div>

            <!-- 页面评论（移动端隐藏） -->
            <div id="comment-area" v-if="!isMobile()">
              <PageComment
                v-if="currentPageId && itemInfo"
                :page-id="currentPageId"
                :item-info="itemInfo"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- 右侧目录区（TOC） -->
      <div id="right-side" v-if="!isMobile() && !isFullPage">
        <Toc :key="tocKey" />
      </div>
    </div>

    <!-- 回到顶部 -->
    <CommonTop :visibilityHeight="100" />

    <!-- AI 聊天对话框（仅在项目开启了 AI 知识库时显示） -->
    <AiChatDialog
      v-if="aiEnabled && itemInfo?.item_id"
      :item-id="itemInfo.item_id"
      :item-name="itemInfo.item_name"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useItemStore } from '@/store'
import { renderPageContent } from '@/models/page'
import { toggleNthTaskCheckbox } from '@/models/markdown'
import { getItem } from '@/models/item'
import request from '@/utils/request'
// 引入 ShowDoc 编辑器适配器（包装底层 EditormdEditor 组件）
// 适配器提供了 ShowDoc 特定的默认配置和事件处理
import EditormdEditor from '@/components/EditormdEditor/ShowdocAdapter.vue'
import AlertModal from '@/components/AlertModal'
import CommonTop from '@/components/CommonTop.vue'
import ItemHeader from '@/views/item/components/ItemHeader.vue'
import ItemHeaderRight from './HeaderRight.vue'
import MobileHeader from '@/views/item/components/MobileHeader.vue'
import CatalogTree from './CatalogTree.vue'
import CatalogActions from './CatalogActions.vue'
import SearchBreadcrumb from '@/components/SearchBreadcrumb.vue'
import AttachmentListModal from '@/views/modals/page/AttachmentListModal/index'
import PageComment from './components/PageComment.vue'
import PageFeedback from './components/PageFeedback.vue'
import Toc from '@/components/Toc.vue'
import AiChatDialog from '@/components/AiChatDialog.vue'

// Props
interface Props {
  itemInfo?: any
}

const props = withDefaults(defineProps<Props>(), {
  itemInfo: () => ({}),
})

// Emits
const emit = defineEmits<{
  reloadItem: []
}>()

// Composables
const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const itemStore = useItemStore()

// Refs
const catalogTreeRef = ref()
const showComp = ref(true)
const showPCHeader = ref(true)
const showMobileHeader = ref(false)
const showLeftSide = ref(true)
const device = ref<'pc' | 'mobile'>('pc')
const drawerVisible = ref(false) // 移动端目录抽屉显示状态
const hideHeader = ref(false) // 移动端隐藏头部
const _lastScrollTop = ref(0) // 记录上次滚动位置
const currentPageId = ref<number>(0)
const pageTitle = ref('')
const pageContent = ref('')
const pageInfo = ref<any>(null)
const catId = ref(0)
const itemId = ref(0)
const attachmentCount = ref(0)
const searchKeyword = ref('')
const isFullPage = ref(false) // 全屏模式
const emptyItem = ref(false)
const itemInfo = ref<any>(props.itemInfo || {})
const _taskSaveTimer = ref<number | null>(null)
const tocKey = ref(0) // 用于强制重新挂载 Toc 组件
const _lastFetchTime = ref<Record<number, number>>({}) // 记录每个页面ID上次请求时间
const aiEnabled = ref(false) // 项目是否开启了 AI 知识库
const lastLoadedPageId = ref(0) // 记录上一次加载的页面ID，用于移动端抽屉控制

// Computed
// 判断是否为移动设备或全屏模式
const isMobile = () => window.innerWidth < 768 || isFullPage.value
const isItemEditable = computed(() => {
  // 使用弱等于判断，因为后端可能返回字符串
  return itemInfo.value?.item_edit == 1
})

// 检测项目是否开启了 AI 知识库
const checkAiEnabled = async () => {
  if (!itemInfo.value?.item_id) {
    return
  }

  try {
    const res = await request('/api/item/getAiKnowledgeBaseConfig', {
      item_id: itemInfo.value.item_id,
    })

    if (res.error_code === 0 && res.data) {
      let enabled = false

      // 兼容两种返回格式
      if (res.data.ai_config) {
        // 旧版接口格式：data.ai_config.enabled
        enabled = res.data.ai_config.enabled > 0
      } else if (res.data.enabled !== undefined) {
        // 新版接口格式：data.enabled
        enabled = res.data.enabled > 0
      } else if (res.data.ai_knowledge_base_enabled !== undefined) {
        // 兼容格式：data.ai_knowledge_base_enabled
        enabled = res.data.ai_knowledge_base_enabled > 0
      }

      aiEnabled.value = enabled
    } else {
      aiEnabled.value = false
    }
  } catch (error) {
    aiEnabled.value = false
  }
}

// Methods
// 高亮文本中的关键词
const highlightKeyword = (text: string, keyword: string): string => {
  if (!keyword || !text) return text

  // 转义正则表达式特殊字符
  const escapedKeyword = keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  const regex = new RegExp(`(${escapedKeyword})`, 'gi')

  return text.replace(regex, '<mark>$1</mark>')
}

const handleGetPageContent = async (pageId: number) => {
  if (pageId <= 0) return

  // 移动端：只有页面ID变化时才关闭抽屉
  // 这样可以避免 CatalogTree 初始化时自动加载默认页面导致抽屉关闭
  if (isMobile() && drawerVisible.value && pageId !== lastLoadedPageId.value) {
    drawerVisible.value = false
  }

  // 记录当前加载的页面ID
  lastLoadedPageId.value = pageId

  // 防重复请求：1秒内同一页面ID只请求一次
  const now = Date.now()
  const lastTime = _lastFetchTime.value[pageId] || 0
  if (now - lastTime < 1000) {
    return
  }
  _lastFetchTime.value[pageId] = now

  adaptScreen()

  // 只在搜索模式下请求路径信息
  const hasKeyword = !!searchKeyword.value
  const params: any = {
    page_id: pageId,
  }

  if (hasKeyword) {
    params.with_path = 1
  }

  try {
    const data = await request('/api/page/info', params, 'post', false)

    if (data.error_code === 0 && data.data) {
      let content = renderPageContent(
        data.data.page_content || '',
        itemInfo.value.global_param
      )

      // 如果是搜索结果，对内容进行关键词高亮
      if (hasKeyword && searchKeyword.value) {
        content = highlightKeyword(content, searchKeyword.value)
      }

      pageContent.value = content

      itemStore.setOpenCatId(data.data.cat_id)
      pageTitle.value = data.data.page_title

      // 标记为搜索结果
      if (hasKeyword) {
        data.data.from_search_result = true
      }

      pageInfo.value = data.data
      catId.value = data.data.cat_id
      itemId.value = data.data.item_id
      attachmentCount.value = data.data.attachment_count || 0

      // 更新URL (使用Hash模式)
      const domain = itemInfo.value.item_domain || itemInfo.value.item_id
      const newPath = `/${domain}/${pageId}`
      if (route.path !== newPath) {
        router.replace(newPath)
      }

      // 延迟更新 currentPageId，避免组件频繁卸载/挂载
      setTimeout(() => {
        currentPageId.value = pageId
        handleAnchorScroll()
        document.title = `${pageTitle.value}--ShowDoc`

        // 滚动目录树到选中页面
        if (catalogTreeRef.value?.scrollToPage && !searchKeyword.value) {
          catalogTreeRef.value.scrollToPage(pageId)
        }
      }, 0)
    }
  } catch (error) {
    console.error('获取页面内容失败:', error)
    await AlertModal(t('fetch_page_content_failed'))
  }
}

const handleShowAttachment = async () => {
  if (pageInfo.value) {
    await AttachmentListModal({
      itemId: pageInfo.value.item_id,
      pageId: pageInfo.value.page_id,
      manage: false,
      onClose: () => {},
    })
  }
}

const handleSearch = async (keyword: string) => {
  searchKeyword.value = keyword

  if (keyword) {
    const searchResultPage = {
      page_title: `${t('search_results_for')} "${keyword}"`,
      cat_id: 0,
      is_search_result: true,
    }
    pageInfo.value = searchResultPage
    pageTitle.value = searchResultPage.page_title

    // 搜索项目：调用 /api/item/info 接口带 keyword 参数
    try {
      const itemId = itemInfo.value?.item_id
      if (itemId) {
        const res = await request(
          '/api/item/info',
          {
            item_id: itemId,
            keyword: keyword,
          },
          'post',
          false
        )
        if (res.error_code === 0 && res.data) {
          // 更新项目信息（包含搜索后的目录树）
          itemInfo.value = res.data
          // 清空当前页面内容
          pageContent.value = ''
          currentPageId.value = 0
          // 重新检测 AI 配置
          checkAiEnabled()
        }
      }
    } catch (error) {
      console.error('搜索项目失败:', error)
    }
  } else {
    // 清空搜索：重新加载原始项目数据
    if (!currentPageId.value) {
      pageInfo.value = null
    }
    try {
      const itemId = itemInfo.value?.item_id
      if (itemId) {
        const res = await getItem(String(itemId))
        if (res.error_code === 0 && res.data) {
          // 恢复原始项目信息
          itemInfo.value = res.data
          // 加载默认页面
          if (res.data.default_page_id) {
            await handleGetPageContent(res.data.default_page_id)
          }
        }
      }
    } catch (error) {
      console.error('加载项目失败:', error)
    }
  }
}

const handleTaskToggle = ({
  index,
  checked,
}: {
  index: number
  checked: boolean
}) => {
  if (!itemInfo.value?.item_edit) return

  pageContent.value = toggleNthTaskCheckbox(pageContent.value, index, checked)
  scheduleSave()
}

const scheduleSave = () => {
  if (_taskSaveTimer.value) {
    clearTimeout(_taskSaveTimer.value)
  }
  _taskSaveTimer.value = window.setTimeout(async () => {
    if (!currentPageId.value) return

    try {
      await request(
        '/api/page/save',
        {
          page_id: currentPageId.value,
          item_id: itemInfo.value.item_id,
          cat_id: catId.value,
          page_title: pageTitle.value,
          is_urlencode: 1,
          page_content: encodeURIComponent(pageContent.value),
        },
        'post',
        false
      )
    } catch (error) {
      console.error('保存任务列表失败:', error)
    }
  }, 800)
}

const expandAllCatalogs = () => {
  if (catalogTreeRef.value) {
    catalogTreeRef.value.expandAll()
  }
}

const collapseAllCatalogs = () => {
  if (catalogTreeRef.value) {
    catalogTreeRef.value.collapseAll()
  }
}

const handleReloadItem = () => {
  emit('reloadItem')
}

const adaptToMobile = () => {
  showPCHeader.value = false
  showMobileHeader.value = true
  showLeftSide.value = false
  device.value = 'mobile'
}
// 移动端滚动控制头部显示/隐藏
const handleScroll = () => {
  if (!isMobile()) return

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

const adaptScreen = () => {
  if (isMobile()) {
    adaptToMobile()
  }
}

const toggleFullPage = () => {
  isFullPage.value = !isFullPage.value

  if (isFullPage.value) {
    // 进入全屏模式：切换到移动端，自动利用现有的移动端适配逻辑
    adaptToMobile()
    // 移动端模式下 hideHeader 会在滚动时自动控制
  } else {
    // 退出全屏：恢复 PC 端
    showPCHeader.value = true
    showMobileHeader.value = false
    showLeftSide.value = true
    device.value = 'pc'
    hideHeader.value = false // 重置隐藏状态
  }
}

// 退出全屏（刷新页面）
const exitFullscreen = () => {
  window.location.reload()
}

const handleAnchorScroll = () => {
  const hash = window.location.hash
  if (hash === '#comment-area' || hash === '#feedback-area') {
    setTimeout(() => {
      const element = document.querySelector(hash)
      if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' })
      }
    }, 1500)
  } else {
    document.body.scrollTop = document.documentElement.scrollTop = 0
  }
}

// Watchers
watch(
  () => props.itemInfo,
  (newItemInfo) => {
    if (newItemInfo) {
      itemInfo.value = newItemInfo

      // 检查是否为空项目
      if (
        newItemInfo.menu &&
        newItemInfo.menu.catalogs &&
        newItemInfo.menu.catalogs.length === 0 &&
        newItemInfo.menu.pages &&
        newItemInfo.menu.pages.length === 0
      ) {
        emptyItem.value = true
      } else {
        emptyItem.value = false
      }

      // 检测项目是否开启了 AI 知识库
      // 使用 setTimeout 确保 itemInfo 已经更新
      setTimeout(() => {
        checkAiEnabled()
      }, 100)
    }
  },
  { immediate: true }
)

// 监听页面内容变化，重新生成 TOC
watch(
  () => pageContent.value,
  (newContent, oldContent) => {
    // 只有当内容真正变化时才重新挂载 TOC
    if (newContent !== oldContent && newContent) {
      tocKey.value++
    }
  }
)

// Lifecycle
onMounted(() => {
  adaptScreen()

  // 移动端添加滚动监听
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  // 移除滚动监听
  window.removeEventListener('scroll', handleScroll)
})
</script>

<style scoped lang="scss">
.show-regular-item {
  min-height: 100vh;
}

.doc-container {
  display: flex;
  gap: 24px; // 适度增加间距（原来可能是 20px）
  padding: 110px 24px 24px 24px;
  min-height: 750px;
  max-width: 1400px;
  margin: 0 auto;
  align-items: flex-start;
}

#left-side {
  width: 280px;
  min-width: 280px;
  background-color: var(--color-bg-secondary);
  border-radius: 8px;
  overflow-x: hidden;
  overflow-y: hidden;
  position: sticky;
  top: 100px;
  height: calc(100vh - 140px);
  align-self: flex-start;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border: 1px solid var(--color-border);

  // 让 CatalogActions 可以相对于此定位
  display: flex;
  flex-direction: column;

  // CatalogTree 占据剩余空间，并处理滚动
  :deep(.catalog-tree) {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    min-height: 0; // 确保滚动条正常显示
  }

  [data-theme='dark'] & {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  }
}

#content-side {
  flex: 1;
  min-width: 0;
  max-width: 900px;
  background-color: var(--color-bg-primary);
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  padding: 24px 32px 32px 32px;
  min-height: calc(100vh - 140px);
  border: 1px solid var(--color-border);

  [data-theme='dark'] & {
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
  }
}

#right-side {
  width: 260px;
  min-width: 260px;
  flex-shrink: 0;
  position: sticky;
  top: 100px;
  height: fit-content;
  max-height: calc(100vh - 140px);
  display: flex;
  align-items: flex-start;
}

#p-content {
  min-height: 100%;
}

.doc-title-box {
  border-bottom: 1px solid var(--color-border);
  padding-bottom: 20px;
  padding-top: 0;
  margin-bottom: 24px;
  text-align: center;
  position: relative;
}

.doc-title {
  font-size: 26px; // 适度增大（原来 24px）
  font-weight: 600;
  color: var(--color-text-primary);
  line-height: 1.4;
}

// 草稿标签
.draft-tag {
  margin-left: 12px;
  font-size: 12px;
  line-height: 20px;
  padding: 0 8px;
  border-radius: 4px;
  vertical-align: middle;
}

.doc-actions {
  float: right;
  margin-top: -2px;
  display: flex;
  gap: 8px;
}

.attachment-icon,
.full-page-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  cursor: pointer;
  color: var(--color-text-secondary);
  background: var(--color-bg-secondary);
  border-radius: 6px;
  transition: all 0.15s ease;

  &:hover {
    background: var(--hover-overlay);
    color: var(--color-active);
  }
}

.doc-body {
  margin: 0;
}

.page-content-main {
  margin-bottom: 60px;

  // Markdown 内容样式已移至 EditormdEditor/themes/base.css
  // 可通过该文件复用到其他项目（如 RunAPI）
}

.empty-tips {
  margin: 5% auto;
  width: 400px;
  text-align: center;
  min-height: 50vh;
  padding: 40px;
  opacity: 0.7;
}

.empty-tips .icon {
  font-size: 80px;
  margin-bottom: 20px;
  color: var(--color-text-secondary);
}

.empty-tips .text {
  text-align: center;
  color: var(--color-text-secondary);
  font-size: 14px;
  line-height: 1.6;
}

#feedback-area,
#comment-area {
  margin-top: 40px;
  padding-top: 20px;
  border-top: 1px solid var(--color-border);
}

// 移动端适配
.mobile .doc-container {
  padding: 60px 16px 20px 16px;
  display: block;
  width: 100%;
  max-width: none;
}

.mobile #left-side {
  display: none;
}

.mobile #content-side {
  margin-left: 0;
  min-width: auto;
  max-width: none;
  width: 100%;
  padding: 20px 16px;
}

.mobile #right-side {
  display: none;
}

.mobile .doc-title {
  font-size: 20px;
}

.mobile .doc-body {
  margin: 0;
}

// 响应式优化
@media (max-width: 1200px) {
  #right-side {
    display: none !important;
  }

  #content-side {
    max-width: none;
  }
}

@media (max-width: 1100px) {
  #left-side {
    display: none;
  }

  #content-side {
    max-width: none;
    width: 100%;
  }
}

@media (max-width: 768px) {
  .show-regular-item {
    .mobile {
      &.doc-container {
        padding: 60px 10px 20px 10px;
      }
    }
  }
}

// 抽屉内容样式
.drawer-content {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding-bottom: 0;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch; /* 为iOS设备提供平滑滚动 */

  // 确保目录树在抽屉中可以正常滚动
  :deep(.catalog-tree) {
    flex: 1;
    min-height: 0;
    overflow: visible; // 让 tree-container 处理滚动
  }

  :deep(.tree-container) {
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
  }
}

:deep(.ant-drawer-body) {
  padding: 0;
}

// 移动端占位元素样式
.pos {
  height: 60px;
  width: 100%;
}
</style>
