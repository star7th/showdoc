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
        :before-close="callback"
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
    callback: () => {},
    page_id: '',
    item_id: '',
    is_modal: true,
    cat_id: ''
  },
  data() {
    return {
      currentDate: new Date(),
      dialogTableVisible: true,
      pages: [],
      belong_to_catalogs: []
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
    getPages() {
      this.request('/api/catalog/getPagesBycat', {
        item_id: this.item_id,
        cat_id: this.cat_id
      }).then(data => {
        this.pages = data.data
      })
    },
    endMove(evt) {
      let data = {}
      for (var i = 0; i < this.pages.length; i++) {
        let key = this.pages[i]['page_id']
        data[key] = i + 1
      }
      this.sortPage(data)
    },
    sortPage(data) {
      this.request(
        '/api/page/sort',
        {
          pages: JSON.stringify(data),
          item_id: this.item_id
        },
        'post',
        false
      ).then(data => {
        if (data.error_code === 0) {
          this.getPages()
          // window.location.reload();
        } else {
          this.$alert(data.error_message, '', {
            callback: function() {
              window.location.reload()
            }
          })
        }
      })
    },
    getCatListName() {
      this.request('/api/catalog/catListName', {
        item_id: this.item_id
      }).then(data => {
        this.belong_to_catalogs = data.data
      })
    }
  },
  watch: {
    cat_id: function() {
      this.getPages()
    }
  },
  mounted() {
    this.getPages()
    this.getCatListName()
  }
}
</script>
