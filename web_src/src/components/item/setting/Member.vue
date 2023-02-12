<template>
  <div class="hello">
    <SDialog
      :onCancel="callback"
      :title="$t('member_manage')"
      width="650px"
      :onOK="callback"
      :showCancel="false"
      :btn1Text="$t('add_member')"
      btn1Icon="el-icon-plus"
      :btn1Medthod="
        () => {
          dialogFormVisible = true
        }
      "
      :btn2Text="$t('add_team')"
      btn2Icon="el-icon-plus"
      :btn2Medthod="
        () => {
          dialogFormTeamVisible = true
        }
      "
    >
      <h4 v-if="members.length > 0">{{ $t('item_member') }}</h4>
      <!-- 单个成员列表 -->
      <el-table
        align="left"
        class="mb-8"
        v-if="members.length > 0"
        :data="members"
        style="width: 100%"
      >
        <el-table-column
          prop="username"
          :label="$t('member_username')"
        ></el-table-column>
        <el-table-column prop="name" :label="$t('name')"></el-table-column>
        <el-table-column
          prop="addtime"
          :label="$t('add_time')"
        ></el-table-column>
        <el-table-column prop="member_group_id" :label="$t('authority')">
          <template slot-scope="scope"
            >{{
              memberGroupText(scope.row.member_group_id, scope.row.cat_name)
            }}
          </template>
        </el-table-column>
        <el-table-column prop :label="$t('operation')">
          <template slot-scope="scope">
            <el-button
              @click="deleteMember(scope.row.item_member_id)"
              type="text"
              size="small"
              >{{ $t('delete') }}</el-button
            >
          </template>
        </el-table-column>
      </el-table>

      <h4 v-if="teamItems.length > 0">{{ $t('item_team_info') }}</h4>
      <!-- 团队列表 -->
      <el-table
        align="left"
        v-if="teamItems.length > 0"
        :data="teamItems"
        style="width: 100%"
      >
        <el-table-column
          prop="team_name"
          :label="$t('team_name')"
        ></el-table-column>
        <el-table-column
          prop="addtime"
          :label="$t('add_time')"
        ></el-table-column>

        <el-table-column prop :label="$t('operation')">
          <template slot-scope="scope">
            <el-button
              @click="getTeamItemMember(scope.row.team_id)"
              type="text"
              size="small"
              >{{ $t('member_authority') }}</el-button
            >
            <el-button
              @click="deleteTeam(scope.row.id)"
              type="text"
              size="small"
              >{{ $t('delete') }}</el-button
            >
          </template>
        </el-table-column>
      </el-table>
      <p
        v-if="members.length == 0 && teamItems.length == 0"
        class="v3-color-aux"
      >
        {{ $t('no_member_tips') }}
      </p>
    </SDialog>

    <!-- 添加单个成员弹窗 -->
    <SDialog
      v-if="dialogFormVisible"
      :onCancel="
        () => {
          dialogFormVisible = false
        }
      "
      :title="$t('add_member')"
      width="400px"
      :onOK="
        () => {
          myFormSubmit()
        }
      "
    >
      <el-form>
        <el-form-item :label="$t('member_username') + ':'">
          <el-select
            v-model="MyForm.username"
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
        <el-form-item>
          <el-radio v-model="MyForm.member_group_id" label="1">{{
            $t('edit_member')
          }}</el-radio>
          <el-radio v-model="MyForm.member_group_id" label="0">{{
            $t('readonly_member')
          }}</el-radio>
          <el-radio v-model="MyForm.member_group_id" label="2">{{
            $t('item_admin')
          }}</el-radio>
        </el-form-item>
        <el-form-item label v-show="MyForm.member_group_id < 2">
          <el-select
            style="width:100%"
            v-model="MyForm.cat_id"
            :placeholder="$t('all_cat2')"
          >
            <el-option
              v-for="item in catalogs"
              :key="item.cat_id"
              :label="item.cat_name"
              :value="item.cat_id"
            ></el-option>
          </el-select>
        </el-form-item>
      </el-form>

      <p class="v3-font-size-sm v3-color-aux">
        {{ $t('member_authority_tips') }}
      </p>
    </SDialog>

    <!-- 添加团队弹窗 -->
    <SDialog
      v-if="dialogFormTeamVisible"
      :onCancel="
        () => {
          dialogFormTeamVisible = false
        }
      "
      :title="$t('member_manage')"
      width="400px"
      :onOK="
        () => {
          addTeam()
        }
      "
      :btn1Text="$t('go_to_new_an_team')"
      btn1Icon="el-icon-plus"
      :btn1Medthod="
        () => {
          showTeam = true
        }
      "
    >
      <div>
        <el-form>
          <el-form-item :label="$t('c_team')">
            <el-select class v-model="MyForm2.team_id">
              <el-option
                v-for="team in teams"
                :key="team.team_name"
                :label="team.team_name"
                :value="team.id"
              ></el-option>
            </el-select>
          </el-form-item>
        </el-form>
      </div>
    </SDialog>

    <!-- 成员权限弹窗 -->
    <SDialog
      v-if="dialogFormTeamMemberVisible"
      :onCancel="
        () => {
          dialogFormTeamMemberVisible = false
        }
      "
      :title="$t('adjust_member_authority')"
      width="700px"
      :onOK="
        () => {
          dialogFormTeamMemberVisible = false
        }
      "
      :showCancel="false"
    >
      <el-table
        align="left"
        :empty-text="$t('team_member_empty_tips')"
        :data="teamItemMembers"
        style="width: 100%"
      >
        <el-table-column
          prop="member_username"
          :label="$t('username')"
        ></el-table-column>
        <el-table-column
          prop="member_group_id"
          :label="$t('authority')"
          width="130"
        >
          <template slot-scope="scope">
            <el-select
              size="mini"
              v-model="scope.row.member_group_id"
              @change="changeTeamItemMemberGroup($event, scope.row.id)"
              :placeholder="$t('please_choose')"
            >
              <el-option
                v-for="item in authorityOptions"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              ></el-option>
            </el-select>
          </template>
        </el-table-column>
        <el-table-column prop="cat_id" :label="$t('catalog')" width="130">
          <template slot-scope="scope">
            <el-select
              v-if="scope.row.member_group_id <= 1"
              size="mini"
              v-model="scope.row.cat_id"
              @change="changeTeamItemMemberCat($event, scope.row.id)"
              :placeholder="$t('please_choose')"
            >
              <el-option
                v-for="item in catalogs"
                :key="item.cat_id"
                :label="item.cat_name"
                :value="item.cat_id"
              ></el-option>
            </el-select>
          </template>
        </el-table-column>
        <el-table-column
          prop="addtime"
          :label="$t('add_time')"
        ></el-table-column>
      </el-table>
      <br />
      <p class="v3-font-size-sm v3-color-aux">
        {{ $t('team_member_authority_tips') }}
      </p>
    </SDialog>

    <!-- 去新建团队/团队管理 -->
    <Team
      v-if="showTeam"
      :callback="
        () => {
          getTeams()
          showTeam = false
        }
      "
    ></Team>
  </div>
</template>

<script>
import Team from '@/components/team/Index'
export default {
  name: '',
  components: { Team },
  props: {
    callback: () => {},
    item_id: 0
  },
  data() {
    return {
      MyForm: {
        username: '',
        cat_id: '',
        member_group_id: '1'
      },
      MyForm2: {
        team_id: ''
      },
      members: [],
      dialogFormVisible: false,
      dialogFormTeamVisible: false,
      dialogFormTeamMemberVisible: false,
      teams: [],
      teamItems: [],
      teamItemMembers: [],
      authorityOptions: [
        {
          label: this.$t('edit_member'),
          value: '1'
        },
        {
          label: this.$t('readonly_member'),
          value: '0'
        },
        {
          label: this.$t('item_admin'),
          value: '2'
        }
      ],
      catalogs: [],
      myAllList: [], // 我之前添加过的成员列表,
      showTeam: false,
      memberOptions: []
    }
  },
  methods: {
    getMembers() {
      this.request('/api/member/getList', {
        item_id: this.item_id
      }).then(data => {
        const json = data.data
        this.members = json
      })
    },
    getTeams() {
      this.request('/api/team/getList', {}).then(data => {
        const json = data.data
        this.teams = json
      })
    },
    getTeamItem() {
      this.request('/api/teamItem/getList', {
        item_id: this.item_id
      }).then(data => {
        const json = data.data
        this.teamItems = json
      })
    },
    getTeamItemMember(team_id) {
      this.dialogFormTeamMemberVisible = true
      this.request('/api/teamItemMember/getList', {
        item_id: this.item_id,
        team_id: team_id
      }).then(data => {
        const json = data.data
        this.teamItemMembers = json
      })
    },
    myFormSubmit() {
      this.request(
        '/api/member/save',
        {
          item_id: this.item_id,
          username: this.MyForm.username,
          cat_id: this.MyForm.cat_id,
          member_group_id: this.MyForm.member_group_id
        },
        'post',
        false
      ).then(data => {
        if (data.error_code === 0) {
          this.dialogFormVisible = false
          this.getMembers()
          this.MyForm.username = ''
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
    addTeam() {
      this.request('/api/teamItem/save', {
        item_id: this.item_id,
        team_id: this.MyForm2.team_id
      }).then(data => {
        this.dialogFormTeamVisible = false
        this.getTeamItem()
        this.MyForm.team_id = ''
      })
    },
    deleteMember(item_member_id) {
      this.$confirm(this.$t('confirm_delete'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/member/delete', {
          item_id: this.item_id,
          item_member_id: item_member_id
        }).then(data => {
          this.getMembers()
        })
      })
    },
    deleteTeam(id) {
      this.$confirm(this.$t('confirm_delete'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/teamItem/delete', {
          id: id
        }).then(data => {
          this.getTeamItem()
        })
      })
    },
    changeTeamItemMemberGroup(member_group_id, id) {
      this.request('/api/teamItemMember/save', {
        member_group_id: member_group_id,
        id: id
      }).then(data => {
        this.$message(this.$t('auth_success'))
      })
    },
    getCatalog() {
      this.request('/api/catalog/catListGroup', {
        item_id: this.item_id
      }).then(data => {
        var json = data.data
        json.unshift({
          cat_id: '0',
          cat_name: this.$t('all_cat')
        })
        this.catalogs = json
      })
    },
    changeTeamItemMemberCat(cat_id, id) {
      this.request('/api/teamItemMember/save', {
        cat_id: cat_id,
        id: id
      }).then(data => {
        this.$message(this.$t('cat_success'))
      })
    },
    memberGroupText(member_group_id, cat_name) {
      if (member_group_id == '2') {
        return this.$t('item_admin')
      }
      if (member_group_id == '1') {
        return this.$t('edit') + '/' + this.$t('catalog') + '：' + cat_name
      }
      return this.$t('readonly') + '/' + this.$t('catalog') + '：' + cat_name
    },
    // 获取选择之前添加过的成员名列表
    getMyAllList() {
      this.request('/api/member/getMyAllList', {}).then(data => {
        this.myAllList = data.data
      })
    },
    dropdownCallback(data) {
      this.MyForm.username = data
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
      let list = this.members
      for (var i = 0; i < list.length; i++) {
        if (list[i]['username'] == username) {
          return true
        }
      }
      return false
    }
  },

  mounted() {
    this.getMembers()
    this.getTeams()
    this.getTeamItem()
    this.getCatalog()
    this.getAllUser()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.hello {
  text-align: left;
}

.add-member {
  margin-left: 10px;
}
</style>
