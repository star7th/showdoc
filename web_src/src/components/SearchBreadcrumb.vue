<template>
  <div v-if="keyword" class="search-breadcrumb">
    <a-breadcrumb>
      <!-- 项目名称 -->
      <a-breadcrumb-item>
        <router-link :to="`/${itemInfo?.item_domain || itemInfo?.item_id}`">
          {{ itemInfo?.item_name }}
        </router-link>
      </a-breadcrumb-item>

      <!-- 显示搜索关键词 -->
      <a-breadcrumb-item>
        <span class="search-label">{{ $t('search') }}</span>
        <span class="search-keyword">"{{ keyword }}"</span>
      </a-breadcrumb-item>

      <!-- 显示页面路径（搜索结果页面的目录层级） -->
      <template v-if="pageInfo && pageInfo.full_path && pageInfo.full_path.length > 0">
        <a-breadcrumb-item
          v-for="(pathItem, index) in pageInfo.full_path"
          :key="index"
        >
          <template v-if="pathItem.cat_name">
            {{ pathItem.cat_name }}
          </template>
          <template v-else-if="pathItem.page_title">
            {{ pathItem.page_title }}
          </template>
        </a-breadcrumb-item>
      </template>

      <!-- 当前页面标题（如果没有在 full_path 中显示） -->
      <a-breadcrumb-item
        v-if="pageInfo && pageInfo.page_title && (!pageInfo.full_path || pageInfo.full_path.length === 0)"
        class="current-page"
      >
        {{ pageInfo.page_title }}
      </a-breadcrumb-item>
    </a-breadcrumb>
  </div>
</template>

<script setup lang="ts">
// Props
interface Props {
  keyword?: string
  pageInfo?: any
  itemInfo?: any
}

const props = withDefaults(defineProps<Props>(), {
  keyword: '',
  pageInfo: () => ({}),
  itemInfo: () => ({})
})
</script>


<style scoped lang="scss">
.search-breadcrumb {
  margin-bottom: 20px;
  padding: 12px 16px;
  background-color: var(--color-bg-secondary);
  border-radius: 6px;
  border-left: 3px solid var(--color-primary);
}

.search-label {
  margin-right: 5px;
  color: var(--color-text-secondary);
}

.search-keyword {
  font-weight: 600;
  color: var(--color-primary);
}
</style>

