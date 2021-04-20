// mutation必须是同步操作。异步操作需使用action

const SOME_MUTATION = 'SOME_MUTATION'
export default {
  increment(state, payload) {
    state.count++
  },
  changeItemInfo(state, val) {
    state.item_info = val
  },
  changeOpenCatId(state, val) {
    state.open_cat_id = val
  },
  [SOME_MUTATION](state) {
    // mutate state
  }
}
