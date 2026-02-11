<template>
  <CommonModal
    :show="show"
    :title="$t('page.interface_list_view')"
    @close="handleClose"
    width="900px"
  >
    <div class="interface-list-modal">
      <!-- 搜索框 -->
      <div class="search-box">
        <a-input
          v-model:value="searchKeyword"
          :placeholder="$t('page.search_interface')"
          allowClear
          @input="handleSearch"
        >
          <template #prefix>
            <i class="far fa-search"></i>
          </template>
        </a-input>
      </div>

      <!-- 接口列表 -->
      <CommonTable
        :tableHeader="tableHeader"
        :tableData="filteredInterfaceList"
        :loading="loading"
        :pagination="pagination"
        :dataSource="'client'"
        @pageChange="handlePageChange"
        rowKey="page_id"
      >
        <!-- 页面标题列自定义渲染 -->
        <template #cell-page_title="{ row }">
          <a class="page-title-link" @click="handleNavigateToPage(row)">
            {{ row.page_title }}
          </a>
        </template>

        <!-- 目录路径列自定义渲染 -->
        <template #cell-cat_path="{ row }">
          <span class="cat-path">{{ row.cat_path || '-' }}</span>
        </template>

        <!-- 更新时间列自定义渲染 -->
        <template #cell-addtime="{ row }">
          <span>{{ formatTime(row.addtime) }}</span>
        </template>

        <!-- 操作列自定义渲染 -->
        <template #cell-actions="{ row }">
          <a-button type="link" size="small" @click="handleNavigateToPage(row)">
            {{ $t('page.view') }}
          </a-button>
        </template>
      </CommonTable>
    </div>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import request from '@/utils/request'
import CommonModal from '@/components/CommonModal.vue'
import CommonTable from '@/components/CommonTable.vue'

interface Props {
  itemId: number
  onClose: () => void
}

const props = defineProps<Props>()

// Composables
const { t } = useI18n()
const router = useRouter()

// Refs
const show = ref(true)
const loading = ref(false)
const interfaceList = ref<any[]>([])
const searchKeyword = ref('')

// 分页配置
const pagination = ref({
  current: 1,
  pageSize: 8,
  total: 0,
})

// 表头配置
const tableHeader = computed(() => [
  {
    title: t('item.page_title'),
    key: 'page_title',
    width: 300,
  },
  {
    title: t('page.catalog_path'),
    key: 'cat_path',
    width: 250,
  },
  {
    title: t('page.update_time'),
    key: 'addtime',
    width: 170,
  },
  {
    title: t('item.operation'),
    key: 'actions',
    width: 100,
  },
])

// 过滤后的接口列表
const filteredInterfaceList = computed(() => {
  if (!searchKeyword.value) {
    return interfaceList.value
  }
  const keyword = searchKeyword.value.toLowerCase()
  return interfaceList.value.filter((item) => {
    return (
      item.page_title?.toLowerCase().includes(keyword) ||
      item.cat_path?.toLowerCase().includes(keyword)
    )
  })
})

// Methods
// 格式化时间（秒级时间戳）
const formatTime = (timestamp: number) => {
  // 如果时间戳为0或不存在，返回空字符串
  if (timestamp === 0 || !timestamp) return ''

  const now = Date.now() / 1000
  const diff = now - timestamp

  // 1小时内
  if (diff < 3600) {
    const minutes = Math.floor(diff / 60)
    return minutes <= 1
      ? t('time.just_now')
      : t('time.minutes_ago', { n: minutes })
  }

  // 24小时内
  if (diff < 86400) {
    const hours = Math.floor(diff / 3600)
    return t('time.hours_ago', { n: hours })
  }

  // 7天内
  if (diff < 604800) {
    const days = Math.floor(diff / 86400)
    return t('time.days_ago', { n: days })
  }

  // 显示日期
  const date = new Date(timestamp * 1000)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hour = String(date.getHours()).padStart(2, '0')
  const minute = String(date.getMinutes()).padStart(2, '0')

  // 如果是今年，只显示月-日 时:分
  const currentYear = new Date().getFullYear()
  if (year === currentYear) {
    return `${month}-${day} ${hour}:${minute}`
  }

  // 否则显示年-月-日 时:分
  return `${year}-${month}-${day} ${hour}:${minute}`
}

const fetchInterfaceList = async () => {
  loading.value = true
  try {
    const result = await request(
      '/api/item/info',
      {
        item_id: props.itemId,
      },
      'post',
      false,
    )

    if (result.error_code === 0 && result.data) {
      // 扁平化目录树，提取所有页面
      const pages: any[] = []
      const menu = result.data.menu || {}

      // 处理根目录下的页面
      if (menu.pages && Array.isArray(menu.pages)) {
        menu.pages.forEach((page: any) => {
          pages.push({
            ...page,
            cat_path: t('page.root_catalog'),
          })
        })
      }

      // 递归处理目录
      const flattenCatalogs = (catalogs: any[], parentPath: string = '') => {
        catalogs.forEach((catalog: any) => {
          const currentPath = parentPath
            ? `${parentPath} / ${catalog.cat_name}`
            : catalog.cat_name

          // 添加当前目录下的页面
          if (catalog.pages && Array.isArray(catalog.pages)) {
            catalog.pages.forEach((page: any) => {
              pages.push({
                ...page,
                cat_path: currentPath,
              })
            })
          }

          // 递归处理子目录
          if (catalog.catalogs && Array.isArray(catalog.catalogs)) {
            flattenCatalogs(catalog.catalogs, currentPath)
          }
        })
      }

      if (menu.catalogs && Array.isArray(menu.catalogs)) {
        flattenCatalogs(menu.catalogs)
      }

      interfaceList.value = pages
      pagination.value.total = pages.length
    }
  } catch (error) {
    console.error('获取接口列表失败:', error)
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  pagination.value.current = 1
}

const handlePageChange = (page: number) => {
  pagination.value.current = page
}

const handleNavigateToPage = (row: any) => {
  // 关闭弹窗
  handleClose()

  // 使用 router.resolve 生成正确的 URL，兼容历史路由和哈希路由
  const route = router.resolve({
    name: 'ItemShowPage',
    params: {
      item_id: String(props.itemId),
      page_id: String(row.page_id),
    },
  })

  // 使用 window.location.assign() 跳转，然后强制刷新
  window.location.assign(route.href)
  // 延迟刷新确保跳转生效
  setTimeout(() => {
    window.location.reload()
  }, 100)
}

const handleClose = () => {
  show.value = false
  setTimeout(() => {
    props.onClose()
  }, 300)
}

// Lifecycle
onMounted(() => {
  fetchInterfaceList()
})
</script>

<style scoped lang="scss">
.interface-list-modal {
  min-height: 200px;

  .search-box {
    margin-bottom: 16px;

    :deep(.ant-input) {
      border-radius: 4px;
    }

    i {
      color: var(--color-text-secondary);
    }
  }

  .page-title-link {
    color: var(--color-primary);
    cursor: pointer;
    text-decoration: none;

    &:hover {
      text-decoration: underline;
    }
  }

  .cat-path {
    color: var(--color-text-secondary);
    font-size: 13px;
  }

  :deep(.common-table-wrapper) {
    main {
      max-height: 500px;
    }
  }

  :deep(.ant-btn-link) {
    padding: 0 4px;
    margin: 0 2px;
  }
}
</style>
