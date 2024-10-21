import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex)

export default new Vuex.Store({
  state: {
    item_info: {},
    item_key: 1, // 用来标识组件key，以方便更新item组件的
    open_cat_id: 0,
    user_info: {},
    new_msg: 0
  },
  actions: {
    changeItemInfo(ctx, val) {
      // console.log(val)  //val是dispatch派发传递过来的值
      // console.log(ctx)  //ctx是上下文，必传
      ctx.commit('changeItemInfo', val) // commit到mutation
    },
    reloadItem(ctx) {
      ctx.commit('reloadItem')
    },
    changeOpenCatId(ctx, val) {
      ctx.commit('changeOpenCatId', val)
    },
    changeUserInfo(ctx, val) {
      ctx.commit('changeUserInfo', val)
    },
    changeNewMsg(ctx, val) {
      ctx.commit('changeNewMsg', val)
    }
  },
  mutations: {
    changeItemInfo(state, val) {
      state.item_info = val
    },
    reloadItem(state) {
      state.item_key++
    },
    changeOpenCatId(state, val) {
      state.open_cat_id = val
    },
    changeUserInfo(state, val) {
      state.user_info = val
    },
    changeNewMsg(state, val) {
      state.new_msg = val
    }
  }
})
