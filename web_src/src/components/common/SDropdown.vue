<template>
  <el-popover v-model="showPopover" :placement="placement" trigger="hover">
    <div class="more-card" :style="{ width: width }">
      <div class="more-card-header">
        <i :class="titleIcon + ' mr-3'"></i><span>{{ title }}</span>
        <i class="close-btn fas fa-close" @click="showPopover = false"></i>
      </div>
      <template v-if="menuListGroup" v-for="item in menuListGroup">
        <div class="group-bar">{{ item.group_name }}</div>
        <div
          v-for="item2 in item.listMenu"
          class="more-card-item"
          @click="item2.method"
        >
          <div class="more-card-item-left">
            <div class="more-card-item-icon">
              <i :class="item2.icon"></i>
            </div>
          </div>
          <div class="more-card-item-right">
            <div class="right-div">
              <div class="title">{{ item2.title }}</div>
              <div class="desc">
                {{ item2.desc }}
              </div>
            </div>
          </div>
        </div>
      </template>
      <template v-if="menuList" v-for="item in menuList">
        <div class="more-card-item" @click="item.method">
          <div class="more-card-item-left">
            <div class="more-card-item-icon">
              <i :class="item.icon"></i>
            </div>
          </div>
          <div class="more-card-item-right">
            <div class="right-div">
              <div class="title">{{ item.title }}</div>
              <div class="desc">
                {{ item.desc }}
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!--  slot="reference" 表示这是popover显示在外面的html元素 -->
    <div slot="reference">
      <!-- // 这里是上层组件的slot -->
      <slot></slot>
    </div>
  </el-popover>
</template>

<script>
export default {
  name: 'SDropdown',
  props: {
    width: {
      type: String,
      required: false,
      default: '300px'
    },
    title: {
      type: String,
      required: false,
      default: ''
    },
    titleIcon: {
      type: String,
      required: false,
      default: 'el-icon-s-platform'
    },
    placement: {
      type: String,
      required: false,
      default: 'bottom-start'
    },
    menuList: {
      type: Array,
      required: false,
      default: []
    },
    menuListGroup: {
      type: Array,
      required: false,
      default: []
    }
  },
  data() {
    return {
      showPopover: false
    }
  },
  methods: {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.more-card {
  border-radius: 8px;
  padding-left: 20px;
  padding-right: 20px;
  padding-bottom: 20px;
  background: #f9f9f9;
}

.more-card-item {
  padding-left: 10px;
  height: 80px;
  display: flex;
  cursor: pointer;
}
.more-card-item:not(:first-child) {
  border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.more-card-header {
  height: 40px;
  line-height: 40px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.more-card-header .close-btn {
  float: right;
  cursor: pointer;
  line-height: 40px;
}

.group-bar {
  color: #9b9b9b;
  font-size: 11px;
  height: 40px;
  line-height: 40px;
}
.more-card-item-icon {
  background-color: white;
  width: 40px;
  height: 40px;
  justify-content: center; /*水平居中*/
  align-items: center; /*垂直居中*/
  display: inline-flex;
  margin-right: 20px;
  border-radius: 10px;
  box-shadow: 0 0 4px #0000001a;
}
.more-card-item-left,
.more-card-item-right {
  height: 80px;
  display: flex;
  justify-content: left;
  align-items: center;
}

.more-card-item-left {
  width: 60px;
}

.more-card-item-right .right-div {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: left;
}

.more-card-item-right .title {
  font-weight: 600;
  font-size: 14px;
  color: #343a40;
}
.more-card-item-right .desc {
  margin-top: 10px;
  font-size: 11px;
  color: #9b9b9b;
}
</style>
