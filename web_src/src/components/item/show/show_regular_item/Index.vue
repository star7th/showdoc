<template>
  <div class="hello" v-if="showComp">
    <Header></Header>
    <div id="header"></div>

    <div class="container doc-container" id="doc-container">
      <div id="left-side">
        <LeftMenu
          ref="leftMenu"
          :get_page_content="get_page_content"
          :keyword="keyword"
          :item_info="item_info"
          :search_item="search_item"
          v-if="item_info"
        ></LeftMenu>
      </div>

      <div id="right-side">
        <div
          id="p-content"
          @mouseenter="showfullPageBtn = true"
          @mouseleave="showfullPageBtn = false"
        >
          <div class="doc-title-box" id="doc-title-box">
            <span id="doc-title-span" class="dn"></span>
            <h2 id="doc-title">{{ page_title }}</h2>
            <i
              class="el-icon-full-screen"
              id="full-page"
              v-show="showfullPageBtn && page_id"
              @click="clickFullPage"
            ></i>
            <i
              class="el-icon-upload item"
              id="attachment"
              v-if="attachment_count"
              @click="ShowAttachment"
            ></i>
          </div>
          <div id="doc-body">
            <div id="page_md_content" class="page_content_main">
              <Editormd
                v-bind:content="content"
                v-if="page_id"
                type="html"
                :keyword="keyword"
              ></Editormd>
            </div>
            <div v-if="emptyItem && lang == 'zh-cn'" class="empty-tips">
              <div class="icon"><i class="el-icon-shopping-cart-2"></i></div>
              <div class="text">
                <p>
                  当前项目是空的，你可以点击右上方的 + 以手动添加页面。
                </p>

                <div>
                  除了手动添加外，你还可以通过以下三种方式自动化生成文档：
                  <p class="links">
                    <a href="https://www.showdoc.com.cn/runapi" target="_blank"
                      >使用runapi工具自动生成（推荐）</a
                    ><br />
                    <a
                      href="https://www.showdoc.com.cn/page/741656402509783"
                      target="_blank"
                    >
                      使用程序注释自动生成</a
                    ><br />
                    <a
                      href="https://www.showdoc.com.cn/page/102098"
                      target="_blank"
                      >自己写程序调用接口来生成</a
                    >
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <OpBar
          :page_id="page_id"
          :item_id="item_info.item_id"
          :item_info="item_info"
          :page_info="page_info"
        ></OpBar>
      </div>
    </div>

    <BackToTop></BackToTop>
    <Toc v-if="page_id && showToc"></Toc>

    <!-- 附件列表 -->
    <AttachmentList
      callback
      :item_id="page_info.item_id"
      :manage="false"
      :page_id="page_info.page_id"
      ref="AttachmentList"
    ></AttachmentList>

    <Footer></Footer>
  </div>
</template>

<script>
import Editormd from '@/components/common/Editormd'
import BackToTop from '@/components/common/BackToTop'
import Toc from '@/components/item/show/show_regular_item/Toc'
import LeftMenu from '@/components/item/show/show_regular_item/LeftMenu'
import OpBar from '@/components/item/show/show_regular_item/OpBar'
import AttachmentList from '@/components/page/edit/AttachmentList'
import { rederPageContent } from '@/models/page'

export default {
  props: {
    item_info: '',
    search_item: '',
    keyword: ''
  },
  data() {
    return {
      content: '###正在加载...',
      page_id: '',
      page_title: '',
      dialogVisible: false,
      share_item_link: '',
      qr_item_link: '',
      page_info: '',
      copyText: '',
      attachment_count: '',
      fullPage: false,
      showfullPageBtn: false,
      showToc: true,
      showComp: true,
      emptyItem: false,
      lang: ''
    }
  },
  components: {
    Editormd,
    LeftMenu,
    OpBar,
    BackToTop,
    Toc,
    AttachmentList
  },
  methods: {
    // 获取页面内容
    get_page_content(page_id) {
      if (page_id <= 0) {
        return
      }
      this.adaptScreen()
      var that = this
      this.request(
        '/api/page/info',
        {
          page_id: page_id
        },
        'post',
        false
      ).then(data => {
        // loading.close();
        if (data.error_code === 0) {
          that.content = rederPageContent(
            data.data.page_content,
            that.$store.state.item_info.global_param
          )
          that.$store.dispatch('changeOpenCatId', data.data.cat_id)
          that.page_title = data.data.page_title
          that.page_info = data.data
          that.attachment_count =
            data.data.attachment_count > 0 ? data.data.attachment_count : ''
          // 切换变量让它重新加载、渲染子组件
          that.page_id = 0
          that.item_info.default_page_id = page_id
          that.$nextTick(() => {
            that.page_id = page_id
            // 页面回到顶部
            document.body.scrollTop = document.documentElement.scrollTop = 0
            document.title = that.page_title + '--ShowDoc'
          })
        } else {
          // that.$alert(data.error_message);
        }
      })
    },
    // 根据屏幕宽度进行响应(应对移动设备的访问)
    adaptToMobile() {
      let childRef = this.$refs.leftMenu // 获取子组件
      childRef.hide_menu()
      this.show_page_bar = false
      var doc_container = document.getElementById('doc-container')
      doc_container.style.width = '95%'
      doc_container.style.padding = '5px'
      var header = document.getElementById('header')
      header.style.height = '10px'
      var docTitle = document.getElementById('doc-title-box')
      docTitle.style.marginTop = '40px'
      this.showToc = false
    },
    // 根据屏幕宽度进行响应。应对小屏幕pc设备(如笔记本)的访问
    adaptToSmallpc() {
      var doc_container = document.getElementById('doc-container')
      doc_container.style.width = 'calc( 95% - 300px )'
      doc_container.style.marginLeft = '300px'
      doc_container.style.padding = '20px'
      var header = document.getElementById('header')
      header.style.height = '20px'
      var docTitle = document.getElementById('doc-title-box')
      docTitle.style.marginTop = '30px'
    },
    // 响应式
    adaptScreen() {
      this.$nextTick(() => {
        // 根据屏幕宽度进行响应(应对移动设备的访问)
        if (this.isMobile() || window.innerWidth < 1300) {
          if (window.innerWidth < 1300 && window.innerWidth > 1100) {
            this.adaptToSmallpc()
          } else {
            this.adaptToMobile()
          }
        }
      })
    },
    onCopy() {
      this.$message(this.$t('copy_success'))
    },
    ShowAttachment() {
      let childRef = this.$refs.AttachmentList // 获取子组件
      childRef.show()
    },
    clickFullPage() {
      // 点击放大页面。由于历史包袱，只能操作dom。这是不规范的，但是现在没时间重构整块页面
      if (this.fullPage) {
        // 通过v-if指令起到刷新组件的作用
        this.showComp = false
        this.$nextTick(() => {
          this.showComp = true
          this.showToc = true
        })
      } else {
        this.adaptToMobile()
        // 切换变量让它重新加载、渲染子组件
        var page_id = this.page_id
        this.page_id = 0
        this.$nextTick(() => {
          this.page_id = page_id
          setTimeout(() => {
            $('.editormd-html-preview').css('font-size', '16px')
          }, 200)
        })

        $('#left-side').hide()
        $('.op-bar').hide()
      }

      this.fullPage = !this.fullPage
    }
  },
  mounted() {
    this.adaptScreen()
    this.set_bg_grey()
    this.lang = DocConfig.lang
    if (
      this.item_info &&
      this.item_info.menu &&
      this.item_info.menu.catalogs.length === 0 &&
      this.item_info.menu.pages.length === 0
    ) {
      this.emptyItem = true
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.page_content_main {
  width: 800px;
  margin: 0 auto;
  height: 50%;
  overflow: visible;
}

.editormd-html-preview {
  width: 95%;
  font-size: 16px;
  overflow-y: hidden;
}

#attachment {
  float: right;
  font-size: 25px;
  margin-top: -40px;
  margin-right: 5px;
  cursor: pointer;
  color: #abd1f1;
}
#full-page {
  float: right;
  font-size: 25px;
  margin-top: -50px;
  margin-right: 30px;
  cursor: pointer;
  color: #ccc;
}
#page_md_content {
  padding: 10px 10px 90px 10px;
  overflow: hidden;
  font-size: 11pt;
  line-height: 1.7;
  color: #333;
}

.doc-container {
  position: static;
  -webkit-box-shadow: 0px 1px 6px #ccc;
  -moz-box-shadow: 0px 1px 6px #ccc;
  -ms-box-shadow: 0px 1px 6px #ccc;
  -o-box-shadow: 0px 1px 6px #ccc;
  box-shadow: 0px 1px 6px #ccc;
  background-color: #fff;
  border-bottom: 1px solid #d9d9d9;
  margin-bottom: 20px;
  width: 800px;
  min-height: 750px;
  margin-left: auto;
  margin-right: auto;
  padding: 20px;
}

#header {
  height: 80px;
}

#doc-body {
  width: 95%;
  margin: 0 auto;
  background-color: #fff;
}

.doc-title-box {
  height: auto;
  width: auto;
  border-bottom: 1px solid #ebebeb;
  padding-bottom: 10px;
  width: 100%;
  margin: 10px auto;
  text-align: center;
}

pre ol {
  list-style: none;
}

.markdown-body pre {
  background-color: #f7f7f9;
  border: 1px solid #e1e1e8;
}
.hljs {
  background-color: #f7f7f9;
}
.tool-bar {
  margin-top: -38px;
}
.editormd-html-preview,
.editormd-preview-container {
  padding: 0px;
  font-size: 14px;
}
.empty-tips {
  margin: 5% auto;
  width: 400px;
  text-align: center;
  color: #909399;
}

.empty-tips .icon {
  font-size: 100px;
  margin-left: -50px;
}

.empty-tips .text {
  text-align: left;
}

.empty-tips .links {
  line-height: 2em;
}
.empty-tips .links a {
  color: #909399;
  text-decoration: underline;
}
</style>
