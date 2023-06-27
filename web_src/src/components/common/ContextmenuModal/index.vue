<template>
  <div>
    <div
      class="contextmenu-modal"
      :class="{ show: show }"
      @click.stop="closeHandle"
      @dblclick.stop="closeHandle"
      @contextmenu.stop="closeHandle"
    >
      <div
        class="container"
        ref="contextmenuRef"
        :style="{ top: `${top}px`, left: `${left}px` }"
        @click.stop
      >
        <div class="column">
          <div
            class="contextmenu-item bgColor-select"
            v-for="(item, index) in useList"
            :key="index"
            v-show="!item.hidden"
            @click="clickHandle(item)"
            @dblclick.stop
            @contextmenu.stop
          >
            <div class="line-container text-default">
              <div class="item-container">
                <img class="img" v-if="item.img" :src="item.img" />
                <div class="icon" v-if="item.icon">
                  <i :class="item.icon"> </i>
                </div>
                <div class="text">{{ item.text }}</div>
              </div>

              <div class="checked" v-if="item.checked"></div>
              <div
                class="arrow text-secondary"
                v-if="item.children && item.children.length"
              >
                <i class="fas fa-angle-right"> </i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  props: {
    x: {
      type: Number,
      required: true
    },
    y: {
      type: Number,
      required: true
    },
    list: {
      type: Array,
      required: true
    },
    onCancel: {
      type: Function,
      required: true
    }
  },
  data() {
    return {
      show: false,
      contextmenuRef: null,
      top: 0,
      left: 0,
      width: 0
    }
  },
  computed: {
    useList() {
      return this.list.filter(v => !v.hidden)
    }
  },
  mounted() {
    const clientWidth = window.innerWidth
    const clientHeight = window.innerHeight
    let tempTop = this.y
    let tempLeft = this.x
    this.$nextTick(() => {
      if (this.$refs.contextmenuRef.clientWidth + tempLeft > clientWidth - 5) {
        tempLeft = clientWidth - this.$refs.contextmenuRef.clientWidth - 5
      }
      if (this.$refs.contextmenuRef.clientHeight + tempTop > clientHeight - 5) {
        tempTop = clientHeight - this.$refs.contextmenuRef.clientHeight - 5
      }
      this.top = tempTop
      this.left = tempLeft
      this.width = this.$refs.contextmenuRef.clientWidth
      setTimeout(() => {
        this.show = true
      })
    })
  },
  methods: {
    clickHandle(item) {
      if (item.children) return
      this.closeHandle()
      item.onclick && item.onclick()
    },
    closeHandle() {
      this.show = false
      setTimeout(() => {
        this.onCancel()
      }, 100)
    }
  }
}
</script>

<style scoped>
.contextmenu-modal {
  position: fixed;
  top: 0;
  left: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  opacity: 0;
  transition: opacity 100ms ease-in-out;
  z-index: 1000;
  user-select: none;
  -webkit-app-region: no-drag;
}

.contextmenu-modal.show {
  opacity: 1;
}

.contextmenu-modal.show .container {
  transform: scale(1);
}

.container {
  position: absolute;
  max-height: 80vh;
  max-width: 200px;
  background-color: #f9f9f9;
  border: 1px solid #0000000d;
  border-radius: 8px;
  box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.1);
  transform-origin: left top;
  transform: scale(0.3);
  transition: transform 100ms ease-in-out;
  z-index: 1;
  font-size: 13px;
}

.container::-webkit-scrollbar {
  width: 0 !important;
  display: none;
}

.column {
  max-height: 80vh;
}

.contextmenu-item {
  display: flex;
  align-items: center;
  padding: 0 10px;
  cursor: pointer;
  overflow: unset;
}

.contextmenu-item:first-of-type {
  border-top-left-radius: 8px;
  border-top-right-radius: 8px;
}

.contextmenu-item:first-of-type::after {
  border-top-left-radius: 8px;
  border-top-right-radius: 8px;
}

.contextmenu-item:last-of-type {
  border-bottom-left-radius: 8px;
  border-bottom-right-radius: 8px;
}

.contextmenu-item:last-of-type::after {
  border-bottom-left-radius: 8px;
  border-bottom-right-radius: 8px;
}

.contextmenu-item:last-of-type .line-container {
  border: none;
}

.contextmenu-item:hover .item-container .children {
  opacity: 1;
  pointer-events: unset;
}

.contextmenu-item .line-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  border-bottom: 1px solid var(--color-interval);
}

.contextmenu-item .item-container {
  display: flex;
  align-items: center;
  height: 50px;
  padding: 0 10px 0 5px;
}

.contextmenu-item .img {
  width: 30px;
  height: 30px;
  margin-right: 10px;
  border-radius: 50%;
  object-fit: cover;
}

.contextmenu-item .icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 25px;
}

.contextmenu-item .text {
  margin-left: 5px;
  white-space: nowrap;
}

.contextmenu-item .children {
  opacity: 0;
  transition: opacity 100ms ease-in-out;
  pointer-events: none;
}

.contextmenu-item .checked {
  font-size: 12px;
  margin: 0 10px;
}

.bgColor-select {
  position: relative;
  overflow: hidden;
}
.bgColor-select::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
}
.bgColor-select:hover::after,
.bgColor-select:active::after,
.bgColor-select.active::after {
  background-color: rgba(0, 0, 0, 0.03);
}
</style>
