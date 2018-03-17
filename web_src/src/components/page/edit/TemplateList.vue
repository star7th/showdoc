<!-- 更多模板 -->
<template>
  <div class="hello">
    <Header> </Header>

    <el-container class="container-narrow">

      <el-dialog :title="$t('templ_list')" :visible.sync="dialogTableVisible">
        <el-table :data="content">
          <el-table-column property="addtime" :label="$t('save_time')" width="170"></el-table-column>
          <el-table-column property="template_title" :label="$t('templ_title')" ></el-table-column>
          <el-table-column
            :label="$t('operation')"
            width="150">
            <template slot-scope="scope">
              <el-button @click="insertTemplate(scope.row)" type="text" size="small">{{$t('insert_templ')}}</el-button>
              <el-button type="text" size="small" @click="deleteTemplate(scope.row)">{{$t('delete_templ')}}</el-button>
            </template>
          </el-table-column>
        </el-table>
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
  },
  data () {
    return {
      currentDate: new Date(),
      content: [],
      dialogTableVisible: false,
    };
  },
  components:{
    
  },
  methods:{
    get_content(){
        var that = this ;
        var url = DocConfig.server+'/api/template/getList';
        var params = new URLSearchParams();
        //params.append('page_id',  that.$route.params.page_id);
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              //that.$message.success("加载成功");
              var json = response.data.data ;
              if (json.length > 0 ) {
                that.content = response.data.data ;
                that.dialogTableVisible = true ;
              }else{
                that.dialogTableVisible = false;
                that.$alert(that.$t('no_templ_text'));
              }
              
            }else{
              that.dialogTableVisible = false;
              that.$alert(response.data.error_message);
            }
            
          })
          .catch(function (error) {
            console.log(error);
          });
    },
    show(){
      this.get_content();

    },
    insertTemplate(row){
      this.callback(row.template_content);
      this.dialogTableVisible = false;
    },

    deleteTemplate(row){
      var id = row['id'] ;
        var that = this ;
        var url = DocConfig.server+'/api/template/delete';
        var params = new URLSearchParams();
        params.append('id',  id);
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
                that.get_content();
              
            }else{
              that.$alert(response.data.error_message);
            }
            
          })
          .catch(function (error) {
            console.log(error);
          });
    }

  },
  mounted () {
    

  }
}
</script>