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
        <span class="title-header-left float-left "
          ><i :class="titleIcon"></i>&nbsp;&nbsp;{{ title }}</span
        >
        <span class="title-header-right float-right mr-2">
          <div v-if="btn1Text" class="btn-div" @click="btn1Medthod">
            <i :class="btn1Icon"></i> {{ btn1Text }}
          </div>
          <div v-if="btn2Text" class="btn-div" @click="btn2Medthod">
            <i :class="btn2Icon"></i> {{ btn2Text }}
          </div>
          <div v-if="btn3Text" class="btn-div" @click="btn3Medthod">
            <i :class="btn3Icon"></i> {{ btn3Text }}
          </div>
          <div v-if="btn4Text" class="btn-div" @click="btn4Medthod">
            <i :class="btn4Icon"></i> {{ btn4Text }}
          </div>

          <i
            class="close-btn far fa-close"
            @click="!isEmptyFunction(onCancel) ? onCancel() : goHome()"
          ></i>
        </span>
      </div>
      <slot></slot>
      <div slot="footer" class="dialog-footer text-center">
        <el-button
          v-if="showCancel"
          class="v3-lg-btn"
          @click="!isEmptyFunction(onCancel) ? onCancel() : goHome()"
          >{{ cancelText ? cancelText : $t('cancel') }}</el-button
        >
        <el-button
          v-if="showOk"
          class="v3-lg-btn"
          type="primary"
          @click="!isEmptyFunction(onOK) ? onOK() : goHome()"
          >{{ okText ? okText : $t('confirm') }}</el-button
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
    showOk: {
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
      dialogVisible: false
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
  mounted() {
    setTimeout(() => {
      this.dialogVisible = true
    }, 200)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.title-header {
  height: 40px;
  padding-bottom: 10px;
  font-size: 13px;
  color: #343a40;
}

.title-header-left {
  line-height: 50px;
}

.title-header-right {
  line-height: 50px;
}

.close-btn {
  font-size: 16px;
  cursor: pointer;
}
.btn-div {
  height: 36px;
  background: #ffffff;
  border-radius: 8px;
  line-height: 36px;
  display: inline-block;
  cursor: pointer;
  padding-left: 15px;
  padding-right: 15px;
  margin-right: 10px;
  font-weight: 600;
  color: #343a40;
}
</style>

<style>
.sdialog .el-dialog__header {
  padding-top: 0px;
  padding-bottom: 0px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.sdialog .el-dialog__footer {
  border-top: 1px solid rgba(0, 0, 0, 0.05);
  padding-top: 20px;
}
</style>
