<template>
  <div>
    <SDialog
      v-if="dialogFormVisible"
      :title="$t('beautify_json')"
      :onCancel="callback"
      :onOK="transform"
    >
      <el-form>
        <el-input
          type="textarea"
          class="dialoContent"
          :placeholder="$t('beautify_json_description')"
          :rows="10"
          v-model="content"
        ></el-input>
      </el-form>
    </SDialog>
  </div>
</template>

<script>
export default {
  name: 'JsonBeautify',
  props: {
    formLabelWidth: '120px',
    callback: ''
  },
  data() {
    return {
      content: '',
      json_table_data: '',
      dialogFormVisible: true
    }
  },
  methods: {
    transform() {
      var data = this.content
      try {
        var formattedStr = JSON.stringify(JSON.parse(data), null, 2)
        var text = '\n ``` \n ' + formattedStr + ' \n\n ```\n\n' //
        this.callback(text)
      } catch (e) {
        // 非json数据直接显示
        this.callback(data)
      }
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped></style>
