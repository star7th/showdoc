<template>
  <div :id="id" class="main-editor">
    <!-- 放大图片 -->
    <BigImg v-if="showImg" @clickit="showImg = false" :imgSrc="imgSrc"></BigImg>
  </div>
</template>
<style src="@/../static/editor.md/css/editormd.min.css"></style>
<style src="@/../static/highlight/atom-one-dark.min.css"></style>
<style>
.editormd-preview-container {
  min-height: 60%;
}

.markdown-body {
  font-size: 13px;
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
  color: #d14;
  font-family: Consolas, Monaco, Lucida Console, Liberation Mono,
    DejaVu Sans Mono, Bitstream Vera Sans Mono, Courier New, monospace;
}
.markdown-body pre code {
  color: #d1d2d2;
}
.editormd-html-preview blockquote,
.editormd-preview-container blockquote {
  font-style: normal;
}

.markdown-body table thead tr {
  background-color: #409eff;
  color: #fff;
}

.markdown-body pre {
  position: relative;
  background-color: #384548;
  padding: 0;
  color: #d1d2d2;
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
  font-size: 12px;
  line-height: 1;
  cursor: pointer;
  color: #999;
  transition: color 0.1s;
}
.markdown-body pre:before {
  content: '';
  display: block;
  background: url(data:image/png;base64,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);
  height: 32px;
  width: 100%;
  background-size: 40px;
  background-repeat: no-repeat;
  background-color: #384548;
  margin-bottom: 0;
  border-top-left-radius: 5px;
  border-top-right-radius: 5px;
  background-position: 10px 10px;
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
</style>
<script>
import BigImg from '@/components/common/BigImg'
import { getUserInfoFromStorage } from '@/models/user.js'
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
          height: 750,
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
              'preview',
              'fullscreen',
              'clear',
              'search',
              '|',
              'help',
              'info'
            ]
          },
          toolbarIconsClass: {
            toc: 'fa-bars ', // 指定一个FontAawsome的图标类
            mindmap: 'fa-sitemap ', // 指定一个FontAawsome的图标类
            video: 'fa-file-video-o',
            center: 'fa-align-center'
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
              video: '插入视频',
              center: '居中'
            }
          },
          onchange: () => {
            this.deal_with_content()
          },
          previewCodeHighlight: false // 关闭编辑默认的代码高亮模块。用其他插件实现高亮
        }
      }
    }
  },
  components: {
    BigImg
  },
  data() {
    return {
      instance: null,
      showImg: false,
      imgSrc: '',
      user_token: ''
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
                $s(`${this.editorPath}/editormd.js`, () => {
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
    for (var i = 1; i < 999; i++) {
      window.clearInterval(i)
    }

    // window.removeEventListener('beforeunload', e => this.beforeunloadHandler(e))
  },
  methods: {
    initEditor() {
      this.$nextTick((editorMD = window.editormd) => {
        const editorConfig = this.editorConfig
        editorConfig.markdown = this.content
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
      setInterval(() => {
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
        that.showImg = true // 获取当前图片地址
        that.imgSrc = img_url
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
