<template>
  <div class="hello">
    <Header> </Header>

    <el-container>
      <el-card class="center-card">

      <el-button  type="text" class="add-cat" @click="addTeam">{{$t('add_team')}}</el-button>
      <el-button type="text" class="goback-btn" @click="goback">{{$t('goback')}}</el-button>
       <el-table align="left"
            :empty-text="$t('empty_team_tips')"
            :data="list"
             height="400"
            style="width: 100%">
            <el-table-column
              prop="team_name"
              :label="$t('team_name')"
              >
            </el-table-column>
            <el-table-column
              prop="memberCount"
              :label="$t('memberCount')"
              width="80">
              <template slot-scope="scope">
                  <router-link :to="'/team/member/'+scope.row.id" >{{scope.row.memberCount}}</router-link>
              </template>
            </el-table-column>
            <el-table-column
              prop="itemCount"
              :label="$t('itemCount')">
              <template slot-scope="scope">
                  <router-link :to="'/team/item/'+scope.row.id" >{{scope.row.itemCount}}</router-link>
              </template>
            </el-table-column>
            <el-table-column
              prop=""
              width="200"
              :label="$t('operation')">
              <template slot-scope="scope">
                <el-button @click="$router.push({path:'/team/member/'+scope.row.id})" type="text" size="small">{{$t('member')}}</el-button>
                <el-button @click="$router.push({path:'/team/item/'+scope.row.id})"  type="text" size="small">{{$t('team_item')}}</el-button>
                <el-button @click="edit(scope.row)" type="text" size="small">{{$t('edit')}}</el-button>
                <el-button @click="deleteTeam(scope.row.id)" type="text" size="small">{{$t('delete')}}</el-button>
              </template>
            </el-table-column>
          </el-table>


            </el-card>
            <el-dialog :visible.sync="dialogFormVisible"  width="300px">
              <el-form >
                  <el-form-item :label="$t('team_name')+':'" >
                     <el-input   v-model="MyForm.team_name"></el-input>
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
        team_name:''
      },
      list:[],
      dialogFormVisible:false,
      dialogMemberVisible:false,
    }
  }, 
  methods: {


      geList(){
        var that = this ;
        var url = DocConfig.server+'/api/team/getList';
        var params = new URLSearchParams();
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
          var url = DocConfig.server+'/api/team/save';

          var params = new URLSearchParams();
          params.append('id', this.MyForm.id);
          params.append('team_name', this.MyForm.team_name);
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
      edit(row){
        this.MyForm.id = row.id;
        this.MyForm.team_name = row.team_name;
        this.dialogFormVisible = true;
      },

      deleteTeam(id){
          var that = this ;
          var url = DocConfig.server+'/api/team/delete';

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
      addTeam(){
        this.MyForm = [] ;
        this.dialogFormVisible = true;

      },
      goback(){
        this.$router.push({path:"/item/index"})
      }
  },

  mounted(){
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
  margin-left: 400px;
}
</style>

<!-- 全局css -->
<style >
.el-table .success-row {
  background: #f0f9eb;
}

.el-table__empty-text{
  text-align: left;
}

</style>
