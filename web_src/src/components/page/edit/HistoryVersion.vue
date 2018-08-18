<!-- 更多模板 -->
<template>
  <div class="hello">
    <Header> </Header>

    <el-container class="container-narrow">

      <el-dialog :title="$t('history_version')" :modal="is_modal"  :visible.sync="dialogTableVisible">
        <el-table :data="content">
          <el-table-column property="addtime" :label="$t('update_time')" width="170"></el-table-column>
          <el-table-column property="author_username" :label="$t('update_by_who')" ></el-table-column>
          <el-table-column
            label="操作"
            width="150">
            <template slot-scope="scope">
              <el-button @click="preview_diff(scope.row)" type="text" size="small">{{$t('overview')}}</el-button>
              <el-button v-if="is_show_recover_btn"  type="text" size="small" @click="recover(scope.row)">{{$t('recover_to_this_version')}}</el-button>
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
    page_id:'',
    is_modal:true,
    is_show_recover_btn:true,
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
        var url = DocConfig.server+'/api/page/history';
        var params = new URLSearchParams();
        let page_id = this.page_id ? this.page_id: that.$route.params.page_id ;
        params.append('page_id',  page_id);
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
                that.$alert('no data');
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
    recover(row){
      this.callback(row.page_content,true);
      this.dialogTableVisible = false;
    },

    preview_diff(row){
      var page_history_id = row['page_history_id'] ;
      let page_id = this.page_id ? this.page_id: this.$route.params.page_id ;
      var url = '#/page/diff/'+page_id+'/'+page_history_id;
      window.open(url);
    }

  },
  mounted () {
    

  }
}
</script>