<template>
  <div class="page">
    <div class="item-list">
      <div
        class="item-list-one"
        v-loading="loading"
        v-for="item in itemResultList"
        :key="item.item_id"
      >
        <div class="item-title" @click="toOneItem(item)">
          <div class="left   float-left">
            <i class="item-icon el-icon-document"></i>
            <text-highlight :queries="queries">{{
              item.item_name
            }}</text-highlight>
          </div>
        </div>
        <div class="item-page-content">
          <div v-for="onePage in item.pages" :key="onePage.page_id">
            <div @click="toOnePage(onePage)" class="page-title">
              <i class="item-icon el-icon-document"></i>
              <text-highlight :queries="queries">{{
                onePage.page_title
              }}</text-highlight>
            </div>
            <div class="search-content break-all">
              <text-highlight :queries="queries">{{
                onePage.search_content
              }}</text-highlight>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      class="itemResultList"
      v-if="false"
      v-for="item in itemResultList"
      :key="item.item_id"
    >
      <hr />
      <p class="title">
        <router-link :to="'/' + item.item_id" tag="a" target="_blank"
          ><text-highlight :queries="queries">{{
            item.item_name
          }}</text-highlight></router-link
        >
      </p>
      <p class="content"></p>
      <div v-for="onePage in item.pages">
        <p>
          <text-highlight :queries="queries">{{
            onePage.page_title
          }}</text-highlight>
        </p>
        <p>
          <text-highlight :queries="queries">{{
            onePage.search_content
          }}</text-highlight>
        </p>
      </div>
    </div>

    <div v-if="showLoading" class="loading">
      <i class="el-icon-loading"></i>
    </div>
  </div>
</template>

<script>
import TextHighlight from 'vue-text-highlight'
export default {
  name: '',
  components: { TextHighlight },
  props: {
    keyword: '',
    itemList: []
  },
  data() {
    return {
      showLoading: false,
      queries: [],
      itemResultList: []
    }
  },
  watch: {
    keyword: function(val) {
      this.queries = []
      this.queries.push(this.keyword)
      this.itemResultList = []
      this.searchItems()
    }
  },
  methods: {
    async searchItems() {
      this.showLoading = true
      for (let index = 0; index < this.itemList.length; index++) {
        const element = this.itemList[index]
        // 先初始化几个变量，保存状态
        let isInItemName = 0 // 关键字是否存在项目标题中
        let isInpages = 0 // 关键字是否存在页面内容中
        let pages = [] // 含有关键字额页面
        if (
          element &&
          element.item_name &&
          element.item_name.indexOf(this.keyword) > -1
        ) {
          isInItemName = 1
        }

        // 远程搜索，按项目，一个个项目搜索
        const res = await this.request('/api/item/search', {
          keyword: this.keyword,
          item_id: element.item_id
        })
        let json = res.data
        if (json && json.pages && json.pages.length > 0) {
          isInpages = 1
          pages = json.pages
        }

        if (isInItemName || isInpages) {
          this.itemResultList.push({ ...element, pages: pages })
        }
      }
      this.showLoading = false
    },
    toOneItem(item) {
      const to = '/' + (item.item_domain ? item.item_domain : item.item_id)
      let routeData = this.$router.resolve({ path: to })
      window.open(routeData.href, '_blank')
    },
    toOnePage(page) {
      const to = '/' + page.item_id + '/' + page.page_id
      let routeData = this.$router.resolve({ path: to })
      window.open(routeData.href, '_blank')
    }
  },
  mounted() {
    this.queries = []
    this.queries.push(this.keyword)
    this.searchItems()
  },
  beforeDestroy() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.page {
  margin-top: 20px;
  margin-bottom: 80px;
}
.loading {
  width: 50px;
  margin: 0 auto;
  font-size: 20px;
}

.item-title {
  width: 600px;
  height: 60px;
  background-color: white;
  margin-top: 10px;
  margin-bottom: 10px;
  color: #343a40;
  border-radius: 12px;
  box-shadow: 0 0 2px #0000001a;
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
  color: rgba(0, 0, 0, 0.1);
  font-size: 18px;
}
.item-page-content {
  max-width: 540px;
  padding-left: 10px;
  margin-left: 30px;
  border-left: 1px solid rgba(0, 0, 0, 0.05);
}

.item-page-content .page-title {
  font-size: 13px;
  line-height: 40px;
  cursor: pointer;
}
.item-page-content .search-content {
  font-size: 11px;
  color: rgba(155, 155, 155, 1);
  margin-left: 30px;
  line-height: 24px;
}
</style>
