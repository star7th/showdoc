<!-- 更多模板 -->
<template>
  <div class="hello">
    <Header> </Header>

    <el-container class="container-narrow">

      <el-dialog :title="$t('paste_insert_table')" :modal="is_modal"  :visible.sync="dialogFormVisible">
        <el-form >
		  	<el-input type="textarea" class="dialoContent" :placeholder="$t('paste_insert_table_tips')" :rows="10" v-model="content"></el-input>
		  </el-form>
		  <div slot="footer" class="dialog-footer">
		    <el-button @click="dialogFormVisible = false">{{$t('cancel')}}</el-button>
		    <el-button type="primary" @click="transform">{{$t('confirm')}}</el-button>
		  </div>
      </el-dialog>

      </el-container>
    <Footer> </Footer>
    <div class=""></div>
  </div>
</template>

<style>


</style>

<script>

export default {
  props:{
    callback:'',
    page_id:'',
    is_modal:true,
    is_show_recover_btn:true,
  },
  data () {
    return {
      currentDate: new Date(),
      content: '',
      dialogFormVisible: false,
    };
  },
  components:{
    
  },
  methods:{
    transform: function (){
      var md=this.content;
      var sheet_str="\n\n";
      for (const [index,row] of md.split("\n").entries()){
        var cols=row.split("\t");
        sheet_str += '| ' + cols.join(" | ") +" |\n";
        if (index ==0){
          for (var i=0;i<cols.length;i++){
            sheet_str+='|:--- ';
          }
          sheet_str+=" |\n";
        }
      }
      this.callback(sheet_str+"\n\n");
      this.dialogFormVisible=false;
    }

  },
  mounted () {
    

  }
}
</script>