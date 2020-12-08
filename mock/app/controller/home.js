'use strict';
var Mock = require('mockjs');

const Controller = require('egg').Controller;

class HomeController extends Controller {
  async index() {
    const { ctx } = this;
    ctx.body = 'hi, egg';
  }

  async mock() {
    const { ctx } = this;
    var data = '';
    var template = ctx.request.body.template ;
    /* 
    template = {
	  'list|20':[{
	        'id|+1':1,
	        'serial_number|1-100':1,
	        'warn_number|1-100':1,
	        'warn_name|1':['流水线编排服务异常','磁盘占用超过阈值'],
	        'warn_level|1':['紧急','重要'],
	        'warn_detail':'环境IP:127.0.0.1,服务名称:XX',
	        'create_time':'@date("yyyy-MM-dd")',
	        'finish_time':'@date("yyyy-MM-dd")',
	        'contact|4':'abc'
	    }] 
	 };
	 */
    try{
    	data =Mock.mock(JSON.parse(template));
    }catch(e){
    	data = '为了服务器安全，只允许符合json语法的字符串'
    }
    ctx.body = data;
  }

}

module.exports = HomeController;
