<?php

namespace Api\Controller;

use Think\Controller;

class UpdateController extends BaseController
{

    //检测数据库并更新
    public function checkDb($showBack = true)
    {

        // 由于在BaseController的构造函数执行过一次升级了，所以这里不需要动作
        // 保留着是为了兼容历史

        if ($showBack) {
            $this->sendResult(array());
        }
    }
}
