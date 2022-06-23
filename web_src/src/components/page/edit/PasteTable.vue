<!-- 更多模板 -->
<template>
  <div class="hello">
    <Header></Header>

    <el-container class="container-narrow">
      <el-dialog
        :title="$t('paste_insert_table')"
        :modal="is_modal"
        :visible.sync="dialogFormVisible"
        :close-on-click-modal="false"
        @close="callback()"
      >
        <el-form>
          <el-input
            type="textarea"
            class="dialoContent"
            :placeholder="$t('paste_insert_table_tips')"
            :rows="10"
            v-model="content"
          ></el-input>
        </el-form>
        <div slot="footer" class="dialog-footer">
          <el-button @click="callback()">{{ $t('cancel') }}</el-button>
          <el-button type="primary" @click="transform">{{
            $t('confirm')
          }}</el-button>
        </div>
      </el-dialog>
    </el-container>
    <Footer></Footer>
    <div class></div>
  </div>
</template>

<style></style>

<script>
export default {
  props: {
    callback: '',
    is_modal: true
  },
  data() {
    return {
      content: '',
      dialogFormVisible: true
    }
  },
  components: {},
  methods: {
    transform: function() {
      var md = this.content
      var sheet_str = '\n\n'
      for (const [index, row] of md.split('\n').entries()) {
        var cols = row.split('\t')
        sheet_str += '| ' + cols.join(' | ') + ' |\n'
        if (index == 0) {
          for (var i = 0; i < cols.length; i++) {
            sheet_str += '|:--- '
          }
          sheet_str += ' |\n'
        }
      }
      this.callback(sheet_str + '\n\n')
    }
  },
  mounted() {}
}
</script>
