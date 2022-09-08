<template>
  <div class="hello">
    <Header></Header>

    <el-container>
      <el-card class="center-card">
        <el-button type="text" class="goback-btn" @click="goback"
          ><i class="el-icon-back"></i>&nbsp;{{ $t('goback') }}</el-button
        >
        <el-button
          v-if="team_manage"
          type="text"
          class="add-cat"
          @click="addTeamMember"
          ><i class="el-icon-plus"></i>&nbsp;{{ $t('add_member') }}</el-button
        >
        <el-table align="left" :data="list" height="400" style="width: 100%">
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
            width="160"
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
      </el-card>

      <el-dialog
        :visible.sync="dialogFormVisible"
        width="300px"
        :close-on-click-modal="false"
      >
        <el-form>
          <el-form-item :label="$t('member_username') + ':'">
            <el-select
              v-model="MyForm.member_username"
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
            <el-radio v-model="MyForm.team_member_group_id" label="1">{{
              $t('ordinary_member')
            }}</el-radio>
            <el-radio v-model="MyForm.team_member_group_id" label="2">{{
              $t('team_admin')
            }}</el-radio>
          </el-form-item>
        </el-form>

        <div slot="footer" class="dialog-footer">
          <el-button @click="dialogFormVisible = false">{{
            $t('cancel')
          }}</el-button>
          <el-button type="primary" @click="MyFormSubmit">{{
            $t('confirm')
          }}</el-button>
        </div>
      </el-dialog>
    </el-container>

    <Footer></Footer>
  </div>
</template>

<script>
export default {
  components: {},
  data() {
    return {
      MyForm: {},
      list: [],
      dialogFormVisible: false,
      team_id: '',
      memberOptions: [],
      team_manage: 1
    }
  },
  methods: {
    geList() {
      this.request('/api/teamMember/getList', {
        team_id: this.team_id
      }).then(data => {
        this.list = data.data
        list.getAllUser()
      })
    },
    reSetMyForm() {
      this.MyForm = {
        id: '',
        member_username: '',
        team_member_group_id: '1'
      }
    },
    MyFormSubmit() {
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
    this.team_id = this.$route.params.team_id
    this.team_manage = this.$route.query.team_manage > 0 ? 1 : 0
    this.geList()
    this.getAllUser()
    this.reSetMyForm()
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
