<template>
  <div class="hello">
      <ul class="page-bar">
        <li>
            <el-tooltip class="item" effect="dark" :content="$t('edit_page')" placement="right">
                  <el-button type="text" icon="el-icon-edit" @click="edit_page"></el-button>
            </el-tooltip>
        </li>
        <li>
            <el-tooltip class="item" effect="dark" :content="$t('share_page')" placement="right">
                  <el-button type="text" icon="el-icon-share" @click="share_page"></el-button>
            </el-tooltip>

          </li>
        <li>
          <el-dropdown @command="dropdown_callback">
            <span class="el-dropdown-link">
              <i class="el-icon-arrow-down el-icon--down"></i>
            </span>
            <el-dropdown-menu slot="dropdown">
              <router-link :to="'/page/edit/'+item_id+'/0?copy_page_id='+page_id"><el-dropdown-item>{{$t('copy')}}</el-dropdown-item></router-link>
              <el-dropdown-item :command="show_page_info">{{$t('detail')}}</el-dropdown-item>
              <el-dropdown-item :command="ShowHistoryVersion">{{$t('history_version')}}</el-dropdown-item>
              <el-dropdown-item :command="delete_page">{{$t('delete')}}</el-dropdown-item>
            </el-dropdown-menu>
          </el-dropdown>
        </li>
      </ul>

  <el-dialog
    :title="$t('share_page')"
    :visible.sync="dialogVisible"
    width="600px"
    :modal="false"
    >
    
    <p>{{$t('item_page_address')}} : <code >{{share_page_link}}</code>
    </p>
      <p v-if="false" style="border-bottom: 1px solid #eee;"><img  id="qr-page-link" style="width:114px;height:114px;" :src="qr_page_link"> </p>
      
      <div v-show="item_info.ItemPermn">
        <el-checkbox v-model="isCreateSiglePage" @change="CreateSiglePage">{{$t('create_sigle_page')}}</el-checkbox>

        <p v-if="isCreateSiglePage">{{$t('single_page_address')}} : <code >{{share_single_link}}</code> <p>

        <p>
          {{$t('create_sigle_page_tips')}}
        </p>
      </div>



    <span slot="footer" class="dialog-footer">
      <el-button type="primary" @click="dialogVisible = false">{{$t('confirm')}}</el-button>
    </span>
  </el-dialog>

    <!-- 历史版本 -->
    <HistoryVersion :page_id="page_id" :is_show_recover_btn="false" :is_modal="false" callback="insertValue" ref="HistoryVersion"></HistoryVersion>

  </div>
</template>


<style scoped>
  .page-bar{
   
  }

</style>

<script>
  import HistoryVersion from '@/components/page/edit/HistoryVersion'
  export default {
  props:{
    item_id:'',
    page_id:'',
    page_info:{},
    item_info:'',
  },
    data() {
      return {
        menu: [],
        dialogVisible:false,
        qr_page_link:"#",
        qr_single_link:"#",
        share_page_link:"",
        share_single_link:"",
        copyText1:'',
        copyText2:'',
        isCreateSiglePage:false
      }
    },
  components:{
    HistoryVersion
  },
  methods:{
    edit_page(){
      var page_id = this.page_id > 0 ? this.page_id : 0 ;
      var url = '/page/edit/'+this.item_id+'/'+page_id;
      this.$router.push({path:url}) ;
    },
    share_page(){
      var page_id = this.page_id > 0 ? this.page_id : 0 ;
      this.share_page_link = this.getRootPath()+"#/"+this.item_id +'?page_id='+page_id ;
      //this.share_single_link= this.getRootPath()+"#/page/"+page_id ;
      this.qr_page_link = DocConfig.server +'/api/common/qrcode&size=3&url='+encodeURIComponent(this.share_page_link);
      //this.qr_single_link = DocConfig.server +'/api/common/qrcode&size=3&url='+encodeURIComponent(this.share_single_link);
      this.dialogVisible = true;
      this.copyText1 = this.item_info.item_name+' - '+this.page_info.page_title+"\r\n"+ this.share_page_link;
      this.copyText2 = this.page_info.page_title+"\r\n"+ this.share_single_link;
    },
    dropdown_callback(data){
      if (data) {
        data();
      };
    },
    show_page_info(){
      var html ="本页面由 "+this.page_info.author_username+' 于 '+this.page_info.addtime+' 更新';
      this.$alert(html);
    },
    
    //展示历史版本
    ShowHistoryVersion(){
        let childRef = this.$refs.HistoryVersion ;//获取子组件
        childRef.show() ; 
    },

    delete_page(){
      var page_id = this.page_id > 0 ? this.page_id : 0 ;
      var that = this ;
      var url = DocConfig.server+'/api/page/delete';

      this.$confirm(that.$t('comfirm_delete'), ' ', {
        confirmButtonText: that.$t('confirm'),
        cancelButtonText: that.$t('cancel'),
        type: 'warning'
      }).then(() => {
        var params = new URLSearchParams();
        params.append('page_id',  page_id);
        that.axios.post(url, params)
        .then(function (response) {
          if (response.data.error_code === 0 ) {
            window.location.reload() 
          }else{
            that.$alert(response.data.error_message);
          }
        }); 
      });
    },
    onCopy(){
      this.$message(this.$t("copy_success"));
    },
    CreateSiglePage(){
        var page_id = this.page_id > 0 ? this.page_id : 0 ;
        var that = this ;
        var url = DocConfig.server+'/api/page/createSinglePage';
        var params = new URLSearchParams();
        params.append('page_id',  page_id);
        params.append('isCreateSiglePage',  this.isCreateSiglePage);

        that.axios.post(url, params)
        .then(function (response) {
          if (response.data.error_code === 0 ) {
            var unique_key = response.data.data.unique_key ;
            if (unique_key) {
              that.share_single_link = that.getRootPath()+"#/p/"+unique_key ;
            }else{
              that.share_single_link = '';
            }
            

          }else{
            that.$alert(response.data.error_message);
          }
        });

    }

  },
  mounted () {
    var that = this ;
    if (this.page_info.unique_key) {
      this.isCreateSiglePage = true ;
      this.share_single_link = this.getRootPath()+"#/p/"+this.page_info.unique_key ;
    };
    document.onkeydown=function(e){  //对整个页面文档监听 其键盘快捷键
      var keyNum=window.event ? e.keyCode :e.which;  //获取被按下的键值 
      if (keyNum == 69 && e.ctrlKey) {  //Ctrl +e 为编辑
        that.edit_page();
        e.preventDefault();
      };

      if (keyNum == 46 && e.ctrlKey) {  //Ctrl +del 为删除
        that.delete_page();
        e.preventDefault();
      };

    }
  }
};
</script>
