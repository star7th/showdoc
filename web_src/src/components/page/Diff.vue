<!-- 更多模板 -->
<template>
  <div class="hello">
    <Header> </Header>
    <link href="static/diff/diffview.css" rel="stylesheet">
    <el-container class="container-narrow">
        <div class="textInput">
          <textarea id="baseText" v-html="content" style="display:none;">    </textarea>
        </div>
        <div class="textInput spacer">
          <textarea id="newText" v-html="historyContent" style="display:none;">    </textarea>
        </div>

        <div id="diffoutput"> </div>

      </el-container>
    <Footer> </Footer>
    <div class=""></div>
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
  width:100%;
  height:300px;
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
    width: 835px;
    margin: 0 auto;
}


</style>

<script>
if (typeof window !== 'undefined') {
  var $s = require('scriptjs');
}
export default {
  props:{
    callback:'',
  },
  data () {
    return {
      content: '',
      historyContent:''
    };
  },
  components:{
    
  },
  methods:{
    get_content(){
        var that = this ;
        var url = DocConfig.server+'/api/page/diff';
        var params = new URLSearchParams();
        params.append('page_id',  that.$route.params.page_id);
        params.append('page_history_id',  that.$route.params.page_history_id);
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              var json = response.data.data ;
              that.content = json.page.page_content ;
              that.historyContent = json.history_page.page_content ;
              that.$nextTick(()=>{
                that.diffUsingJS(0);
              });
            
            }else{
              that.$alert(response.data.error_message);
            }
            
          })
          .catch(function (error) {
            console.log(error);
          });
    },
    diffUsingJS(viewType) {
      "use strict";
      var that = this ;
      var byId = function (id) { return document.getElementById(id); },
        base = difflib.stringAsLines(byId("baseText").value),
        newtxt = difflib.stringAsLines(byId("newText").value),
        sm = new difflib.SequenceMatcher(base, newtxt),
        opcodes = sm.get_opcodes(),
        diffoutputdiv = byId("diffoutput")

      diffoutputdiv.innerHTML = "";

      diffoutputdiv.appendChild(diffview.buildView({
        baseTextLines: base,
        newTextLines: newtxt,
        opcodes: opcodes,
        baseTextName: that.$t('cur_page_content'),
        newTextName: that.$t('history_version'),
        viewType: viewType
      }));
    }

  },
  mounted () {
    $s([
      `static/diff/difflib.js`,
      `static/diff/diffview.js`,
      ],()=>{

      this.get_content();
      
    })


  }
}
</script>