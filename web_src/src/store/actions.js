// action比mutation的好处是可以任意异步。本来mutation只能同步。现在通过action封装，可以任意异步操作数据

export default {
  incrementAsync({ commit }) {
    setTimeout(() => {
      commit('increment')
    }, 1000)
  },
  changeItemInfo(ctx, val) {
    // console.log(val)  //val是dispatch派发传递过来的值
    // console.log(ctx)  //ctx是上下文，必传
    ctx.commit('changeItemInfo', val)// commit到mutation
  },
  changeOpenCatId(ctx, val) {
    // console.log(val)  //val是dispatch派发传递过来的值
    // console.log(ctx)  //ctx是上下文，必传
    ctx.commit('changeOpenCatId', val)// commit到mutation
  }
}
