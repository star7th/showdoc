<!-- 更多模板 -->
<template>
  <div class="">
    <SDialog
      v-if="dialogFormVisible"
      :title="$t('paste_insert_table')"
      :onCancel="callback"
      :onOK="transform"
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
    </SDialog>
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
