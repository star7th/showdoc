<template>
  <div class="hello">
    <Header></Header>

    <el-container>
      <el-card class="hor-center-card">
        <el-button type="text" @click="goback" class="goback-btn">
          <i class="el-icon-back"></i>
        </el-button>

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
          <!-- <el-tab-pane
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
          </el-tab-pane> -->
        </el-tabs>
      </el-card>
    </el-container>

    <Footer></Footer>
  </div>
</template>

<script>
export default {
  name: '',
  components: {},
  data() {
    return {
      page: 1,
      count: 8,
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
    }
  },

  mounted() {
    this.getRemindList()
    if (this.$route.query.dtab) {
      this.dtab = this.$route.query.dtab
    }
  },
  beforeDestroy() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.hor-center-card a {
  font-size: 12px;
}

.hor-center-card {
  width: 1000px;
}

.goback-btn {
  font-size: 18px;
  margin-right: 800px;
  margin-bottom: 5px;
}
</style>
