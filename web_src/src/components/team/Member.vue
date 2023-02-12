<template>
  <div class="hello">
    <Header></Header>

    <SDialog
      :title="$t('manage_members')"
      :btn1Text="team_manage ? $t('add_member') : ''"
      btn1Icon="el-icon-plus"
      :btn1Medthod="addTeamMember"
      :onCancel="callback"
      :showCancel="false"
      :onOK="callback"
    >
      <div>
        <el-table
          align="left"
          :data="list"
          :empty-text="$t('empty_team_member_tips')"
        >
          <el-table-column
            prop="member_username"
            :label="$t('member_username')"
          ></el-table-column>
          <el-table-column
            prop="team_member_group_id"
            :label="$t('member_authority')"
          >
            <template slot-scope="scope">
              {{
                scope.row.team_member_group_id == 2
                  ? $t('team_admin')
                  : $t('ordinary_member')
              }}
            </template>
          </el-table-column>
          <el-table-column prop="name" :label="$t('name')"></el-table-column>
          <el-table-column
            prop="addtime"
            :label="$t('addtime')"
          ></el-table-column>

          <el-table-column prop :label="$t('operation')">
            <template slot-scope="scope">
              <el-button
                v-if="team_manage"
                @click="deleteTeamMember(scope.row.id)"
                type="text"
                size="small"
                >{{ $t('delete') }}</el-button
              >
            </template>
          </el-table-column>
        </el-table>
      </div>
    </SDialog>

    <SDialog
      v-if="dialogFormVisible"
      :title="$t('add_member')"
      :onCancel="
        () => {
          dialogFormVisible = false
        }
      "
      :onOK="myFormSubmit"
      width="400px"
    >
      <el-form>
        <el-form-item :label="$t('member_username') + ':'">
          <el-select
            v-model="MyForm.member_username"
            multiple
            filterable
            reserve-keyword
            placeholder
          >
            <el-option
              v-for="item in memberOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            ></el-option>
          </el-select>
        </el-form-item>
        <el-form-item class="text-center">
          <el-radio v-model="MyForm.team_member_group_id" label="1">{{
            $t('ordinary_member')
          }}</el-radio>
          <el-radio v-model="MyForm.team_member_group_id" label="2">{{
            $t('team_admin')
          }}</el-radio>
        </el-form-item>
      </el-form>
    </SDialog>
  </div>
</template>

<script>
export default {
  components: {},
  props: {
    callback: {
      type: Function,
      required: false,
      default: () => {}
    },
    team_id: {
      type: Number,
      required: false,
      default: 0
    },
    team_manage: {
      type: Number || String,
      required: false,
      default: 1
    }
  },
  data() {
    return {
      MyForm: {},
      list: [],
      dialogFormVisible: false,
      myAllList: [], // 我之前添加过的成员列表
      memberOptions: []
    }
  },
  methods: {
    geList() {
      this.request('/api/teamMember/getList', {
        team_id: this.team_id
      }).then(data => {
        this.list = data.data
      })
    },
    reSetMyForm() {
      this.MyForm = {
        id: '',
        member_username: '',
        team_member_group_id: '1'
      }
    },
    myFormSubmit() {
      this.request(
        '/api/teamMember/save',
        {
          team_id: this.team_id,
          member_username: this.MyForm.member_username,
          team_member_group_id: this.MyForm.team_member_group_id
        },
        'post',
        false
      ).then(data => {
        if (data.error_code === 0) {
          this.dialogFormVisible = false
          this.geList()
          this.reSetMyForm()
        } else if (data.error_code === 10310) {
          this.$alert(
            '你添加的协作成员数量超出限制(所有团队成员以及所有项目的单独成员加起来后去重，就是协作成员数)。你可以开通高级版以获取更多配额。<a href="/prices" target="_blank" >点此查看不同账户类型的额度限制差异</a>，也可以<a href="/user/setting" target="_blank" >点此去升级账户类型</a>。<br>如果你现在不方便处理，你可以等会再自行回到项目列表页，点击右上角的用户中心去升级。',
            {
              dangerouslyUseHTMLString: true
            }
          )
        } else {
          this.$alert(data.error_message)
        }
      })
    },

    deleteTeamMember(id) {
      this.$confirm(this.$t('confirm_delete'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/teamMember/delete', {
          id: id
        }).then(data => {
          this.geList()
        })
      })
    },
    addTeamMember() {
      this.reSetMyForm()
      this.dialogFormVisible = true
    },
    goback() {
      this.$router.push({ path: '/team/index' })
    },
    // 获取选择之前添加过的成员名列表
    getMyAllList() {
      this.request('/api/member/getMyAllList', {}).then(data => {
        this.myAllList = data.data
      })
    },
    dropdownCallback(data) {
      this.MyForm.member_username = data
    },
    getAllUser() {
      this.request('/api/user/allUser', {
        username: ''
      }).then(data => {
        var Info = data.data
        var newInfo = []
        // 过滤掉已经是成员的用户
        for (var i = 0; i < Info.length; i++) {
          let isMember = this.isMember(Info[i]['value'])
          if (!isMember) {
            newInfo.push(Info[i])
          }
        }
        this.memberOptions = []
        for (let index = 0; index < newInfo.length; index++) {
          this.memberOptions.push({
            value: newInfo[index].username,
            label: newInfo[index].name
              ? newInfo[index].username + '(' + newInfo[index].name + ')'
              : newInfo[index].username,
            key: newInfo[index].username
          })
        }
      })
    },
    // 判断某个用户是否已经是会员
    isMember(username) {
      let list = this.list
      for (var i = 0; i < list.length; i++) {
        if (list[i]['member_username'] == username) {
          return true
        }
      }
      return false
    }
  },

  mounted() {
    this.reSetMyForm()
    this.geList()
    this.getAllUser()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.hello {
  text-align: left;
}

.add-cat {
  float: right;
  margin-right: 15px;
  font-size: 14px;
}

.center-card {
  text-align: left;
  width: 700px;
  height: 600px;
}

.goback-btn {
  z-index: 999;
  font-size: 14px;
}
</style>

<!-- 全局css -->
<style>
.el-table .success-row {
  background: #f0f9eb;
}
</style>
