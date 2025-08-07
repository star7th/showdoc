<template>
  <div :id="id" class="main-editor">
  </div>
</template>
<style src="@/../static/editor.md/css/editormd.min.css"></style>
<style src="@/../static/highlight/atom-one-dark.min.css"></style>
<style>
.editormd-preview-container {
  min-height: 60%;
}

.markdown-body {
  font-size: 14px;
  line-height:1.75;
}

.markdown-body h1 {
  font-size: 1.8em !important;
}
.markdown-body h2 {
  font-size: 1.5em !important;
}
.markdown-body h3 {
  font-size: 1.25em !important;
}
.markdown-body h4 {
  font-size: 1.1em !important;
}
.markdown-body code {
  color: #409eff;
  font-family: Consolas, Monaco, Lucida Console, Liberation Mono,
    DejaVu Sans Mono, Bitstream Vera Sans Mono, Courier New, monospace;
  background: #f9f9f9;
}
.markdown-body pre code {
  color: #d1d2d2;
}
.editormd-html-preview blockquote,
.editormd-preview-container blockquote {
  font-style: normal;
}

.markdown-body table thead tr {
  background-color: rgba(64, 158, 255, 0.1);
}

.markdown-body pre {
  position: relative;
  background-color: #384548;
  padding: 0;
  color: #d1d2d2;
  padding: 1em;
  border-radius: 4px;
}

.markdown-body pre code {
  padding: 0em; /* .markdown-body pre 已经设置 padding: 1em , 所以代码块不再需要边距 */
}

.markdown-body pre .btn-pre-copy {
  display: none;
}

.markdown-body pre:hover .btn-pre-copy {
  display: block;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  -khtml-user-select: none;
  user-select: none;
  position: absolute;
  top: 10px;
  right: 12px;
  font-size: 11px;
  line-height: 1;
  cursor: pointer;
  color: #999;
  transition: color 0.1s;
}
.markdown-body pre:before {
  content: '';
  display: block;
  background-image: url('~@/assets/code-radio.png');
  height: 32px;
  width: 100%;
  background-size: 40px;
  background-repeat: no-repeat;
  background-color: #384548;
  margin-bottom: 0;
  border-top-left-radius: 5px;
  border-top-right-radius: 5px;
  background-position: 0px 10px;
}
/* 默认的代码高亮主题样式里，对代码注释的颜色看不清楚，所以重写下 */
.hljs-comment,
.hljs-quote {
  color: #aaa;
}

/* 调整下编辑器的按钮样式 */
.editormd-menu > li {
  margin-left: 10px;
}
.editormd-menu > li > a > .fa {
  font-size: 13px;
}

/* 因跟fa 图标的样式冲突，这个按钮冒出来了。这里强制把它隐藏 */
.editormd-preview-close-btn {
  display: none !important;
}

.markdown-body h1,
.markdown-body h2,
.markdown-body h3,
.markdown-body h4,
.markdown-body h5,
.markdown-body h6 {
  margin-bottom: 1.5em;
  line-height: 1.75;
}
.markdown-body p,
.markdown-body blockquote,
.markdown-body ul,
.markdown-body ol,
.markdown-body dl,
.markdown-body table,
.markdown-body pre {
  margin-bottom: 1.5em;
}
.markdown-body dl dd {
  margin-bottom: 1.5em;
}
.markdown-body .highlight {
  margin-bottom: 1.5em;
}

/* 仅在编辑器预览区域增强代码块横向滚动条的可见性，不影响全局滚动条 */
.markdown-body pre,
.markdown-body .highlight pre,
.editormd-preview-container pre,
.editormd-html-preview pre {
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.5) #2f3a3d; /* 适配深色代码块背景 */
}

.markdown-body pre::-webkit-scrollbar,
.markdown-body .highlight pre::-webkit-scrollbar,
.editormd-preview-container pre::-webkit-scrollbar,
.editormd-html-preview pre::-webkit-scrollbar {
  height: 8px;
}

.markdown-body pre::-webkit-scrollbar-track,
.markdown-body .highlight pre::-webkit-scrollbar-track,
.editormd-preview-container pre::-webkit-scrollbar-track,
.editormd-html-preview pre::-webkit-scrollbar-track {
  background-color: #2f3a3d;
}

.markdown-body pre::-webkit-scrollbar-thumb,
.markdown-body .highlight pre::-webkit-scrollbar-thumb,
.editormd-preview-container pre::-webkit-scrollbar-thumb,
.editormd-html-preview pre::-webkit-scrollbar-thumb {
  background-color: rgba(255, 255, 255, 0.5);
  border-radius: 6px;
}

.markdown-body pre::-webkit-scrollbar-thumb:hover,
.markdown-body .highlight pre::-webkit-scrollbar-thumb:hover,
.editormd-preview-container pre::-webkit-scrollbar-thumb:hover,
.editormd-html-preview pre::-webkit-scrollbar-thumb:hover {
  background-color: rgba(255, 255, 255, 0.65);
}

</style>
<script>
import { getUserInfoFromStorage } from '@/models/user.js'
import 'viewerjs/dist/viewer.css'
import { api as viewerApi } from 'v-viewer'
if (typeof window !== 'undefined') {
  var $s = require('scriptjs')
}

export default {
  name: 'Editor',
  props: {
    width: '',
    content: {
      type: String,
      default: '###初始化成功'
    },
    type: {
      type: String,
      default: 'editor'
    },
    // 是否允许在阅读模式下切换任务列表复选框
    taskToggle: {
      type: Boolean,
      default: true
    },
    keyword: {
      type: String,
      default: ''
    },
    id: {
      type: String,
      default: 'editor-md'
    },
    editorPath: {
      type: String,
      default: 'static/editor.md'
    },
    editorConfig: {
      type: Object,
      default() {
        return {
          path: 'static/editor.md/lib/',
          height: '70vh',
          taskList: true,
          atLink: false,
          emailLink: false,
          tex: true, // 默认不解析
          flowChart: true, // 默认不解析
          sequenceDiagram: true, // 默认不解析
          syncScrolling: 'single',
          htmlDecode: 'style,script,iframe|filterXSS',
          imageUpload: true,
          imageFormats: [
            'jpg',
            'jpeg',
            'gif',
            'png',
            'bmp',
            'webp',
            'JPG',
            'JPEG',
            'GIF',
            'PNG',
            'BMP',
            'WEBP'
          ],
          imageUploadURL: '',
          onload: () => {
            console.log('onload')
          },
          toolbarIcons: function() {
            // Or return editormd.toolbarModes[name]; // full, simple, mini
            // Using "||" set icons align right.
            return [
              'undo',
              'redo',
              '|',
              'bold',
              'del',
              'italic',
              'quote',
              '|',
              'toc',
              'mindmap',
              'plantuml',
              'tasklist',
              'h1',
              'h2',
              'h3',
              'h4',
              'h5',
              'h6',
              '|',
              'list-ul',
              'list-ol',
              'hr',
              'center',
              '|',
              'link',
              'reference-link',
              'image',
              'video',
              'code',
              'code-block',
              'table',
              'datetime',
              'html-entities',
              'pagebreak',
              '|',
              'watch',
              'fullscreen',
              'clear',
              'search',
              '|',
              'help'
            ]
          },
          toolbarIconsClass: {
            toc: 'fa-bars ', // 指定一个FontAawsome的图标类
            mindmap: 'fa-sitemap ', // 指定一个FontAawsome的图标类
            plantuml: 'fa-random ', // 修改为确实存在的图标类
            video: 'fa-file-video-o',
            center: 'fa-align-center',
            tasklist: 'fa-check-square-o'
          },
          // 自定义工具栏按钮的事件处理
          toolbarHandlers: {
            toc: function(cm, icon, cursor, selection) {
              cm.setCursor(0, 0)
              cm.replaceSelection('[TOC]\r\n\r\n')
            },
            video: function(cm, icon, cursor, selection) {
              // 替换选中文本，如果没有选中文本，则直接插入
              cm.replaceSelection(
                '\r\n<video src="http://your-site.com/your.mp4" style="width: 100%; height: 100%;" controls="controls"></video>\r\n'
              )

              // 如果当前没有选中的文本，将光标移到要输入的位置
              if (selection === '') {
                cm.setCursor(cursor.line, cursor.ch + 1)
              }
            },
            tasklist: function(cm, icon, cursor, selection) {
              const text = `
## 任务清单

### 今日
- [ ] 整理站内消息与通知
- [ ] 跟进两条用户反馈
- [x] 合并并审核一个 PR

### 本周
- [ ] 完成文档目录整理与迁移
- [ ] 优化图片加载与懒加载策略

### 待办
- [ ] 编写部署与备份说明
- [ ] 补充接口错误码表
`
              cm.replaceSelection(text)
              if (selection === '') {
                cm.setCursor(cursor.line, cursor.ch + 1)
              }
            },
            mindmap: function(cm, icon, cursor, selection) {
              // 替换选中文本，如果没有选中文本，则直接插入
              var text = `
\`\`\`mindmap
# 一级
## 二级分支1
### 三级分支1
### 三级分支2
## 二级分支2
### 三级分支3
### 三级分支4
\`\`\`
`
              cm.replaceSelection(text)

              // 如果当前没有选中的文本，将光标移到要输入的位置
              if (selection === '') {
                cm.setCursor(cursor.line, cursor.ch + 1)
              }
            },
            plantuml: function(cm, icon, cursor, selection) {
              // 替换选中文本，如果没有选中文本，则直接插入
              var text = `
\`\`\`plantuml
@startuml
actor 用户
用户 -> 系统: 请求
系统 --> 用户: 响应
@enduml
\`\`\`
`
              cm.replaceSelection(text)

              // 如果当前没有选中的文本，将光标移到要输入的位置
              if (selection === '') {
                cm.setCursor(cursor.line, cursor.ch + 1)
              }
            },
            center: function(cm, icon, cursor, selection) {
              // 替换选中文本，如果没有选中文本，则直接插入
              cm.replaceSelection('<center>' + selection + '</center>')

              // 如果当前没有选中的文本，将光标移到要输入的位置
              if (selection === '') {
                cm.setCursor(cursor.line, cursor.ch + 1)
              }
            }
          },
          lang: {
            toolbar: {
              toc: '在最开头插入TOC，自动生成标题目录',
              mindmap: '插入思维导图',
              plantuml: '插入UML图',
              video: '插入视频',
              center: '居中',
              tasklist: '插入任务列表'
            }
          },
          onchange: () => {
            this.deal_with_content()
          },
          previewCodeHighlight: false , // 关闭编辑默认的代码高亮模块。用其他插件实现高亮
          katexURL: {
            css: '',
            js: ''
          }
        }
      }
    }
  },
  components: {
    
  },
  data() {
    return {
      instance: null,
      imgSrc: '',
      user_token: '',
      intervalId: 0

    }
  },
  computed: {},
  mounted() {
    const userInfo = getUserInfoFromStorage()
    this.user_token = userInfo.user_token
    // 加载依赖
    $s(
      [
        `${this.editorPath}/../jquery.min.js`,
        `${this.editorPath}/lib/raphael.min.js`
      ],
      () => {
        $s(
          [
            `${this.editorPath}/lib/d3@5.min.js`,
            `${this.editorPath}/lib/flowchart.min.js`
          ],
          () => {
            $s(
              [
                `${this.editorPath}/../xss.min.js`,
                `${this.editorPath}/lib/marked.min.js`,
                `${this.editorPath}/lib/underscore.min.js`,
                `${this.editorPath}/lib/sequence-diagram.min.js`,
                `${this.editorPath}/lib/jquery.flowchart.min.js`,
                `${this.editorPath}/lib/jquery.mark.min.js`,
                `${this.editorPath}/lib/plantuml.js?v=2`,
                `${this.editorPath}/lib/view.min.js`,
                `${this.editorPath}/lib/transform.min.js`
              ],
              () => {
                $s(`${this.editorPath}/editormd.js?v=34`, () => {
                  this.initEditor()
                })
              }
            )
          }
        )
      }
    )
  },
  beforeDestroy() {
    // 清理所有定时器
    window.clearInterval(this.intervalId)
    // window.removeEventListener('beforeunload', e => this.beforeunloadHandler(e))
  },
  methods: {
    initEditor() {
      this.$nextTick((editorMD = window.editormd) => {
        const editorConfig = this.editorConfig
        editorConfig.markdown = this.content
        // 设置 katex 路径
        editorConfig.katexURL = {
          css: `${this.editorPath}/katex/katex.min`,
          js: `${this.editorPath}/katex/katex.min`
        }
        if (DocConfig.server.indexOf('?') > -1) {
          editorConfig.imageUploadURL =
            DocConfig.server +
            '/api/page/uploadImg&user_token=' +
            this.user_token
        } else {
          editorConfig.imageUploadURL =
            DocConfig.server +
            '/api/page/uploadImg?user_token=' +
            this.user_token
        }
        if (editorMD) {
          if (this.type == 'editor') {
            this.instance = editorMD(this.id, editorConfig)
            // 草稿
            // this.draft(); 鉴于草稿功能未完善。先停掉。
            // window.addEventListener('beforeunload', e => this.beforeunloadHandler(e));
          } else {
            this.instance = editorMD.markdownToHTML(this.id, editorConfig)
          }
          this.deal_with_content()
        }
      })
    },

    // 插入数据到编辑器中。插入到光标处
    insertValue(insertContent) {
      this.instance.insertValue(this.html_decode(insertContent))
    },

    getMarkdown() {
      return this.instance.getMarkdown()
    },
    editorUnwatch() {
      return this.instance.unwatch()
    },

    editorWatch() {
      return this.instance.watch()
    },
    setCursorToTop() {
      return this.instance.setCursor({ line: 0, ch: 0 })
    },

    clear() {
      return this.instance.clear()
    },

    // 草稿
    draft() {
      var that = this
      // 定时保存文本内容到localStorage
      this.intervalId = setInterval(() => {
        localStorage.page_content = that.getMarkdown()
      }, 60000)

      // 检测是否有定时保存的内容
      var page_content = localStorage.page_content
      if (page_content && page_content.length > 0) {
        localStorage.removeItem('page_content')
        that
          .$confirm(that.$t('draft_tips'), '', {
            showClose: false
          })
          .then(() => {
            that.clear()
            that.insertValue(page_content)
            localStorage.removeItem('page_content')
          })
          .catch(() => {
            localStorage.removeItem('page_content')
          })
      }
    },
    // 关闭前提示
    beforeunloadHandler(e) {
      e = e || window.event

      // 兼容IE8和Firefox 4之前的版本
      if (e) {
        e.returnValue = '提示'
      }

      // Chrome, Safari, Firefox 4+, Opera 12+ , IE 9+
      return '提示'
    },

    // 对内容做些定制化改造
    deal_with_content() {
      var that = this

      // 代码高亮
      $s(`${this.editorPath}/../highlight/highlight.min.js`, () => {
        hljs.highlightAll()
      })

      // 当表格列数过长时将自动出现滚动条
      $.each($('#' + this.id + ' table'), function() {
        $(this).prop(
          'outerHTML',
          '<div style="width: 100%;overflow-x: auto;">' +
            $(this).prop('outerHTML') +
            '</div>'
        )
      })

      // 默认超链接都在新窗口打开。但如果是本项目的页面链接，则在本窗口打开。
      $('#' + this.id + ' a[href^="http"]').click(function() {
        var url = $(this).attr('href')
        var obj = that.parseURL(url)
        if (
          window.location.hostname == obj.hostname &&
          window.location.pathname == obj.pathname
        ) {
          window.location.href = url
          if (obj.hash) {
            window.location.reload()
          }
        } else {
          window.open(url)
        }
        return false
      })

      // 对表格进行一些改造
      $('#' + this.id + ' table tbody tr').each(function() {
        var tr_this = $(this)
        var td1 = tr_this
          .find('td')
          .eq(1)
          .html()
        var td2 = tr_this
          .find('td')
          .eq(2)
          .html()
        if (
          td1 == 'object' ||
          td1 == 'array[object]' ||
          td2 == 'object' ||
          td2 == 'array[object]'
        ) {
          tr_this.css({ 'background-color': '#F8F8F8' })
        } else {
          tr_this.css('background-color', '#fff')
        }
        // 设置表格hover
        tr_this.hover(
          function() {
            tr_this.css('background-color', '#F8F8F8')
          },
          function() {
            if (
              td1 == 'object' ||
              td1 == 'array[object]' ||
              td2 == 'object' ||
              td2 == 'array[object]'
            ) {
              tr_this.css({ 'background-color': '#F8F8F8' })
            } else {
              tr_this.css('background-color', '#fff')
            }
          }
        )
      })

      // 获取内容总长度
      var contentWidth = $('#' + this.id + ' p').width()
      contentWidth = contentWidth || 722
      // 表格列 的宽度
      $('#' + this.id + ' table').each(function(i) {
        var $v = $(this).get(0) // 原生dom对象
        var num = $v.rows.item(0).cells.length // 表格的列数
        var colWidth = Math.floor(contentWidth / num) - 2
        if (num <= 5) {
          $(this)
            .find('th')
            .css('width', colWidth.toString() + 'px')
        }
      })

      // 图片点击放大
      $('#' + this.id + ' img').click(function() {
        var img_url = $(this).attr('src')
        that.imgSrc = img_url
        viewerApi({
          images: [img_url]
        })
      })

      // 高亮关键字
      if (this.keyword) $('#' + this.id).mark(this.keyword)

      // 给每一串代码元素增加复制代码节点
      // 这种操作dom的方式其实我也不想做的，但是编辑器的代码块无复制功能，只能自己hack
      let pre = $('#page_md_content pre')
      let btn = $('<span class="btn-pre-copy" >复制</span>')
      btn.prependTo(pre)
      $('#' + this.id).on('click', '.btn-pre-copy', function() {
        that.doCopy(this)
      })

      // 阅读模式下的任务列表交互
      if (this.type === 'html') {
        const $container = $('#' + this.id)
        // 查找不在代码块中的 checkbox
        const $checkboxes = $container
          .find('input[type="checkbox"]')
          .filter(function() {
            return (
              $(this).closest('pre, code, .editormd-code-block, .hljs').length ===
              0
            )
          })

        // 先解绑旧事件
        $container.off('change.tasklist')

        if (!this.taskToggle) {
          // 无交互权限，禁用
          $checkboxes.prop('disabled', true)
          return
        }

        // 允许交互，则启用并编号
        $checkboxes.each(function(i, el) {
          $(el).prop('disabled', false)
          $(el).attr('data-task-index', i)
        })

        // 事件代理，回传索引与状态
        $container.on('change.tasklist', 'input[type="checkbox"]', e => {
          const $target = $(e.target)
          if (
            $target.closest('pre, code, .editormd-code-block, .hljs').length > 0
          ) {
            return
          }
          const index = parseInt($target.attr('data-task-index'))
          if (isNaN(index)) return
          const checked = $target.is(':checked')
          this.$emit('task-toggle', { index, checked })
        })
      }
    },
    // 处理代码块复制
    doCopy(jqThis) {
      // 执行复制
      let btn = $(jqThis)
      let pre = btn.parent()
      // 避免复制内容时把按钮文字也复制进去。先临时置空
      btn.text('')
      this.$copyText(pre.text()).then(
        // 修改按钮名
        btn.text('复制成功')
      )
      // 一定时间后吧按钮名改回来
      setTimeout(() => {
        btn.text('复制')
      }, 1500)
    },
    // 转义
    html_decode(str) {
      var s = ''
      if (str.length == 0) return ''
      s = str.replace(/&amp;/g, '&')
      s = s.replace(/&lt;/g, '<')
      s = s.replace(/&gt;/g, '>')
      s = s.replace(/&nbsp;/g, ' ')
      s = s.replace(/&#39;/g, "'")
      s = s.replace(/&quot;/g, '"')
      // s = s.replace(/<br>/g, "\n");
      return s
    },

    parseURL(url) {
      var a = document.createElement('a')
      a.href = url
      // var a = new URL(url);
      return {
        source: url,
        protocol: a.protocol.replace(':', ''),
        host: a.hostname,
        hostname: a.hostname,
        port: a.port,
        query: a.search,
        params: (function() {
          // eslint-disable-next-line one-var
          var params = {},
            seg = a.search.replace(/^\?/, '').split('&'),
            len = seg.length,
            p
          for (var i = 0; i < len; i++) {
            if (seg[i]) {
              p = seg[i].split('=')
              params[p[0]] = p[1]
            }
          }
          return params
        })(),
        hash: a.hash.replace('#', ''),
        // eslint-disable-next-line no-useless-escape
        path: a.pathname.replace(/^([^\/])/, '/$1'),
        // eslint-disable-next-line no-useless-escape
        pathname: a.pathname.replace(/^([^\/])/, '/$1')
      }
    }
  }
}
</script>
