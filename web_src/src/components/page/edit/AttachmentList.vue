<!-- 附件 -->
<template>
  <div class="hello">
    <Header> </Header>

    <el-container class="container-narrow">

      <el-dialog :title="$t('attachment')" :visible.sync="dialogTableVisible" >
        <el-upload
          class="upload-file"
          :action="uploadUrl"
          :on-success="clearFiles"
          :on-error="clearFiles"
          :data="uploadData"
          ref="uploadFile"
          v-if="manage"
          >
          <el-button size="small" type="primary">{{$t('upload_file')}}</el-button><small>&nbsp;&nbsp;&nbsp;{{$t('file_size_tips')+uploadFile_maxSize+'M'}}</small>
        </el-upload>

        <el-table :data="content">
          <el-table-column property="addtime" :label="$t('add_time')" width="170"></el-table-column>
          <el-table-column property="display_name" :label="$t('file_name')" ></el-table-column>
          <el-table-column
            :label="$t('operation')"
            width="150">
            <template slot-scope="scope">
              <el-button @click="downloadFile(scope.row)" type="text" size="small">{{$t('download')}}</el-button>
              <el-button type="text" size="small" @click="deleteFile(scope.row)" v-if="manage">{{$t('delete')}}</el-button>
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
    item_id:'',
    manage:true,
  },
  data () {
    return {
      currentDate: new Date(),
      content: [],
      dialogTableVisible: false,
      uploadUrl:DocConfig.server+'/api/page/upload',
      uploadFile_maxSize:'',
    };
  },
  components:{

  },
  computed:{
    uploadData:function(){
      return {
        page_id:this.page_id,
        item_id:this.item_id,
      }
    }
  },
  methods:{
    get_fileMaxSize(){
      var that = this ;
      var url = DocConfig.server+'/api/adminSetting/getUploadSetting';
      var params = new URLSearchParams();
      that.axios.post(url,params)
        .then(function(response){
          if (response.data.error_code === 0) {
            that.uploadFile_maxSize = response.data.data.uploadFile_maxSize;
          }
        })
    },
    get_content(){
        var that = this ;
        var url = DocConfig.server+'/api/page/uploadList';
        var params = new URLSearchParams();
        params.append('page_id',  this.page_id);
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              that.dialogTableVisible = true ;
              //that.$message.success("加载成功");
              var json = response.data.data ;
              that.content = response.data.data ;
              that.get_fileMaxSize();
            }else{
              that.dialogTableVisible = false;
              that.$alert(response.data.error_message);
            }

          });
    },
    show(){
      this.get_content();

    },
    downloadFile(row){
      var url  = row.url ;
      window.open(url);
    },

    deleteFile(row){
      var that = this ;
      this.$confirm(that.$t('comfirm_delete'), ' ', {
        confirmButtonText: that.$t('confirm'),
        cancelButtonText: that.$t('cancel'),
        type: 'warning'
      }).then(() => {

        var file_id = row['file_id'] ;
        var that = this ;
        var url = DocConfig.server+'/api/page/deleteUploadFile';
        var params = new URLSearchParams();
        params.append('file_id',  file_id);
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
                that.get_content();

            }else{
              that.$alert(response.data.error_message);
            }

          })

      });



    },
    clearFiles(){
        let childRef = this.$refs.uploadFile ;//获取子组件
        childRef.clearFiles() ;
        this.get_content();
    }

  },
  mounted () {


  }
}
</script>
