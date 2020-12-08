import Vue from 'vue'
import Vuex from 'vuex'
import mutations from './mutations'
import actions from './actions'
import state from './state'

Vue.use(Vuex)

export default new Vuex.Store({
  state,
  actions,
  mutations
})
