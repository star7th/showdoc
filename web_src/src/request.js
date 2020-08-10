/**
 *
 */

import axios from "@/http";
import router from "@/router/index";
import { MessageBox } from "element-ui";

const request = (path, data, method = "post", msgAlert = true) => {
  var params = new URLSearchParams(data);
  let url = DocConfig.server + path;
  return new Promise((resolve, reject) => {
    axios({
      url: url,
      method: method,
      data: params,
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      }
    })
      .then(
        response => {
          //超时登录
          if (
            response.data.error_code === 10102 &&
            response.config.data.indexOf("redirect_login=false") === -1
          ) {
            router.replace({
              path: "/user/login",
              query: { redirect: router.currentRoute.fullPath }
            });
            reject(new Error("登录态无效"));
          }

          if (msgAlert && response.data && response.data.error_code !== 0) {
            MessageBox.alert(response.data.error_message);
            return reject(new Error("业务级别的错误"));
          }
          //上面没有return的话，最后返回这个
          resolve(response.data);
        },
        err => {
          if (err.Cancel) {
            console.log(err);
          } else {
            reject(err);
          }
        }
      )
      .catch(err => {
        reject(err);
      });
  });
};

export default request;
