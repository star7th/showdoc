<template>
  <div class="hello" @keydown.ctrl.83.prevent="save" @keydown.meta.83.prevent="save">
    <Header></Header>

    <el-container class="container-narrow">
      <el-row class="masthead">
        <el-form :inline="true" class="demo-form-inline" size="small">
          <el-form-item :label="$t('title')+' : '">
            <el-input placeholder v-model="title"></el-input>
          </el-form-item>

          <el-form-item :label="$t('catalog')+' : '">
            <el-select
              :placeholder="$t('optional')"
              class="cat"
              v-model="cat_id"
              v-if="belong_to_catalogs"
            >
              <el-option
                v-for="cat in belong_to_catalogs "
                :key="cat.cat_name"
                :label="cat.cat_name"
                :value="cat.cat_id"
              ></el-option>
            </el-select>
          </el-form-item>

          <el-form-item label>
            <el-button type="text" @click="ShowSortPage">{{$t('sort_pages')}}</el-button>
          </el-form-item>
          <el-form-item label>
            <el-button type="text" @click="ShowHistoryVersion">{{$t('history_version')}}</el-button>
          </el-form-item>

          <el-form-item class="pull-right">
            <el-dropdown
              @command="dropdown_callback"
              split-button
              type="primary"
              size="medium"
              title="Ctrl + S"
              trigger="click"
              @click="save"
            >
              <span id="save-page">{{$t('save')}}</span>
              <el-dropdown-menu slot="dropdown">
                <el-dropdown-item :command="save_to_template">{{$t('save_to_templ')}}</el-dropdown-item>
                <!-- <el-dropdown-item>保存前添加注释</el-dropdown-item> -->
              </el-dropdown-menu>
            </el-dropdown>
            <el-button type size="medium" @click="goback">{{$t('goback')}}</el-button>
          </el-form-item>
        </el-form>

        <el-row class="fun-btn-group">
          <el-button
            type
            size="medium"
            @click="insert_api_template"
          >{{$t('insert_apidoc_template')}}</el-button>
          <el-button
            type
            size="medium"
            @click="insert_database_template"
          >{{$t('insert_database_doc_template')}}</el-button>
          <el-button type size="medium" @click.native="ShowTemplateList">{{$t('more_templ')}}</el-button>

          <el-dropdown split-button type style="margin-left:100px;" size="medium" trigger="hover">
            {{$t('format_tools')}}
            <el-dropdown-menu slot="dropdown">
              <el-dropdown-item @click.native="ShowJsonToTable">{{$t('json_to_table')}}</el-dropdown-item>
              <el-dropdown-item @click.native="ShowJsonBeautify">{{$t('beautify_json')}}</el-dropdown-item>
              <el-dropdown-item @click.native="ShowPasteTable">{{$t('paste_insert_table')}}</el-dropdown-item>
            </el-dropdown-menu>
          </el-dropdown>
          <el-button type size="medium" @click="ShowRunApi">{{$t('http_test_api')}}</el-button>

          <el-badge :value="attachment_count" class="item">
            <el-button type size="medium" @click="ShowAttachment">{{$t('attachment')}}</el-button>
          </el-badge>
        </el-row>

        <Editormd v-bind:content="content" v-if="content" ref="Editormd" type="editor"></Editormd>
      </el-row>

      <!-- 更多模板 -->
      <TemplateList :callback="insertValue" ref="TemplateList"></TemplateList>

      <!-- 历史版本 -->
      <HistoryVersion :callback="insertValue" :is_show_recover_btn="true" ref="HistoryVersion"></HistoryVersion>

      <!-- Json转表格 组件 -->
      <JsonToTable :callback="insertValue" ref="JsonToTable"></JsonToTable>

      <!-- Json格式化 -->
      <JsonBeautify :callback="insertValue" ref="JsonBeautify"></JsonBeautify>

      <!-- 附件列表 -->
      <AttachmentList
        :callback="insertValue"
        :item_id="item_id"
        :manage="true"
        :page_id="page_id"
        ref="AttachmentList"
      ></AttachmentList>

      <!-- 粘贴插入表格 -->
      <PasteTable
        :callback="insertValue"
        :item_id="item_id"
        :manage="true"
        :page_id="page_id"
        ref="PasteTable"
      ></PasteTable>

      <!-- 页面排序 -->
      <SortPage
        :callback="insertValue"
        :belong_to_catalogs="belong_to_catalogs"
        :item_id="item_id"
        :page_id="page_id"
        :cat_id="cat_id"
        ref="SortPage"
      ></SortPage>
    </el-container>
    <Footer></Footer>
    <div class></div>
  </div>
</template>

<style scoped>
.container-narrow {
  margin: 0 auto;
  max-width: 90%;
}

.masthead {
  width: 100%;
  margin-top: 5px;
}

.cat {
  width: 200px;
}

.num {
  width: 60px;
}
.fun-btn-group {
  margin-top: 15px;
  margin-bottom: 15px;
}
</style>

<script>
import Editormd from '@/components/common/Editormd'
import JsonToTable from '@/components/common/JsonToTable'
import JsonBeautify from '@/components/common/JsonBeautify'
import TemplateList from '@/components/page/edit/TemplateList'
import HistoryVersion from '@/components/page/edit/HistoryVersion'
import AttachmentList from '@/components/page/edit/AttachmentList'
import PasteTable from '@/components/page/edit/PasteTable'
import SortPage from '@/components/page/edit/SortPage'
import { Base64 } from 'js-base64'

export default {
  data() {
    return {
      currentDate: new Date(),
      itemList: {},
      content: '',
      title: '',
      item_id: 0,
      cat_id: '',
      s_number: '',
      page_id: '',
      copy_page_id: '',
      attachment_count: '',
      catalogs: []
    }
  },
  computed: {
    //新建/编辑目录时供用户选择的上级目录列表
    belong_to_catalogs: function() {
      if (!this.catalogs || this.catalogs.length <= 0) {
        return []
      }

      var Info = this.catalogs.slice(0)
      var cat_array = []

      //这个函数将递归
      var rename = function(catalog, p_cat_name) {
        if (catalog.length > 0) {
          for (var j = 0; j < catalog.length; j++) {
            var cat_name = p_cat_name + ' / ' + catalog[j]['cat_name']
            cat_array.push({
              cat_id: catalog[j]['cat_id'],
              cat_name: cat_name
            })
            if (catalog[j].sub && catalog[j].sub.length > 0) {
              rename(catalog[j].sub, cat_name)
            }
          }
        }
      }

      for (var i = 0; i < Info.length; i++) {
        cat_array.push(Info[i])
        rename(Info[i]['sub'], Info[i].cat_name)
      }
      var no_cat = { cat_id: 0, cat_name: this.$t('none') }
      cat_array.push(no_cat)
      return cat_array
    }
  },
  components: {
    Editormd,
    JsonToTable,
    JsonBeautify,
    TemplateList,
    HistoryVersion,
    AttachmentList,
    PasteTable,
    SortPage
  },
  methods: {
    //获取页面内容
    get_page_content(page_id) {
      if (!page_id) {
        var page_id = this.page_id
      }
      var that = this
      var url = DocConfig.server + '/api/page/info'
      var params = new URLSearchParams()
      params.append('page_id', page_id)
      that.axios
        .post(url, params)
        .then(function(response) {
          if (response.data.error_code === 0) {
            //that.$message.success("加载成功");
            that.content = response.data.data.page_content
            setTimeout(function() {
              that.insertValue(that.content, 1)
              document.body.scrollTop = document.documentElement.scrollTop = 0 //回到顶部
            }, 500)
            setTimeout(function() {
              //如果长度大于3000,则关闭预览
              if (that.content.length > 3000) {
                that.editor_unwatch()
              } else {
                that.editor_watch()
              }
            }, 1000)
            that.title = response.data.data.page_title
            that.item_id = response.data.data.item_id
            that.s_number = response.data.data.s_number
            that.attachment_count =
              response.data.data.attachment_count > 0 ? '...' : ''
          } else {
            that.$alert(response.data.error_message)
          }
        })
        .catch(function(error) {
          console.log(error)
        })
    },

    //获取所有目录
    get_catalog(item_id) {
      var that = this
      var url = DocConfig.server + '/api/catalog/catListGroup'
      var params = new URLSearchParams()
      params.append('item_id', item_id)
      that.axios
        .post(url, params)
        .then(function(response) {
          if (response.data.error_code === 0) {
            var Info = response.data.data

            that.catalogs = Info
            that.get_default_cat()
          } else {
            that.$alert(response.data.error_message)
          }
        })
        .catch(function(error) {
          console.log(error)
        })
    },
    //获取默认该选中的目录
    get_default_cat() {
      var that = this
      var url = DocConfig.server + '/api/catalog/getDefaultCat'
      var params = new URLSearchParams()
      params.append('page_id', this.page_id)
      params.append('item_id', that.$route.params.item_id)
      params.append('copy_page_id', this.copy_page_id)

      that.axios.post(url, params).then(function(response) {
        if (response.data.error_code === 0) {
          //that.$message.success("加载成功");
          var json = response.data.data
          that.cat_id = json.default_cat_id
        } else {
          that.$alert(response.data.error_message)
        }
      })
    },
    //插入数据到编辑器中。插入到光标处。如果参数is_cover为真，则清空后再插入(即覆盖)。
    insertValue(value, is_cover) {
      if (value) {
        let childRef = this.$refs.Editormd //获取子组件
        if (is_cover) {
          // 清空
          childRef.clear()
        }
        childRef.insertValue(value) //调用子组件的方法
      }
    },

    //插入api模板
    insert_api_template() {
      if (DocConfig.lang == 'zh-cn') {
        var val = Base64.decode(
          'CiAgICAKKirnroDopoHmj4/ov7DvvJoqKiAKCi0g55So5oi35rOo5YaM5o6l5Y+jCgoqKuivt+axglVSTO+8mioqIAotIGAgaHR0cDovL3h4LmNvbS9hcGkvdXNlci9yZWdpc3RlciBgCiAgCioq6K+35rGC5pa55byP77yaKioKLSBQT1NUIAoKKirlj4LmlbDvvJoqKiAKCnzlj4LmlbDlkI185b+F6YCJfOexu+Wei3zor7TmmI58Cnw6LS0tLSAgICB8Oi0tLXw6LS0tLS0gfC0tLS0tICAgfAp8dXNlcm5hbWUgfOaYryAgfHN0cmluZyB855So5oi35ZCNICAgfAp8cGFzc3dvcmQgfOaYryAgfHN0cmluZyB8IOWvhueggSAgICB8CnxuYW1lICAgICB85ZCmICB8c3RyaW5nIHwg5pi156ewICAgIHwKCiAqKui/lOWbnuekuuS+iyoqCgpgYGAgCiAgewogICAgImVycm9yX2NvZGUiOiAwLAogICAgImRhdGEiOiB7CiAgICAgICJ1aWQiOiAiMSIsCiAgICAgICJ1c2VybmFtZSI6ICIxMjE1NDU0NSIsCiAgICAgICJuYW1lIjogIuWQtOezu+aMgiIsCiAgICAgICJncm91cGlkIjogMiAsCiAgICAgICJyZWdfdGltZSI6ICIxNDM2ODY0MTY5IiwKICAgICAgImxhc3RfbG9naW5fdGltZSI6ICIwIiwKICAgIH0KICB9CmBgYAoKICoq6L+U5Zue5Y+C5pWw6K+05piOKiogCgp85Y+C5pWw5ZCNfOexu+Wei3zor7TmmI58Cnw6LS0tLS0gIHw6LS0tLS18LS0tLS0gICAgICAgICAgICAgICAgICAgICAgICAgICB8Cnxncm91cGlkIHxpbnQgICB855So5oi357uEaWTvvIwx77ya6LaF57qn566h55CG5ZGY77ybMu+8muaZrumAmueUqOaItyAgfAoKICoq5aSH5rOoKiogCgotIOabtOWkmui/lOWbnumUmeivr+S7o+eggeivt+eci+mmlumhteeahOmUmeivr+S7o+eggeaPj+i/sAoKCg=='
        )
      } else {
        var val = Base64.decode(
          'ICAgIAoqKkJyaWVmIGRlc2NyaXB0aW9uOioqIAoKLSBVc2VyIFJlZ2lzdHJhdGlvbiBJbnRlcmZhY2UKCgoqKlJlcXVlc3QgVVJM77yaKiogCi0gYCBodHRwOi8veHguY29tL2FwaS91c2VyL3JlZ2lzdGVyIGAKICAKKipNZXRob2Q6KioKLSBQT1NUIAoKKipQYXJhbWV0ZXI6KiogCgp8UGFyYW1ldGVyIG5hbWV8UmVxdWlyZWR8VHlwZXxFeHBsYWlufAp8Oi0tLS0gICAgfDotLS18Oi0tLS0tIHwtLS0tLSAgIHwKfHVzZXJuYW1lIHxZZXMgIHxzdHJpbmcgfFlvdXIgdXNlcm5hbWUgICB8CnxwYXNzd29yZCB8WWVzICB8c3RyaW5nIHwgWW91ciBwYXNzd29yZCAgICB8CnxuYW1lICAgICB8Tm8gIHxzdHJpbmcgfCBZb3VyIG5hbWUgICAgfAoKICoqUmV0dXJuIGV4YW1wbGUqKgoKYGBgIAogIHsKICAgICJlcnJvcl9jb2RlIjogMCwKICAgICJkYXRhIjogewogICAgICAidWlkIjogIjEiLAogICAgICAidXNlcm5hbWUiOiAiMTIxNTQ1NDUiLAogICAgICAibmFtZSI6ICJoYXJyeSIsCiAgICAgICJncm91cGlkIjogMiAsCiAgICAgICJyZWdfdGltZSI6ICIxNDM2ODY0MTY5IiwKICAgICAgImxhc3RfbG9naW5fdGltZSI6ICIwIiwKICAgIH0KICB9CmBgYAoKICoqUmV0dXJuIHBhcmFtZXRlciBkZXNjcmlwdGlvbioqIAoKfFBhcmFtZXRlciBuYW1lfFR5cGV8RXhwbGFpbnwKfDotLS0tLSAgfDotLS0tLXwtLS0tLSAgICAgICAgICAgICAgICAgICAgICAgICAgIHwKfGdyb3VwaWQgfGludCAgIHwgIC58CgogKipSZW1hcmsqKiAKCi0gRm9yIG1vcmUgZXJyb3IgY29kZSByZXR1cm5zLCBzZWUgdGhlIGVycm9yIGNvZGUgZGVzY3JpcHRpb24gb24gdGhlIGhvbWUgcGFnZQoKCg=='
        )
      }
      this.insertValue(val)
    },

    //插入数据字典模板
    insert_database_template() {
      if (DocConfig.lang == 'zh-cn') {
        var val = Base64.decode(
          'CiAgICAKLSAg55So5oi36KGo77yM5YKo5a2Y55So5oi35L+h5oGvCgp85a2X5q61fOexu+Wei3znqbp86buY6K6kfOazqOmHinwKfDotLS0tICAgIHw6LS0tLS0tLSAgICB8Oi0tLSB8LS0gLXwtLS0tLS0gICAgICB8Cnx1aWQgICAgfGludCgxMCkgICAgIHzlkKYgfCAgfCAgICAgICAgICAgICB8Cnx1c2VybmFtZSB8dmFyY2hhcigyMCkgfOWQpiB8ICAgIHwgICDnlKjmiLflkI0gIHwKfHBhc3N3b3JkIHx2YXJjaGFyKDUwKSB85ZCmICAgfCAgICB8ICAg5a+G56CBICAgIHwKfG5hbWUgICAgIHx2YXJjaGFyKDE1KSB85pivICAgfCAgICB8ICAgIOaYteensCAgICAgfAp8cmVnX3RpbWUgfGludCgxMSkgICAgIHzlkKYgICB8IDAgIHwgICDms6jlhozml7bpl7QgIHwKCi0g5aSH5rOo77ya5pegCgoK'
        )
      } else {
        var val = Base64.decode(
          'ICAgIAotICBVc2VyIHRhYmxlICwgdG8gc3RvcmUgdXNlciBpbmZvcm1hdGlvbgoKCgp8RmllbGR8VHlwZXxFbXB0eXxEZWZhdWx0fEV4cGxhaW58Cnw6LS0tLSAgICB8Oi0tLS0tLS0gICAgfDotLS0gfC0tIC18LS0tLS0tICAgICAgfAp8dWlkICAgIHxpbnQoMTApICAgICB8Tm8gfCAgfCAgICAgICAgICAgICB8Cnx1c2VybmFtZSB8dmFyY2hhcigyMCkgfE5vIHwgICAgfCAgICAgfAp8cGFzc3dvcmQgfHZhcmNoYXIoNTApIHxObyAgIHwgICAgfCAgICAgICB8CnxuYW1lICAgICB8dmFyY2hhcigxNSkgfE5vICAgfCAgICB8ICAgICAgICAgfAp8cmVnX3RpbWUgfGludCgxMSkgICAgIHxObyAgIHwgMCAgfCAgICAuIHwKCi0gUmVtYXJrIDogbm8KCg=='
        )
      }
      this.insertValue(val)
    },
    //关闭预览
    editor_unwatch() {
      let childRef = this.$refs.Editormd //获取子组件
      childRef.editor_unwatch()
      if (sessionStorage.getItem('page_id_unwatch_' + this.page_id)) {
      } else {
        this.$alert(this.$t('long_page_tips'))
        sessionStorage.setItem('page_id_unwatch_' + this.page_id, 1)
      }
    },
    //
    editor_watch() {
      let childRef = this.$refs.Editormd //获取子组件
      childRef.editor_watch()
    },
    //json转参数表格
    ShowJsonToTable() {
      let childRef = this.$refs.JsonToTable //获取子组件
      childRef.dialogFormVisible = true
    },
    //json格式化
    ShowJsonBeautify() {
      let childRef = this.$refs.JsonBeautify //获取子组件
      childRef.dialogFormVisible = true
    },

    ShowRunApi() {
      window.open('http://runapi.showdoc.cc/')
    },
    //更多模板、模板列表
    ShowTemplateList() {
      let childRef = this.$refs.TemplateList //获取子组件
      childRef.show()
    },
    //粘贴插入表格
    ShowPasteTable() {
      let childRef = this.$refs.PasteTable //获取子组件
      childRef.dialogFormVisible = true
    },

    //展示历史版本
    ShowHistoryVersion() {
      let childRef = this.$refs.HistoryVersion //获取子组件
      childRef.show()
    },
    //展示页面排序
    ShowSortPage() {
      this.save(() => {
        let childRef = this.$refs.SortPage //获取子组件
        childRef.show()
      })
    },
    save(callback) {
      var that = this
      var loading = that.$loading()
      let childRef = this.$refs.Editormd
      var content = childRef.getMarkdown()
      var cat_id = this.cat_id
      var item_id = that.$route.params.item_id
      var page_id = that.$route.params.page_id
      var url = DocConfig.server + '/api/page/save'
      var params = new URLSearchParams()
      params.append('page_id', page_id)
      params.append('item_id', item_id)
      params.append('page_title', that.title)
      params.append('page_content', encodeURIComponent(content))
      params.append('is_urlencode', 1)
      params.append('cat_id', cat_id)
      that.axios.post(url, params).then(function(response) {
        loading.close()
        if (response.data.error_code === 0) {
          if (typeof callback == 'function') {
            callback()
          } else {
            that.$message({
              showClose: true,
              message: that.$t('save_success'),
              type: 'success'
            })
          }

          //删除草稿
          that.deleteDraft()

          if (page_id <= 0) {
            that.$router.push({
              path: '/page/edit/' + item_id + '/' + response.data.data.page_id
            })
            that.page_id = response.data.data.page_id
          }
        } else {
          that.$alert(response.data.error_message)
        }
      })
      //设置一个最长关闭时间
      setTimeout(() => {
        loading.close()
      }, 20000)
    },
    goback() {
      var url = '/' + this.$route.params.item_id
      this.$router.push({
        path: url,
        query: { page_id: this.$route.params.page_id }
      })
    },
    dropdown_callback(data) {
      if (data) {
        data()
      }
    },
    //另存为模板
    save_to_template() {
      var that = this
      let childRef = this.$refs.Editormd
      var content = childRef.getMarkdown()
      this.$prompt(that.$t('save_templ_title'), ' ', {}).then(function(data) {
        var url = DocConfig.server + '/api/template/save'
        var params = new URLSearchParams()
        params.append('template_title', data.value)
        params.append('template_content', content)
        that.axios.post(url, params).then(function(response) {
          if (response.data.error_code === 0) {
            that.$alert(that.$t('save_templ_text'))
          } else {
            that.$alert(response.data.error_message)
          }
        })
      })
    },
    //附件
    ShowAttachment() {
      let childRef = this.$refs.AttachmentList //获取子组件
      childRef.show()
    },
    /** 粘贴上传图片 **/
    upload_paste_img(e) {
      var that = this
      var url = DocConfig.server + '/api/page/uploadImg'
      var clipboard = e.clipboardData
      for (var i = 0, len = clipboard.items.length; i < len; i++) {
        if (
          clipboard.items[i].kind == 'file' ||
          clipboard.items[i].type.indexOf('image') > -1
        ) {
          var imageFile = clipboard.items[i].getAsFile()
          var form = new FormData()
          form.append('t', 'ajax-uploadpic')
          form.append('editormd-image-file', imageFile)
          var loading = ''
          var callback = function(type, data) {
            type = type || 'before'
            switch (type) {
              // 开始上传
              case 'before':
                loading = that.$loading()
                break
              // 服务器返回错误
              case 'error':
                loading.close()
                that.$alert('图片上传失败')
                break
              // 上传成功
              case 'success':
                loading.close()
                if (data.success == 1) {
                  var value = '![](' + data.url + ')'
                  that.insertValue(value)
                } else {
                  that.$alert(data.message)
                }

                break
            }
          }
          $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: form,
            processData: false,
            contentType: false,
            beforeSend: function() {
              callback('before')
            },
            error: function() {
              callback('error')
            },
            success: function(data) {
              callback('success', data)
            }
          })
          e.preventDefault()
        }
      }
    },
    //草稿
    draft() {
      var that = this
      var pkey = 'page_content_' + this.page_id
      //定时保存文本内容到localStorage
      setInterval(() => {
        let childRef = this.$refs.Editormd
        var content = childRef.getMarkdown()
        localStorage.setItem(pkey, content)
      }, 30 * 1000)

      //检测是否有定时保存的内容
      var page_content = localStorage.getItem(pkey)
      if (page_content && page_content.length > 0) {
        localStorage.removeItem(pkey)
        that
          .$confirm(that.$t('draft_tips'), '', {
            showClose: false
          })
          .then(() => {
            that.insertValue(page_content, true)
            localStorage.removeItem(pkey)
          })
          .catch(() => {
            localStorage.removeItem(pkey)
          })
      }
    },

    //遍历删除草稿
    deleteDraft() {
      for (var i = 0; i < localStorage.length; i++) {
        var name = localStorage.key(i)
        if (name.indexOf('page_content_') > -1) {
          localStorage.removeItem(name)
        }
      }
    }
  },

  mounted() {
    var that = this
    this.page_id = this.$route.params.page_id
    this.copy_page_id = this.$route.query.copy_page_id
      ? this.$route.query.copy_page_id
      : ''

    if (this.copy_page_id > 0) {
      this.get_page_content(this.copy_page_id)
    } else if (this.page_id > 0) {
      this.get_page_content(this.page_id)
    } else {
      this.item_id = this.$route.params.item_id
      this.content = this.$t('welcome_use_showdoc')
    }
    this.get_catalog(this.$route.params.item_id)

    this.draft()

    /** 监听粘贴上传图片 **/
    document.addEventListener('paste', this.upload_paste_img)
  },

  beforeDestroy() {
    //解除对粘贴上传图片的监听
    document.removeEventListener('paste', this.upload_paste_img)
    this.$message.closeAll()
  }
}
</script>
