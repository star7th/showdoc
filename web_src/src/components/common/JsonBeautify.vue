<template>
	<div>
		<el-dialog :title="$t('beautify_json')" :visible.sync="dialogFormVisible">
		  <el-form >
		  	<el-input type="textarea" class="dialoContent" :placeholder="$t('beautify_json_description')" :rows="10" v-model="content"></el-input>
		  </el-form>
		  <div slot="footer" class="dialog-footer">
		    <el-button @click="dialogFormVisible = false">{{$t('cancel')}}</el-button>
		    <el-button type="primary" @click="transform">{{$t('confirm')}}</el-button>
		  </div>
		</el-dialog>
	</div>


</template>

<script>
export default {
  name: 'JsonBeautify',
  props:{
  	
  	formLabelWidth: '120px',
  	callback:'',
  },
  data () {
    return {
    	content:'',
    	json_table_data:'',
    	dialogFormVisible:false,
    }
  },
  methods:{
  	transform(){
  		var data = this.content;
		try {
		  var formattedStr = JSON.stringify(JSON.parse(data), null, 2);
		  var text = "\n ``` \n " + formattedStr + " \n\n ```\n\n"; //
		  this.callback(text);
		} catch (e) {
		  //非json数据直接显示
		  this.callback(data);
		}
  		this.dialogFormVisible = false;
  	}
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

.dialoContent{
	
}
</style>
