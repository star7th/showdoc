<template>
  <div class="hello">
    <SDialog
      :title="$t('team_mamage')"
      :btn1Text="$t('add_team')"
      btn1Icon="el-icon-plus"
      :btn1Medthod="addTeam"
      :onCancel="callback"
      :onOK="callback"
    >
      <div class="">
        <el-table align="left" :empty-text="$t('empty_team_tips')" :data="list">
          <el-table-column
            prop="team_name"
            :label="$t('team_name')"
          ></el-table-column>
          <el-table-column prop="memberCount" :label="$t('memberCount')">
            <template slot-scope="scope">
              <el-button
                @click="clickToMember(scope.row)"
                type="text"
                size="small"
                >{{ scope.row.memberCount }}</el-button
              >
            </template>
          </el-table-column>
          <el-table-column prop="itemCount" :label="$t('itemCount')">
            <template slot-scope="scope">
              <el-button
                @click="clickToItem(scope.row)"
                type="text"
                size="small"
                >{{ scope.row.itemCount }}</el-button
              >
            </template>
          </el-table-column>
          <el-table-column prop :label="$t('operation')">
            <template slot-scope="scope">
              <el-button
                @click="clickToMember(scope.row)"
                type="text"
                size="small"
                >{{ $t('member') }}</el-button
              >
              <el-button
                @click="clickToItem(scope.row)"
                type="text"
                size="small"
                >{{ $t('team_item') }}</el-button
              >
              <el-button
                v-if="scope.row.team_manage > 0"
                @click="edit(scope.row)"
                type="text"
                size="small"
                >{{ $t('edit') }}</el-button
              >
              <el-button
                v-if="scope.row.team_manage > 0"
                @click="attornDialog(scope.row)"
                type="text"
                size="small"
                >{{ $t('attorn') }}</el-button
              >
              <el-button
                v-if="scope.row.team_manage > 0"
                @click="deleteTeam(scope.row.id)"
                type="text"
                size="small"
                >{{ $t('delete') }}</el-button
              >
              <el-button
                v-if="scope.row.team_manage <= 0"
                @click="exitTeam(scope.row.id)"
                type="text"
                size="small"
                >{{ $t('team_exit') }}</el-button
              >
            </template>
          </el-table-column>
        </el-table>
      </div>
    </SDialog>

    <!-- 添加/编辑团队 -->
    <SDialog
      v-if="dialogFormVisible"
      :onCancel="
        () => {
          dialogFormVisible = false
        }
      "
      :onOK="myFormSubmit"
      width="400px"
    >
      <el-form>
        <el-form-item :label="$t('team_name') + ':'">
          <el-input v-model="MyForm.team_name"></el-input>
        </el-form-item>
      </el-form>
    </SDialog>

    <SDialog
      v-if="dialogAttornVisible"
      :title="$t('attorn')"
      :onCancel="
        () => {
          dialogAttornVisible = false
        }
      "
      :onOK="attorn"
      width="400px"
    >
      <el-form>
        <el-form-item label>
          <el-input
            :placeholder="$t('attorn_username')"
            auto-complete="new-password"
            v-model="attornForm.username"
          ></el-input>
        </el-form-item>
        <el-form-item label>
          <el-input
            type="password"
            auto-complete="new-password"
            :placeholder="$t('input_login_password')"
            v-model="attornForm.password"
          ></el-input>
        </el-form-item>
      </el-form>
      <p class="tips-text">
        {{ $t('attornTeamTips') }}
      </p>
    </SDialog>

    <ItemCom
      v-if="dialogItemVisible"
      :team_id="currentOperationRow.id"
      :team_manage="currentOperationRow.team_manage"
      :callback="
        () => {
          dialogItemVisible = false
          geList()
        }
      "
    ></ItemCom>

    <MemberCom
      v-if="dialogMemberVisible"
      :team_id="currentOperationRow.id"
      :team_manage="currentOperationRow.team_manage"
      :callback="
        () => {
          dialogMemberVisible = false
          geList()
        }
      "
    ></MemberCom>

    <Footer></Footer>
  </div>
</template>

<script>
import ItemCom from './Item.vue'
import MemberCom from './Member.vue'
export default {
  components: { ItemCom, MemberCom },
  props: {
    callback: {
      type: Function,
      required: false,
      default: () => {}
    }
  },
  data() {
    return {
      MyForm: {
        id: '',
        team_name: ''
      },
      list: [],
      dialogFormVisible: false,
      dialogAttornVisible: false,
      attornForm: {
        team_id: '',
        username: '',
        password: ''
      },
      dialogMemberVisible: false,
      dialogItemVisible: false,
      currentOperationRow: {
        id: 0,
        team_manage: 0,
        team_name: ''
      }
    }
  },
  methods: {
    geList() {
      this.request('/api/team/getList', {}).then(data => {
        this.list = data.data
      })
    },
    myFormSubmit() {
      this.request('/api/team/save', {
        id: this.MyForm.id,
        team_name: this.MyForm.team_name
      }).then(data => {
        this.dialogFormVisible = false
        this.geList()
        this.MyForm = {
          id: '',
          team_name: ''
        }
      })
    },
    edit(row) {
      this.MyForm.id = row.id
      this.MyForm.team_name = row.team_name
      this.dialogFormVisible = true
    },

    deleteTeam(id) {
      this.$confirm(this.$t('confirm_delete'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/team/delete', {
          id: id
        }).then(data => {
          this.geList()
        })
      })
    },
    addTeam() {
      this.MyForm = {
        id: '',
        team_name: ''
      }
      this.dialogFormVisible = true
    },
    goback() {
      this.$router.push({ path: '/item/index' })
    },
    attornDialog(row) {
      this.attornForm.team_id = row.id
      this.dialogAttornVisible = true
    },
    attorn() {
      this.request('/api/team/attorn', {
        team_id: this.attornForm.team_id,
        username: this.attornForm.username,
        password: this.attornForm.password
      }).then(data => {
        this.dialogAttornVisible = false
        this.geList()
      })
    },
    exitTeam(id) {
      this.$confirm(this.$t('team_exit_confirm'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/team/exitTeam', { id }).then(data => {
          this.geList()
        })
      })
    },
    clickToMember(row) {
      this.currentOperationRow = row
      this.dialogMemberVisible = true
    },
    clickToItem(row) {
      this.currentOperationRow = row
      this.dialogItemVisible = true
    }
  },

  mounted() {
    this.geList()
  },
  beforeDestroy() {}
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
  width: 750px;
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
.el-table__empty-text {
  text-align: left;
  line-height: 30px !important;
  margin-top: 20px;
}
</style>
