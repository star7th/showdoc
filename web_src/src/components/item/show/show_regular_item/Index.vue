<template>
  <div :class="device" v-if="showComp">
    <Header v-if="showPCHeader"  id="header" :item_info="item_info">
      <HeaderRight
        :page_id="page_id"
        :item_info="item_info"
        :page_info="page_info"
        :searchItem="searchItem"
      ></HeaderRight>
    </Header>

    <MobileHeader
      :item_info="item_info"
      :searchItem="searchItem"
      :getPageContent="getPageContent"
      v-if="showMobileHeader"
    ></MobileHeader>

    <div class="doc-container " id="doc-container">
      <div id="left-side">
        <LeftMenu
          ref="leftMenu"
          :getPageContent="getPageContent"
          :keyword="keyword"
          :item_info="item_info"
          :searchItem="searchItem"
          v-if="item_info && !showMobileHeader"
        ></LeftMenu>

        <LeftMenuBottomBar
          :item_id="item_info.item_id"
          :page_info="page_info"
          :searchItem="searchItem"
          :page_id="page_id"
          :item_info="item_info"
          v-if="item_info"
        ></LeftMenuBottomBar>
      </div>

      <div id="content-side">
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
            <div v-if="emptyItem && $lang == 'zh-cn'" class="empty-tips">
              <div class="icon">
                <i class="el-icon-warning"></i>
              </div>
              <div class="text">
                <p>
                  当前项目是空的，你可以点击左下方的 + 以手动添加页面。
                </p>

                <div>
                  除了手动添加外，你还可以通过以下三种方式自动化生成文档：
                  <p class="links">
                    <i class="el-icon-star-on v3-color-yellow"></i>
                    <a href="https://www.showdoc.com.cn/runapi" target="_blank">
                      使用runapi工具自动生成（推荐）</a
                    ><i class="el-icon-star-on v3-color-yellow"></i><br />
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

      <div id="right-side">
        <div id="toc-pos"></div>
      </div>
    </div>

    <el-backtop right="40" bottom="40"></el-backtop>
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
      :cancel="
        () => {
          showAttachmentListDialog = false
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
import MobileHeader from '../MobileHeader'
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
      showAttachmentListDialog: false,
      showMobileHeader: false,
      showPCHeader: true,
      device: 'pc'
    }
  },
  components: {
    Editormd,
    LeftMenu,
    Toc,
    AttachmentList,
    Header,
    HeaderRight,
    LeftMenuBottomBar,
    MobileHeader
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
      doc_container.style.padding = '0px'
      doc_container.style.width = 'calc( 100vw - 1px )'
      doc_container.style.minWidth = 'calc( 100vw - 1px )'
      doc_container.style.maxWidth = 'calc( 100vw - 1px )'
      doc_container.style.margin = '0px'

      this.showPCHeader = false
      this.showMobileHeader = true
      this.device = 'mobile'

      var rightSide = document.getElementById('content-side')
      rightSide.style.width = 'calc (100% -1px)'
      rightSide.style.minWidth = 'calc( 100% - 1px )'
      rightSide.style.maxWidth = 'calc( 100% - 1px )'
      rightSide.style.minHeight = 'calc(100vh - 60px + 5px )'
      rightSide.style.marginLeft = '0px'
      rightSide.style.borderRadius = '0px'
      var docTitle = document.getElementById('doc-title-box')
      docTitle.style.marginTop = '0px'
      this.showToc = false
      var leftMenuBottomBar = document.getElementById('left-menu-bottom-bar')
      if (leftMenuBottomBar) {
        leftMenuBottomBar.style.display = 'none'
      }
    },
    // 响应式
    adaptScreen() {
      this.$nextTick(() => {
        // 适应移动端
        if (this.isMobile()) {
          this.adaptToMobile()
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
          this.showPCHeader = true
          this.showMobileHeader = false
          this.device = 'pc'
        })
      } else {
        this.adaptToMobile()
        this.showMobileHeader = false
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
        $('#right-side').hide()
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
  font-size: 18px;
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
  margin-bottom: 20px;
  min-height: 750px;
  margin-left: auto;
  margin-right: auto;
  margin-top: 110px;
  max-width: 1500px;
  min-width: 855px;
  display: flex;
  justify-content: center;
}

#left-side {
  width: 300px;
  background-color: #f9f9f9;
}

#content-side {
  background-color: #fff;
  box-shadow: 0 0 4px #0000001a;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  min-width: 830px;
  max-width: 850px;
  border-radius: 8px;
  margin-left: 10px;
  margin-right: 10px;
}

#right-side {
}

.pc #doc-body {
  width: calc(100% - 10px);
  margin-left: 10px;
}

.mobile #doc-body {
  width: 100%;
  margin-left: 0px;
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
  font-size: 14px;
}

.empty-tips {
  margin: 5% auto;
  width: 400px;
  text-align: center;
  min-height: 50vh;
  opacity: 0.5;
}

.empty-tips .icon {
  font-size: 80px;
}

.empty-tips .text {
  text-align: center;
}

.empty-tips .links {
  line-height: 2em;
  text-align: center;
}
.empty-tips .links a {
  text-decoration: underline;
  color: #007bff;
}

/*小屏设备（但不是移动端设备） */
@media (max-width: 1300px) {
  .doc-container {
    display: block;
  }
  #content-side {
    min-width: 300px;
    margin-left: 300px;
    margin-top: -10px;
  }
  #right-side {
    display: none;
  }
}
</style>
