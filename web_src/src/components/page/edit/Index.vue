<template>
  <div>
    <el-dialog
      class="sdialog"
      width="98%"
      :visible.sync="dialogVisible"
      @close="goback"
      :show-close="false"
      top="2vh"
      :append-to-body="true"
      :close-on-press-escape="false"
      :close-on-click-modal="false"
    >
      <div slot="title" class="title-header pt-4">
        <div class="inline-block">
          <el-button
            class="close-btn"
            type="text"
            icon="el-icon-close"
            @click="goback"
          ></el-button>

          <el-tooltip effect="dark" :content="$t('click_to_edit_page_title')">
            <span @click="editTitle" class="page-title">{{ title }}</span>
          </el-tooltip>

          <el-tooltip effect="dark" :content="$t('select_catalog')">
            <span
              @click="showSelectCat = true"
              class="cat-class v3-color-aux v3-font-size-sm"
            >
              <i class="el-icon-folder-opened mr-1"></i>{{ currentCatName }}
            </span>
          </el-tooltip>
        </div>

        <div class="inline-block float-right">
          <el-button class="mr-2" type size="medium" @click="showNotify"
            ><i class="el-icon-s-comment mr-2"></i
            >{{ $t('save_and_notify') }}</el-button
          >

          <el-dropdown
            @command="dropdownCallback"
            split-button
            type="primary"
            size="medium"
            trigger="click"
            title="Ctrl + S"
            @click="save"
          >
            <span id="save-page"
              ><i class="el-icon-s-shop mr-2"></i>{{ $t('save') }}</span
            >
            <el-dropdown-menu slot="dropdown">
              <el-dropdown-item @click.native="saveToTemplate">{{
                $t('save_to_templ')
              }}</el-dropdown-item>
              <el-tooltip
                effect="dark"
                :content="$t('lock_edit_tips')"
                placement="left"
              >
                <el-dropdown-item v-if="!isLock" @click.native="setLock">{{
                  $t('lock_edit')
                }}</el-dropdown-item>
              </el-tooltip>
              <el-dropdown-item v-if="isLock" @click.native="unlock">{{
                $t('cacel_lock')
              }}</el-dropdown-item>
              <!-- <el-dropdown-item>保存前添加注释</el-dropdown-item> -->
            </el-dropdown-menu>
          </el-dropdown>
        </div>
      </div>

      <div @keydown.ctrl.83.prevent="save" @keydown.meta.83.prevent="save">
        <el-row class="fun-btn-group">
          <el-dropdown type class="" size="medium" trigger="hover">
            <el-button icon="el-icon-s-tools">
              {{ $t('doc_tool')
              }}<i class="el-icon-arrow-down el-icon--right"></i>
            </el-button>
            <el-dropdown-menu slot="dropdown">
              <el-dropdown-item @click.native="showHistoryVersionDialog = true"
                ><i class="el-icon-lock"> </i
                >{{ $t('history_version') }}</el-dropdown-item
              >
              <el-dropdown-item @click.native="showAttachment">
                <el-badge :value="attachment_count"
                  ><i class="el-icon-upload"> </i
                  >{{ $t('attachment') }}</el-badge
                ></el-dropdown-item
              >
              <el-dropdown-item @click.native="showSortPage"
                ><i class="el-icon-sort"> </i
                >{{ $t('sort_pages') }}</el-dropdown-item
              >

              <el-dropdown-item @click.native="showJsonToTableDialog = true"
                ><i class="el-icon-film"> </i
                >{{ $t('json_to_table') }}</el-dropdown-item
              >
              <el-dropdown-item @click.native="showJsonBeautifyDialog = true"
                ><i class="el-icon-scissors"> </i
                >{{ $t('beautify_json') }}</el-dropdown-item
              >
              <el-dropdown-item @click.native="showPasteTableDialog = true"
                ><i class="el-icon-attract"> </i
                >{{ $t('paste_insert_table') }}</el-dropdown-item
              >
              <el-dropdown-item
                @click.native="showSqlToMarkdownTableDialog = true"
                ><i class="el-icon-takeaway-box"> </i
                >{{ $t('sql_to_markdown_table') }}</el-dropdown-item
              >
              <el-dropdown-item
                v-if="$lang == 'zh-cn'"
                @click.native="showMock = true"
                ><i class="el-icon-video-camera"> </i>Mock
              </el-dropdown-item>
              <el-dropdown-item
                v-if="$lang == 'zh-cn'"
                @click.native="showRunApi"
                ><i class="el-icon-video-camera"> </i
                >{{ $t('http_test_api') }}</el-dropdown-item
              >
            </el-dropdown-menu>
          </el-dropdown>

          <el-button
            icon="el-icon-takeaway-box"
            v-if="$lang == 'zh-cn' && showAIBtn"
            style="padding-top: 12px;padding-bottom: 12px;"
            @click.native="showAI = true"
            size="medium"
            @click="createContent"
            >{{ $t('ai_assistant') }}</el-button
          >

          <el-dropdown type class="" size="medium" trigger="hover">
            <el-button icon="el-icon-document">
              {{ $t('add_from_template')
              }}<i class="el-icon-arrow-down el-icon--right"></i>
            </el-button>
            <el-dropdown-menu slot="dropdown">
              <el-dropdown-item @click.native="insertApiTemplate"
                ><i class="el-icon-chicken"> </i
                >{{ $t('insert_apidoc_template') }}</el-dropdown-item
              >
              <el-dropdown-item @click.native="insertDatabaseTemplate"
                ><i class="el-icon-sugar"> </i
                >{{ $t('insert_database_doc_template') }}</el-dropdown-item
              >
              <el-dropdown-item @click.native="showTemplateDialog = true"
                ><i class="el-icon-document-copy"> </i
                >{{ $t('more_templ') }}</el-dropdown-item
              >
            </el-dropdown-menu>
          </el-dropdown>
        </el-row>
        <Editormd
          v-bind:content="content"
          v-if="showContent"
          ref="Editormd"
          type="editor"
          id="page-editor"
        ></Editormd>
      </div>

      <div slot="footer" class="dialog-footer"></div>
    </el-dialog>

    <!-- 更多模板 -->
    <TemplateList
      v-if="showTemplateDialog"
      :item_id="item_id"
      :callback="
        data => {
          if (data && typeof data == 'string') {
            insertValue(data)
          }
          showTemplateDialog = false
        }
      "
      ref="TemplateList"
    ></TemplateList>

    <!-- 历史版本 -->
    <HistoryVersion
      :is_show_recover_btn="true"
      v-if="showHistoryVersionDialog"
      :page_id="page_id"
      :callback="
        data => {
          this.showHistoryVersionDialog = false
          if (data && typeof data == 'string') {
            insertValue(data)
          }
        }
      "
    ></HistoryVersion>

    <!-- Json转表格 组件 -->
    <JsonToTable
      v-if="showJsonToTableDialog"
      :callback="
        data => {
          this.showJsonToTableDialog = false
          if (data && typeof data == 'string') {
            insertValue(data)
          }
        }
      "
    ></JsonToTable>

    <!-- Json格式化 -->
    <JsonBeautify
      v-if="showJsonBeautifyDialog"
      :callback="
        data => {
          this.showJsonBeautifyDialog = false
          if (data && typeof data == 'string') {
            insertValue(data)
          }
        }
      "
    ></JsonBeautify>

    <!-- sql转表格 -->
    <SqlToMarkdownTable
      v-if="showSqlToMarkdownTableDialog"
      :callback="
        data => {
          this.showSqlToMarkdownTableDialog = false
          if (data && typeof data == 'string') {
            insertValue(data)
          }
        }
      "
    ></SqlToMarkdownTable>

    <!-- 附件列表 -->
    <AttachmentList
      :item_id="item_id"
      :manage="true"
      :page_id="page_id"
      v-if="showAttachmentListDialog"
      :callback="
        data => {
          this.showAttachmentListDialog = false
          if (data && typeof data == 'string') {
            insertValue(data)
          }
        }
      "
    ></AttachmentList>

    <!-- 粘贴插入表格 -->
    <PasteTable
      v-if="showPasteTableDialog"
      :callback="
        data => {
          this.showPasteTableDialog = false
          if (data && typeof data == 'string') {
            insertValue(data)
          }
        }
      "
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

    <SelectCat
      :cat_id="cat_id"
      :item_id="item_id"
      v-if="showSelectCat"
      :callback="
        data => {
          this.showSelectCat = false
          if (data && (typeof data == 'string' || typeof data == 'number')) {
            this.cat_id = data
            refreshCat()
          }
        }
      "
    ></SelectCat>

    <Mock
      :page_id="page_id"
      :item_id="item_id"
      v-if="showMock"
      :callback="
        () => {
          showMock = false
        }
      "
    ></Mock>

    <AI
      :page_id="page_id"
      :item_id="item_id"
      v-if="showAI"
      :callback="
        data => {
          this.showAI = false
          if (data && typeof data == 'string') {
            insertValue(data)
          }
        }
      "
    ></AI>

    <!-- 一个隐藏的可编辑元素。用于承载粘贴html代码。后续会用于转markdown -->
    <div contenteditable="true" id="pastebin"></div>
  </div>
</template>

<script>
import Editormd from '@/components/common/Editormd'
import JsonToTable from '@/components/common/JsonToTable'
import JsonBeautify from '@/components/common/JsonBeautify'
import SqlToMarkdownTable from '@/components/common/SqlToMarkdownTable'
import TemplateList from '@/components/page/edit/TemplateList'
import HistoryVersion from '@/components/page/edit/HistoryVersion'
import AttachmentList from '@/components/page/edit/AttachmentList'
import PasteTable from '@/components/page/edit/PasteTable'
import SortPage from '@/components/page/edit/SortPage'
import Notify from '@/components/page/edit/Notify'
import Mock from '@/components/page/edit/Mock'
import SelectCat from '@/components/catalog/Select'
import { Base64 } from 'js-base64'
import { rederPageContent } from '@/models/page'
import { getUserInfoFromStorage } from '@/models/user.js'
import AI from '@/components/page/edit/AI'

import {
  apiTemplateZh,
  databaseTemplateZh,
  apiTemplateEn,
  databaseTemplateEn
} from '@/models/template'
import TurndownService from 'turndown'

const turndownPluginGfm = require('turndown-plugin-gfm')

export default {
  props: {
    callback: () => {},
    item_id: 0,
    edit_page_id: 0,
    copy_page_id: 0
  },
  data() {
    return {
      page_id: 0,
      content: '',
      title: '',
      cat_id: '',
      s_number: '',
      attachment_count: '',
      catalogs: [],
      isLock: 0,
      intervalId: 0,
      saving: false,
      sortPageVisiable: false,
      notifyVisiable: false,
      is_notify: 0,
      notify_content: '',
      showTemplateDialog: false,
      showJsonToTableDialog: false,
      showJsonBeautifyDialog: false,
      showSqlToMarkdownTableDialog: false,
      showHistoryVersionDialog: false,
      showAttachmentListDialog: false,
      showPasteTableDialog: false,
      user_token: '',
      dialogVisible: true,
      showSelectCat: false,
      currentCatName: '/',
      showContent: false,
      showMock: false,
      showAI: false,
      showAIBtn: false
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
    Notify,
    SqlToMarkdownTable,
    SelectCat,
    Mock,
    AI
  },
  methods: {
    // 获取页面内容
    getPageContent(page_id) {
      if (!page_id) {
        page_id = this.page_id
      }
      this.request('/api/page/info', {
        page_id: page_id
      }).then(data => {
        const json = data.data

        this.content = rederPageContent(json.page_content)
        this.showContent = true

        setTimeout(() => {
          // 如果长度大于4000,则关闭预览
          if (this.content.length > 4000) {
            this.editorUnwatch()
          } else {
            this.editorWatch()
          }

          // 开启草稿
          this.draft()
        }, 1000)

        this.title = json.page_title
        this.item_id = json.item_id
        this.cat_id = json.cat_id
        this.s_number = json.s_number
        this.attachment_count =
          json.attachment_count > 0 ? '...' : this.attachment_count
        this.refreshCat()
      })
    },

    // 获取所有目录
    getCatalog(item_id) {
      this.request('/api/catalog/catListName', {
        item_id: item_id
      }).then(data => {
        var Info = data.data
        Info.unshift({ cat_id: '0', cat_name: '/' })
        this.catalogs = Info
        if (this.catalogs && this.catalogs.length) {
          for (let index = 0; index < this.catalogs.length; index++) {
            const element = this.catalogs[index]
            if (element.cat_id == this.cat_id) {
              this.currentCatName = element.cat_name
              return
            }
          }
        }
        this.currentCatName = '/'
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
    insertApiTemplate() {
      if (this.$lang == 'zh-cn') {
        this.insertValue(apiTemplateZh)
      } else {
        this.insertValue(apiTemplateEn)
      }
    },

    // 插入数据字典模板
    insertDatabaseTemplate() {
      if (this.$lang == 'zh-cn') {
        this.insertValue(databaseTemplateZh)
      } else {
        this.insertValue(databaseTemplateEn)
      }
    },
    // 关闭预览
    editorUnwatch() {
      let childRef = this.$refs.Editormd // 获取子组件
      childRef.editorUnwatch()
      if (localStorage.getItem('page_id_unwatch_' + this.page_id)) {
      } else {
        this.$alert(
          '检测到本页面内容比较多，showdoc暂时关闭了html实时预览功能，以防止过多内容造成页面卡顿。你可以在编辑栏中找到预览按钮进行手动打开。'
        )
        localStorage.setItem('page_id_unwatch_' + this.page_id, 1)
      }
    },
    //
    editorWatch() {
      let childRef = this.$refs.Editormd // 获取子组件
      childRef.editorWatch()
    },

    showRunApi() {
      window.open('http://runapi.showdoc.cc/')
    },
    // 附件
    showAttachment() {
      this.showAttachmentListDialog = true
      localStorage.setItem('attachment', '1')
      if (this.attachment_count == 'new') {
        this.attachment_count = ''
      }
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

      var item_id = that.item_id
      var page_id = that.page_id

      this.request(
        '/api/page/save',
        {
          page_id: page_id,
          item_id: item_id,
          page_title: that.title,
          page_content: encodeURIComponent(content),
          is_urlencode: 1,
          cat_id: cat_id,
          is_notify: this.is_notify,
          notify_content: this.notify_content
        },
        'post',
        false
      ).then(data => {
        loading.close()
        that.saving = false
        if (data.error_code === 0) {
          if (typeof callback == 'function') {
            callback()
          } else {
            that.$message({
              showClose: true,
              message: '保存成功',
              type: 'success'
            })
          }

          // 删除草稿
          that.deleteDraft()
          if (that.page_id <= 0) {
            that.page_id = data.data.page_id
            // 更改url
            that.$router.replace({
              path: '/' + that.item_id + '/' + that.page_id
            })
          }
        } else if (data.error_code === 10250) {
          // 删除草稿
          that.deleteDraft()
          that.$alert(
            '内容已保存。检测到你的账户尚未绑定邮箱或者邮箱还没有经过验证。当忘记密码的时候，你将面临着<font color="red">丢失数据</font>的风险。<a href="/user/setting" target="_blank">点此绑定邮箱</a>，忘记密码时可通过邮箱重置密码',
            '提示',
            {
              dangerouslyUseHTMLString: true
            }
          )
        } else {
          that.$alert(data.error_message)
        }
      })

      // 设置一个最长关闭时间
      setTimeout(() => {
        loading.close()
        that.saving = false
      }, 10000)
    },
    goback() {
      var url = '/' + this.item_id + '/' + this.page_id
      this.$router.push({
        path: url
      })
      this.callback()
    },
    // 另存为模板
    saveToTemplate() {
      var that = this
      let childRef = this.$refs.Editormd
      var content = childRef.getMarkdown()
      this.$prompt(that.$t('save_templ_title'), ' ', {}).then(data => {
        this.request('/api/template/save', {
          template_title: data.value,
          template_content: content
        }).then(data => {
          this.$alert(this.$t('save_templ_text'))
        })
      })
    },

    /** 监听剪切板 **/
    // 以实现粘贴上传图片，html网页转markdown等
    clipboardEvents(e) {
      var that = this
      // 如果当前鼠标的焦点不在编辑器内，则中止
      if (
        !document
          .querySelector('#page-editor .CodeMirror-wrap')
          .contains(document.activeElement)
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
          form.append('user_token', that.user_token)
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
      }, 20 * 60 * 1000)
    },
    // 判断页面是否被锁定编辑
    remoteIsLock() {
      this.request('/api/page/isLock', {
        page_id: this.page_id,
        item_id: this.item_id
      }).then(res => {
        // 判断已经锁定了不
        if (res.data.lock > 0) {
          if (res.data.is_cur_user > 0) {
            this.isLock = 1
          } else {
            this.$alert(this.$t('locking') + res.data.lock_username, '', {
              showClose: false
            }).then(() => {
              this.goback()
            })
          }
        } else if (res.data.exceed > 0 && this.page_id <= 0) {
          this.$alert(
            '该项目页面数量超出限制。项目创建者可以开通高级版以获取更多配额。<br>如果你是项目创建者，你可以<a href="/prices" target="_blank" >点此查看不同账户类型的额度限制差异</a>，也可以<a href="/order/index" target="_blank" >点此去升级账户类型</a>。<br>如果你现在不方便处理，你可以等会再自行回到项目列表页，点击右上角的用户中心去升级。',
            {
              dangerouslyUseHTMLString: true
            }
          ).then(() => {
            this.goback()
          })
        } else {
          this.setLock() // 如果没有被别人锁定，则进编辑页面后自己锁定。
        }
      })
    },
    // 由于页面关闭事件无法直接发起异步的ajax请求，所以用浏览器的navigator.sendBeacon来实现
    unLockOnClose() {
      let user_token = this.user_token
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
      this.getCatalog(this.item_id)
    },
    // 去新建目录
    goToCat() {
      let routeUrl = this.$router.resolve({
        path: '/catalog/' + this.item_id
      })
      window.open(routeUrl.href, '_blank')
    },
    editTitle() {
      let defaultVar = ''
      if (this.page_id || this.copy_page_id) {
        defaultVar = this.title
      }
      this.$prompt('', '', { inputValue: defaultVar }).then(data => {
        this.title = data.value
      })
    },
    isShowAI() {
      // 判断是否应该展示AI助手按钮
      this.request('/api/common/homePageSetting', {}).then(res => {
        if (res.data && res.data.is_show_ai) {
          this.showAIBtn = true
        }
      })
    }
  },

  mounted() {
    this.page_id = this.edit_page_id
    if (this.copy_page_id > 0) {
      this.getPageContent(this.copy_page_id)
    } else if (this.page_id > 0) {
      this.getPageContent(this.page_id)
    } else {
      this.content = '\n'
      this.showContent = true
      this.title = this.$t('default_title')
      this.getCatalog(this.item_id)
    }

    this.heartBeatLock()
    this.remoteIsLock()
    /** 监听剪切板 **/
    document.addEventListener('paste', this.clipboardEvents)
    window.addEventListener('beforeunload', this.unLockOnClose)
    let g_open_cat_id = this.$store.state.open_cat_id // 全局变量-当前打开的目录id
    // 如果this.page_id无效，则可以判定用户是在新建页面
    // 此时可以考虑把目录设置为用户当前打开的页面的所属目录
    if (this.page_id <= 0 && g_open_cat_id > 0) {
      this.cat_id = g_open_cat_id
    }
    const userInfo = getUserInfoFromStorage()
    this.user_token = userInfo.user_token
    this.isShowAI()
  },

  beforeDestroy() {
    // 监听剪切板
    document.removeEventListener('paste', this.clipboardEvents)
    this.$message.closeAll()
    clearInterval(this.intervalId)
    this.unlock()
    window.removeEventListener('beforeunload', this.unLockOnClose)
  }
}
</script>

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

.title-header .el-button--text {
  color: #343a40 !important;
  font-weight: 400w;
}
.title-header-right .el-button {
  font-weight: 600;
}
.close-btn {
  font-size: 13px;
  width: 40px;
  height: 40px;
  background: #ffffff;
  border-radius: 8px;
}
.cat-class {
  background-color: white;
  padding: 5px;
  border: 1px solid rgba(0, 0, 0, 0.05);
  border-radius: 4px;
  cursor: pointer;
}

.title-header {
  min-height: 40px;
  padding-bottom: 10px;
}
.page-title {
  margin-left: 10px;
  margin-right: 10px;
  cursor: pointer;
}
.page-title:hover {
  text-decoration: underline;
}
</style>

<!-- 写全局的样式 -->
<style>
.sdialog .el-dialog__header {
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}
.sdialog .el-dialog__body {
  padding-top: 15px;
}
</style>
