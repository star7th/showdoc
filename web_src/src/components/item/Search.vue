<template>
  <div class="page">
    <div class="resultList" v-for="item in resultList" :key="item.item_id">
      <div v-for="page in item.pages" :key="page.page_id">
        <hr />
        <p class="title">
          <router-link
            :to="'/' + page.item_id + '?page_id=' + page.page_id"
            tag="a"
            target="_blank"
            ><text-highlight :queries="queries"
              >{{ page.item_name }} - {{ page.page_title }}</text-highlight
            ></router-link
          >
        </p>
        <p class="content">
          <text-highlight :queries="queries">{{
            page.search_content
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
      searchItemIds: [], // 要搜索的item_id们
      resultList: [],
      showLoading: false,
      queries: []
    }
  },
  watch: {
    keyword: function(val) {
      // 如果关键词发生变化，则先清空当前搜索队列，然后用itemList重新填充
      this.searchItemIds = []
      this.resultList = []
      this.queries = []
      this.queries.push(this.keyword)
      this.itemList.forEach(element => {
        this.searchItemIds.push(element.item_id)
      })
      this.search()
    }
  },
  methods: {
    search() {
      this.showLoading = true
      let item_id = this.searchItemIds.shift()
      if (!item_id) {
        this.showLoading = false
        return false
      }
      this.request('/api/item/search', {
        keyword: this.keyword,
        item_id
      }).then(data => {
        let json = data.data
        if (json && json.pages && json.pages.length > 0) {
          this.resultList.push(json)
        }
        this.search()
      })
    }
  },
  mounted() {
    this.queries = []
    this.queries.push(this.keyword)
    this.itemList.forEach(element => {
      this.searchItemIds.push(element.item_id)
    })
    this.search()
  },
  beforeDestroy() {
    this.searchItemIds = []
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.page {
  margin-top: 20px;
}
.loading {
  width: 50px;
  margin: 0 auto;
  font-size: 20px;
}
.resultList {
  width: 85%;
  margin: 0 auto;
}
.title {
  font-size: 16px;
  font-weight: bold;
}
.content {
  margin-top: 20px;
  margin-bottom: 20px;
}
</style>
