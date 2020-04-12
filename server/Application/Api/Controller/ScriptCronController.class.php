<?php
namespace Api\Controller;
use Think\Controller;
/*
    由网站前台脚本触发的周期任务
 */
class ScriptCronController extends BaseController {


    public function run(){
        set_time_limit(100);
        ini_set('memory_limit','800M');
        ignore_user_abort(true);

        //定期清理已删除项目和已删除页面
        $this->clean_deleted_data();

    }


    //定期清理已删除项目和已删除页面
    public function clean_deleted_data(){
        //30天前的已删除项目
        $items = D("Item")->where(" is_del = 1 and last_update_time < ".(time() - 30*24*60*60))->select();
        if ($items) {
            foreach ($items as $key => $value) {
                $ret = D("Item")->delete_item($value['item_id']);
            }
        }


        $pages = D("Page")->where(" is_del = 1 and addtime < ".(time() - 30*24*60*60))->select();
        if ($pages) {
            foreach ($pages as $key => $value) {
                $ret = D("Page")->deletePage($value['page_id']);
            }
        }

        $pages = D("Recycle")->where(" del_time < ".(time() - 30*24*60*60))->select();
        if ($pages) {
            foreach ($pages as $key => $value) {
                $ret = D("Page")->deletePage($value['page_id']);
                D("Recycle")->where(" id = '$value[id]' ")->delete();
            }
        }


    }




}