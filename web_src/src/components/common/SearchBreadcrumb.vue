<template>
  <div class="search-breadcrumb-container" v-if="show">
    <el-breadcrumb separator="/">
      <el-breadcrumb-item :to="{ path: '/' + item_domain }">
        <i class="el-icon-s-home"></i>
      </el-breadcrumb-item>
      <!-- 如果后端返回了完整路径，直接使用 -->
      <template v-if="fullPath && fullPath.length > 0">
        <el-breadcrumb-item
          v-for="(path, index) in fullPath"
          :key="index"
          :to="
            index < fullPath.length - 1
              ? { path: '/' + item_domain + '/' + path.cat_id }
              : ''
          "
          :class="{ 'is-highlight': index === fullPath.length - 1 }"
        >
          {{ path.cat_name || path.page_title }}
        </el-breadcrumb-item>
      </template>
      <!-- 否则使用本地计算的路径 -->
      <template v-else>
        <el-breadcrumb-item
          v-for="(path, index) in pathArray"
          :key="index"
          :to="
            index < pathArray.length - 1
              ? { path: '/' + item_domain + '/' + catPathIdMap[path] }
              : ''
          "
          :class="{ 'is-highlight': index === pathArray.length - 1 }"
        >
          {{ path }}
        </el-breadcrumb-item>
      </template>
    </el-breadcrumb>
  </div>
</template>

<script>
export default {
  props: {
    page_info: {
      type: Object,
      default: () => ({})
    },
    item_info: {
      type: Object,
      default: () => ({})
    },
    keyword: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      show: false,
      pathArray: [],
      catPathIdMap: {}, // 目录路径名称到目录ID的映射
      isSearchOnly: false, // 标记是否只是搜索结果页面
      fullPath: null // 存储后端返回的完整路径
    }
  },
  computed: {
    item_domain() {
      return this.item_info.item_domain
        ? this.item_info.item_domain
        : this.item_info.item_id
    }
  },
  watch: {
    page_info: {
      handler(newVal, oldVal) {
        if (newVal && this.keyword) {
          // 检查后端是否返回了完整路径
          if (newVal.full_path && Array.isArray(newVal.full_path)) {
            this.fullPath = newVal.full_path
            this.show = true
            console.log('使用后端返回的完整路径')
          } else {
            this.fullPath = null
            this.getPathFromCatId()
          }
          // 强制设置 show 为 true
          this.show = true
        }
      },
      deep: true,
      immediate: true
    },
    keyword(newVal) {
      if (newVal && this.page_info) {
        this.show = true

        // 检查后端是否返回了完整路径
        if (
          this.page_info.full_path &&
          Array.isArray(this.page_info.full_path)
        ) {
          this.fullPath = this.page_info.full_path
          console.log('关键词变化：使用后端返回的完整路径')
        } else {
          this.fullPath = null
          this.getPathFromCatId()
        }
      } else {
        this.show = false
        this.pathArray = []
        this.fullPath = null
      }
    }
  },
  methods: {
    // 根据页面信息获取完整路径
    getPathFromCatId() {
      // 检查是否是搜索结果页面
      if (this.page_info && this.page_info.is_search_result) {
        this.isSearchOnly = true
        this.show = true // 强制显示
        this.pathArray = []
        return
      }

      // 检查是否是来自搜索结果的页面
      if (this.page_info && this.page_info.from_search_result) {
        // 检查后端是否返回了完整路径
        if (
          this.page_info.full_path &&
          Array.isArray(this.page_info.full_path)
        ) {
          this.fullPath = this.page_info.full_path
          this.isSearchOnly = false
          this.show = true
          console.log('点击搜索结果：使用后端返回的完整路径')
          return
        }
      }

      // 如果点击了搜索结果中的某个页面，需要使用原始的目录结构来查找路径
      if (this.keyword && this.page_info && this.page_info.cat_id) {
        this.isSearchOnly = false
        this.show = true

        // 首先检查页面信息中是否包含 original_item_info
        if (
          this.page_info.original_item_info &&
          this.page_info.original_item_info.menu
        ) {
          this.getFullPath(
            this.page_info.cat_id,
            this.page_info.original_item_info.menu
          )
          return
        }

        // 如果没有，则尝试从 item_info 的 original_item_info 中获取
        if (
          this.item_info &&
          this.item_info.original_item_info &&
          this.item_info.original_item_info.menu
        ) {
          this.getFullPath(
            this.page_info.cat_id,
            this.item_info.original_item_info.menu
          )
          return
        }

        // 然后尝试从原始item_info中获取完整路径
        if (this.item_info && this.item_info.menu) {
          this.getFullPath(this.page_info.cat_id, this.item_info.menu)
          return
        }
      }

      // 如果是搜索结果页面（没有cat_id）
      if (!this.page_info || !this.page_info.cat_id) {
        this.isSearchOnly = true
        this.show = true // 强制显示
        if (this.page_info && this.page_info.page_title) {
          this.pathArray = [this.page_info.page_title]
        } else {
          this.pathArray = []
        }
        return
      }

      this.isSearchOnly = false
      this.show = true // 强制显示
      this.getFullPath(this.page_info.cat_id)
    },

    // 递归获取完整路径 - 可选传入指定的menu
    getFullPath(catId, customMenu) {
      if (!catId) return

      const menu = customMenu || (this.item_info ? this.item_info.menu : null)
      if (!menu || !menu.catalogs) return

      // 清空旧的映射
      this.catPathIdMap = {}

      // 定义递归函数查找目录路径
      const findPath = (catalogs, catId, path = []) => {
        for (const catalog of catalogs) {
          if (catalog.cat_id == catId) {
            path.unshift(catalog.cat_name)
            this.catPathIdMap[catalog.cat_name] = catalog.cat_id
            return path
          }

          if (catalog.catalogs && catalog.catalogs.length > 0) {
            const foundPath = findPath(catalog.catalogs, catId, [...path])
            if (foundPath) {
              foundPath.unshift(catalog.cat_name)
              this.catPathIdMap[catalog.cat_name] = catalog.cat_id
              return foundPath
            }
          }
        }
        return null
      }

      // 开始查找路径
      const path = findPath(menu.catalogs, catId)

      if (path) {
        // 添加页面标题
        this.pathArray = [...path, this.page_info.page_title || '']
      } else {
        this.pathArray = [this.page_info.page_title || '']
      }
    }
  },
  mounted() {
    // 组件挂载后立即尝试生成路径
    if (this.page_info && this.keyword) {
      // 检查后端是否返回了完整路径
      if (this.page_info.full_path && Array.isArray(this.page_info.full_path)) {
        this.fullPath = this.page_info.full_path
        this.show = true
        console.log('组件挂载：使用后端返回的完整路径')
      } else {
        this.getPathFromCatId()
      }
    }
  }
}
</script>

<style scoped>
.search-breadcrumb-container {
  padding: 8px 0;
  margin-bottom: 8px;
  background-color: #f8f9fa;
  border-radius: 4px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-left: 10px;
  padding-right: 10px;
}

.is-highlight {
  font-weight: bold;
  color: #409eff;
}
</style>
