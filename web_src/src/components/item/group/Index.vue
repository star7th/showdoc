<template>
  <div class="hello">
    <Header></Header>

    <el-container>
      <el-card class="center-card">
        <el-button type="text" class="goback-btn" @click="goback"
          ><i class="el-icon-back"></i>&nbsp;{{ $t('goback') }}</el-button
        >
        <el-button type="text" class="add-cat" @click="addDialog"
          ><i class="el-icon-plus"></i>&nbsp;{{ $t('add_group') }}</el-button
        >
        <p class="tips" v-show="list && list.length > 1">
          {{ $t('draggable_tips') }}
        </p>
        <el-table-draggable animate="200" @drop="onDropEnd">
          <el-table
            :show-header="false"
            align="left"
            :empty-text="$t('item_group_empty_tips')"
            :data="list"
            height="400"
            style="width: 100%"
          >
            <el-table-column
              prop="group_name"
              :label="$t('group_name')"
            ></el-table-column>

            <el-table-column width="120" prop :label="$t('operation')">
              <template slot-scope="scope">
                <el-button @click="edit(scope.row)" type="text" size="small">{{
                  $t('edit')
                }}</el-button>

                <el-button
                  @click="del(scope.row.id)"
                  type="text"
                  size="small"
                  >{{ $t('delete') }}</el-button
                >
              </template>
            </el-table-column>
          </el-table>
        </el-table-draggable>
      </el-card>
      <el-dialog
        :visible.sync="dialogFormVisible"
        v-if="dialogFormVisible"
        width="500px"
        :close-on-click-modal="false"
      >
        <el-form>
          <el-form-item>
            <el-input v-model="MyForm.group_name" placeholder="组名"></el-input>
          </el-form-item>
        </el-form>
        <div>
          <p>{{ $t('select_item') }}</p>
          <el-table
            :show-header="false"
            ref="multipleTable"
            :data="itemList"
            tooltip-effect="dark"
            style="width: 100%"
            @selection-change="handleSelectionChange"
          >
            <el-table-column type="selection" width="55"> </el-table-column>
            <el-table-column prop="item_name" :label="$t('item_name')">
            </el-table-column>
          </el-table>
        </div>

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
import ElTableDraggable from '@/components/common/ElTableDraggable'
export default {
  components: { ElTableDraggable },
  data() {
    return {
      MyForm: {
        id: '',
        group_name: ''
      },
      list: [],
      dialogFormVisible: false,
      itemList: [],
      multipleSelection: []
    }
  },
  methods: {
    geList() {
      this.request('/api/itemGroup/getList', {}).then(data => {
        this.list = data.data
      })
    },
    MyFormSubmit() {
      const group_name = this.MyForm.group_name
      const id = this.MyForm.id
      const item_ids_array = []
      this.multipleSelection.map(element => {
        item_ids_array.push(element.item_id)
      })
      const item_ids = item_ids_array.join(',')
      this.request('/api/itemGroup/save', { group_name, id, item_ids }).then(
        data => {
          this.geList()
          this.dialogFormVisible = false
          this.multipleSelection = []
          this.$nextTick(() => {
            this.toggleSelection(this.multipleSelection)
          })
          this.MyForm.id = ''
          this.MyForm.group_name = ''
        }
      )
    },
    edit(row) {
      this.MyForm.id = row.id
      this.MyForm.group_name = row.group_name
      this.multipleSelection = []
      const item_ids_array = row.item_ids.split(',')
      item_ids_array.map(item_id => {
        this.itemList.map(element2 => {
          if (item_id == element2.item_id) {
            this.multipleSelection.push(element2)
          }
        })
      })
      this.dialogFormVisible = true
      this.$nextTick(() => {
        this.toggleSelection(this.multipleSelection)
      })
    },

    del(id) {
      var that = this

      this.$confirm(that.$t('confirm_delete'), ' ', {
        confirmButtonText: that.$t('confirm'),
        cancelButtonText: that.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/itemGroup/delete', { id }).then(data => {
          this.geList()
        })
      })
    },
    addDialog() {
      this.MyForm = {
        id: '',
        team_name: ''
      }
      this.dialogFormVisible = true
    },
    goback() {
      this.$router.push({ path: '/item/index' })
    },
    get_item_list() {
      this.request('/api/item/myList', {}).then(data => {
        this.itemList = data.data
      })
    },
    handleSelectionChange(val) {
      this.multipleSelection = val
    },
    toggleSelection(rows) {
      if (rows) {
        rows.forEach(row => {
          this.$refs.multipleTable.toggleRowSelection(row)
        })
      } else {
        this.$refs.multipleTable.clearSelection()
      }
    },
    onDropEnd() {
      const groups_array = []
      this.list.map((element, index) => {
        groups_array.push({
          id: element.id,
          s_number: index + 1
        })
      })
      this.request('/api/itemGroup/saveSort', {
        groups: JSON.stringify(groups_array)
      }).then(data => {
        this.itemList = data.data
      })
    }
  },

  mounted() {
    this.geList()
    this.get_item_list()
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
.tips {
  color: #777;
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
