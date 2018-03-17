<template>
	<div>
		<el-dialog :title="$t('json_to_table')" :visible.sync="dialogFormVisible">
		  <el-form >
		  	<el-input type="textarea" class="dialoContent" :placeholder="$t('json_to_table_description')" :rows="10" v-model="content"></el-input>
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
  name: 'JsonToTable',
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

	    try {
	      var jsonData = JSON.parse(this.content);
	      this.json_table_data = '|参数|类型|描述|\n|:-------|:-------|:-------|\n';
	      this.Change(jsonData);
	      this.callback(this.json_table_data);
	    } catch (e) {
	      this.$alert("Json解析失败");
	    }
  		this.dialogFormVisible = false;
  	},
  	Change(data){
  		var that =  this;
	    var level_str = "- ";
	    if (arguments.length > 1) {
	      var level;
	      arguments[1] > 0 ? level = arguments[1] : level = 1;
	      for (var i = 0; i < level; i++) {
	        level_str += "- ";
	      }
	    }

	    for (var key in data) {
	      var value = data[key];
	      var type = typeof(value);
	      if (type == "object") {
	        that.json_table_data += '| ' + level_str + key + ' |' + type + '  | 无 |\n';
	        if (value instanceof Array) {
	          var j = level + 1;
	          this.Change(value[0], j);
	          continue;
	        }
	        //else
	        //{
	        this.Change(value, level);
	        //}

	      } else {
	        that.json_table_data += '| ' + key + ' | ' + type + '| 无 |\n';
	      }
	  }
  	}
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

.dialoContent{
	
}
</style>
