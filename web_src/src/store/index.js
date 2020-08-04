import Vue from 'vue'
import Vuex from 'vuex'
import mutations from './mutations'
import actions from './actions'

Vue.use(Vuex)

const state = {
  count: '1'
}

export default new Vuex.Store({
  state,
  actions,
  mutations
})
