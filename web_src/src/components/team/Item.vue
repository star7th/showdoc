<template>
  <div class="hello">
    <Header></Header>
    <SDialog
      :title="$t('item_manage')"
      :btn1Text="team_manage ? $t('binding_item') : ''"
      btn1Icon="el-icon-plus"
      :btn1Medthod="addTeamItem"
      :onCancel="callback"
      :showCancel="false"
      :onOK="callback"
      width="40%"
    >
      <div>
        <el-table
          align="left"
          :data="list"
          :empty-text="$t('empty_team_item_tips')"
        >
          <el-table-column
            prop="item_name"
            :label="$t('item_name')"
          ></el-table-column>
          <el-table-column
            prop="addtime"
            :label="$t('Join_time')"
          ></el-table-column>

          <el-table-column prop width="210" :label="$t('operation')">
            <template slot-scope="scope">
              <router-link :to="'/' + scope.row.item_id" target="_blank">{{
                $t('check_item')
              }}</router-link>

              <el-button
                v-if="team_manage"
                @click="getTeamItemMember(scope.row.item_id)"
                type="text"
                size="small"
                >{{ $t('member_authority') }}</el-button
              >

              <el-button
                v-if="team_manage"
                @click="deleteTeamItem(scope.row.id)"
                type="text"
                size="small"
                >{{ $t('unassign') }}</el-button
              >
            </template>
          </el-table-column>
        </el-table>
      </div>
    </SDialog>

    <!-- 绑定项目弹窗 -->
    <SDialog
      v-if="dialogFormVisible"
      :title="$t('item')"
      :onCancel="
        () => {
          dialogFormVisible = false
        }
      "
      :showCancel="false"
      :onOK="myFormSubmit"
      width="300px"
    >
      <el-form>
        <el-select
          multiple
          v-model="MyForm.item_id"
          :placeholder="$t('please_choose')"
        >
          <el-option
            v-for="item in itemList"
            :key="item.item_id"
            :label="item.item_name"
            :value="item.item_id"
          ></el-option>
        </el-select>
      </el-form>
      <br />
      <router-link to="/item/index" target="_blank">{{
        $t('go_to_new_an_item')
      }}</router-link>
    </SDialog>

    <!-- 成员权限弹窗 -->
    <SDialog
      v-if="dialogFormTeamMemberVisible"
      :onCancel="
        () => {
          dialogFormTeamMemberVisible = false
        }
      "
      :showCancel="false"
      :onOK="
        () => {
          dialogFormTeamMemberVisible = false
        }
      "
      top="10vh"
      :title="$t('adjust_member_authority')"
      width="600px"
    >
      <p>
        <el-button type="text" @click="setAllMemberRead"
          >&nbsp;{{ $t('all_member_read') }}</el-button
        >
      </p>
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
          width="120"
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
              size="mini"
              v-if="scope.row.member_group_id <= 1"
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
      <p class="tips-text">{{ $t('team_member_authority_tips') }}</p>
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
      MyForm: {
        item_id: ''
      },
      list: [],
      dialogFormVisible: false,
      itemList: [],
      teamItemMembers: [],
      dialogFormTeamMemberVisible: false,
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
      catalogs: []
    }
  },
  methods: {
    geList() {
      this.request('/api/teamItem/getListByTeam', {
        team_id: this.team_id
      }).then(data => {
        this.list = data.data
      })
    },
    getItemList() {
      this.request('/api/item/myList', { original: 1 }).then(data => {
        this.itemList = data.data
      })
    },
    myFormSubmit() {
      this.request('/api/teamItem/save', {
        team_id: this.team_id,
        item_id: this.MyForm.item_id
      }).then(data => {
        this.dialogFormVisible = false
        this.geList()
        this.MyForm = {}
      })
    },

    deleteTeamItem(id) {
      this.$confirm(this.$t('confirm_unassign'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/teamItem/delete', {
          id: id
        }).then(data => {
          this.geList()
        })
      })
    },
    addTeamItem() {
      this.MyForm = []
      this.dialogFormVisible = true
    },
    goback() {
      this.$router.push({ path: '/team/index' })
    },
    getTeamItemMember(item_id) {
      this.dialogFormTeamMemberVisible = true
      this.getCatalog(item_id)
      this.request('/api/teamItemMember/getList', {
        item_id: item_id,
        team_id: this.team_id
      }).then(data => {
        this.teamItemMembers = data.data
      })
    },
    changeTeamItemMemberGroup(member_group_id, id, showMsg = true) {
      this.request('/api/teamItemMember/save', {
        member_group_id: member_group_id,
        id: id
      }).then(data => {
        if (showMsg) this.$message(this.$t('auth_success'))
      })
    },
    changeTeamItemMemberCat(cat_id, id) {
      this.request('/api/teamItemMember/save', {
        id: id,
        cat_id: cat_id
      }).then(data => {
        this.$message(this.$t('cat_success'))
      })
    },
    getCatalog(item_id) {
      this.request('/api/catalog/catListGroup', {
        item_id: item_id
      }).then(data => {
        var Info = data.data
        Info.unshift({
          cat_id: '0',
          cat_name: this.$t('all_cat')
        })
        this.catalogs = Info
      })
    },
    // 一键全部设置为只读
    setAllMemberRead() {
      this.teamItemMembers.forEach(element => {
        this.changeTeamItemMemberGroup(0, element.id, false)
        setTimeout(() => {
          this.getTeamItemMember(element.item_id)
        }, 500)
      })
    }
  },

  mounted() {
    this.geList()
    this.getItemList()
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
  width: 800px;
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
