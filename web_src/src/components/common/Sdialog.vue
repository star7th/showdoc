<template>
  <div>
    <el-dialog
      class="sdialog"
      :width="width"
      :visible.sync="dialogVisible"
      :close-on-click-modal="false"
      @close="!isEmptyFunction(onCancel) ? onCancel() : goHome()"
      :show-close="false"
      :modal="modal"
      :append-to-body="true"
      :top="top"
      :close-on-press-escape="false"
    >
      <div slot="title" class="title-header">
        <span class="title-text  float-left mt-4"
          ><i :class="titleIcon"></i>&nbsp;{{ title }}</span
        >
        <span class="title-header-right float-right mr-2">
          <el-button v-if="btn1Text" :icon="btn1Icon" @click="btn1Medthod">
            {{ btn1Text }}
          </el-button>
          <el-button v-if="btn2Text" :icon="btn2Icon" @click="btn2Medthod">
            {{ btn2Text }}
          </el-button>
          <el-button v-if="btn3Text" :icon="btn3Icon" @click="btn3Medthod">
            {{ btn3Text }}
          </el-button>
          <el-button v-if="btn4Text" :icon="btn3Icon" @click="btn4Medthod">
            {{ btn3Text }}
          </el-button>
          <el-button
            class="close-btn"
            type="text"
            icon="el-icon-close"
            @click="!isEmptyFunction(onCancel) ? onCancel() : goHome()"
          ></el-button>
        </span>
      </div>
      <slot></slot>
      <div slot="footer" class="dialog-footer text-center">
        <el-button
          v-if="showCancel"
          class="v3-lg-btn"
          @click="!isEmptyFunction(onCancel) ? onCancel() : goHome()"
          >{{  cancelText ? cancelText :  $t('cancel') }}</el-button
        >
        <el-button
          class="v3-lg-btn"
          type="primary"
          @click="!isEmptyFunction(onOK) ? onOK() : goHome()"
          >{{  okText ? okText :  $t('confirm') }}</el-button
        >
      </div>
    </el-dialog>
  </div>
</template>

<script>
export default {
  name: 'SDialog',
  props: {
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
    modal: {
      type: Boolean,
      required: false,
      default: true
    },
    onCancel: {
      type: Function,
      required: false,
      default: () => {}
    },
    showCancel: {
      type: Boolean,
      required: false,
      default: true
    },
    onOK: {
      type: Function,
      required: false,
      default: () => {}
    },
    width: {
      type: String,
      required: false,
      default: '50%'
    },
    btn1Text: {
      type: String,
      required: false,
      default: ''
    },
    btn1Icon: {
      type: String,
      required: false,
      default: ''
    },
    btn1Medthod: {
      type: Function,
      required: false,
      default: () => {}
    },
    btn2Text: {
      type: String,
      required: false,
      default: ''
    },
    btn2Icon: {
      type: String,
      required: false,
      default: ''
    },
    btn2Medthod: {
      type: Function,
      required: false,
      default: () => {}
    },
    btn3Text: {
      type: String,
      required: false,
      default: ''
    },
    btn3Icon: {
      type: String,
      required: false,
      default: ''
    },
    btn3Medthod: {
      type: Function,
      required: false,
      default: () => {}
    },
    btn4Text: {
      type: String,
      required: false,
      default: ''
    },
    btn4Icon: {
      type: String,
      required: false,
      default: ''
    },
    btn4Medthod: {
      type: Function,
      required: false,
      default: () => {}
    },
    top: {
      type: String,
      required: false,
      default: '15vh'
    },
    cancelText: {
      type: String,
      required: false,
      default: ''
    },
    okText: {
      type: String,
      required: false,
      default: ''
    }
  },
  data() {
    return {
      dialogVisible: true
    }
  },
  methods: {
    // 判断是否为空函数
    isEmptyFunction(func) {
      if (typeof func != 'function') {
        console.log('请输入函数')
        return false
      }
      if (func.toString() == 'function _default() {}') {
        return true
      }
      let str = func.toString().replace(/\s+/g, '')
      str = str.match(/{.*}/g)[0]
      return str === '{}'
    },
    goHome() {
      this.$router.push({ path: '/item/index' })
    }
  },
  mounted() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.title-header {
  min-height: 40px;
  padding-bottom: 10px;
}
.title-header .el-button--text {
  color: #343a40 !important;
  font-weight: 400w;
}

.title-header-right .el-button {
  font-weight: 600;
}
.close-btn {
  font-size: 18px;
}
</style>

<style>
.sdialog .el-dialog__header {
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.sdialog .el-dialog__footer {
  border-top: 1px solid rgba(0, 0, 0, 0.05);
  padding-top: 20px;
}
</style>
