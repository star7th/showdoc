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
                  <el-select :disabled="export_type > 1 ? false :true" :placeholder="$t('catalog')" class="cat" v-model="cat_id" >
                    <el-option  v-if="computed_catalogs" v-for="cat in computed_catalogs " :key="cat.cat_name" :label="cat.cat_name" :value="cat.cat_id"></el-option>
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
      catalogs:[],
      cat_id:'',
      export_type:'1',
      item_id:0,

    }

  },
  computed: {

    //新建/编辑页面时供用户选择的归属目录列表
    computed_catalogs:function(){
        var Info = this.catalogs.slice(0);
        var cat_array = [] ;
        for (var i = 0; i < Info.length; i++) {
          cat_array.push(Info[i]);
          var sub = Info[i]['sub'] ;
          if (sub.length > 0 ) {
            for (var j = 0; j < sub.length; j++) {
              cat_array.push( {
                "cat_id":sub[j]['cat_id'] ,
                "cat_name":Info[i]['cat_name']+' / ' + sub[j]['cat_name']
              });

              var sub_sub = sub[j]['sub'] ;
              if (sub_sub.length > 0 ) {
                for (var k = 0; k < sub_sub.length; k++) {
                  cat_array.push( {
                    "cat_id":sub_sub[k]['cat_id'] ,
                    "cat_name":Info[i]['cat_name']+' / ' + sub[j]['cat_name']+' / ' + sub_sub[k]['cat_name']
                  });
                };
              };

            };
          };
        };
        var no_cat = {"cat_id":'' ,"cat_name":this.$t("none")} ;
        cat_array.unshift(no_cat);
        return cat_array;

    }
  },
  methods: {
    //获取所有目录
    get_catalog(item_id){
      var that = this ;
      var url = DocConfig.server+'/api/catalog/catListGroup';
      var params = new URLSearchParams();
      params.append('item_id',  item_id);
      that.axios.post(url, params)
        .then(function (response) {
          if (response.data.error_code === 0 ) {
            var Info = response.data.data ;

            that.catalogs =  Info;
          }else{
            that.$alert(response.data.error_message);
          }
          
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    onSubmit() {
        if (this.export_type ==1 ) {
          this.cat_id = ''
        };
        var url = DocConfig.server+'/api/export/word&item_id='+this.item_id+'&cat_id='+this.cat_id ;
        window.location.href = url;
      },
    goback(){
      this.$router.go(-1);
    }

  },
  mounted() {
    this.get_catalog(this.$route.params.item_id);
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
