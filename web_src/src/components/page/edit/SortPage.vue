<!-- 页面排序 -->
<template>
  <div class="hello">
    <Header></Header>

    <el-container class="container-narrow">
      <el-dialog
        :title="$t('sort_pages')"
        :modal="is_modal"
        :visible.sync="dialogTableVisible"
        :close-on-click-modal="false"
      >
        <div class="dialog-body">
          <p class="tips">{{ $t('sort_pages_tips') }}</p>
          <el-select
            :placeholder="$t('optional')"
            class="select-cat"
            v-model="cat_id"
            v-if="belong_to_catalogs"
          >
            <el-option
              v-for="cat in belong_to_catalogs"
              :key="cat.cat_name"
              :label="cat.cat_name"
              :value="cat.cat_id"
            ></el-option>
          </el-select>
          <draggable v-model="pages" tag="div" group="page" @end="endMove">
            <div class="page-box" v-for="page in pages" :key="page.page_id">
              <span class="page-name">{{ page.page_title }}</span>
            </div>
          </draggable>
        </div>
      </el-dialog>
    </el-container>
    <Footer></Footer>
    <div class></div>
  </div>
</template>

<style scoped>
.dialog-body {
  min-height: 400px;
  max-height: 90%;
  overflow-x: hidden;
  overflow-y: auto;
}
.page-box {
  background-color: rgb(250, 250, 250);
  width: 98%;
  height: 40px;
  margin-top: 10px;
  border: 1px solid #d9d9d9;
  border-radius: 2px;
}
.page-name {
  line-height: 40px;
  margin-left: 20px;
  color: #262626;
}
.tips {
  margin-left: 10px;
  color: #9ea1a6;
  font-size: 11px;
}
</style>

<script>
import draggable from 'vuedraggable'
export default {
  props: {
    callback: '',
    page_id: '',
    item_id: '',
    is_modal: true,
    belong_to_catalogs: [],
    cat_id: ''
  },
  data() {
    return {
      currentDate: new Date(),
      dialogTableVisible: false,
      pages: []
    }
  },
  components: {
    draggable
  },
  methods: {
    show() {
      this.dialogTableVisible = true
    },
    // 获取某目录下的所有页面
    get_pages() {
      var that = this
      var url = DocConfig.server + '/api/catalog/getPagesBycat'
      var params = new URLSearchParams()
      params.append('item_id', this.item_id)
      params.append('cat_id', this.cat_id)
      that.axios
        .post(url, params)
        .then(function(response) {
          if (response.data.error_code === 0) {
            that.pages = response.data.data
          } else {
            that.$alert(response.data.error_message)
          }
        })
        .catch(function(error) {
          console.log(error)
        })
    },
    endMove(evt) {
      let data = {}
      for (var i = 0; i < this.pages.length; i++) {
        let key = this.pages[i]['page_id']
        data[key] = i + 1
      }
      this.sort_page(data)
    },
    sort_page(data) {
      var that = this
      var url = DocConfig.server + '/api/page/sort'
      var params = new URLSearchParams()
      params.append('pages', JSON.stringify(data))
      params.append('item_id', this.item_id)
      that.axios.post(url, params).then(function(response) {
        if (response.data.error_code === 0) {
          that.get_pages()
          // window.location.reload();
        } else {
          that.$alert(response.data.error_message, '', {
            callback: function() {
              window.location.reload()
            }
          })
        }
      })
    }
  },
  watch: {
    cat_id: function() {
      this.get_pages()
    },
    dialogTableVisible: function() {
      this.get_pages()
    }
  },
  mounted() {}
}
</script>
