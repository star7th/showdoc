<template>
  <div class="hello">
    <p class="tips">{{ $t('recycle_tips') }}</p>
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
        item_id: this.$route.params.item_id
      }).then(data => {
        this.lists = data.data
      })
    },
    recover(page_id) {
      this.$confirm(this.$t('recover_tips'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/recycle/recover', {
          page_id: page_id,
          item_id: this.$route.params.item_id
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
<style scoped>
.hello {
  text-align: left;
}
.tips {
  margin-left: 10px;
  color: #9ea1a6;
}
.recycle-table {
  max-height: 400px;
  overflow: auto;
}
</style>
