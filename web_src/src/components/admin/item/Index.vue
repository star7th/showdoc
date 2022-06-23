<template>
  <div class="hello">
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
          <el-button @click="jumpToItem(scope.row)" type="text" size="small">{{
            $t('link')
          }}</el-button>
        </template>
      </el-table-column>
      <el-table-column
        prop="username"
        :label="$t('owner')"
        width="160"
      ></el-table-column>
      <el-table-column :label="$t('memberCount')" width="80"></el-table-column>
      <el-table-column
        prop="addtime"
        :label="$t('add_time')"
        width="160"
      ></el-table-column>
      <el-table-column prop="item_domain" :label="$t('operation')">
        <template slot-scope="scope">
          <el-button
            @click="clickAttornItem(scope.row)"
            type="text"
            size="small"
            >{{ $t('attorn') }}</el-button
          >
          <el-button @click="deleteItem(scope.row)" type="text" size="small">{{
            $t('delete')
          }}</el-button>
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
  </div>
</template>

<style scoped></style>

<script>
export default {
  data() {
    return {
      page: 1,
      count: 7,
      item_name: '',
      username: '',
      itemList: [],
      total: 0,
      dialogAttornVisible: false,
      attornForm: {
        username: ''
      },
      attorn_item_id: ''
    }
  },
  methods: {
    getItemList() {
      this.request('/api/adminItem/getList', {
        item_name: this.item_name,
        username: this.username,
        page: this.page,
        count: this.count
      }).then(data => {
        var json = data.data
        this.itemList = json.items
        this.total = json.total
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
