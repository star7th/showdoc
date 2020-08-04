// mutation必须是同步操作。异步操作需使用action

const SOME_MUTATION = 'SOME_MUTATION'
export default {
  increment(state, payload) {
    state.count++
  },
  [SOME_MUTATION](state) {
    // mutate state
  }
}
