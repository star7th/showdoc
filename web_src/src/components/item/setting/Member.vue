<template>
  <div class="hello">
    <el-button
      type="text"
      class="add-member"
      @click="dialogFormVisible = true"
      >{{ $t('add_member') }}</el-button
    >
    <el-button
      type="text"
      class="add-member"
      @click="dialogFormTeamVisible = true"
      >{{ $t('add_team') }}</el-button
    >

    <!-- 单个成员列表 -->
    <el-table
      align="left"
      v-if="members.length > 0"
      :data="members"
      height="200"
      style="width: 100%"
    >
      <el-table-column
        prop="username"
        :label="$t('member_username')"
        width="100"
      ></el-table-column>
      <el-table-column prop="name" :label="$t('name')"></el-table-column>
      <el-table-column
        prop="addtime"
        :label="$t('add_time')"
        width="100"
      ></el-table-column>
      <el-table-column
        prop="member_group_id"
        :label="$t('authority')"
        width="120"
      >
        <template slot-scope="scope"
          >{{ memberGroupText(scope.row.member_group_id, scope.row.cat_name) }}
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

    <!-- 团队列表 -->
    <el-table
      align="left"
      v-if="teamItems.length > 0"
      :data="teamItems"
      height="200"
      style="width: 100%"
    >
      <el-table-column
        prop="team_name"
        :label="$t('team_name')"
      ></el-table-column>
      <el-table-column prop="addtime" :label="$t('add_time')"></el-table-column>

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

    <!-- 添加单个成员弹窗 -->
    <el-dialog
      :visible.sync="dialogFormVisible"
      :modal="false"
      top="10vh"
      width="400px"
      :close-on-click-modal="false"
    >
      <el-form>
        <el-form-item label>
          <el-select
            v-model="MyForm.username"
            multiple
            filterable
            reserve-keyword
            placeholder
            :loading="loading"
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

      <p class="tips">{{ $t('member_authority_tips') }}</p>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{
          $t('cancel')
        }}</el-button>
        <el-button type="primary" @click="myFormSubmit">{{
          $t('confirm')
        }}</el-button>
      </div>
    </el-dialog>

    <!-- 添加团队弹窗 -->
    <el-dialog
      :visible.sync="dialogFormTeamVisible"
      :modal="false"
      top="10vh"
      :close-on-click-modal="false"
    >
      <el-form>
        <el-form-item label="选择团队">
          <el-select class v-model="MyForm2.team_id">
            <el-option
              v-for="team in teams"
              :key="team.team_name"
              :label="team.team_name"
              :value="team.id"
            ></el-option>
          </el-select>
        </el-form-item>
        <router-link to="/team/index" target="_blank">{{
          $t('go_to_new_an_team')
        }}</router-link>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormTeamVisible = false">{{
          $t('cancel')
        }}</el-button>
        <el-button type="primary" @click="addTeam">{{
          $t('confirm')
        }}</el-button>
      </div>
    </el-dialog>

    <!-- 成员权限弹窗 -->
    <el-dialog
      :visible.sync="dialogFormTeamMemberVisible"
      :modal="false"
      top="10vh"
      :title="$t('adjust_member_authority')"
      width="90%"
      :close-on-click-modal="false"
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
      <p class="tips">{{ $t('team_member_authority_tips') }}</p>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormTeamMemberVisible = false">{{
          $t('close')
        }}</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
export default {
  name: 'Login',
  components: {},
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
      memberOptions: []
    }
  },
  methods: {
    getMembers() {
      this.request('/api/member/getList', {
        item_id: this.$route.params.item_id
      }).then(data => {
        this.members = data.data
        this.getAllUser()
      })
    },
    getTeams() {
      this.request('/api/team/getList', {}).then(data => {
        this.teams = data.data
      })
    },
    getTeamItem() {
      this.request('/api/teamItem/getList', {
        item_id: this.$route.params.item_id
      }).then(data => {
        this.teamItems = data.data
      })
    },
    getTeamItemMember(team_id) {
      this.dialogFormTeamMemberVisible = true
      this.request('/api/teamItemMember/getList', {
        item_id: this.$route.params.item_id,
        team_id: team_id
      }).then(data => {
        this.teamItemMembers = data.data
      })
    },
    myFormSubmit() {
      this.request('/api/member/save', {
        item_id: this.$route.params.item_id,
        username: this.MyForm.username,
        cat_id: this.MyForm.cat_id,
        member_group_id: this.MyForm.member_group_id
      }).then(data => {
        this.dialogFormVisible = false
        this.getMembers()
        this.MyForm.username = ''
      })
    },
    addTeam() {
      this.request('/api/teamItem/save', {
        item_id: this.$route.params.item_id,
        team_id: this.MyForm2.team_id
      }).then(data => {
        this.dialogFormTeamVisible = false
        this.getTeamItem()
        this.MyForm2.team_id = ''
      })
    },
    deleteMember(item_member_id) {
      this.$confirm(this.$t('confirm_delete'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/member/delete', {
          item_member_id: item_member_id,
          item_id: this.$route.params.item_id
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
        id: id,
        member_group_id: member_group_id
      }).then(data => {
        this.$message(this.$t('auth_success'))
      })
    },
    getAllUser(queryString, cb) {
      if (!queryString) {
        queryString = ''
      }
      this.request('/api/user/allUser', {
        username: queryString
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
        if (cb) cb(Info)
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
    },
    getCatalog() {
      this.request('/api/catalog/catListGroup', {
        item_id: this.$route.params.item_id
      }).then(data => {
        var Info = data.data
        Info.unshift({
          cat_id: '0',
          cat_name: this.$t('all_cat')
        })
        this.catalogs = Info
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
    }
  },

  mounted() {
    this.getMembers()
    this.getTeams()
    this.getTeamItem()
    this.getAllUser()
    this.getCatalog()
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

.tips {
  font-size: 12px;
  margin-bottom: 0px;
  margin-top: 0px;
}
</style>
