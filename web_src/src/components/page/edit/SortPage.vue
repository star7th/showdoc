<!-- 页面排序 -->
<template>
  <div class="">
    <SDialog
      :title="$t('sort_pages')"
      :onCancel="callback"
      :showCancel="false"
      :onOK="callback"
    >
      <p class="tips-text">{{ $t('sort_pages_tips') }}</p>
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
        <div
          class="page-box cursor-move"
          v-for="page in pages"
          :key="page.page_id"
        >
          <span class="page-name "
            ><i class="el-icon-sort font-bold	"></i>&nbsp;&nbsp;{{
              page.page_title
            }}</span
          >
        </div>
      </draggable>
    </SDialog>
  </div>
</template>

<style scoped>
.page-box {
  width: 98%;
  height: 60px;
  margin-top: 10px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}
.page-box:hover {
  background-color: white;
}
.page-name {
  line-height: 60px;
  margin-left: 10px;
  color: #262626;
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
      pages: [],
      belong_to_catalogs: []
    }
  },
  components: {
    draggable
  },
  methods: {
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
      this.request('/api/page/sort', {
        pages: JSON.stringify(data),
        item_id: this.item_id
      }).then(data => {
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
