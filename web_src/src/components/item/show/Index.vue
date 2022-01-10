<template>
  <div class="hello">
    <Header></Header>

    <!-- 展示常规项目 -->
    <ShowRegularItem
      :item_info="item_info"
      :search_item="search_item"
      :keyword="keyword"
      v-if="
        item_info &&
          (item_info.item_type == 1 ||
            item_info.item_type == 3 ||
            item_info.item_type === '0' ||
            item_info.item_type === 0)
      "
    ></ShowRegularItem>

    <!-- 展示单页项目 -->
    <ShowSinglePageItem
      :item_info="item_info"
      v-if="item_info && item_info.item_type == 2"
    ></ShowSinglePageItem>

    <!-- 展示表格项目 -->
    <ShowTableItem
      :item_info="item_info"
      v-if="item_info && item_info.item_type == 4"
    ></ShowTableItem>
    <!-- 如果是处于登录态的话，则引入通知组件  -->
    <Notify v-if="item_info.is_login"></Notify>

    <Footer></Footer>
  </div>
</template>

<script>
import ShowRegularItem from '@/components/item/show/show_regular_item/Index'
import ShowSinglePageItem from '@/components/item/show/show_single_page_item/Index'
import ShowTableItem from '@/components/item/show/show_table_item/Index'
import watermark from 'watermark-dom'
import moment from 'moment'
import Notify from '@/components/common/Notify'

export default {
  data() {
    return {
      item_info: '',
      keyword: '',
      watermark_txt: '测试水印，1021002301，测试水印，100101010111101'
    }
  },
  components: {
    ShowRegularItem,
    ShowSinglePageItem,
    ShowTableItem,
    Notify
  },
  methods: {
    // 获取菜单
    get_item_menu(keyword) {
      if (!keyword) {
        keyword = ''
      }
      var that = this
      var loading = that.$loading()
      var item_id = this.$route.params.item_id ? this.$route.params.item_id : 0
      var page_id = this.$route.query.page_id ? this.$route.query.page_id : 0
      page_id = this.$route.params.page_id
        ? this.$route.params.page_id
        : page_id
      let params = {
        item_id: item_id,
        keyword: keyword
      }
      if (!keyword) {
        params.default_page_id = page_id
      }
      this.request('/api/item/info', params, 'post', false).then(data => {
        loading.close()
        if (data.error_code === 0) {
          var json = data.data
          if (json.default_page_id <= 0) {
            if (json.menu.pages[0]) {
              json.default_page_id = json.menu.pages[0].page_id
            }
          }
          // 如果是runapi类型项目，则去掉编辑权限。只允许在runapi里编辑
          if (json.item_type == 3) {
            json.item_manage = json.item_edit = false
          }
          that.item_info = json
          that.$store.dispatch('changeItemInfo', json)
          document.title = that.item_info.item_name + '--ShowDoc'
          if (json.unread_count > 0) {
            that.$message({
              showClose: true,
              duration: 10000,
              dangerouslyUseHTMLString: true,
              message: '<a href="#/notice/index">你有新的未读消息，点击查看</a>'
            })
          }
          // 是否显示水印
          if (json.show_watermark > 0) {
            this.renderWatermark()
          }
        } else if (data.error_code === 10307 || data.error_code === 10303) {
          // 需要输入密码
          that.$router.replace({
            path: '/item/password/' + item_id,
            query: {
              page_id: page_id,
              redirect: that.$router.currentRoute.fullPath
            }
          })
        } else {
          that.$alert(data.error_message)
        }
      })

      // 设置一个最长关闭时间
      setTimeout(() => {
        loading.close()
      }, 20000)
    },
    search_item(keyword) {
      this.item_info = ''
      this.$store.dispatch('changeItemInfo', '')
      this.keyword = keyword
      this.get_item_menu(keyword)
    },

    // 渲染水印
    renderWatermark() {
      // 如果已经有全局缓存的登录数据
      if (this.$store.state.user_info && this.$store.state.user_info.username) {
        let user_info = this.$store.state.user_info
        if (user_info && user_info.username) {
          this.watermark_txt = user_info.username + '，' + moment().format('L')
          if (user_info.name) {
            this.watermark_txt += '，' + user_info.name
          }
          setTimeout(() => {
            watermark.load({
              monitor: false, // monitor 是否监控， true: 不可删除水印; false: 可删水印。
              watermark_txt: this.watermark_txt
            })
          }, 700)
        }
      } else {
        // 网络请求获取用户信息
        this.get_user_info(response => {
          if (response.data.error_code === 0) {
            let user_info = response.data.data
            this.$store.dispatch('changeUserInfo', user_info).then(() => {
              this.renderWatermark()
            })
          } else {
            // 假如没登录
            // 探索了下，纯js无法获取ip，不能展示ip水印。所以做不了啥
            // 如果说是通过showdoc后端获取ip的话，假如showdoc部署在网关后面，它获取的ip不一定准确
          }
        })
      }
    }
  },
  mounted() {
    this.get_item_menu()
    this.$store.dispatch('changeOpenCatId', 0)
  },
  beforeDestroy() {
    this.$message.closeAll()
    document.title = 'ShowDoc'
    watermark.remove() // 去掉水印
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped></style>
