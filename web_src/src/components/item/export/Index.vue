<template>
  <div class="">
    <SDialog
      :onCancel="callback"
      :title="$t('export')"
      width="400px"
      :onOK="onSubmit"
    >
      <div class="text-center">
        <el-form status-icon label-width="0px" class="demo-ruleForm">
          <h2></h2>
          <el-form-item label>
            <el-radio-group v-model="export_format">
              <el-radio-button label="word">{{
                $t('export_format_word')
              }}</el-radio-button>
              <el-radio-button v-show="showMarkdown" label="markdown">{{
                $t('export_format_markdown')
              }}</el-radio-button>
            </el-radio-group>
          </el-form-item>

          <el-form-item label v-if="export_format == 'word'">
            <el-radio v-model="export_type" label="1">{{
              $t('export_all')
            }}</el-radio>
            <el-radio v-model="export_type" label="2">{{
              $t('export_cat')
            }}</el-radio>
          </el-form-item>

          <el-form-item label v-if="export_format == 'markdown'">
            <p class="tips-text">{{ $t('export_markdown_tips') }}</p>
          </el-form-item>

          <el-form-item
            label
            v-if="export_format == 'word' && export_type == 2"
          >
            <el-select
              :placeholder="$t('catalog')"
              class="cat"
              v-model="cat_id"
              v-if="computed_catalogs"
              @change="getPages"
            >
              <el-option
                v-for="cat in computed_catalogs"
                :key="cat.cat_name"
                :label="cat.cat_name"
                :value="cat.cat_id"
              ></el-option>
            </el-select>
          </el-form-item>
          <el-form-item
            label
            v-if="export_format == 'word' && export_type == 2"
          >
            <el-select class="cat" v-model="page_id" v-if="pages">
              <el-option
                v-for="page in pages"
                :key="page.page_title"
                :label="page.page_title"
                :value="page.page_id"
              ></el-option>
            </el-select>
          </el-form-item>
        </el-form>
      </div>
    </SDialog>
  </div>
</template>

<script>
import { getUserInfoFromStorage } from '@/models/user.js'
export default {
  name: 'Login',
  components: {},
  props: {
    callback: () => {},
    item_id: 0
  },
  data() {
    return {
      catalogs: [],
      cat_id: '',
      export_type: '1',
      export_format: 'word',
      pages: [{ page_id: '0', page_title: this.$t('all_pages') }],
      page_id: '0',
      showMarkdown: true,
      user_token: ''
    }
  },
  computed: {
    // 新建/编辑页面时供用户选择的归属目录列表
    computed_catalogs: function() {
      var Info = this.catalogs.slice(0)
      var cat_array = []
      for (var i = 0; i < Info.length; i++) {
        cat_array.push(Info[i])
        var sub = Info[i]['sub']
        if (sub.length > 0) {
          for (var j = 0; j < sub.length; j++) {
            cat_array.push({
              cat_id: sub[j]['cat_id'],
              cat_name: Info[i]['cat_name'] + ' / ' + sub[j]['cat_name']
            })

            var sub_sub = sub[j]['sub']
            if (sub_sub.length > 0) {
              for (var k = 0; k < sub_sub.length; k++) {
                cat_array.push({
                  cat_id: sub_sub[k]['cat_id'],
                  cat_name:
                    Info[i]['cat_name'] +
                    ' / ' +
                    sub[j]['cat_name'] +
                    ' / ' +
                    sub_sub[k]['cat_name']
                })
              }
            }
          }
        }
      }
      var no_cat = { cat_id: '', cat_name: this.$t('none') }
      cat_array.unshift(no_cat)
      return cat_array
    }
  },
  methods: {
    // 获取所有目录
    getCatalog(item_id) {
      this.request('/api/catalog/catListGroup', {
        item_id: item_id
      }).then(data => {
        const json = data.data
        this.catalogs = json
      })
    },
    onSubmit() {
      this.request('/api/export/checkMarkdownLimit', {
        export_format: this.export_format
      }).then(data => {
        if (this.export_type == 1) {
          this.cat_id = ''
        }
        var url =
          DocConfig.server +
          '/api/export/word&item_id=' +
          this.item_id +
          '&cat_id=' +
          this.cat_id +
          '&page_id=' +
          this.page_id +
          '&user_token=' +
          this.user_token
        if (this.export_format == 'markdown') {
          url =
            DocConfig.server +
            '/api/export/markdown&item_id=' +
            this.item_id +
            '&user_token=' +
            this.user_token
        }
        window.location.href = url
        this.callback()
      })
    },
    goback() {
      this.$router.go(-1)
    },
    // 获取某目录下的所有页面
    getPages(cat_id) {
      this.request('/api/catalog/getPagesBycat', {
        item_id: this.item_id,
        cat_id: cat_id
      }).then(data => {
        var pages = data.data
        pages.unshift({
          page_id: '0',
          page_title: this.$t('all_pages')
        })
        this.pages = pages
        this.page_id = '0'
      })
    }
  },
  mounted() {
    this.getCatalog(this.item_id)
    // 获取项目类型。如果是runapi项目，则无法导出markdown压缩包
    this.request('/api/item/detail', {
      item_id: this.item_id
    }).then(data => {
      if (data.data.item_type == '3') {
        this.showMarkdown = false // 不显示markdown选项
      }
    })
    const userInfo = getUserInfoFromStorage()
    this.user_token = userInfo.user_token
  },
  beforeDestroy() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.center-card a {
  font-size: 12px;
}

.center-card {
  text-align: center;
  width: 400px;
}

.markdown-tips {
  text-align: left;
  margin-left: 25px;
  font-size: 11px;
}
</style>
