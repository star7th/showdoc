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
		  data = data.replace(/(^\s*)|(\s*$)/g, "");
		  var op1 = data.substr(0, 1) == "[" ? "[" : "{";
		  var  op2 = (op1 == "[") ? "]" : "}";
		  var text = "\n ``` \n " + op1 + " \n" + this.dump(JSON.parse(data)) + " " + op2 + " \n\n ```\n\n"; //整体加个大括号
		  this.callback(text);
		} catch (e) {
		  //非json数据直接显示
		  this.callback(data);
		}
  		this.dialogFormVisible = false;
  	},

  dump(arr, level) {
		var dumped_text = "";
		if (!level) level = 0;

		//The padding given at the beginning of the line. 
		var level_padding = "";
		for (var j = 0; j < level + 1; j++) level_padding += "     ";
		if (typeof(arr) == 'object') { //Array/Hashes/Objects 
		var i = 0;
		for (var item in arr) {
		var value = arr[item];
		if (typeof(value) == 'object') { //If it is an array, 
		  dumped_text += level_padding + "\"" + item + "\" : \{ \n";
		  dumped_text += this.dump(value, level + 1);
		  dumped_text += level_padding + "\}";
		} else {
		  if (typeof(value) == "number") {
		    dumped_text += level_padding + "\"" + item + "\" : " + value ;
		  }else{
		    dumped_text += level_padding + "\"" + item + "\" : \"" + value + "\"";
		  }
		}
		if (i < Object.getOwnPropertyNames(arr).length - 1) {
		  dumped_text += ", \n";
		} else {
		  dumped_text += " \n";
		}
		i++;
		}
		} else { //Stings/Chars/Numbers etc. 
		dumped_text = "===>" + arr + "<===(" + typeof(arr) + ")";
		}
		return dumped_text;
	}
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

.dialoContent{
	
}
</style>
