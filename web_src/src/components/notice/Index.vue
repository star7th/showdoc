<template>
  <div class="hello">
    <Header> </Header>

    <el-container>
          <el-card class="center-card">
          <template>
            <el-button type="text" class="goback-btn "  @click="feedback" ><span class="feedback">反馈</span></el-button><router-link to="/item/index">返回</router-link>
            <el-tabs  value="first" type="card">
              <el-tab-pane label="我的消息" name="first">

               <el-table align="left"
                    :data="allList"
                     height="350"
                     :default-expand-all="false"
                    style="width: 100%">

                    <el-table-column
                      prop="notice_title"
                      label="标题"
                      >
                    <template slot-scope="props" >

                      <span v-html="props.row.notice_title"></span>
                      <el-badge class="mark" value="未读" v-if="props.row.is_read == 0"/>
                    </template>

                    </el-table-column>
                    <el-table-column
                      prop="from_name"
                      label="发送人">
                    </el-table-column>
                    <el-table-column
                      prop="notice_time"
                      label="时间"
                      width="100">
                    </el-table-column>
                    <el-table-column
                      prop=""
                      label="操作">
                      <template slot-scope="scope">
                        <el-button @click="show_notice(scope.row)" type="text" size="small">查看</el-button>
                        <el-button @click="delete_notice(scope.row)" type="text" size="small">删除</el-button>
                      </template>
                    </el-table-column>
                  </el-table>

            </el-tab-pane>


            </el-tabs>
          </template>
          </el-card>
    </el-container>

    <Footer> </Footer>
    
  </div>
</template>

<script>


export default {
  name: 'Login',
  components : {

  },
  data () {
    return {
      unreadList:[],
      allList:[]
    }

  },
  methods: {

      getList(notice_type){
        if (!notice_type) {
          notice_type = 'all';
        };
        var that = this ;
        var url = DocConfig.server+'/api/notice/getList';
        var params = new URLSearchParams();
        params.append('notice_type',  notice_type);
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              var Info = response.data.data
              if (notice_type == 'unread') {
                that.unreadList =  Info;
              }else{
                that.allList =  Info;
              }
              
            }else{
              that.$alert(response.data.error_message);
            } 
          });
      },

      show_notice(row){
        var notice_id = row.notice_id ;
        row.is_read = 1 ;
        var that = this ;

        that.$alert(row.notice_content, '', {
          dangerouslyUseHTMLString: true
        });

        var url = DocConfig.server+'/api/notice/setRead';
        var params = new URLSearchParams();
        params.append('notice_id',  notice_id);
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              var Info = response.data.data ;
            }else{
              that.$alert(response.data.error_message);
            } 
          });
      },
      delete_notice(row){
        var notice_id = row.notice_id ;
        var that = this ;
        var url = DocConfig.server+'/api/notice/delete';

        this.$confirm('确认删除吗?', '提示', {
          confirmButtonText: '确定',
          cancelButtonText: '取消',
          type: 'warning'
        }).then(() => {
          var params = new URLSearchParams();
          params.append('notice_id',  notice_id);
          that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              that.getList() ;
            }else{
              that.$alert(response.data.error_message);
            }
          }); 
        });
      },
      feedback(){
        this.$alert("对showdoc.cc的任何疑问、建议都可以反馈到 xing7th@gmail.com");
      }


  },

  mounted(){
    this.getList() ;
  },

  beforeCreate() {
    /*给body添加类，设置背景色*/
    document.getElementsByTagName("body")[0].className="grey-bg";
  },
  beforeDestroy(){
    /*去掉添加的背景色*/
    document.body.removeAttribute("class","grey-bg");
  }
  
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

.center-card a {
  font-size: 12px;
}

.center-card{
  text-align: center;
  width: 650px;
  height: 500px;
}

.infoForm{
  width:350px;
  margin-left: 60px;
  margin-top: 30px;
}

.goback-btn{
  margin-left: 500px;
}

.feedback{
  font-size: 12px;
  margin-right: 30px;
}
</style>
