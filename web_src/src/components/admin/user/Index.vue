<template>
  <div class="hello">
    <el-form :inline="true" class="demo-form-inline">
      <el-form-item label>
        <el-input v-model="username" :placeholder="$t('username')"></el-input>
      </el-form-item>
      <el-form-item>
        <el-button @click="onSubmit">{{ $t('search') }}</el-button>
      </el-form-item>
    </el-form>
    <el-button type="primary" @click="dialogAddVisible = true">{{
      $t('add_user')
    }}</el-button>
    <el-table :data="itemList" style="width: 100%">
      <el-table-column
        prop="username"
        :label="$t('username')"
        width="200"
      ></el-table-column>
      <el-table-column prop="name" :label="$t('name')"></el-table-column>
      <el-table-column
        prop="groupid"
        :label="$t('userrole')"
        :formatter="formatGroup"
        width="150"
      ></el-table-column>
      <el-table-column
        prop="reg_time"
        :label="$t('reg_time')"
        width="160"
      ></el-table-column>
      <el-table-column
        prop="last_login_time"
        :label="$t('last_login_time')"
        width="160"
      ></el-table-column>
      <el-table-column prop="item_domain" :label="$t('operation')">
        <template slot-scope="scope">
          <el-button @click="clickEdit(scope.row)" type="text" size="small">{{
            $t('edit')
          }}</el-button>
          <el-button
            @click="deleteUser(scope.row)"
            v-if="scope.row.groupid != 1"
            type="text"
            size="small"
            >{{ $t('delete') }}</el-button
          >
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
      :visible.sync="dialogAddVisible"
      :before-close="resetForm"
      :close-on-click-modal="false"
      width="300px"
    >
      <el-form>
        <el-form-item label>
          <el-input
            type="text"
            :placeholder="$t('username')"
            :readonly="addForm.uid > 0"
            v-model="addForm.username"
          ></el-input>
        </el-form-item>
        <el-form-item label>
          <el-input
            type="text"
            :placeholder="$t('name')"
            v-model="addForm.name"
          ></el-input>
        </el-form-item>

        <el-form-item label v-if="addForm.uid <= 0">
          <el-input
            type="password"
            :placeholder="$t('password')"
            v-model="addForm.password"
          ></el-input>
        </el-form-item>

        <el-form-item label v-if="addForm.uid > 0">
          <el-input
            type="password"
            :placeholder="$t('update_pwd_tips')"
            v-model="addForm.password"
          ></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="resetForm">{{ $t('cancel') }}</el-button>
        <el-button type="primary" @click="addUser">{{
          $t('confirm')
        }}</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<style scoped></style>

<script>
export default {
  data() {
    return {
      itemList: [],
      username: '',
      page: 1,
      count: 7,
      total: 0,
      addForm: {
        username: '',
        password: '',
        uid: 0,
        name: ''
      },
      dialogAddVisible: false
    }
  },
  methods: {
    getUserList() {
      this.request('/api/adminUser/getList', {
        username: this.username,
        page: this.page,
        count: this.count
      }).then(data => {
        this.itemList = data.data.users
        this.total = data.data.total
      })
    },
    formatGroup(row, column) {
      if (row) {
        if (row.groupid == 1) {
          return this.$t('administrator')
        } else if (row.groupid == 2) {
          return this.$t('ordinary_users')
        } else {
          return ''
        }
      }
    },
    handleCurrentChange(currentPage) {
      this.page = currentPage
      this.getUserList()
    },
    onSubmit() {
      this.page = 1
      this.getUserList()
    },
    deleteUser(row) {
      this.$confirm(this.$t('confirm_delete'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/adminUser/deleteUser', {
          uid: row.uid
        }).then(data => {
          this.$message.success('success')
          this.getUserList()
          this.username = ''
        })
      })
    },
    clickEdit(row) {
      this.dialogAddVisible = true
      this.addForm = {
        uid: row.uid,
        name: row.name,
        username: row.username,
        password: ''
      }
    },
    addUser() {
      this.request('/api/adminUser/addUser', {
        username: this.addForm.username,
        password: this.addForm.password,
        uid: this.addForm.uid,
        name: this.addForm.name
      }).then(data => {
        this.dialogAddVisible = false
        this.addForm.password = ''
        this.addForm.username = ''
        this.addForm.uid = 0
        this.addForm.name = ''
        this.$message.success(this.$t('success'))
        this.getUserList()
      })
    },
    resetForm() {
      this.addForm = {
        uid: 0,
        name: '',
        username: '',
        password: ''
      }
      this.dialogAddVisible = false
    }
  },
  mounted() {
    this.getUserList()
  },
  beforeDestroy() {
    this.$message.closeAll()
  }
}
</script>
