<template>
  <div class="item-show-container">
    <!-- 展示常规项目 (item_type: 1, 3, 0) -->
    <ShowRegularItem
      v-if="itemInfo && isRegularItem"
      :key="itemKey"
      :item-info="itemInfo"
      :search-keyword="keyword"
      @search="handleSearch"
      @reload-item="handleReloadItem"
    />

    <!-- 展示单页项目 (item_type: 2) -->
    <ShowSinglePageItem
      v-if="itemInfo && itemInfo.item_type == 2"
      :key="itemKey"
      :item-info="itemInfo"
    />

    <!-- 展示表格项目 (item_type: 4) -->
    <ShowTableItem
      v-if="itemInfo && itemInfo.item_type == 4"
      :key="itemKey"
      :item-info="itemInfo"
    />

    <!-- 展示白板项目 (item_type: 5) -->
    <ShowWhiteboardItem
      v-if="itemInfo && itemInfo.item_type == 5"
      :key="itemKey"
      :item-info="itemInfo"
    />

    <!-- 如果是处于登录态的话，则引入通知组件 -->
    <Notify v-if="itemInfo && itemInfo.is_login" :popup="true" />

    <!-- 加载中 -->
    <div v-if="loading" class="loading-container">
      <a-spin :spinning="loading" size="large" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import ShowRegularItem from './ShowRegularItem/index.vue'
import ShowSinglePageItem from './ShowSinglePageItem/index.vue'
import ShowTableItem from './ShowTableItem/index.vue'
import ShowWhiteboardItem from './ShowWhiteboardItem/index.vue'
import Notify from '@/components/Notify.vue'
import request from '@/utils/request'
import { useUserStore } from '@/store/user'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const loading = ref(false)
const itemInfo = ref<any>(null)
const keyword = ref('')
const itemKey = ref(1)
let watermarkContainer: HTMLElement | null = null

// 判断是否为常规项目
const isRegularItem = computed(() => {
  if (!itemInfo.value) return false
  const type = itemInfo.value.item_type
  return type == 1 || type == 3 || type == 0 || type == '0'
})

// 获取项目信息
const fetchItemInfo = async (searchKeyword = '') => {
  loading.value = true
  const itemId = route.params.item_id || 0
  const pageId = route.params.page_id || route.query.page_id || 0

  try {
    const params: any = {
      item_id: itemId,
      keyword: searchKeyword
    }
    if (!searchKeyword) {
      params.default_page_id = pageId
    }

    const response = await request('/api/item/info', params, 'post', false)
    loading.value = false

    if (response.error_code === 0) {
      const data = response.data

      // 处理默认页面ID
      if (data.default_page_id <= 0) {
        if (data.item_type == 1 && data.menu) {
          // 递归查找第一个可用页面（与旧前端逻辑一致）
          const firstPageId = findFirstAvailablePage(data.menu)
          if (firstPageId) {
            data.default_page_id = firstPageId
          }
        } else if (data.menu && data.menu.pages && data.menu.pages[0]) {
          data.default_page_id = data.menu.pages[0].page_id
        }
      }

      // 如果是 runapi 类型项目（item_type == 3），去掉编辑权限
      if (data.item_type == 3) {
        data.item_manage = false
        data.item_edit = false
      }

      itemInfo.value = data
      itemKey.value = itemKey.value + 1 // key自增以便重新渲染组件
      document.title = data.item_name + '--ShowDoc'

      // 如果启用水印，则渲染水印
      if (data.show_watermark > 0) {
        renderWatermark()
      }
    } else if (response.error_code === 10307 || response.error_code === 10303) {
      // 需要输入密码
      router.replace({
        path: '/item/password/' + itemId,
        query: {
          page_id: pageId,
          redirect: router.currentRoute.value.fullPath
        }
      })
    } else if (response.error_code === 10312) {
      // 强制登录
      router.replace({
        path: '/user/login/',
        query: {
          redirect: router.currentRoute.value.fullPath
        }
      })
    } else if (response.error_code === 10104) {
      // 需要输入验证码
      router.replace({
        path: '/captcha/index',
        query: {
          item_id: itemId,
          type: 'visit_item',
          redirect: router.currentRoute.value.fullPath
        }
      })
    } else {
      message.error(response.error_message)
    }
  } catch (error) {
    loading.value = false
    console.error('获取项目信息失败:', error)
  }
}

// 递归查找第一个可用的页面
const findFirstAvailablePage = (menu: any): number | null => {
  if (!menu) return null

  // 1. 先检查根目录是否有页面
  if (menu.pages && menu.pages.length > 0 && menu.pages[0]) {
    return menu.pages[0].page_id
  }

  // 2. 如果根目录没有页面，检查是否有子目录
  if (!menu.catalogs || menu.catalogs.length === 0) {
    return null
  }

  // 3. 递归查找第一个子目录的第一个页面
  const findInCatalogs = (catalogs: any[]): number | null => {
    if (!catalogs || catalogs.length === 0) return null

    for (const catalog of catalogs) {
      // 先检查当前目录是否有页面
      if (catalog.pages && catalog.pages.length > 0 && catalog.pages[0]) {
        return catalog.pages[0].page_id
      }

      // 如果当前目录没有页面，递归查找子目录
      if (catalog.catalogs && catalog.catalogs.length > 0) {
        const pageId = findInCatalogs(catalog.catalogs)
        if (pageId) return pageId
      }
    }

    return null
  }

  return findInCatalogs(menu.catalogs)
}

// 处理搜索
const handleSearch = (searchKeyword: string) => {
  keyword.value = searchKeyword
  itemInfo.value = null
  fetchItemInfo(searchKeyword)
}

// 处理重新加载项目（创建/编辑目录后）
const handleReloadItem = () => {
  fetchItemInfo(keyword.value)
}

// 格式化日期为 YYYY/MM/DD 格式
const formatDate = (date: Date): string => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}/${month}/${day}`
}

// 渲染水印
const renderWatermark = async () => {
  // 先确保本地用户信息已加载
  userStore.loadUserInfo()

  // 如果 store 中有用户信息，直接使用
  if (userStore.userInfo && userStore.userInfo.username) {
    applyWatermark(userStore.userInfo)
  } else {
    // 如果没有用户信息，尝试从网络获取
    try {
      await userStore.fetchUserInfo()
      if (userStore.userInfo && userStore.userInfo.username) {
        applyWatermark(userStore.userInfo)
      }
    } catch (error) {
      console.error('获取用户信息失败，无法显示水印:', error)
    }
  }
}

// 应用水印（轻量级实现）
const applyWatermark = (userInfo: any) => {
  let watermarkText = userInfo.username + '，' + formatDate(new Date())
  if (userInfo.name) {
    watermarkText += '，' + userInfo.name
  }

  // 延迟渲染以确保 DOM 已准备就绪
  setTimeout(() => {
    createWatermark(watermarkText)
  }, 500)
}

// 创建水印（自定义轻量级实现）
const createWatermark = (text: string) => {
  // 先移除旧的水印
  removeWatermark()

  // 创建水印容器
  const container = document.createElement('div')
  container.id = 'watermark-container'
  Object.assign(container.style, {
    position: 'fixed',
    top: '0',
    left: '0',
    width: '100%',
    height: '100%',
    pointerEvents: 'none',
    zIndex: '9999999',
    overflow: 'hidden'
  })

  // 创建水印画布
  const canvas = document.createElement('canvas')
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  // 设置画布尺寸
  const canvasWidth = 200
  const canvasHeight = 120
  canvas.width = canvasWidth
  canvas.height = canvasHeight

  // 绘制水印
  ctx.save()
  ctx.translate(canvasWidth / 2, canvasHeight / 2)
  ctx.rotate((-30 * Math.PI) / 180) // 旋转 -30 度
  ctx.font = '14px Arial'
  ctx.fillStyle = 'rgba(0, 0, 0, 0.05)' // 半透明黑色
  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'
  ctx.fillText(text, 0, 0)
  ctx.restore()

  // 转换为 data URL
  const dataUrl = canvas.toDataURL('image/png')

  // 创建背景图
  Object.assign(container.style, {
    backgroundImage: `url(${dataUrl})`,
    backgroundRepeat: 'repeat'
  })

  // 添加到页面
  document.body.appendChild(container)
  watermarkContainer = container

  // 监听 DOM 变化，防止水印被删除
  const observer = new MutationObserver(() => {
    if (!document.getElementById('watermark-container')) {
      document.body.appendChild(container)
    }
  })
  observer.observe(document.body, {
    childList: true,
    subtree: true
  })

  // 保存 observer 引用以便后续清理
  ;(container as any).observer = observer
}

// 移除水印
const removeWatermark = () => {
  const oldWatermark = document.getElementById('watermark-container')
  if (oldWatermark) {
    const observer = (oldWatermark as any).observer
    if (observer) {
      observer.disconnect()
    }
    oldWatermark.remove()
  }
}

// 监听路由变化
watch(
  () => route.params.item_id,
  () => {
    fetchItemInfo()
  }
)

onMounted(() => {
  fetchItemInfo()
})

// 组件卸载时移除水印
onBeforeUnmount(() => {
  removeWatermark()
  document.title = 'ShowDoc'
})
</script>

<style lang="scss" scoped>
.item-show-container {
  min-height: 100vh;
}

.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
}
</style>

