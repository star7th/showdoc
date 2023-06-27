import ContextmenuModal from './index.vue'
import Vue from 'vue'

export default function({ x = 0, y = 0, list = [] }) {
  const mountNode = document.createElement('div')
  const randomString = Math.random()
    .toString(36)
    .substring(2)
  mountNode.id = randomString
  document.body.appendChild(mountNode)
  const mountNode2 = document.createElement('div')
  mountNode.appendChild(mountNode2)
  const el = new Vue({
    render: h =>
      h(ContextmenuModal, {
        props: {
          x,
          y,
          list,
          onCancel: () => {
            el.$destroy()
            var element = document.getElementById(randomString) // Get the element by its ID
            if (element) {
              element.parentNode.removeChild(element) // Remove the element from its parent node
            }
          }
        }
      })
  })
  el.$mount(mountNode2)
}
