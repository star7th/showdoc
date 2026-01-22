<template>
  <div class="page">
    <div class="item-list">
      <div
        class="item-list-one"
        v-for="item in itemResultList"
        :key="item.item_id"
      >
        <div class="item-title" @click="toOneItem(item)">
          <div class="left float-left">
            <i class="item-icon fas fa-file"></i>
            <TextHighlight :queries="queries">
              {{ item.item_name }}
            </TextHighlight>
          </div>
        </div>
        <div class="item-page-content">
          <div v-for="onePage in item.pages" :key="onePage.page_id">
            <div @click="toOnePage(onePage)" class="page-title">
              <i class="item-icon fas fa-file"></i>
              <TextHighlight :queries="queries">
                {{ onePage.page_title }}
              </TextHighlight>
            </div>
            <div class="search-content break-all">
              <TextHighlight :queries="queries">
                {{ onePage.search_content }}
              </TextHighlight>
            </div>
          </div>
        </div>
      </div>
      
      <!-- 底部加载提示 -->
      <div class="loading-indicator" v-if="loading">
        <a-spin />
        <span>{{ $t('common.searching') }}</span>
      </div>
    </div>

    <a-empty v-if="itemResultList.length === 0 && !loading" :description="$t('item.search_no_results')" />
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { searchItem } from '@/models/item'
import TextHighlight from '@/components/TextHighlight.vue'

const router = useRouter()

// Props
const props = defineProps<{
  keyword: string
  itemList: any[]
}>()

// 数据状态
const loading = ref(false)
const queries = ref<string[]>([])
const itemResultList = ref<any[]>([])

// 监听关键词变化
watch(() => props.keyword, (val) => {
  queries.value = []
  queries.value.push(val)
  itemResultList.value = []
  searchItems()
})

// 搜索项目
const searchItems = async () => {
  loading.value = true
  for (let index = 0; index < props.itemList.length; index++) {
    const element = props.itemList[index]
    // 先初始化几个变量，保存状态
    let isInItemName = 0 // 关键字是否存在项目标题中
    let isInPages = 0 // 关键字是否存在页面内容中
    let pages: any[] = [] // 含有关键字的页面
    if (
      element &&
      element.item_name &&
      element.item_name.indexOf(props.keyword) > -1
    ) {
      isInItemName = 1
    }

    // 远程搜索，按项目，一个个项目搜索
    try {
      const res = await searchItem(props.keyword, element.item_id)
      const json = res.data
      if (json && json.pages && json.pages.length > 0) {
        isInPages = 1
        pages = json.pages
      }
    } catch (error) {
      console.error('Search item failed:', error)
    }

    if (isInItemName || isInPages) {
      itemResultList.value.push({ ...element, pages: pages })
    }
  }
  loading.value = false
}

// 跳转到项目
const toOneItem = (item: any) => {
  const to = '/' + (item.item_domain ? item.item_domain : item.item_id)
  const routeData = router.resolve({ path: to })
  window.open(routeData.href, '_blank')
}

// 跳转到页面
const toOnePage = (page: any) => {
  const to = '/' + page.item_id + '/' + page.page_id
  const routeData = router.resolve({ path: to })
  window.open(routeData.href, '_blank')
}

onMounted(() => {
  queries.value = []
  queries.value.push(props.keyword)
  searchItems()
})
</script>

<style scoped lang="scss">
.page {
  margin-top: 20px;
  margin-bottom: 80px;
}

.item-list {
  padding-left: 5px;
}

.item-list-one {
  margin-top: 10px;
  margin-bottom: 10px;
  border-radius: 8px;
}

.item-title {
  width: 600px;
  height: 60px;
  background-color: var(--color-bg-primary);
  margin-top: 10px;
  margin-bottom: 10px;
  color: var(--color-text-primary);
  border-radius: 12px;
  box-shadow: 0 0 2px var(--shadow-default);
  cursor: pointer;
}

.item-title .left {
  position: relative;
  top: 50%;
  transform: translateY(-50%);
  padding-left: 20px;
}

.item-list-one .item-icon {
  margin-right: 10px;
  color: var(--color-text-secondary);
  font-size: 18px;
}

.item-page-content {
  max-width: 540px;
  padding-left: 10px;
  margin-left: 30px;
  border-left: 1px solid var(--color-border);
}

.item-page-content .page-title {
  font-size: 13px;
  line-height: 40px;
  cursor: pointer;

  &:hover {
    color: var(--color-primary);
  }
}

.item-page-content .search-content {
  font-size: 11px;
  color: var(--color-text-secondary);
  margin-left: 30px;
  line-height: 24px;
}

// 底部加载指示器
.loading-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 24px;
  color: var(--color-text-secondary);
  font-size: 14px;
  
  :deep(.ant-spin) {
    --ant-dot-size: 8px;
  }
}
</style>


