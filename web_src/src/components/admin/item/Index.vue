<template>
  <div class="hello">
    <el-tabs v-model="activeItemTab" @tab-click="onItemTabChange" style="margin-bottom: 10px;">
      <el-tab-pane :label="$t('admin_item_tab_list')" name="normal"></el-tab-pane>
      <el-tab-pane :label="$t('admin_item_tab_deleted')" name="deleted"></el-tab-pane>
    </el-tabs>
    <el-form :inline="true" class="demo-form-inline">
      <el-form-item label>
        <el-input v-model="item_name" :placeholder="$t('item_name')"></el-input>
      </el-form-item>
      <el-form-item label>
        <el-input v-model="username" :placeholder="$t('owner')"></el-input>
      </el-form-item>
      <!--   <el-form-item label="活动区域">
    <el-select v-model="formInline.region" placeholder="活动区域">
      <el-option label="区域一" value="shanghai"></el-option>
      <el-option label="区域二" value="beijing"></el-option>
    </el-select>
      </el-form-item>-->
      <el-form-item>
        <el-button @click="onSubmit">{{ $t('search') }}</el-button>
      </el-form-item>
    </el-form>

    <el-table :data="itemList" style="width: 100%">
      <el-table-column
        prop="item_name"
        :label="$t('item_name')"
        width="140"
      ></el-table-column>
      <el-table-column
        prop="item_description"
        :label="$t('item_description')"
        width="140"
      ></el-table-column>
      <el-table-column
        prop="password"
        :label="$t('privacy')"
        :formatter="formatPrivacy"
        width="80"
      ></el-table-column>

      <el-table-column prop="item_id" :label="$t('link')" width="100">
        <template slot-scope="scope">
          <el-button v-if="is_del === '0'" @click="jumpToItem(scope.row)" type="text" size="small">{{ $t('link') }}</el-button>
          <span v-else>已删除</span>
        </template>
      </el-table-column>
      <el-table-column
        prop="username"
        :label="$t('owner')"
        width="160"
      ></el-table-column>
      <el-table-column :label="$t('memberCount')" prop="member_num" width="80"></el-table-column>
      <el-table-column
        prop="addtime"
        :label="$t('add_time')"
        width="160"
      ></el-table-column>
      <el-table-column prop="item_domain" :label="$t('operation')">
        <template slot-scope="scope">
          <template v-if="is_del === '0'">
            <el-button @click="manageMember(scope.row)" type="text" size="small">{{ $t('member_manage') }}</el-button>
            <el-button @click="clickAttornItem(scope.row)" type="text" size="small">{{ $t('attorn') }}</el-button>
            <el-button @click="deleteItem(scope.row)" type="text" size="small">{{ $t('delete') }}</el-button>
          </template>
          <template v-else>
            <el-button @click="recoverItem(scope.row)" type="text" size="small">恢复</el-button>
            <el-button @click="hardDeleteItem(scope.row)" type="text" size="small" style="color:#F56C6C;">永久删除</el-button>
          </template>
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

    <el-dialog
      :visible.sync="dialogAttornVisible"
      :close-on-click-modal="false"
      width="300px"
    >
      <el-form>
        <el-form-item label>
          <el-input
            :placeholder="$t('attorn_username')"
            v-model="attornForm.username"
          ></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogAttornVisible = false">{{
          $t('cancel')
        }}</el-button>
        <el-button type="primary" @click="attorn">{{ $t('attorn') }}</el-button>
      </div>
    </el-dialog>

    <!-- 成员管理对话框 -->
    <el-dialog
      :visible.sync="dialogMemberVisible"
      :close-on-click-modal="false"
      width="750px"
      :title="$t('member_manage')"
    >
      <div>
        <el-tabs v-model="activeTab">
          <el-tab-pane :label="$t('item_member')" name="members">
            <!-- 添加成员按钮 -->
            <div class="mb-3">
              <el-button type="primary" size="small" @click="openAddMemberDialog">
                <i class="el-icon-plus"></i> {{ $t('add_member') }}
              </el-button>
            </div>
            <!-- 单个成员列表 -->
            <el-table
              align="left"
              class="mb-8"
              :data="members"
              :empty-text="$t('no_member_tips')"
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
          </el-tab-pane>
          
          <el-tab-pane :label="$t('item_team_info')" name="teams">
            <!-- 添加团队按钮 -->
            <div class="mb-3">
              <el-button type="primary" size="small" @click="openAddTeamDialog">
                <i class="el-icon-plus"></i> {{ $t('add_team') }}
              </el-button>
            </div>
            <!-- 团队列表 -->
            <el-table
              align="left"
              :data="teamItems"
              :empty-text="$t('no_team_tips')"
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
              <el-table-column prop="id" :label="$t('operation')">
                <template slot-scope="scope">
                  <el-button
                    @click="getTeamItemMember(scope.row.team_id)"
                    type="text"
                    size="small"
                    >{{ $t('member') }}</el-button
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
          </el-tab-pane>
        </el-tabs>
      </div>
    </el-dialog>

    <!-- 团队成员详情对话框 -->
    <el-dialog
      v-if="dialogTeamMemberVisible"
      :visible.sync="dialogTeamMemberVisible"
      :close-on-click-modal="false"
      :title="$t('member') + ' - ' + currentTeamName"
      width="700px"
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
        >
          <template slot-scope="scope">
            {{
              memberGroupText(scope.row.member_group_id, scope.row.cat_name)
            }}
          </template>
        </el-table-column>
        <el-table-column
          prop="addtime"
          :label="$t('add_time')"
        ></el-table-column>
      </el-table>
    </el-dialog>

    <!-- 添加成员对话框 -->
    <el-dialog
      :visible.sync="dialogAddMemberVisible"
      :close-on-click-modal="false"
      :title="$t('add_member')"
      width="400px"
    >
      <el-form :model="memberForm">
        <el-form-item :label="$t('member_username') + ':'">
          <el-select
            v-model="memberForm.username"
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
          <el-radio v-model="memberForm.member_group_id" label="1">{{
            $t('edit_member')
          }}</el-radio>
          <el-radio v-model="memberForm.member_group_id" label="0">{{
            $t('readonly_member')
          }}</el-radio>
          <el-radio v-model="memberForm.member_group_id" label="2">{{
            $t('item_admin')
          }}</el-radio>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogAddMemberVisible = false">{{
          $t('cancel')
        }}</el-button>
        <el-button type="primary" @click="saveMember">{{ $t('confirm') }}</el-button>
      </div>
    </el-dialog>

    <!-- 添加团队对话框 -->
    <el-dialog
      :visible.sync="dialogAddTeamVisible"
      :close-on-click-modal="false"
      :title="$t('add_team')"
      width="400px"
    >
      <el-form :model="teamForm">
        <el-form-item :label="$t('team_name')">
          <el-select filterable class v-model="teamForm.team_id">
            <el-option
              v-for="team in teamList"
              :key="team.id"
              :label="team.team_name"
              :value="team.id"
            ></el-option>
          </el-select>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogAddTeamVisible = false">{{
          $t('cancel')
        }}</el-button>
        <el-button type="primary" @click="saveTeam">{{ $t('confirm') }}</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<style scoped>
.mb-3 {
  margin-bottom: 12px;
}
.mb-8 {
  margin-bottom: 20px;
}
</style>

<script>
export default {
  data() {
    return {
      page: 1,
      count: 7,
      activeItemTab: 'normal',
      item_name: '',
      username: '',
      itemList: [],
      total: 0,
      dialogAttornVisible: false,
      attornForm: {
        username: ''
      },
      attorn_item_id: '',
      // 成员管理相关数据
      dialogMemberVisible: false,
      members: [], // 单个成员列表
      teamItems: [], // 绑定的团队列表
      currentItemId: 0, // 当前操作的项目ID
      dialogTeamMemberVisible: false,
      teamItemMembers: [], // 团队成员列表
      activeTab: 'members',
      currentTeamName: '',
      dialogAddMemberVisible: false,
      dialogAddTeamVisible: false,
      memberForm: {
        username: '',
        member_group_id: ''
      },
      memberOptions: [],
      teamForm: {
        team_id: ''
      },
      teamList: [],
      is_del: '0'
    }
  },
  methods: {
    onItemTabChange() {
      this.is_del = this.activeItemTab === 'deleted' ? '1' : '0'
      this.page = 1
      this.getItemList()
    },
    getItemList() {
      this.request('/api/adminItem/getList', {
        item_name: this.item_name,
        username: this.username,
        page: this.page,
        count: this.count,
        is_del: this.is_del
      }).then(data => {
        var json = data.data
        this.itemList = json.items
        this.total = json.total
      })
    },
    recoverItem(row) {
      this.$confirm('确认恢复该项目？', ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/adminItem/recoverItem', {
          item_id: row.item_id
        }).then(() => {
          this.$message.success('恢复成功')
          this.getItemList()
        })
      })
    },
    hardDeleteItem(row) {
      this.$confirm('该操作不可恢复，确定永久删除该项目及其所有数据？', '危险操作', {
        confirmButtonText: '确定',
        cancelButtonText: this.$t('cancel'),
        type: 'error'
      }).then(() => {
        this.request('/api/adminItem/hardDeleteItem', {
          item_id: row.item_id
        }).then(() => {
          this.$message.success('已永久删除')
          this.getItemList()
        })
      })
    },
    formatPrivacy(row, column) {
      if (row) {
        if (row.password.length > 0) {
          return this.$t('private')
        } else {
          return this.$t('public')
        }
      }
    },
    // 跳转到项目
    jumpToItem(row) {
      let url = '#/' + row.item_id
      window.open(url)
    },
    handleCurrentChange(currentPage) {
      this.page = currentPage
      this.getItemList()
    },
    onSubmit() {
      this.page = 1
      this.getItemList()
    },
    deleteItem(row) {
      this.$confirm(this.$t('confirm_delete'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/adminItem/deleteItem', {
          item_id: row.item_id
        }).then(data => {
          this.$message.success('删除成功')
          this.getItemList()
        })
      })
    },
    clickAttornItem(row) {
      this.dialogAttornVisible = true
      this.attorn_item_id = row.item_id
    },
    attorn() {
      this.request('/api/adminItem/attorn', {
        item_id: this.attorn_item_id,
        username: this.attornForm.username
      }).then(() => {
        this.dialogAttornVisible = false
        this.$message.success(this.$t('success'))
        this.getItemList()
      })
    },
    // 成员管理相关方法
    manageMember(row) {
      this.currentItemId = row.item_id
      this.activeTab = 'members'
      this.dialogMemberVisible = true
      
      // 初始化成员表单
      this.memberForm = {
        username: '',
        member_group_id: '1'
      }
      
      // 获取项目成员和团队
      this.getMembers()
      this.getTeamItem()
      
      // 获取用户列表和团队列表
      this.getAllUser()
      this.getTeamList()
    },
    // 获取项目单独成员
    getMembers() {
      this.request('/api/member/getList', {
        item_id: this.currentItemId
      }).then(data => {
        const json = data.data
        this.members = json
      })
    },
    // 获取项目绑定的团队
    getTeamItem() {
      this.request('/api/teamItem/getList', {
        item_id: this.currentItemId
      }).then(data => {
        const json = data.data
        this.teamItems = json
      })
    },
    // 获取团队成员列表
    getTeamItemMember(team_id) {
      this.dialogTeamMemberVisible = true
      
      // 从teamItems中找到当前团队的名称
      const currentTeam = this.teamItems.find(team => team.id == team_id || team.team_id == team_id)
      if (currentTeam) {
        this.currentTeamName = currentTeam.team_name
      } else {
        this.currentTeamName = team_id
      }
      
      this.request('/api/teamItemMember/getList', {
        item_id: this.currentItemId,
        team_id: team_id
      }).then(data => {
        const json = data.data
        this.teamItemMembers = json
      })
    },
    // 获取成员权限名称
    memberGroupText(member_group_id, cat_name) {
      if (member_group_id == '2') {
        return this.$t('item_admin')
      }
      if (member_group_id == '1') {
        return this.$t('edit') + '/' + this.$t('catalog') + '：' + (cat_name || this.$t('all_cat'))
      }
      return this.$t('readonly') + '/' + this.$t('catalog') + '：' + (cat_name || this.$t('all_cat'))
    },
    deleteMember(item_member_id) {
      this.$confirm(this.$t('confirm_delete'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/member/delete', {
          item_id: this.currentItemId,
          item_member_id: item_member_id
        }).then(data => {
          this.$message.success(this.$t('delete_success'))
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
          this.$message.success(this.$t('delete_success'))
          this.getTeamItem()
        })
      })
    },
    saveMember() {
      if (!this.memberForm.username || this.memberForm.username.length <= 0) {
        this.$message.error(this.$t('input_target_member'))
        return
      }
      
      this.request('/api/member/save', {
        item_id: this.currentItemId,
        username: this.memberForm.username,
        member_group_id: this.memberForm.member_group_id || '1'
      }).then(data => {
        this.dialogAddMemberVisible = false
        this.$message.success(this.$t('save_success'))
        this.getMembers()
        // 重置表单
        this.memberForm = {
          username: '',
          member_group_id: '1'
        }
      })
    },
    saveTeam() {
      if (!this.teamForm.team_id) {
        this.$message.error(this.$t('please_choose') + this.$t('team_name'))
        return
      }
      
      this.request('/api/teamItem/save', {
        item_id: this.currentItemId,
        team_id: this.teamForm.team_id
      }).then(data => {
        this.dialogAddTeamVisible = false
        this.$message.success(this.$t('save_success'))
        this.getTeamItem()
        // 重置表单
        this.teamForm = {
          team_id: ''
        }
      })
    },
    // 获取所有用户列表，用于添加成员
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
    },
    // 获取所有团队列表
    getTeamList() {
      this.request('/api/team/getList', {}).then(data => {
        const json = data.data
        this.teamList = json
      })
    },
    openAddMemberDialog() {
      // 刷新用户列表
      this.getAllUser()
      // 重置表单
      this.memberForm = {
        username: '',
        member_group_id: '1'
      }
      this.dialogAddMemberVisible = true
    },
    openAddTeamDialog() {
      // 刷新团队列表
      this.getTeamList()
      // 重置表单
      this.teamForm = {
        team_id: ''
      }
      this.dialogAddTeamVisible = true
    }
  },
  mounted() {
    this.getItemList()
  },
  beforeDestroy() {
    this.$message.closeAll()
  }
}
</script>
