<!-- 更多模板 -->
<template>
  <div class="hello">
    <Header></Header>
    <link href="static/diff/diffview.css" rel="stylesheet" />
    <el-container class="container-narrow">
      <div class="textInput">
        <textarea
          id="baseText"
          v-html="content"
          style="display:none;"
        ></textarea>
      </div>
      <div class="textInput spacer">
        <textarea
          id="newText"
          v-html="historyContent"
          style="display:none;"
        ></textarea>
      </div>

      <div id="diffoutput"></div>
    </el-container>
    <Footer></Footer>
    <div class></div>
  </div>
</template>

<style scoped>
.top {
  text-align: center;
}
.textInput {
  display: block;
  width: 49%;
  float: left;
  display: none;
}
textarea {
  width: 100%;
  height: 300px;
}
label:hover {
  text-decoration: underline;
  cursor: pointer;
}
.spacer {
  margin-left: 10px;
}
.viewType {
  font-size: 16px;
  clear: both;
  text-align: center;
  padding: 1em;
}
#diffoutput {
  margin: 0 auto;
}
</style>

<script>
if (typeof window !== 'undefined') {
  var $s = require('scriptjs')
}
export default {
  props: {
    callback: ''
  },
  data() {
    return {
      content: '',
      historyContent: ''
    }
  },
  components: {},
  methods: {
    getContent() {
      this.request('/api/page/diff', {
        page_id: this.$route.params.page_id,
        page_history_id: this.$route.params.page_history_id
      }).then(data => {
        var json = data.data
        this.content = json.page.page_content
        this.historyContent = json.history_page.page_content
        this.$nextTick(() => {
          this.diffUsingJS(0)
        })
      })
    },
    diffUsingJS(viewType) {
      'use strict'
      var that = this
      // eslint-disable-next-line one-var
      var byId = function(id) {
          return document.getElementById(id)
        },
        base = difflib.stringAsLines(byId('baseText').value),
        newtxt = difflib.stringAsLines(byId('newText').value),
        sm = new difflib.SequenceMatcher(base, newtxt),
        opcodes = sm.get_opcodes(),
        diffoutputdiv = byId('diffoutput')

      diffoutputdiv.innerHTML = ''

      diffoutputdiv.appendChild(
        diffview.buildView({
          baseTextLines: base,
          newTextLines: newtxt,
          opcodes: opcodes,
          baseTextName: that.$t('cur_page_content'),
          newTextName: that.$t('history_version'),
          viewType: viewType
        })
      )
    }
  },
  mounted() {
    $s([`static/diff/difflib.js`, `static/diff/diffview.js`], () => {
      this.getContent()
    })
  },
  beforeDestroy() {}
}
</script>
