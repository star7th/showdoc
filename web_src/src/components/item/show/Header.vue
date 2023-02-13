<!-- 附件 -->
<template>
  <div class="header grey-bg">
    <div class="logo float-left mt-4 ml-5  cursor-pointer">
      <div>
        <div class="inline-block align-middle">
          <router-link to="/item/index">
            <div class="icon-item">
              <i class="el-icon-back"></i>
            </div>
          </router-link>
        </div>
        <div class="inline-block align-middle">
          <div class="font-bold">{{ item_info.item_name }}</div>
        </div>
      </div>
    </div>
    <slot></slot>
  </div>
</template>

<script>
export default {
  props: {
    item_info: ''
  },
  data() {
    return {}
  },
  components: {},
  computed: {},
  methods: {},
  mounted() {}
}
</script>

<style scoped>
.header {
  height: 90px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  position: fixed;
  top: 0;
  width: 100%;
  z-index: 999;
}
.icon-item {
  background-color: white;
  width: 40px;
  height: 40px;
  font-size: 16px;
  justify-content: center; /*水平居中*/
  align-items: center; /*垂直居中*/
  display: inline-flex;
  margin-right: 10px;
  border-radius: 10px;
  box-shadow: 0 0 4px #0000001a;
  cursor: pointer;
}
.icon-item a {
  color: black;
}

.el-dropdown-link,
a {
  color: #343a40;
}
</style>
