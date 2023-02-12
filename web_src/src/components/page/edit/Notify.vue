<!-- 附件 -->
<template>
  <div class="hello">
    <SDialog
      v-if="dialogVisible"
      :title="$t('save_and_notify')"
      :onCancel="
        () => {
          callback('')
        }
      "
      :onOK="
        () => {
          callback(textarea)
        }
      "
      width="500px"
    >
      <div>
        <el-input
          type="textarea"
          :rows="4"
          :placeholder="$t('input_update_remark')"
          v-model="textarea"
        >
        </el-input>
      </div>
      <p>
        <el-button @click="dialogVisible2 = true" type="text">{{
          $t('click_to_edit_member')
        }}</el-button>
        <span class="tips-text"
          >( {{ $t('cur_setting_notify') }} {{ list.length }}
          {{ $t('people') }} )</span
        >
      </p>
      <p class="tips-text">
        {{ $t('notify_tips1') }}
      </p>
    </SDialog>

    <!-- 通知人员列表的对话框 -->
    <SDialog
      v-if="dialogVisible2"
      :onCancel="
        () => {
          dialogVisible2 = false
        }
      "
      :onOK="
        () => {
          dialogVisible2 = false
        }
      "
      width="500px"
    >
      <div>
        <el-button @click="dialogVisible3 = true">{{
          $t('add_single_member')
        }}</el-button>
        <el-button @click="addAllMember">{{ $t('add_all_member') }}</el-button>
        <br />
        <el-table :data="list">
          <el-table-column
            property="username"
            :label="$t('username')"
          ></el-table-column>
          <el-table-column
            property="name"
            :label="$t('name')"
          ></el-table-column>
          <el-table-column
            property="sub_time"
            :label="$t('addtime')"
          ></el-table-column>
          <el-table-column prop :label="$t('operation')">
            <template slot-scope="scope">
              <el-button type="text" @click="DeleteOne(scope.row)">{{
                $t('delete')
              }}</el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </SDialog>

    <!-- 添加人员对话框 -->
    <SDialog
      v-if="dialogVisible3"
      :onCancel="
        () => {
          dialogVisible3 = false
        }
      "
      :onOK="addMember"
      width="400px"
    >
      <el-form>
        <el-form-item :label="$t('username') + ':'">
          <el-select
            v-model="to_add_member_uid"
            multiple
            filterable
            reserve-keyword
            placeholder="请选择或搜索"
          >
            <el-option
              v-for="item in allItemMemberList"
              :key="item.uid"
              :label="item.username_name"
              :value="item.uid"
            ></el-option>
          </el-select>
          <el-tooltip effect="dark" :content="$t('refresh_member_list')">
            <i
              class="el-icon-refresh-right icon-btn"
              @click="getAllItemMemberList"
            ></i>
          </el-tooltip>
          <p class="leading-8	v3-font-size-sm v3-color-aux">
            {{ $t('notify_add_member_tips1') }}
          </p>
        </el-form-item>
      </el-form>
    </SDialog>
  </div>
</template>

<style scoped>
.icon-btn {
  cursor: pointer;
  margin-left: 5px;
}
</style>

<script>
export default {
  props: {
    callback: () => {},
    page_id: '',
    item_id: ''
  },
  data() {
    return {
      list: [],
      textarea: '',
      dialogVisible: true,
      dialogVisible2: false,
      dialogVisible3: false,
      allItemMemberList: [],
      to_add_member_uid: []
    }
  },
  components: {},
  computed: {},
  methods: {
    getList() {
      this.request('/api/subscription/getPageList', {
        page_id: this.page_id
      }).then(data => {
        const json = data.data
        this.list = json
      })
    },
    handleCurrentChange(currentPage) {
      this.page = currentPage
      this.getList()
    },
    toItemSetting() {
      let routeUrl = this.$router.resolve({
        path: '/item/setting/' + this.item_id
      })
      window.open(routeUrl.href, '_blank')
    },
    toTeam() {
      let routeUrl = this.$router.resolve({
        path: '/team/index'
      })
      window.open(routeUrl.href, '_blank')
    },
    // 获取一个项目的所有成员列表。包括单独成员和绑定的团队成员
    getAllItemMemberList() {
      this.request('/api/member/getAllList', {
        item_id: this.item_id
      }).then(data => {
        const json = data.data
        this.allItemMemberList = json
      })
    },
    addMember() {
      this.request('/api/subscription/savePage', {
        page_id: this.page_id,
        uids: this.to_add_member_uid.join(',')
      }).then(data => {
        this.dialogVisible3 = false
        this.getList()
        this.to_add_member_uid = []
      })
    },
    // 一键添加全部成员
    addAllMember() {
      if (this.allItemMemberList && this.allItemMemberList.length > 0) {
        const memberList = []
        this.allItemMemberList.map(element => {
          memberList.push(element.uid)
        })
        this.request('/api/subscription/savePage', {
          page_id: this.page_id,
          uids: memberList.join(',')
        }).then(data => {
          this.getList()
        })
      }
    },
    DeleteOne(row) {
      this.request('/api/subscription/deletePage', {
        uids: row.uid,
        page_id: this.page_id
      }).then(data => {
        this.getList()
      })
    }
  },
  mounted() {
    this.getAllItemMemberList()
    this.getList()
  }
}
</script>
