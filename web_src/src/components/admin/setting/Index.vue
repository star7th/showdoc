<template>

<div class="hello">
      <el-form ref="form" :model="form" label-width="150px">

      </el-form-item>

      <el-form-item :label="$t('register_open')">
        <el-switch v-model="form.register_open"></el-switch>
      </el-form-item>
      <!-- 待支持
      <el-form-item label="所有人可以新建项目">
        <el-switch v-model="form.register_open"></el-switch>
      </el-form-item>

      <el-form-item label="网站首页设置为">
          <el-select v-model="form.home_page" placeholder="请选择">
            <el-option label="全屏介绍页" value="1"></el-option>
            <el-option label="展示全站项目" value="2"></el-option>
          </el-select>
      </el-form-item>
      -->
      <el-form-item :label="$t('ldap_open_label')">
        <el-switch v-model="form.ldap_open"></el-switch>
      </el-form-item>

      <div v-if="form.ldap_open" style="margin-left:50px" >

        <el-form-item label="ldap host">
           <el-input v-model="form.ldap_form.host"   class="form-el"></el-input>
        </el-form-item>

        <el-form-item label="ldap port">
          <el-input v-model="form.ldap_form.port"  style="width:90px"></el-input>
        </el-form-item>



        <el-form-item label="ldap base dn ">
          <el-input v-model="form.ldap_form.base_dn"  class="form-el" placeholder="例如 dc=showdoc,dc=com"></el-input>
        </el-form-item>

        <el-form-item label="ldap bind dn ">
          <el-input v-model="form.ldap_form.bind_dn"  class="form-el" placeholder="cn=admin,dc=showdoc,dc=com"></el-input>
        </el-form-item>

        <el-form-item label="ldap bind password ">
          <el-input v-model="form.ldap_form.bind_password"  class="form-el" placeholder="例如 123456"></el-input>
        </el-form-item>

        <el-form-item label="ldap version">
            <el-select v-model="form.ldap_form.version"  class="form-el">
              <el-option label="3" value="3"></el-option>
              <el-option label="2" value="2"></el-option>
            </el-select>
        </el-form-item>

        
        <el-form-item label="ldap user filed">
          <el-input v-model="form.ldap_form.user_field" class="form-el" placeholder="例如 cn"></el-input>
        </el-form-item>
       
      </div>



      <el-form-item >
        <el-button type="primary" @click="onSubmit">{{$t('save')}}</el-button>
        <el-button>{{$t('cancel')}}</el-button>
      </el-form-item>
    </el-form>
</div>

</template>

<style scoped>
  .form-el{
    width: 230px;
  }


</style>

<script>

export default {
  data() {
    return {
      form:{
        register_open:true,
        ldap_open:false,
        home_page:'1',
        ldap_form:{
          "host":'',
          "port":'389',
          "version":"3",
          "base_dn":'',
          "bind_dn":'',
          "bind_password":'',
          "user_field":'',
        }
      }
    };
  },
  methods:{

    onSubmit(){
      var url = DocConfig.server+'/api/adminSetting/saveConfig';
      this.axios.post(url, this.form)
        .then( (response) =>{
          if (response.data.error_code === 0 ) {
              this.$alert(this.$t("success"));
          }else{
            this.$alert(response.data.error_message);
          }
          
        });
    },
    loadConfig(){
      var url = DocConfig.server+'/api/adminSetting/loadConfig';
      this.axios.post(url, this.form)
        .then( (response) =>{
          if (response.data.error_code === 0 ) {
            if (response.data.data.length === 0) {
              return ;
            };
            this.form.register_open =   response.data.data.register_open > 0 ? true :false ;
            this.form.ldap_open =   response.data.data.ldap_open > 0 ? true :false ;
            this.form.ldap_form =   response.data.data.ldap_form ? response.data.data.ldap_form : this.form.ldap_form ;
          }else{
            this.$alert(response.data.error_message);
          }
          
        });
    }

  },
  mounted () {
    this.loadConfig();
  },
  beforeDestroy(){
    this.$message.closeAll();
    /*去掉添加的背景色*/
    document.body.removeAttribute("class","grey-bg");
  }
}
</script>