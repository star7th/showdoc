<template>
  <div class="hello">
    <Header></Header>

    <el-container>
      <el-card class="center-card">
        <el-button type="text" class="goback-btn" @click="goback"
          ><i class="el-icon-back"></i>&nbsp;{{ $t('goback') }}</el-button
        >
        <el-button type="text" class="add-cat" @click="addTeam"
          ><i class="el-icon-plus"></i>&nbsp;{{ $t('add_team') }}</el-button
        >
        <el-table
          align="left"
          :empty-text="$t('empty_team_tips')"
          :data="list"
          height="400"
          style="width: 100%"
        >
          <el-table-column
            prop="team_name"
            :label="$t('team_name')"
          ></el-table-column>
          <el-table-column
            prop="memberCount"
            width="100"
            :label="$t('memberCount')"
          >
            <template slot-scope="scope">
              <router-link :to="'/team/member/' + scope.row.id">{{
                scope.row.memberCount
              }}</router-link>
            </template>
          </el-table-column>
          <el-table-column
            prop="itemCount"
            width="100"
            :label="$t('itemCount')"
          >
            <template slot-scope="scope">
              <router-link :to="'/team/item/' + scope.row.id">{{
                scope.row.itemCount
              }}</router-link>
            </template>
          </el-table-column>
          <el-table-column prop :label="$t('operation')">
            <template slot-scope="scope">
              <el-button
                @click="$router.push({ path: '/team/member/' + scope.row.id })"
                type="text"
                size="small"
                >{{ $t('member') }}</el-button
              >
              <el-button
                @click="$router.push({ path: '/team/item/' + scope.row.id })"
                type="text"
                size="small"
                >{{ $t('team_item') }}</el-button
              >
              <el-button @click="edit(scope.row)" type="text" size="small">{{
                $t('edit')
              }}</el-button>
              <el-button
                @click="attornDialog(scope.row)"
                type="text"
                size="small"
                >{{ $t('attorn') }}</el-button
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
      </el-card>
      <el-dialog
        :visible.sync="dialogFormVisible"
        width="300px"
        :close-on-click-modal="false"
      >
        <el-form>
          <el-form-item :label="$t('team_name') + ':'">
            <el-input v-model="MyForm.team_name"></el-input>
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

      <el-dialog
        :visible.sync="dialogAttornVisible"
        :modal="false"
        width="300px"
        :close-on-click-modal="false"
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
        <p class="tips">
          <small>{{ $t('attornTeamTips') }}</small>
        </p>
        <div slot="footer" class="dialog-footer">
          <el-button @click="dialogAttornVisible = false">{{
            $t('cancel')
          }}</el-button>
          <el-button type="primary" @click="attorn">{{
            $t('attorn')
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
      MyForm: {
        id: '',
        team_name: ''
      },
      list: [],
      dialogFormVisible: false,
      dialogMemberVisible: false,
      dialogAttornVisible: false,
      attornForm: {
        team_id: '',
        username: '',
        password: ''
      }
    }
  },
  methods: {
    geList() {
      var that = this
      var url = DocConfig.server + '/api/team/getList'
      var params = new URLSearchParams()
      that.axios.post(url, params).then(function(response) {
        if (response.data.error_code === 0) {
          var Info = response.data.data

          that.list = Info
        } else {
          that.$alert(response.data.error_message)
        }
      })
    },
    MyFormSubmit() {
      var that = this
      var url = DocConfig.server + '/api/team/save'

      var params = new URLSearchParams()
      params.append('id', this.MyForm.id)
      params.append('team_name', this.MyForm.team_name)
      that.axios.post(url, params).then(function(response) {
        if (response.data.error_code === 0) {
          that.dialogFormVisible = false
          that.geList()
          that.MyForm = {
            id: '',
            team_name: ''
          }
        } else {
          that.$alert(response.data.error_message)
        }
      })
    },
    edit(row) {
      this.MyForm.id = row.id
      this.MyForm.team_name = row.team_name
      this.dialogFormVisible = true
    },

    deleteTeam(id) {
      var that = this
      var url = DocConfig.server + '/api/team/delete'

      this.$confirm(that.$t('confirm_delete'), ' ', {
        confirmButtonText: that.$t('confirm'),
        cancelButtonText: that.$t('cancel'),
        type: 'warning'
      }).then(() => {
        var params = new URLSearchParams()
        params.append('id', id)

        that.axios.post(url, params).then(function(response) {
          if (response.data.error_code === 0) {
            that.geList()
          } else {
            that.$alert(response.data.error_message)
          }
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
      var that = this
      var url = DocConfig.server + '/api/team/attorn'

      var params = new URLSearchParams()
      params.append('team_id', this.attornForm.team_id)
      params.append('username', this.attornForm.username)
      params.append('password', this.attornForm.password)

      that.axios.post(url, params).then(function(response) {
        if (response.data.error_code === 0) {
          that.dialogAttornVisible = false
          that.geList()
        } else {
          that.$alert(response.data.error_message)
        }
      })
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
