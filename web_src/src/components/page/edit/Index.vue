<template>
  <div
    class="hello"
    @keydown.ctrl.83.prevent="save"
    @keydown.meta.83.prevent="save"
  >
    <Header></Header>

    <el-container class="container-narrow">
      <el-row class="masthead">
        <el-form :inline="true" class="demo-form-inline" size="small">
          <el-form-item :label="$t('title') + ' : '">
            <el-input placeholder v-model="title"></el-input>
          </el-form-item>

          <el-form-item :label="$t('catalog') + ' : '">
            <el-select
              :placeholder="$t('optional')"
              class="cat"
              v-model="cat_id"
              v-if="belong_to_catalogs"
              filterable
            >
              <el-option
                v-for="cat in belong_to_catalogs"
                :key="cat.cat_name"
                :label="cat.cat_name"
                :value="cat.cat_id"
              ></el-option>
            </el-select>
          </el-form-item>

          <el-form-item label>
            <el-tooltip class="item" effect="dark" :content="$t('refresh_cat')">
              <i class="el-icon-refresh-right icon-btn" @click="refreshCat"></i>
            </el-tooltip>
          </el-form-item>
          <el-form-item label>
            <el-tooltip class="item" effect="dark" :content="$t('go_add_cat')">
              <i class="el-icon-plus icon-btn" @click="goToCat"></i>
            </el-tooltip>
          </el-form-item>

          <el-form-item label>
            <el-tooltip class="item" effect="dark" :content="$t('sort_pages')">
              <i class="el-icon-sort icon-btn" @click="showSortPage"></i>
            </el-tooltip>
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
              <span id="save-page">{{ $t('save') }}</span>
              <el-dropdown-menu slot="dropdown">
                <el-dropdown-item :command="save_to_template">{{
                  $t('save_to_templ')
                }}</el-dropdown-item>
                <el-tooltip
                  class="item"
                  effect="dark"
                  :content="$t('lock_edit_tips')"
                  placement="left"
                >
                  <el-dropdown-item v-if="!isLock" :command="setLock">{{
                    $t('lock_edit')
                  }}</el-dropdown-item>
                </el-tooltip>
                <el-dropdown-item v-if="isLock" :command="unlock">{{
                  $t('cacel_lock')
                }}</el-dropdown-item>
                <!-- <el-dropdown-item>保存前添加注释</el-dropdown-item> -->
              </el-dropdown-menu>
            </el-dropdown>
            <el-button type size="medium" @click="showNotify">{{
              $t('save_and_notify')
            }}</el-button>
            <el-button type size="medium" @click="goback">{{
              $t('goback')
            }}</el-button>
          </el-form-item>
        </el-form>

        <el-row class="fun-btn-group">
          <el-button type size="medium" @click="insert_api_template">{{
            $t('insert_apidoc_template')
          }}</el-button>
          <el-button type size="medium" @click="insert_database_template">{{
            $t('insert_database_doc_template')
          }}</el-button>
          <el-button
            type
            size="medium"
            @click.native="templateVisiable = true"
            >{{ $t('more_templ') }}</el-button
          >

          <el-dropdown
            split-button
            type
            style="margin-left:100px;"
            size="medium"
            trigger="hover"
          >
            {{ $t('format_tools') }}
            <el-dropdown-menu slot="dropdown">
              <el-dropdown-item @click.native="ShowJsonToTable">{{
                $t('json_to_table')
              }}</el-dropdown-item>
              <el-dropdown-item @click.native="ShowJsonBeautify">{{
                $t('beautify_json')
              }}</el-dropdown-item>
              <el-dropdown-item @click.native="ShowPasteTable">{{
                $t('paste_insert_table')
              }}</el-dropdown-item>
              <el-dropdown-item @click.native="showSqlToMarkdownTable">{{
                $t('sql_to_markdown_table')
              }}</el-dropdown-item>
            </el-dropdown-menu>
          </el-dropdown>
          <el-button
            v-if="lang == 'zh-cn'"
            type
            size="medium"
            @click="showMockDialog = true"
            >Mock</el-button
          >
          <el-button
            v-if="lang == 'zh-cn'"
            type
            size="medium"
            @click="ShowRunApi"
            >{{ $t('http_test_api') }}</el-button
          >

          <el-badge :value="attachment_count" class="item">
            <el-button type size="medium" @click="ShowAttachment">{{
              $t('attachment')
            }}</el-button>
          </el-badge>
          <el-button size="medium" @click="ShowHistoryVersion">{{
            $t('history_version')
          }}</el-button>
        </el-row>

        <Editormd
          v-bind:content="content"
          v-if="content"
          ref="Editormd"
          type="editor"
          id="page-editor"
        ></Editormd>
      </el-row>

      <!-- 更多模板 -->
      <TemplateList
        v-if="templateVisiable"
        :item_id="item_id"
        :callback="
          data => {
            if (data && typeof data == 'string') {
              insertValue(data)
            }
            templateVisiable = false
          }
        "
        ref="TemplateList"
      ></TemplateList>

      <!-- 历史版本 -->
      <HistoryVersion
        :callback="insertValue"
        :is_show_recover_btn="true"
        ref="HistoryVersion"
      ></HistoryVersion>

      <!-- Json转表格 组件 -->
      <JsonToTable :callback="insertValue" ref="JsonToTable"></JsonToTable>

      <!-- Json格式化 -->
      <JsonBeautify :callback="insertValue" ref="JsonBeautify"></JsonBeautify>

      <!-- sql转表格 -->
      <SqlToMarkdownTable
        :callback="insertValue"
        ref="SqlToMarkdownTable"
      ></SqlToMarkdownTable>

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
        v-if="sortPageVisiable"
        :callback="
          () => {
            sortPageVisiable = false
          }
        "
        :item_id="item_id"
        :page_id="page_id"
        :cat_id="cat_id"
        ref="SortPage"
      ></SortPage>
      <!-- mock -->
      <Mock
        :page_id="page_id"
        :item_id="item_id"
        v-if="showMockDialog"
        :callback="
          data => {
            if (data) {
              insertValue(data)
            }
            showMockDialog = false
          }
        "
        ref="Mock"
      ></Mock>

      <!-- 通知框 -->
      <Notify
        :page_id="page_id"
        :item_id="item_id"
        v-if="notifyVisiable"
        :callback="
          data => {
            notifyVisiable = false
            if (data && typeof data == 'string') {
              is_notify = 1
              notify_content = data
              save(() => {
                goback()
              })
            }
          }
        "
        ref="Notify"
      ></Notify>
    </el-container>
    <!-- 一个隐藏的可编辑元素。用于承载粘贴html代码。后续会用于转markdown -->
    <div contenteditable="true" id="pastebin"></div>

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
.icon-btn {
  cursor: pointer;
  margin-left: 5px;
}
#pastebin {
  opacity: 0.01;
  width: 100%;
  height: 1px;
  overflow: hidden;
}
</style>

<script>
import Editormd from '@/components/common/Editormd'
import JsonToTable from '@/components/common/JsonToTable'
import JsonBeautify from '@/components/common/JsonBeautify'
import SqlToMarkdownTable from '@/components/common/SqlToMarkdownTable'
import Mock from '@/components/common/Mock'
import TemplateList from '@/components/page/edit/TemplateList'
import HistoryVersion from '@/components/page/edit/HistoryVersion'
import AttachmentList from '@/components/page/edit/AttachmentList'
import PasteTable from '@/components/page/edit/PasteTable'
import SortPage from '@/components/page/edit/SortPage'
import Notify from '@/components/page/edit/Notify'
import { Base64 } from 'js-base64'
import { rederPageContent } from '@/models/page'
const turndownPluginGfm = require('turndown-plugin-gfm')

import {
  apiTemplateZh,
  databaseTemplateZh,
  apiTemplateEn,
  databaseTemplateEn
} from '@/models/template'
import TurndownService from 'turndown'

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
      catalogs: [],
      isLock: 0,
      intervalId: 0,
      saving: false,
      showMockDialog: false,
      lang: '',
      sortPageVisiable: false,
      notifyVisiable: false,
      is_notify: 0,
      notify_content: '',
      templateVisiable: false
    }
  },
  computed: {
    // 新建/编辑目录时供用户选择的上级目录列表
    belong_to_catalogs: function() {
      if (!this.catalogs || this.catalogs.length <= 0) {
        return []
      }

      var Info = this.catalogs.slice(0)
      var cat_array = []

      // 这个函数将递归
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
    SortPage,
    Mock,
    Notify,
    SqlToMarkdownTable
  },
  methods: {
    // 获取页面内容
    get_page_content(page_id) {
      if (!page_id) {
        page_id = this.page_id
      }
      var that = this
      var url = DocConfig.server + '/api/page/info'
      var params = new URLSearchParams()
      params.append('page_id', page_id)
      that.axios
        .post(url, params)
        .then(function(response) {
          if (response.data.error_code === 0) {
            // that.$message.success("加载成功");
            that.content = rederPageContent(response.data.data.page_content)
            setTimeout(function() {
              that.insertValue(that.content, 1)
              document.body.scrollTop = document.documentElement.scrollTop = 0 // 回到顶部
            }, 500)
            setTimeout(function() {
              // 如果长度大于3000,则关闭预览
              if (that.content.length > 3000) {
                that.editor_unwatch()
              } else {
                that.editor_watch()
              }
              // 开启草稿
              that.draft()
            }, 1000)
            that.title = response.data.data.page_title
            that.item_id = response.data.data.item_id
            that.cat_id = response.data.data.cat_id
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

    // 获取所有目录
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
          } else {
            that.$alert(response.data.error_message)
          }
        })
        .catch(function(error) {
          console.log(error)
        })
    },

    // 插入数据到编辑器中。插入到光标处。如果参数is_cover为真，则清空后再插入(即覆盖)。
    insertValue(value, is_cover) {
      if (value) {
        let childRef = this.$refs.Editormd // 获取子组件
        if (is_cover) {
          // 清空
          childRef.clear()
          childRef.insertValue(value)
          childRef.setCursorToTop()
        } else {
          childRef.insertValue(value)
        }
      }
    },

    // 插入api模板
    insert_api_template() {
      var val
      if (DocConfig.lang == 'zh-cn') {
        val = apiTemplateZh
      } else {
        val = apiTemplateEn
      }
      this.insertValue(val)
    },

    // 插入数据字典模板
    insert_database_template() {
      var val
      if (DocConfig.lang == 'zh-cn') {
        val = databaseTemplateZh
      } else {
        val = databaseTemplateEn
      }
      this.insertValue(val)
    },
    // 关闭预览
    editor_unwatch() {
      let childRef = this.$refs.Editormd // 获取子组件
      childRef.editor_unwatch()
      if (localStorage.getItem('page_id_unwatch_' + this.page_id)) {
      } else {
        this.$message(this.$t('long_page_tips'))
        localStorage.setItem('page_id_unwatch_' + this.page_id, 1)
      }
    },
    //
    editor_watch() {
      let childRef = this.$refs.Editormd // 获取子组件
      childRef.editor_watch()
    },
    // json转参数表格
    ShowJsonToTable() {
      let childRef = this.$refs.JsonToTable // 获取子组件
      childRef.dialogFormVisible = true
    },
    // json格式化
    ShowJsonBeautify() {
      let childRef = this.$refs.JsonBeautify // 获取子组件
      childRef.dialogFormVisible = true
    },
    // SQL转表格
    showSqlToMarkdownTable() {
      let childRef = this.$refs.SqlToMarkdownTable // 获取子组件
      childRef.dialogFormVisible = true
    },
    ShowRunApi() {
      window.open('http://runapi.showdoc.cc/')
    },
    // 粘贴插入表格
    ShowPasteTable() {
      let childRef = this.$refs.PasteTable // 获取子组件
      childRef.dialogFormVisible = true
    },

    // 展示历史版本
    ShowHistoryVersion() {
      let childRef = this.$refs.HistoryVersion // 获取子组件
      childRef.show()
    },
    // 展示页面排序
    showSortPage() {
      this.save(() => {
        this.sortPageVisiable = true
      })
    },
    // 展示通知对话框
    showNotify() {
      if (this.page_id > 0) {
        this.notifyVisiable = true
      } else {
        this.save(() => {
          this.notifyVisiable = true
        })
      }
    },
    save(callback) {
      var that = this
      if (this.saving) {
        return false
      }
      this.saving = true
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
      params.append('is_notify', that.is_notify)
      params.append('notify_content', that.notify_content)
      params.append('page_content', encodeURIComponent(content))
      params.append('is_urlencode', 1)
      params.append('cat_id', cat_id)
      that.axios.post(url, params).then(function(response) {
        loading.close()
        that.saving = false
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

          // 删除草稿
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
      // 设置一个最长关闭时间
      setTimeout(() => {
        loading.close()
        that.saving = false
      }, 20000)
    },
    goback() {
      var url =
        '/' + this.$route.params.item_id + '/' + this.$route.params.page_id
      this.$router.push({
        path: url
      })
    },
    dropdown_callback(data) {
      if (data) {
        data()
      }
    },
    // 另存为模板
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
    // 附件
    ShowAttachment() {
      let childRef = this.$refs.AttachmentList // 获取子组件
      childRef.show()
    },
    /** 监听剪切板 **/
    // 以实现粘贴上传图片，html网页转markdown等
    clipboardEvents(e) {
      var that = this
      // 如果当前鼠标的焦点不在编辑器内，则中止
      if (
        !document.querySelector('#page-editor').contains(document.activeElement)
      ) {
        return
      }
      var url = DocConfig.server + '/api/page/uploadImg'
      var clipboard = e.clipboardData
      for (var i = 0, len = clipboard.items.length; i < len; i++) {
        // 如果剪切板里的内容是图片
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
              // eslint-disable-next-line standard/no-callback-literal
              callback('before')
            },
            error: function() {
              // eslint-disable-next-line standard/no-callback-literal
              callback('error')
            },
            success: function(data) {
              // eslint-disable-next-line standard/no-callback-literal
              callback('success', data)
            }
          })
          e.preventDefault()
        }
        // 如果剪切板里的内容是html
        else if (clipboard.items[i].type == 'text/html') {
          e.preventDefault() // 阻止默认粘贴事件
          clipboard.items[i].getAsString(htmlData => {
            var pastebin = document.querySelector('#pastebin')
            pastebin.innerHTML = htmlData
            var text = pastebin.innerText // 利用插入html元素来获取其纯文本
            // 如果其纯文本内容少于200字，那就当作是纯文本粘贴
            if (text.length < 200) {
              that.insertValue(text)
            } else {
              // 如果其纯文本内容多于200字，那就问要转markdown还是纯文本粘贴
              that
                .$confirm(that.$t('past_html_tips'), ' ', {
                  confirmButtonText: that.$t('past_html_markdown'),
                  cancelButtonText: that.$t('past_html_text')
                })
                .then(data => {
                  var turndownService = new TurndownService()
                  var gfm = turndownPluginGfm.gfm
                  var tables = turndownPluginGfm.tables
                  var strikethrough = turndownPluginGfm.strikethrough
                  turndownService.use([gfm, tables, strikethrough])
                  var markdown = turndownService.turndown(htmlData)
                  that.insertValue(markdown)
                })
                .catch(() => {
                  that.insertValue(text)
                })
            }
          })
        } else {
          // 无动作。让默认粘贴事件做事。
        }
      }
    },
    // 草稿
    draft() {
      var that = this
      var pkey = 'page_content_' + this.page_id
      let childRef = this.$refs.Editormd
      // 定时保存文本内容到localStorage
      setInterval(() => {
        var content = childRef.getMarkdown()
        localStorage.setItem(pkey, content)
      }, 30 * 1000)

      // 检测是否有定时保存的内容
      var page_content = localStorage.getItem(pkey)
      if (
        page_content &&
        page_content.length > 0 &&
        page_content != childRef.getMarkdown() &&
        childRef.getMarkdown() &&
        childRef.getMarkdown().length > 10
      ) {
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

    // 遍历删除草稿
    deleteDraft() {
      for (var i = 0; i < localStorage.length; i++) {
        var name = localStorage.key(i)
        if (name.indexOf('page_content_') > -1) {
          localStorage.removeItem(name)
        }
      }
    },

    // 锁定
    setLock() {
      if (this.page_id > 0) {
        this.request('/api/page/setLock', {
          page_id: this.page_id,
          item_id: this.item_id
        }).then(() => {
          this.isLock = 1
        })
      }
    },
    // 解除锁定
    unlock() {
      if (!this.isLock) {
        return // 本来处于未锁定中的话，不发起请求
      }
      this.request('/api/page/setLock', {
        page_id: this.page_id,
        item_id: this.item_id,
        lock_to: 1000
      }).then(() => {
        this.isLock = 0
      })
    },
    // 如果用户处于锁定状态的话，用心跳保持锁定
    heartBeatLock() {
      this.intervalId = setInterval(() => {
        if (this.isLock) {
          this.setLock()
        }
      }, 3 * 60 * 1000)
    },
    // 判断页面是否被锁定编辑
    remoteIsLock() {
      this.request('/api/page/isLock', {
        page_id: this.page_id
      }).then(res => {
        // 判断已经锁定了不
        if (res.data.lock > 0) {
          if (res.data.is_cur_user > 0) {
            this.isLock = 1
          } else {
            this.$alert(this.$t('locking') + res.data.lock_username)
            this.goback()
          }
        } else {
          this.setLock() // 如果没有被别人锁定，则进编辑页面后自己锁定。
        }
      })
    },
    // 由于页面关闭事件无法直接发起异步的ajax请求，所以用浏览器的navigator.sendBeacon来实现
    unLockOnClose() {
      let user_token = ''
      const userinfostr = localStorage.getItem('userinfo')
      if (userinfostr) {
        const userinfo = JSON.parse(userinfostr)
        if (userinfo && userinfo.user_token) {
          user_token = userinfo.user_token
        }
      }
      let analyticsData = new URLSearchParams({
        page_id: this.page_id,
        item_id: this.item_id,
        lock_to: 1000,
        user_token: user_token
      })
      let url = DocConfig.server + '/api/page/setLock'
      if ('sendBeacon' in navigator) {
        navigator.sendBeacon(url, analyticsData)
      } else {
        var client = new XMLHttpRequest()
        client.open('POST', url, false)
        client.send(analyticsData)
      }
    },
    // 刷新目录列表
    refreshCat() {
      this.get_catalog(this.item_id)
    },
    // 去新建目录
    goToCat() {
      let routeUrl = this.$router.resolve({
        path: '/catalog/' + this.item_id
      })
      window.open(routeUrl.href, '_blank')
    }
  },

  mounted() {
    this.page_id = this.$route.params.page_id
    this.item_id = this.$route.params.item_id
    this.copy_page_id = this.$route.query.copy_page_id
      ? this.$route.query.copy_page_id
      : ''

    if (this.copy_page_id > 0) {
      this.get_page_content(this.copy_page_id)
    } else if (this.page_id > 0) {
      this.get_page_content(this.page_id)
    } else {
      this.content = '\n'
    }
    this.get_catalog(this.$route.params.item_id)

    this.heartBeatLock()
    this.remoteIsLock()
    /** 监听剪切板 **/
    document.addEventListener('paste', this.clipboardEvents)

    this.lang = DocConfig.lang
    window.addEventListener('beforeunload', this.unLockOnClose)
    let g_open_cat_id = this.$store.state.open_cat_id // 全局变量-当前打开的目录id
    // 如果this.page_id无效，则可以判定用户是在新建页面
    // 此时可以考虑把目录设置为用户当前打开的页面的所属目录
    if (this.page_id <= 0 && g_open_cat_id > 0) {
      this.cat_id = g_open_cat_id
    }
  },

  beforeDestroy() {
    // 解除对剪切板的监听
    document.removeEventListener('paste', this.clipboardEvents)
    this.$message.closeAll()
    clearInterval(this.intervalId)
    this.unlock()
    window.removeEventListener('beforeunload', this.unLockOnClose)
  }
}
</script>
