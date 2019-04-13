<template>
  <div class="hello">
    <Header> </Header>

    <el-container>
      <el-card class="center-card">
      <el-button  type="text" class="add-cat" @click="addTeamMember">{{$t('add_member')}}</el-button>
      <el-button type="text" class="goback-btn" @click="goback">{{$t('back_to_team')}}</el-button>
       <el-table align="left"
            :data="list"
             height="400"
            style="width: 100%">
            <el-table-column
              prop="member_username"
              :label="$t('member_username')"
              >
            </el-table-column>
            <el-table-column
              prop="name"
              :label="$t('name')"
              >
            </el-table-column>
            <el-table-column
              prop="addtime"
              :label="$t('addtime')"
              >
            </el-table-column>

            <el-table-column
              prop=""
              width="50"
              :label="$t('operation')">
              <template slot-scope="scope">
                <el-button @click="deleteTeamMember(scope.row.id)" type="text" size="small">{{$t('delete')}}</el-button>
              </template>
            </el-table-column>
          </el-table>


            </el-card>

            <el-dialog :visible.sync="dialogFormVisible"  width="300px">
                    <el-form >
                        <el-form-item :label="$t('member_username')+':'" >
                            <el-autocomplete
                              v-model="MyForm.member_username"
                              :fetch-suggestions="getAllUser"
                              v-if="dialogFormVisible"
                            ></el-autocomplete>
                        </el-form-item>
                    </el-form>

                    <div slot="footer" class="dialog-footer">
                      <el-button @click="dialogFormVisible = false">{{$t('cancel')}}</el-button>
                      <el-button type="primary" @click="MyFormSubmit" >{{$t('confirm')}}</el-button>
                    </div>
            </el-dialog>

    </el-container>

    <Footer> </Footer>
  </div>
</template>

<script>


export default {
  components : {

  },
  data () {
    return {
      MyForm:{
        id:'',
        member_username:''
      },
      list:[],
      dialogFormVisible:false,
      team_id:'',
    }
  }, 
  methods: {
      geList(){
        var that = this ;
        var url = DocConfig.server+'/api/teamMember/getList';
        var params = new URLSearchParams();
        params.append('team_id', this.team_id);
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              var Info = response.data.data ;

              that.list =  Info;
            }else{
              that.$alert(response.data.error_message);
            }
            
          });
      },
      MyFormSubmit() {
          var that = this ;
          var url = DocConfig.server+'/api/teamMember/save';

          var params = new URLSearchParams();
          params.append('team_id', this.team_id);
          params.append('member_username', this.MyForm.member_username);
          that.axios.post(url, params)
            .then(function (response) {
              if (response.data.error_code === 0 ) {
                that.dialogFormVisible = false;
                that.geList() ;
                that.MyForm = {};
              }else{
                that.$alert(response.data.error_message);
              }
            });
      },

      deleteTeamMember(id){
          var that = this ;
          var url = DocConfig.server+'/api/teamMember/delete';

          this.$confirm(that.$t('confirm_delete'), ' ', {
            confirmButtonText: that.$t('confirm'),
            cancelButtonText: that.$t('cancel'),
            type: 'warning'
          }).then(() => {
            var params = new URLSearchParams();
            params.append('id', id);

            that.axios.post(url, params)
            .then(function (response) {
              if (response.data.error_code === 0 ) {
                that.geList() ;
              }else{
                that.$alert(response.data.error_message);
              }
              
            }); 
          })

      },
      addTeamMember(){
        this.MyForm = [] ;
        this.dialogFormVisible = true;

      },
      goback(){
        this.$router.push({path:"/team/index"})
      },
      getAllUser(queryString,cb){
        var that = this ;
        var url = DocConfig.server+'/api/user/allUser';
        var params = new URLSearchParams();
        if (!queryString) {queryString = ''};
        params.append('username', queryString);
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              var Info = response.data.data ;
              var newInfo = [];
              //过滤掉已经是成员的用户
              for (var i = 0; i < Info.length; i++) {
                let isMember = that.isMember(Info[i]['value']);
                if (!isMember) {
                  newInfo.push(Info[i]);
                };
              };
              cb(newInfo);
            }else{
              that.$alert(response.data.error_message);
            }
            
          });
      },

      //判断某个用户是否已经是会员
      isMember(username){
        let list = this.list ;
        for (var i = 0; i < list.length; i++) {
          if (list[i]['member_username'] == username) {
            return true ;
          };
          
        };
        return false;
      }
  },

  mounted(){
    this.team_id = this.$route.params.team_id; 
    this.geList();
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

.hello{
  text-align: left;
}

.add-cat{
  margin-left: 10px;
}

.center-card{
  text-align: left;
  width: 600px;
  height: 500px;
}

.goback-btn{
  z-index: 999;
  margin-left: 350px;
}
</style>

<!-- 全局css -->
<style >
.el-table .success-row {
  background: #f0f9eb;
}

</style>