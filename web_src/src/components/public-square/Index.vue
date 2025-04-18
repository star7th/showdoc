<template>
  <div class="hello">
    <Header></Header>
    <div class="container mx-auto py-8 px-4" style="max-width: 1200px;">
      <div class="public-square-header">
        <div class="header-left">
          <button class="back-button" @click="$router.push('/item/index')">
            <i class="el-icon-arrow-left"></i>
          </button>
          <h2>{{ $t('public_square') }}</h2>
        </div>
        <div class="search-box">
          <el-input
            class="search-input"
            v-model="keyword"
            :placeholder="$t('search_placeholder')"
            @keyup.enter.native="search"
          >
            <el-select v-model="searchType" slot="prepend" style="width: 120px">
              <el-option :label="$t('search_title')" value="title"></el-option>
              <el-option
                :label="$t('search_content')"
                value="content"
              ></el-option>
            </el-select>
            <el-button
              slot="append"
              icon="el-icon-search"
              @click="search"
            ></el-button>
          </el-input>
        </div>
      </div>

      <div class="public-square-grid">
        <el-card
          class="item-card"
          v-for="item in items"
          :key="item.item_id"
          @click.native="goToItem(item)"
        >
          <h3 class="item-title">{{ item.item_name }}</h3>
          <p class="item-desc">
            {{ item.item_description || $t('no_description_item') }}
          </p>
          <div class="item-meta">
            <span>{{ $t('project_update_time') }}: {{ item.last_update_time }}</span>
          </div>
        </el-card>

        <div class="empty-list" v-if="items.length === 0">
          <el-empty :description="$t('no_public_items')"></el-empty>
        </div>
      </div>

      <div class="pagination" v-if="total > 0">
        <el-pagination
          @current-change="handleCurrentChange"
          :current-page="page"
          :page-size="count"
          layout="total, prev, pager, next"
          :total="total"
        >
        </el-pagination>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      items: [],
      total: 0,
      page: 1,
      count: 12,
      keyword: '',
      searchType: 'title',
      loading: false,
      featureEnabled: false
    }
  },
  components: {
    Header: () => import('@/components/common/Header')
  },
  methods: {
    checkFeatureEnabled() {
      this.request('/api/publicSquare/checkEnabled', {})
        .then(data => {
          if (data.data.enable === 1) {
            this.featureEnabled = true
            this.getList()
          } else {
            this.$message.error('公开广场功能未开启')
            this.$router.push('/item/index')
          }
        })
        .catch(() => {
          this.$message.error('网络错误，请稍后再试')
          this.$router.push('/item/index')
        })
    },
    getList() {
      if (!this.featureEnabled) return

      this.loading = true
      this.request('/api/publicSquare/getPublicItems', {
        page: this.page,
        count: this.count,
        keyword: this.keyword,
        search_type: this.searchType
      })
        .then(data => {
          this.items = data.data.items
          this.total = data.data.total
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    search() {
      this.page = 1
      this.getList()
    },
    handleCurrentChange(val) {
      this.page = val
      this.getList()
    },
    goToItem(item) {
      let url = '/#/' + (item.item_domain || item.item_id)
      window.open(url, '_blank')
    }
  },
  mounted() {
    this.checkFeatureEnabled()
  }
}
</script>

<style scoped>
.public-square-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.back-button {
  width: 40px;
  height: 40px;
  background-color: #fff;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border: 1px solid #ebeef5;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.back-button:hover {
  background-color: #f5f7fa;
}

.search-box {
  width: 450px;
}

.search-input {
  width: 100%;
}

.public-square-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 30px;
}

.item-card {
  cursor: pointer;
  transition: all 0.3s ease;
  height: 100%;
  padding: 0;
  border-radius: 8px;
  overflow: hidden;
}

.item-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
}

.item-card .el-card__body {
  padding: 16px;
}

.item-title {
  font-size: 16px;
  font-weight: 500;
  color: #333;
  margin-bottom: 8px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.item-desc {
  color: #666;
  margin-bottom: 12px;
  height: 36px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  font-size: 13px;
  line-height: 1.4;
}

.item-meta {
  color: #999;
  font-size: 12px;
}

.pagination {
  text-align: right;
  margin-top: 24px;
}

.empty-list {
  grid-column: span 4;
  padding: 40px 0;
}

@media (max-width: 1200px) {
  .public-square-grid {
    grid-template-columns: repeat(3, 1fr);
  }
  
  .empty-list {
    grid-column: span 3;
  }
}

@media (max-width: 992px) {
  .public-square-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .empty-list {
    grid-column: span 2;
  }
}

@media (max-width: 768px) {
  .public-square-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .search-box {
    width: 100%;
  }
}

@media (max-width: 576px) {
  .public-square-grid {
    grid-template-columns: 1fr;
  }
  
  .empty-list {
    grid-column: span 1;
  }
}
</style> 