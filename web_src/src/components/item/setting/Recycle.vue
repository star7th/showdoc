<template>
  <div class="hello">
    <SDialog
      :onCancel="callback"
      :title="$t('recycle')"
      width="550px"
      :onOK="callback"
      :showCancel="false"
    >
      <p class="v3-font-size-sm v3-color-aux">{{ $t('recycle_tips') }}</p>
      <!-- 页面列表 -->
      <el-table
        align="left"
        class="recycle-table"
        v-if="lists.length > 0"
        :data="lists"
        style="width: 100%"
      >
        <el-table-column
          prop="page_title"
          :label="$t('page_title')"
        ></el-table-column>
        <el-table-column
          prop="del_by_username"
          :label="$t('deleter')"
        ></el-table-column>
        <el-table-column
          prop="del_time"
          :label="$t('del_time')"
        ></el-table-column>
        <el-table-column prop :label="$t('operation')">
          <template slot-scope="scope">
            <el-button
              @click="recover(scope.row.page_id)"
              type="text"
              size="small"
              >{{ $t('recover') }}</el-button
            >
          </template>
        </el-table-column>
      </el-table>
    </SDialog>
  </div>
</template>

<script>
export default {
  name: '',
  components: {},
  props: {
    callback: () => {},
    item_id: 0
  },
  data() {
    return {
      MyForm: {
        username: '',
        is_readonly: false
      },
      MyForm2: {
        team_id: ''
      },
      lists: []
    }
  },
  methods: {
    getList() {
      this.request('/api/recycle/getList', {
        item_id: this.item_id
      }).then(data => {
        const json = data.data
        this.lists = json
      })
    },
    recover(page_id) {
      this.$confirm(this.$t('recover_tips'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/recycle/recover', {
          item_id: this.item_id,
          page_id: page_id
        }).then(data => {
          this.getList()
        })
      })
    }
  },

  mounted() {
    this.getList()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped></style>
