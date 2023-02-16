<template>
  <div class="hello">
    <Header></Header>
    <SDialog
      :title="$t('my_notice')"
      :onCancel="callback"
      :showCancel="false"
      :onOK="callback"
      top="10vh"
    >
      <div>
        <el-tabs v-model="dtab" type="card" @tab-click="tabClick">
          <el-tab-pane :label="$t('system_reminder')" name="remindList">
            <el-table :data="remindList">
              <el-table-column
                prop="addtime"
                :label="$t('send_time')"
              ></el-table-column>
              <el-table-column prop="message_content" :label="$t('content')">
                <template slot-scope="props">
                  <div>
                    <div
                      v-if="
                        (props.row.action_type == 'create' ||
                          props.row.action_type == 'update') &&
                          props.row.object_type == 'page'
                      "
                    >
                      <div>
                        {{ props.row.from_name }} {{ $t('update_the_page') }}
                        <el-button
                          @click="
                            visitPage(
                              props.row.page_data.item_id,
                              props.row.page_data.page_id
                            )
                          "
                          type="text"
                          >{{ props.row.page_data.page_title }}</el-button
                        >
                        <el-badge
                          class="mark"
                          value="new"
                          v-if="props.row.status == 0"
                        />
                      </div>
                      <div v-if="props.row.message_content">
                        {{ $t('update_remark') }}:
                        <span>{{ props.row.message_content }}</span>
                      </div>
                    </div>
                    <div v-if="props.row.object_type == 'vip'">
                      你在showdoc购买的付费版资格很快过期了，你可以<a
                        href="/user/setting"
                        target="_blank"
                        >点此进入用户中心</a
                      >进行续费 (如已续费请忽略该通知)
                      <el-badge
                        class="mark"
                        value="new"
                        v-if="props.row.status == 0"
                      />
                    </div>
                  </div>
                </template>
              </el-table-column>
            </el-table>
            <div class="block">
              <span class="demonstration"></span>
              <el-pagination
                @current-change="handleCurrentChange"
                :page-size="count"
                layout="total, prev, pager, next"
                :total="total"
              ></el-pagination>
            </div>
          </el-tab-pane>
          <el-tab-pane
            :label="$t('system_announcement')"
            name="announcementList"
          >
            <el-table :data="announcementList" style="width: 100%">
              <el-table-column
                prop="addtime"
                :label="$t('send_time')"
              ></el-table-column>
              <el-table-column prop="message_content" :label="$t('content')">
                <template slot-scope="props">
                  <span v-html="props.row.message_content"></span>
                  <el-badge
                    class="mark"
                    value="new"
                    v-if="props.row.status == 0"
                  />
                </template>
              </el-table-column>
            </el-table>
          </el-tab-pane>
        </el-tabs>
      </div>
    </SDialog>

    <Footer></Footer>
  </div>
</template>

<script>
export default {
  name: '',
  components: {},
  props: {
    callback: {
      type: Function,
      required: false,
      default: () => {}
    }
  },
  data() {
    return {
      page: 1,
      count: 5,
      total: 0,
      announcementList: [],
      remindList: [],
      dtab: 'remindList'
    }
  },
  methods: {
    getAnnouncementList() {
      this.request('/api/message/getAnnouncementList', {}).then(data => {
        const json = data.data
        this.announcementList = json
        json.map(element => {
          this.setRead(element.from_uid, element.message_content_id)
        })
      })
    },
    getRemindList() {
      this.request('/api/message/getRemindList', {
        page: this.page,
        count: this.count
      }).then(data => {
        const json = data.data.list
        this.total = data.data.total
        this.remindList = []
        json.map(element => {
          this.remindList.push(element)
          this.setRead(element.from_uid, element.message_content_id)
        })
      })
    },
    setRead(from_uid, message_content_id) {
      if (message_content_id) {
        this.request('/api/message/setRead', {
          message_content_id: message_content_id,
          from_uid: from_uid
        }).then(data => {})
      }
    },
    goback() {
      this.$router.push({ path: '/item/index' })
    },
    visit(url) {
      window.open(url)
    },
    tabClick(e) {
      if (e.name == 'remindList') {
        // this.getRemindList()
      }
    },
    visitPage(item_id, page_id) {
      let routeUrl = this.$router.resolve({
        path: '/' + item_id + '/' + page_id
      })
      window.open(routeUrl.href, '_blank')
    },
    handleCurrentChange(currentPage) {
      this.page = currentPage
      this.getRemindList()
    },
    // 获取用户未读的提醒
    getUnread() {
      this.request('/api/message/getUnread', {}, 'post', false).then(data => {
        const json = data.data
        // 提醒类消息
        if (json['remind'] && json['remind'].id) {
          // 有未读的提醒消息
        }
        // 公告类消息
        if (json['announce'] && json['announce'].id) {
          // 有未读的公告消息,且没有从参数中指定tab，则默认打开公告
          if (!this.$route.query.dtab) {
            this.dtab = 'announcementList'
          }
        }
      })
    }
  },

  mounted() {
    this.getRemindList()
    this.getAnnouncementList()
    if (this.$route.query.dtab) {
      this.dtab = this.$route.query.dtab
    }
    this.getUnread()
  },
  beforeDestroy() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.hor-center-card {
  width: 1000px;
}
</style>
