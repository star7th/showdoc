/**
 * http配置
 */

import axios from 'axios'
import router from '@/router/index'

// axios 配置
axios.defaults.timeout = 20000;
axios.defaults.baseURL = DocConfig.server;

// http request 拦截器
axios.interceptors.request.use(
    config => {
        //if (store.state.token) {
           // config.headers.Authorization = `token ${store.state.token}`;
        //}
        return config;
    },
    err => {
        return Promise.reject(err);
    });

// http response 拦截器
axios.interceptors.response.use(
    response => {
        if (response.config.data && response.config.data.indexOf("redirect_login=false") > -1 ) {
            //不跳转到登录
        }
        else if (response.data.error_code === 10102 ) {
            router.replace({
                path: '/user/login',
                query: {redirect: router.currentRoute.fullPath}
            });
        }
        return response;
    },
    error => {
        // console.log(JSON.stringify(error));//console : Error: Request failed with status code 402
        return Promise.reject(error.response.data)
    });

export default axios;