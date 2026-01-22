<template>
  <div class="item-home">
    <!-- 头部 -->
    <div class="header">
      <div class="logo ml-10" @click="reload">
          <div class="logo-img-div">
            <img
              onerror="this.style.display='none';"
              class="logo-img"
            src="@/assets/Logo.svg"
              alt="ShowDoc"
            />
          </div>
        <div class="">
            <div class="font-bold logo-title">ShowDoc</div>
            <div class="v3-color-aux logo-desc">
            {{ t('index.home_logo_title') }}
          </div>
        </div>
      </div>
      <HeaderRight :isAdmin="isAdmin" />
    </div>

    <!-- 通知检测（只更新右上角标记，不弹窗） -->
    <Notify v-show="userStore.isLoggedIn" :popup="false" />

    <!-- 主容器 -->
    <div class="container" :class="{ 'card-view-mode': viewMode === 'card' }">
      <!-- 左侧分组 -->
      <div class="left-side">
        <div class="all-star-item-group">
          <div
            :class="itemGroupId === 0
              ? 'item-one-block item-one-block-active'
              : 'item-one-block'
            "
            @click="changeGroup(0)"
          >
            <div class="item-one-block-content">
              <i class="mr-1 fas fa-notes"></i>
              {{ t('item.all_items') }}
            </div>
          </div>
          <div
            :class="itemGroupId === -1
              ? 'item-one-block item-one-block-active'
              : 'item-one-block'
            "
            @click="changeGroup(-1)"
          >
            <div class="item-one-block-content">
              <i class="mr-1 fas fa-star v3-color-yellow"></i>
              {{ t('item.star_items') }}
            </div>
          </div>
          <div
            v-if="publicSquareEnabled"
            class="item-one-block public-square-link"
            @click="openPublicSquare"
          >
            <div class="item-one-block-content">
              <i class="mr-1 fas fa-landmark"></i>
              {{ t('item.public_square') }}
            </div>
          </div>
        </div>
        <a-divider class="item-group-divider" />
        <div class="divider-item-block">
          <div class="divider-text">{{ t('item.group') }}</div>
          <div class="divider-icon" @click="showItemGroupCom = true">
            <a-tooltip :title="t('item.item_group_desc')" placement="top">
              <i class="fas fa-plus mr-1"></i>
            </a-tooltip>
          </div>
        </div>
        <div>
          <div
            v-for="one in itemGroupList"
            :key="one.item_id"
            :class="itemGroupId == one.id
              ? 'item-one-block item-one-block-active'
              : 'item-one-block'
            "
            @click="changeGroup(one.id)"
          >
            <div class="item-one-block-content">
              <i class="mr-1 far fa-hashtag v3-font-size-sm"></i>
              {{ one.group_name }}
            </div>
          </div>
        </div>
        <div v-if="locale === 'zh-CN'" class="left-bottom-bar">
          <div class="content">
            <i class="far fa-fire"></i>
            调试API并自动生成文档
            <a class="text-link ml-2" @click="toOutLink('https://www.showdoc.com.cn/runapi')">试试</a>
          </div>
        </div>
      </div>

      <!-- 右侧内容 -->
      <div class="right-side">
        <!-- 搜索区域 -->
        <div class="search-and-view-control">
          <div class="search-box-div">
            <div class="search-box">
              <CommonInput
                v-model="keyword"
                :placeholder="t('item.search_keyword')"
                allow-clear
                autocomplete="off"
                class="search-input"
              >
                <template #prefix>
                  <i class="fas fa-search"></i>
                </template>
              </CommonInput>
            </div>
          </div>
        </div>

        <div class="divider-item-block mt-3 mb-3">
          <div class="divider-text">{{ selectedGroupName }}</div>
          <!-- 视图切换按钮 -->
          <div class="view-switcher">
            <a-tooltip :title="t('item.list_view')" placement="top">
              <div
                class="view-switch-btn"
                :class="{ active: viewMode === 'list' }"
                @click="viewMode = 'list'"
              >
                <i class="fas fa-list-ul"></i>
              </div>
            </a-tooltip>
            <a-tooltip :title="t('item.card_view')" placement="top">
              <div
                class="view-switch-btn"
                :class="{ active: viewMode === 'card' }"
                @click="viewMode = 'card'"
              >
                <i class="fas fa-th-large"></i>
              </div>
            </a-tooltip>
          </div>
        </div>

        <!-- 项目列表组件  -->
        <ItemListCom
          v-if="!loading && !showSearch && viewMode === 'list'"
          :itemList="itemList"
          :getItemList="getItemList"
          :itemGroupId="itemGroupId"
        />

        <!-- 项目卡片视图组件 -->
        <ItemCardList
          v-if="!loading && !showSearch && viewMode === 'card'"
          :itemList="itemList"
          :getItemList="getItemList"
          :itemGroupId="itemGroupId"
        />

        <!-- 加载状态 -->
        <div v-if="loading && !showSearch" class="loading-container">
          <a-spin size="large" />
        </div>

        <!-- 搜索结果列表组件 -->
        <Search
          v-if="showSearch"
          :keyword="keyword"
          :itemList="itemList"
        />

        <!-- 新建项目按钮 -->
        <div
          class="content-wrapper"
          :class="{ 'card-view-mode': viewMode === 'card' }"
        >
          <ItemAdd
            :itemGroupId="itemGroupId"
            :callback="() => { getItemList() }"
          />
        </div>
      </div>
    </div>

    <!-- 项目分组管理弹窗 -->
    <ItemGroupCom
      v-if="showItemGroupCom"
      :callback="() => { showItemGroupCom = false; getItemGroupList() }"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/store/user'
import CommonInput from '@/components/CommonInput.vue'
import { getMyList } from '@/models/item'
import { getGroupList } from '@/models/itemGroup'
import HeaderRight from './HeaderRight.vue'
import ItemListCom from './ItemList.vue'
import ItemCardList from './ItemCardList.vue'
import Search from './Search.vue'
import ItemAdd from './ItemAdd.vue'
import ItemGroupCom from './ItemGroup.vue'
import { checkPublicSquareEnabled } from '@/models/publicSquare'
import Message from '@/components/Message'
import Notify from '@/components/Notify.vue'
import request from '@/utils/request'

const { t, locale } = useI18n()
const router = useRouter()
const userStore = useUserStore()

// 数据状态
const currentDate = ref(new Date())
const itemList = ref<any[]>([])
const isAdmin = ref(false)
const keyword = ref('')
const showSearch = ref(false)
const itemGroupId = ref<number>(0)
const itemGroupList = ref<any[]>([])
const showItemGroupCom = ref(false)
const viewMode = ref<'list' | 'card'>('card')
const publicSquareEnabled = ref(false)
const loading = ref(false) // 加载状态

// 计算属性：已选中的分组名字
const selectedGroupName = computed(() => {
  if (keyword.value) {
    if (locale.value === 'en-US') {
      return `Search results with "${keyword.value}"`
    } else {
      return `含有"${keyword.value}"的搜索结果`
    }
  }
  if (itemGroupId.value === 0) {
    return t('item.all_items')
  }
  if (itemGroupId.value === -1) {
    return t('item.star_items')
  }
  const found = itemGroupList.value.find(
    (element: any) => parseInt(element.id) == itemGroupId.value
  )
  return found ? found.group_name : ''
})

// 监听搜索词的变化
watch(keyword, (val) => {
  if (val) {
    // 当输入的字符只有一个长度的时候，是中文才会搜索。英文或者数字不会搜索
    if (val && val.length === 1) {
      // 验证是否是中文
      var pattern = new RegExp('[\u4E00-\u9FA5]+')
      if (pattern.test(val)) {
        // alert('该字符串是中文')
        showSearch.value = true
      }
    } else {
      showSearch.value = true
    }
  } else {
    showSearch.value = false
  }
})

// 监听视图模式变化，保存到本地存储
watch(viewMode, (val) => {
  localStorage.setItem('defaultViewMode', val)
})

// 获取项目列表
const getItemList = async () => {
  loading.value = true
  try {
    const data = await getMyList(itemGroupId.value)
    if (data && data.data) {
      itemList.value = data.data
    }
  } finally {
    loading.value = false
  }
}

// 获取项目分组列表
const getItemGroupList = async () => {
  const data = await getGroupList()
  if (data && data.data) {
    itemGroupList.value = data.data
    const deaultItemGroupId = localStorage.getItem('deaultItemGroupId')
    // 循环判断记住的id是否还存在列表中
    itemGroupList.value.forEach((element: any) => {
      if (element.id == deaultItemGroupId) {
        itemGroupId.value = parseInt(deaultItemGroupId)
      }
    })
    getItemList()
  }
}

// 切换分组
const changeGroup = (id: number) => {
  itemGroupId.value = id
  localStorage.setItem('deaultItemGroupId', String(id))
  showSearch.value = false // 如果正在展示搜索结果，则切换分组时候，还原
  keyword.value = ''
  getItemList() // 重新获取列表
}

// 刷新页面
const reload = () => {
  window.location.reload()
}

// 刷新用户信息并检查管理员权限
const refreshUserInfo = async () => {
  try {
    // 强制从 API 获取最新用户信息
    await fetchUserInfo()
  } catch (error) {
    console.error('Refresh user info failed:', error)
  }
}

// 获取用户信息 API
const fetchUserInfo = async () => {
  try {
    const data = await request('/api/user/info', {})
    if (data && data.error_code === 0) {

      // 检查管理员权限
      if (data.data.groupid == 1) {
        isAdmin.value = true
      }
    }
  } catch (error) {
    console.error('Fetch user info failed:', error)
  }
}

// 跳转到外部链接
const toOutLink = (url: string) => {
  window.open(url)
}

// 打开公共广场
const openPublicSquare = () => {
  router.push({   path: '/public-square/index' })
}

onMounted(async () => {
  console.log('[ItemHome] 页面加载')
  console.log('[ItemHome] userStore.isLoggedIn:', userStore.isLoggedIn)
  console.log('[ItemHome] userStore.userInfo:', userStore.userInfo)
  
  // 从本地存储加载用户信息（同步）
  userStore.loadUserInfo()
  
  console.log('[ItemHome] 加载后 userStore.isLoggedIn:', userStore.isLoggedIn)
  console.log('[ItemHome] 加载后 userStore.userInfo:', userStore.userInfo)
  
  // 获取默认的分组ID
  const deaultItemGroupId = localStorage.getItem('deaultItemGroupId')
  if (deaultItemGroupId === null) {
    itemGroupId.value = 0
  } else {
    itemGroupId.value = parseInt(deaultItemGroupId)
  }

  // 获取本地存储的视图模式
  const defaultViewMode = localStorage.getItem('defaultViewMode')
  if (defaultViewMode) {
    viewMode.value = defaultViewMode as 'list' | 'card'
  }

  getItemGroupList()
  refreshUserInfo()

  // 检查公共广场是否启用
  try {
    const data = await checkPublicSquareEnabled()
    publicSquareEnabled.value = data && data.data && data.data.enable === 1
  } catch (error) {
    publicSquareEnabled.value = false
  }
})
</script>

<style scoped lang="scss">
.item-home {
  min-height: 100vh;
  background-color: var(--color-bg-primary);
}

// Header（克制设计）
.header {
  height: 80px;
  border-bottom: 1px solid var(--color-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 32px;
  background: var(--color-bg-primary);
}

.logo-area {
  display: flex;
  align-items: center;
  cursor: pointer;
  height: 90px;
}

.logo {
  display: flex;
  align-items: center;
  cursor: pointer;
  height: 80px;
  transition: opacity 0.15s ease;
  
  &:hover {
    opacity: 0.8;
  }
}

.logo-img-div {
  display: flex;
  align-items: center;
  margin-right: 10px;
}

.logo-img {
  width: 50px;
  height: 50px;
}

.logo-title {
  font-size: 20px;
  margin-bottom: 10px;
}

.logo-desc {
  font-size: 10px;
}

.el-dropdown-link,
a {
  color: var(--color-text-primary);
}

.container {
  display: flex;
  max-width: 870px;
  margin: 0 auto;
  transition: max-width 0.15s ease;
}

/* 卡片视图模式下容器变宽 */
.container.card-view-mode {
  max-width: 1050px;
}

.left-side {
  width: 230px;
  padding-top: 40px;
  border-right: 1px solid var(--color-border);
  min-height: calc(100vh - 150px);
  flex-shrink: 0;
  background-color: var(--color-bg-primary);
}

/* 卡片视图下左侧侧边栏可以更窄一些 */
.card-view-mode .left-side {
  width: 200px;
}

// 左侧分组项（克制设计）
.item-one-block {
  height: 36px;
  position: relative;
  border-radius: 6px;
  cursor: pointer;
  padding: 0 12px;
  margin-bottom: 4px;
  transition: all 0.15s ease;
  
  &:hover {
    background-color: var(--hover-overlay);
  }
}

.item-one-block-active {
  background-color: rgba(0, 123, 255, 0.08);
  position: relative;
  
  &::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 20px;
    background: var(--color-active);
    border-radius: 0 2px 2px 0;
  }
}

.item-one-block-content {
  padding-left: 5px;
  position: absolute;
  top: 50%;
  transform: translate(0, -50%);
}

.item-group-divider {
  margin: 8px 0;
}

.divider-text {
  font-size: 11px;
  color: var(--color-text-secondary);
  margin-left: 1px;
}

.divider-icon {
  font-size: 11px;
  color: var(--color-text-secondary);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  margin-left: 5px;
  padding: 4px 6px;
  border-radius: 4px;
  transition: all 0.15s ease;

  &:hover {
    background-color: var(--hover-overlay);
    // 图标颜色保持不变
  }
}

.right-side {
  padding-top: 40px;
  padding-left: 20px;
  flex: 0 1 auto;  /* 改为手动控制伸缩 */
  width: calc(100% - 250px);  /* 与旧版完全一致：870 - 250 = 620px */
  box-sizing: border-box;
}

/* 卡片视图下右侧内容区更宽 */
.card-view-mode .right-side {
  padding-left: 30px;
  width: calc(100% - 220px);
}

.search-and-view-control {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.search-box-div {
  flex: 1;
}

.search-box {
  width: 100%;
  border-radius: 8px;
  overflow: hidden;
  background-color: var(--color-bg-primary);
}

.search-input {
  width: 100%;
  max-width: 600px;
  transition: max-width 0.15s ease;
}

.card-view-mode .search-input {
  max-width: 100%;
}

// 搜索输入框（克制设计）
.search-input :deep(.ant-input-affix-wrapper) {
  height: 40px !important;
  padding: 0;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  box-sizing: border-box;
  transition: all 0.15s ease;
  
  &:hover {
    border-color: var(--color-active);
  }
  
  &:focus-within {
    border-color: var(--color-active);
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
  }
}

.search-input :deep(.ant-input) {
  border: none;
  padding: 0;
  box-shadow: none;
  // 背景色统一走全局样式
}

.search-input :deep(.ant-input) input {
  height: 40px !important;
  padding-left: 40px !important;
  font-size: 14px !important;
  border: none !important;
  box-shadow: none !important;
  line-height: 40px;
  color: var(--color-text-primary);
  // 背景色统一走全局样式
}

// 占位符颜色适配主题
.search-input :deep(.ant-input) input::placeholder {
  color: var(--color-text-secondary);
}

// 修复搜索框前缀图标样式
.search-input :deep(.ant-input-prefix) {
  .fas.fa-search {
    font-size: 16px;
    line-height: 40px;
    margin-left: 5px;
    color: rgba(0, 0, 0, 0.3);
  }
}

// 暗黑主题下图标颜色
[data-theme='dark'] .search-input :deep(.ant-input-prefix) .fas.fa-search {
  color: rgba(255, 255, 255, 0.3);
}

.divider-item-block {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 15px;
  margin-bottom: 20px;
  border-bottom: 1px solid var(--color-border);
  padding-bottom: 10px;
}

.view-switcher {
  display: flex;
  align-items: center;
}

// 视图切换按钮（克制设计）
.view-switch-btn {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  cursor: pointer;
  margin-left: 6px;
  background: var(--color-bg-primary);
  color: var(--color-text-secondary);
  transition: all 0.15s ease;
  
  &:hover {
    background: var(--hover-overlay);
    // 颜色保持不变，只在 active 时变蓝
  }
  
  &.active {
    background: rgba(0, 123, 255, 0.08);
    color: var(--color-active);
  }
}

.content-wrapper {
  position: relative;
  width: 100%;
  min-height: 100px;
}

/* 卡片视图模式下的内容包装器 */
.content-wrapper.card-view-mode {
  width: 100%;
}

// 加载容器样式
.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
}

.public-square-link {
  margin-top: 5px !important;
  margin-bottom: 5px !important;
}

.public-square-link:hover {
  background-color: var(--color-bg-secondary) !important;
}

.left-bottom-bar {
  position: fixed;
  bottom: 35px;
  left: calc((100% - 870px) / 2);
  display: flex;
  justify-content: center;
  align-items: center;
  width: 230px;
  z-index: 10;
  transition: all 0.15s ease;
}

/* 卡片视图模式下的底部元素 */
.card-view-mode .left-bottom-bar {
  left: calc((100% - 1050px) / 2);
  width: 200px;
}

.left-bottom-bar .content {
  width: 200px;
  height: 30px;
  line-height: 30px;
  background-color: var(--color-yellow-bg);
  font-size: 12px;
  color: var(--color-yellow-text);
  border: var(--color-yellow-border);
  border-radius: 8px;
}

.left-bottom-bar .content .text-link {
  font-size: 12px;
  color: var(--color-yellow-text);
  cursor: pointer;
  text-decoration: underline;
}

</style>

<!-- 非 scoped 样式用于强制覆盖 Ant Design 默认样式 -->
<style lang="scss">
.search-box .ant-input-affix-wrapper {
  border-radius: 8px !important;
}
</style>
