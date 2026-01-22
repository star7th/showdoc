<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Model\Item;
use App\Model\ItemMember;
use App\Model\TeamItemMember;
use App\Model\User;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 管理后台项目相关 Api（开源版）
 */
class AdminItemController extends BaseController
{
    /**
     * 获取所有项目列表（兼容旧接口 Api/AdminItem/getList）
     */
    public function getList(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $itemName = $this->getParam($request, 'item_name', '');
        $page = $this->getParam($request, 'page', 1); 
        $count = $this->getParam($request, 'count', 20);
        $positiveType = $this->getParam($request, 'positive_type', 0);
        $itemType = $this->getParam($request, 'item_type', 0);
        $privacyType = $this->getParam($request, 'privacy_type', 0);
        $username = $this->getParam($request, 'username', '');
        $isDel = $this->getParam($request, 'is_del', 0); // 0: 正常；1: 已删除

        // 构建查询
        $query = DB::table('item');
        
        if ($isDel == 1) {
            $query->where('is_del', 1);
        } else {
            $query->where('is_del', 0);
        }

        if (!empty($itemName)) {
            $likeItem = $this->safeLike($itemName);
            $query->where('item_name', 'like', "%{$likeItem}%");
        }

        if (!empty($username)) {
            $likeUser = $this->safeLike($username);
            $query->where('username', 'like', "%{$likeUser}%");
        }

        // 根据项目类型过滤
        if ($itemType > 0) {
            $query->where('item.item_type', $itemType);
        }

        // 公开项目/私密项目
        if ($privacyType > 1) {
            if ($privacyType == 2) {
                $query->where('item.password', '');
            } elseif ($privacyType == 3) {
                $query->where('item.password', '!=', '');
            }
        }

        // 已删除项目按删除时间倒序（使用 last_update_time 作为删除时间），正常项目按创建时间倒序
        if ($isDel == 1) {
            $query->orderBy('last_update_time', 'desc');
        } else {
            $query->orderBy('addtime', 'desc');
        }

        $total = $query->count();
        $items = $query->offset(($page - 1) * $count)->limit($count)->get();

        $return = [];
        $return['total'] = (int) $total;
        $return['items'] = [];

        if ($items) {
            foreach ($items as $item) {
                $itemData = (array) $item;
                $itemData['addtime'] = date("Y-m-d H:i:s", $itemData['addtime']);
                
                if ($isDel == 1) {
                    $itemData['del_time'] = date("Y-m-d H:i:s", (int) $itemData['last_update_time']);
                }

                // 计算成员数
                $memberNum = DB::table('item_member')->where('item_id', $item->item_id)->count() 
                    + DB::table('team_item_member')->where('item_id', $item->item_id)->count();
                $itemData['member_num'] = $memberNum;

                $return['items'][] = $itemData;
            }
        }

        return $this->success($response, $return);
    }

    /**
     * 删除项目（软删除）
     */
    public function deleteItem(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $itemId = $this->getParam($request, 'item_id', 0);
        if ($itemId <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        $result = Item::softDeleteItem($itemId);
        if (!$result) {
            return $this->error($response, 10101, '删除失败');
        }

        return $this->success($response, []);
    }

    /**
     * 恢复已删除项目
     */
    public function recoverItem(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $itemId = $this->getParam($request, 'item_id', 0);
        if ($itemId <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        $item = DB::table('item')->where('item_id', $itemId)->first();
        if (!$item || (int) $item->is_del !== 1) {
            return $this->error($response, 10101, '项目不存在或未被删除');
        }

        // 恢复项目与页面
        DB::table('page')->where('item_id', $itemId)->update(['is_del' => 0]);
        $ret = DB::table('item')
            ->where('item_id', $itemId)
            ->update(['is_del' => 0, 'last_update_time' => time()]);

        if (!$ret) {
            return $this->error($response, 10101, '恢复失败');
        }

        return $this->success($response, []);
    }

    /**
     * 永久删除项目（硬删除，不可恢复）
     */
    public function hardDeleteItem(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $itemId = $this->getParam($request, 'item_id', 0);
        if ($itemId <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        $item = DB::table('item')->where('item_id', $itemId)->first();
        if (!$item || (int) $item->is_del !== 1) {
            return $this->error($response, 10101, '仅允许对已删除项目执行永久删除');
        }

        $ret = Item::deleteItem($itemId);
        if (!$ret) {
            return $this->error($response, 10101, '删除失败');
        }

        return $this->success($response, []);
    }

    /**
     * 转让项目
     */
    public function attorn(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $username = $this->getParam($request, 'username', '');
        $itemId = $this->getParam($request, 'item_id', 0);

        if (empty($username) || $itemId <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        $item = DB::table('item')->where('item_id', $itemId)->first();
        if (!$item) {
            return $this->error($response, 10101, '项目不存在');
        }

        $member = User::findByUsername($username);
        if (!$member) {
            return $this->error($response, 10209, '用户不存在');
        }

        $ret = DB::table('item')
            ->where('item_id', $itemId)
            ->update([
                'username' => $member->username,
                'uid' => $member->uid,
            ]);

        if (!$ret) {
            return $this->error($response, 10101, '转让失败');
        }

        $return = DB::table('item')->where('item_id', $itemId)->first();
        return $this->success($response, (array) $return);
    }
}

