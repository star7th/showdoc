<template>
  <div class="hello" v-if="showComp">
    <Header id="header" :item_info="item_info">
      <HeaderRight
        :page_id="page_id"
        :item_info="item_info"
        :page_info="page_info"
        :searchItem="searchItem"
      ></HeaderRight>
    </Header>

    <div class="doc-container" id="doc-container">
      <div id="left-side">
        <LeftMenu
          ref="leftMenu"
          :getPageContent="getPageContent"
          :keyword="keyword"
          :item_info="item_info"
          :searchItem="searchItem"
          v-if="item_info"
        ></LeftMenu>

        <LeftMenuBottomBar
          :item_id="item_info.item_id"
          :page_info="page_info"
          :searchItem="searchItem"
          :page_id="page_id"
          v-if="item_info"
        ></LeftMenuBottomBar>
      </div>

      <div id="right-side">
        <div id="p-content">
          <div class="doc-title-box" id="doc-title-box">
            <span class="v3-font-size-lg font-bold " id="doc-title">{{
              page_title
            }}</span>
            <span class="float-right">
              <i
                class="el-icon-upload item"
                id="attachment"
                v-if="attachment_count"
                @click="showAttachmentListDialog = true"
              ></i>
              <i
                v-if="page_id && !isMobile()"
                class="el-icon-full-screen"
                id="full-page"
                @click="clickFullPage"
              ></i>
            </span>
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
            <div v-if="emptyItem" class="empty-tips">
              <div class="icon">
                <i class="el-icon-warning"></i>
              </div>
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
                      href="https://www.showdoc.com.cn/page/7416564025093"
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
      </div>
    </div>

    <el-backtop></el-backtop>
    <Toc v-if="page_id && showToc"></Toc>

    <!-- 附件列表 -->
    <AttachmentList
      :item_id="page_info.item_id"
      :manage="false"
      :page_id="page_info.page_id"
      v-if="showAttachmentListDialog"
      :callback="
        data => {
          this.showAttachmentListDialog = false
        }
      "
    ></AttachmentList>
  </div>
</template>

<script>
import Editormd from '@/components/common/Editormd'
import Toc from '@/components/common/Toc'
import LeftMenu from '@/components/item/show/show_regular_item/LeftMenu'
import AttachmentList from '@/components/page/edit/AttachmentList'
import { rederPageContent } from '@/models/page'
import HeaderRight from './HeaderRight'
import Header from '../Header'
import LeftMenuBottomBar from './LeftMenuBottomBar'
export default {
  props: {
    item_info: '',
    searchItem: () => {},
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
      showToc: true,
      showComp: true,
      emptyItem: false,
      showAttachmentListDialog: false
    }
  },
  components: {
    Editormd,
    LeftMenu,
    Toc,
    AttachmentList,
    Header,
    HeaderRight,
    LeftMenuBottomBar
  },
  methods: {
    // 获取页面内容
    getPageContent(page_id) {
      if (page_id <= 0) {
        return
      }
      this.adaptScreen()
      this.request(
        '/api/page/info',
        {
          page_id: page_id
        },
        'post',
        false
      ).then(data => {
        this.content = rederPageContent(
          data.data.page_content,
          this.$store.state.item_info.global_param
        )
        this.$store.dispatch('changeOpenCatId', data.data.cat_id)
        this.page_title = data.data.page_title
        this.page_info = data.data
        this.attachment_count =
          data.data.attachment_count > 0 ? data.data.attachment_count : ''
        // 切换变量让它重新加载、渲染子组件
        this.page_id = 0
        this.item_info.default_page_id = page_id
        this.$nextTick(() => {
          this.page_id = page_id
          // 页面回到顶部
          document.body.scrollTop = document.documentElement.scrollTop = 0
          document.title = this.page_title + '--ShowDoc'
        })
      })
    },
    // 根据屏幕宽度进行响应(应对移动设备的访问)
    adaptToMobile() {
      let childRef = this.$refs.leftMenu // 获取子组件
      childRef.hideMenu()
      var doc_container = document.getElementById('doc-container')
      doc_container.style.padding = '5px'
      doc_container.style.width = 'calc( 100vw - 10px )'
      doc_container.style.minWidth = 'calc( 100vw - 10px )'
      doc_container.style.maxWidth = 'calc( 100vw - 10px )'
      doc_container.style.margin = '0px'
      var header = document.getElementById('header')
      header.style.display = 'none'

      var rightSide = document.getElementById('right-side')
      rightSide.style.width = 'calc (100% -1px)'
      rightSide.style.minWidth = 'calc( 100% - 1px )'
      rightSide.style.maxWidth = 'calc( 100% - 1px )'
      rightSide.style.marginLeft = '0px'
      var docTitle = document.getElementById('doc-title-box')
      docTitle.style.marginTop = '0px'
      this.showToc = false
      var leftMenuBottomBar = document.getElementById('left-menu-bottom-bar')
      leftMenuBottomBar.style.display = 'none'
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
        $('#left-menu-bottom-bar').hide()
      }
      this.fullPage = !this.fullPage
    }
  },
  mounted() {
    this.adaptScreen()
    // console.log(this.$store.state.item_info)
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
.el-dropdown-link,
a {
  color: #343a40;
}
.page_content_main {
  margin: 0 auto;
  height: 50%;
  overflow: visible;
}

.editormd-html-preview {
  width: 100%;
  overflow-y: hidden;
}

#attachment {
  float: right;
  font-size: 25px;
  margin-right: 20px;
  cursor: pointer;
  color: #abd1f1;
}

#full-page {
  float: right;
  font-size: 25px;
  margin-right: 20px;
  cursor: pointer;
  color: #ccc;
}

#page_md_content {
  padding: 10px 10px 90px 10px;
  overflow: hidden;
  color: #333;
}

.doc-container {
  position: static;
  margin-bottom: 20px;
  min-height: 750px;
  margin-left: auto;
  margin-right: auto;
  margin-top: 110px;
  max-width: 1150px;
  min-width: 655px;
}

#left-side {
  position: absolute;
}

#right-side {
  margin-left: 320px;
  background-color: #fff;
  box-shadow: 0 0 4px #0000001a;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  min-width: 355px;
  max-width: 850px;
  border-radius: 8px;
}

#doc-body {
  width: calc(100% - 20px);
  margin-left: 20px;
}

.doc-title-box {
  height: auto;
  width: auto;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  padding-bottom: 25px;
  padding-top: 25px;
  margin: 10px auto;
  text-align: center;
}

pre ol {
  list-style: none;
}

.markdown-body pre {
  background-color: #fff;
  border: 1px solid rgba(0, 0, 0, 0.05);
}
.hljs {
  background-color: #fff;
}
.tool-bar {
  margin-top: -38px;
}
.editormd-html-preview,
.editormd-preview-container {
  padding: 0px;
  font-size: 13px;
}

.empty-tips {
  margin: 5% auto;
  width: 400px;
  text-align: center;
  color: #000;
  min-height: 50vh;
  opacity: 0.3;
}

.empty-tips .icon {
  font-size: 80px;
  margin-left: -50px;
}

.empty-tips .text {
  text-align: left;
}

.empty-tips .links {
  line-height: 2em;
}
.empty-tips .links a {
  text-decoration: underline;
}
</style>
