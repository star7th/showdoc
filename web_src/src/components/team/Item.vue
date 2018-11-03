<template>
  <div class="hello">
    <Header> </Header>

    <el-container>
      <el-card class="center-card">
      <el-button  type="text" class="add-cat" @click="addTeamItem">{{$t('distribution_to_team')}}</el-button>
      <el-button type="text" class="goback-btn" @click="goback">{{$t('back_to_team')}}</el-button>
       <el-table align="left"
            :data="list"
             height="400"
            style="width: 100%">
            <el-table-column
              prop="item_name"
              :label="$t('item_name')"
              >
            </el-table-column>
            <el-table-column
              prop="addtime"
              :label="$t('Join_time')"
              >
            </el-table-column>

            <el-table-column
              prop=""
              width="210"
              :label="$t('operation')">
              <template slot-scope="scope">
                <router-link :to="'/'+scope.row.item_id" target="_blank">{{$t('check_item')}}</router-link>
               
              <el-button @click="getTeamItemMember(scope.row.item_id)" type="text" size="small">{{$t('member_authority')}}</el-button>

                <el-button @click="deleteTeamItem(scope.row.id)" type="text" size="small">{{$t('unassign')}}</el-button>
              </template>
            </el-table-column>
          </el-table>


            </el-card>

            <el-dialog :visible.sync="dialogFormVisible"  width="300px">
                    <el-form >
                        <el-select size="mini" v-model="MyForm.item_id"  :placeholder="$t('please_choose')">
                          <el-option
                            v-for="item in itemList"
                            :key="item.item_id"
                            :label="item.item_name"
                            :value="item.item_id"
                            
                            >
                          </el-option>
                        </el-select>
                    </el-form>
                    <br>
                    <router-link to="/item/index" target="_blank">{{$t('go_to_new_an_item')}}</router-link>
                    <div slot="footer" class="dialog-footer">
                      <el-button @click="dialogFormVisible = false">{{$t('cancel')}}</el-button>
                      <el-button type="primary" @click="MyFormSubmit" >{{$t('confirm')}}</el-button>
                    </div>
            </el-dialog>

      <!-- 成员权限弹窗 -->
      <el-dialog :visible.sync="dialogFormTeamMemberVisible" :modal="false" top="10vh" :title="$t('adjust_member_authority')" >

           <el-table align="left"
                :empty-text="$t('team_member_empty_tips')"
                :data="teamItemMembers"
                style="width: 100%">
                <el-table-column
                  prop="member_username"
                  :label="$t('username')"
                 >
                </el-table-column>
                <el-table-column
                  prop="member_group_id"
                  :label="$t('authority')"
                  width="130"
                  >
                  <template slot-scope="scope">
                    <el-select size="mini" v-model="scope.row.member_group_id" @change="changeTeamItemMemberGroup($event,scope.row.id)" :placeholder="$t('please_choose')">
                      <el-option
                        v-for="item in authorityOptions"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                        
                        >
                      </el-option>
                    </el-select>
                  </template>
                </el-table-column>
                <el-table-column
                  prop="addtime"
                  :label="$t('add_time')"
                  >
                </el-table-column>

              </el-table>
        <br>
        <p class="tips">
          {{$t('team_member_authority_tips')}}
        </p>
        <div slot="footer" class="dialog-footer">
          <el-button @click="dialogFormTeamMemberVisible = false">{{$t('close')}}</el-button>
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
        item_id:''
      },
      list:[],
      dialogFormVisible:false,
      team_id:'',
      itemList:[],
      teamItemMembers:[],
      dialogFormTeamMemberVisible:false,
      authorityOptions:[
          {
            label:"编辑",
            value:'1',
          },
          {
            label:"只读",
            value:'0',
          }
      ]
    }
  }, 
  methods: {
      geList(){
        var that = this ;
        var url = DocConfig.server+'/api/teamItem/getListByTeam';
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
      getItemList(){
          var that = this ;
          var url = DocConfig.server+'/api/item/myList';
          var params = new URLSearchParams();
          that.axios.get(url, params)
            .then(function (response) {
              if (response.data.error_code === 0 ) {
                var json = response.data.data ;
                that.itemList = json ;
              }else{
                that.$alert(response.data.error_message);
              }
              
            });
      },
      MyFormSubmit() {
          var that = this ;
          var url = DocConfig.server+'/api/teamItem/save';

          var params = new URLSearchParams();
          params.append('team_id', this.team_id);
          params.append('item_id', this.MyForm.item_id);
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

      deleteTeamItem(id){
          var that = this ;
          var url = DocConfig.server+'/api/teamItem/delete';

          this.$confirm(that.$t('confirm_unassign'), ' ', {
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
      addTeamItem(){
        this.MyForm = [] ;
        this.dialogFormVisible = true;

      },
      goback(){
        this.$router.push({path:"/team/index"})
      },
      getTeamItemMember(item_id){
        var that = this ;
        this.dialogFormTeamMemberVisible = true;
        var url = DocConfig.server+'/api/teamItemMember/getList';
        var params = new URLSearchParams();
        params.append('item_id',  item_id);
        params.append('team_id',  this.team_id);
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              var Info = response.data.data
              that.teamItemMembers =  Info;
            }else{
              that.$alert(response.data.error_message);
            }
            
          })
      },
      changeTeamItemMemberGroup(member_group_id,id){
          var that = this ;
          var url = DocConfig.server+'/api/teamItemMember/save';

          var params = new URLSearchParams();
          params.append('member_group_id',  member_group_id);
          params.append('id', id);

          that.axios.post(url, params)
          .then(function (response) {
              if (response.data.error_code === 0 ) {
                 that.$message('权限保存成功');
              }else{
                that.$alert(response.data.error_message);
              }
              
          });
      }
  },

  mounted(){
    this.team_id = this.$route.params.team_id; 
    this.geList();
    this.getItemList() ;
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
  margin-left: 300px;
}
</style>

<!-- 全局css -->
<style >
.el-table .success-row {
  background: #f0f9eb;
}

</style>