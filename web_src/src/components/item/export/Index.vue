<template>
  <div class="hello">
    <Header> </Header>

    <el-container>
          <el-card class="center-card">
            <el-form  status-icon  label-width="0px" class="demo-ruleForm">
              <h2></h2>
              <el-form-item label="" >
              <el-radio v-model="export_type"  label="1">{{$t('export_all')}}</el-radio>
              <el-radio v-model="export_type" label="2">{{$t('export_cat')}}</el-radio>
              </el-form-item>


              <el-form-item label="" >
                  <el-select :disabled="export_type > 1 ? false :true" :placeholder="$t('select_cat_2')" class="cat" v-model="cat_id2" @change="get_cat3">
                    <el-option  v-if="cat2" v-for="cat in cat2 " :key="cat.cat_name" :label="cat.cat_name" :value="cat.cat_id"></el-option>
                  </el-select>
              </el-form-item>

              <el-form-item label="" >
                <el-select :disabled="export_type > 1 ? false :true" :placeholder="$t('select_cat_3')" class="cat" v-model="cat_id3">
                  <el-option v-if="cat3" v-for="cat in cat3 " :label="cat.cat_name" :key="cat.cat_name" :value="cat.cat_id"></el-option>
                </el-select>
              </el-form-item>


               <el-form-item label="" >
                <el-button type="primary" style="width:100%;" @click="onSubmit" >{{$t('begin_export')}}</el-button>
              </el-form-item>

              <el-form-item label=""  >
                  <el-button type="text" @click="goback" class="goback-btn " >{{$t('goback')}}</el-button>
              </el-form-item>
            </el-form>
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
      cat2:[],
      cat_id2:'',
      cat3:[],
      cat_id3:'',
      export_type:'1',
      item_id:0,

    }

  },
  methods: {
    //获取二级目录
    get_cat2(item_id){
      var that = this ;
      var url = DocConfig.server+'/api/catalog/secondCatList';
      var params = new URLSearchParams();
      params.append('item_id',  item_id);
      that.axios.post(url, params)
        .then(function (response) {
          if (response.data.error_code === 0 ) {
            //that.$message.success("加载成功");
            that.cat2 = response.data.data ;
            
          }else{
            that.$alert(response.data.error_message);
          }
          
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    //获取三级目录
    get_cat3(cat_id){
      var that = this ;
      var url = DocConfig.server+'/api/catalog/childCatList';
      var params = new URLSearchParams();
      params.append('cat_id',  cat_id);
      that.axios.post(url, params)
        .then(function (response) {
          if (response.data.error_code === 0 ) {
            //that.$message.success("加载成功");
            that.cat_id3 = '';
            that.cat3 = response.data.data ;
            
          }else{
            that.$alert(response.data.error_message);
          }
          
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    onSubmit() {
        var val= this.export_type ;
        if (val == 1 ) {
            var url = DocConfig.server+'/api/export/word&item_id='+this.item_id ;
            window.location.href = url;
        }
        else if (val == 2) {
            var cat_id2 = this.cat_id2;
            var cat_id3 = this.cat_id3;

            if (cat_id2 > 0 ) {
                var cat_id = cat_id3 > 0 ? cat_id3 : cat_id2 ;
                var url = DocConfig.server+'/api/export/word_cat&item_id='+this.item_id+'&cat_id='+cat_id ;
                window.location.href = url;
            }else{
                this.$alert("请选择目录");
            }
        }
      },
    goback(){
      this.$router.go(-1);
    }

  },
  mounted() {
    this.get_cat2(this.$route.params.item_id);
    this.item_id = this.$route.params.item_id ;
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
  width: 350px;
}

</style>
