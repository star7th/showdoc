<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Model\RunapiFlow;
use App\Model\RunapiFlowPage;
use App\Model\RunapiEnv;
use App\Model\Page;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 流程相关 Api（新架构）。
 */
class FlowController extends BaseController
{
    private $pages = null;

    /**
     * 保存流程（兼容旧接口 Api/Flow/save）。
     */
    public function save(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $id = $this->getParam($request, 'id', 0);
        $flowName = $this->getParam($request, 'flow_name', '');
        $itemId = $this->getParam($request, 'item_id', 0);
        $envId = $this->getParam($request, 'env_id', 0);
        $times = $this->getParam($request, 'times', 1);
        $timeInterval = $this->getParam($request, 'time_interval', 0);
        $errorContinue = $this->getParam($request, 'error_continue', 1);
        $saveChange = $this->getParam($request, 'save_change', 1);

        $uid = (int) ($loginUser['uid'] ?? 0);
        $dateTime = date('Y-m-d H:i:s');

        if ($id > 0) {
            // 更新现有流程
            $flow = RunapiFlow::findById($id);
            if (!$flow) {
                return $this->error($response, 10101, '流程不存在');
            }

            if (!$this->checkItemEdit($uid, (int) ($flow->item_id ?? 0))) {
                return $this->error($response, 10303, '没有权限');
            }

            $data = ['last_update_time' => $dateTime];
            if (!empty($flowName)) {
                $data['flow_name'] = $flowName;
            }
            if ($request->getParsedBody() && array_key_exists('env_id', $request->getParsedBody())) {
                $data['env_id'] = $envId;
            }
            if ($request->getParsedBody() && array_key_exists('times', $request->getParsedBody())) {
                $data['times'] = $times;
            }
            if ($request->getParsedBody() && array_key_exists('time_interval', $request->getParsedBody())) {
                $data['time_interval'] = $timeInterval;
            }
            if ($request->getParsedBody() && array_key_exists('error_continue', $request->getParsedBody())) {
                $data['error_continue'] = $errorContinue;
            }
            if ($request->getParsedBody() && array_key_exists('save_change', $request->getParsedBody())) {
                $data['save_change'] = $saveChange;
            }

            RunapiFlow::update($id, $data);
            $finalId = $id;
        } else {
            // 创建新流程
            if ($itemId <= 0) {
                return $this->error($response, 10101, '项目ID不能为空');
            }

            if (!$this->checkItemEdit($uid, $itemId)) {
                return $this->error($response, 10303, '没有权限');
            }

            $data = [
                'username'        => $loginUser['username'] ?? '',
                'uid'            => $uid,
                'flow_name'      => $flowName,
                'env_id'         => $envId,
                'item_id'        => $itemId,
                'times'          => $times,
                'time_interval'  => $timeInterval,
                'error_continue' => $errorContinue,
                'save_change'    => $saveChange,
                'addtime'        => $dateTime,
                'last_update_time' => $dateTime,
            ];

            // 如果环境小于等于0，尝试获取项目的第一个环境变量赋值
            if ($envId <= 0) {
                $envList = RunapiEnv::getListByItemId($itemId);
                if (!empty($envList)) {
                    $data['env_id'] = (int) ($envList[0]['id'] ?? 0);
                }
            }

            $finalId = RunapiFlow::add($data);
            if (!$finalId) {
                return $this->error($response, 10101, '创建失败');
            }
        }

        // 延迟 300ms（兼容旧代码）
        usleep(300000);

        $res = RunapiFlow::findById($finalId);
        if (!$res) {
            return $this->error($response, 10101, '获取流程信息失败');
        }

        return $this->success($response, (array) $res);
    }

    /**
     * 获取流程列表（兼容旧接口 Api/Flow/getList）。
     */
    public function getList(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $itemId = $this->getParam($request, 'item_id', 0);
        $uid = (int) ($loginUser['uid'] ?? 0);

        if ($itemId <= 0) {
            return $this->error($response, 10101, '项目ID不能为空');
        }

        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '没有权限');
        }

        $ret = RunapiFlow::getListByItemId($itemId);
        if (!empty($ret)) {
            return $this->success($response, $ret);
        }

        return $this->success($response, []);
    }

    /**
     * 删除流程（兼容旧接口 Api/Flow/delete）。
     */
    public function delete(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $id = $this->getParam($request, 'id', 0);
        $uid = (int) ($loginUser['uid'] ?? 0);

        if ($id <= 0) {
            return $this->error($response, 10101, '流程ID不能为空');
        }

        $flow = RunapiFlow::findById($id);
        if (!$flow) {
            return $this->error($response, 10101, '流程不存在');
        }

        if (!$this->checkItemEdit($uid, (int) ($flow->item_id ?? 0))) {
            return $this->error($response, 10303, '没有权限');
        }

        $ret = RunapiFlow::delete($id);
        if ($ret) {
            return $this->success($response, ['success' => true]);
        }

        return $this->error($response, 10103, '删除失败');
    }

    /**
     * 新增接口到流程中（兼容旧接口 Api/Flow/addFlowPage）。
     */
    public function addFlowPage(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $flowId = $this->getParam($request, 'flow_id', 0);
        $pageId = $this->getParam($request, 'page_id', 0);
        $uid = (int) ($loginUser['uid'] ?? 0);

        if ($flowId <= 0 || $pageId <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        $flow = RunapiFlow::findById($flowId);
        if (!$flow) {
            return $this->error($response, 10101, '流程不存在');
        }

        if (!$this->checkItemEdit($uid, (int) ($flow->item_id ?? 0))) {
            return $this->error($response, 10303, '没有权限');
        }

        $page = Page::findById($pageId);
        if (!$page) {
            return $this->error($response, 10101, '页面不存在');
        }

        $pageItemId = (int) ($page['item_id'] ?? 0);
        if (!$this->checkItemEdit($uid, $pageItemId)) {
            return $this->error($response, 10303, '没有权限');
        }

        // 获取该流程的最后一个页面的顺序号
        $sNumber = RunapiFlowPage::getLastSNumber($flowId) + 1;

        $id = RunapiFlowPage::add([
            'flow_id'  => $flowId,
            'page_id'  => $pageId,
            's_number' => $sNumber,
            'addtime'  => date('Y-m-d H:i:s'),
        ]);

        if ($id) {
            return $this->success($response, ['id' => $id]);
        }

        return $this->error($response, 10101, '添加失败');
    }

    /**
     * 从流程中删除接口（兼容旧接口 Api/Flow/deleteFlowPage）。
     */
    public function deleteFlowPage(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $id = $this->getParam($request, 'id', 0);
        $uid = (int) ($loginUser['uid'] ?? 0);

        if ($id <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        $flowPage = RunapiFlowPage::findById($id);
        if (!$flowPage) {
            return $this->error($response, 10101, '流程页面不存在');
        }

        $pageId = (int) ($flowPage->page_id ?? 0);
        $page = Page::findById($pageId);
        if (!$page) {
            return $this->error($response, 10101, '页面不存在');
        }

        $pageItemId = (int) ($page['item_id'] ?? 0);
        if (!$this->checkItemEdit($uid, $pageItemId)) {
            return $this->error($response, 10303, '没有权限');
        }

        $res = RunapiFlowPage::delete($id);
        if ($res) {
            return $this->success($response, ['success' => true]);
        }

        return $this->error($response, 10101, '删除失败');
    }

    /**
     * 获取某个流程里的接口列表（兼容旧接口 Api/Flow/getFlowPageList）。
     */
    public function getFlowPageList(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $flowId = $this->getParam($request, 'flow_id', 0);
        $uid = (int) ($loginUser['uid'] ?? 0);

        if ($flowId <= 0) {
            return $this->error($response, 10101, '流程ID不能为空');
        }

        $flow = RunapiFlow::findById($flowId);
        if (!$flow) {
            return $this->error($response, 10101, '流程不存在');
        }

        if (!$this->checkItemEdit($uid, (int) ($flow->item_id ?? 0))) {
            return $this->error($response, 10303, '没有权限');
        }

        $res = RunapiFlowPage::getListByFlowId($flowId);
        if (!empty($res)) {
            $itemId = (int) ($flow->item_id ?? 0);
            foreach ($res as $key => &$value) {
                $pageId = (int) ($value['page_id'] ?? 0);
                $value['page_title'] = $this->getPageTitle($itemId, $pageId);
            }
            return $this->success($response, $res);
        }

        return $this->success($response, []);
    }

    /**
     * 保存顺序关系（兼容旧接口 Api/Flow/saveSort）。
     */
    public function saveSort(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $flowId = $this->getParam($request, 'flow_id', 0);
        $orders = $this->getParam($request, 'orders', '');
        $uid = (int) ($loginUser['uid'] ?? 0);

        if ($flowId <= 0 || empty($orders)) {
            return $this->error($response, 10101, '参数错误');
        }

        $flow = RunapiFlow::findById($flowId);
        if (!$flow) {
            return $this->error($response, 10101, '流程不存在');
        }

        if (!$this->checkItemEdit($uid, (int) ($flow->item_id ?? 0))) {
            return $this->error($response, 10303, '没有权限');
        }

        $dataArray = json_decode(htmlspecialchars_decode($orders), true);
        if ($dataArray) {
            RunapiFlowPage::updateSort($flowId, $dataArray);
            return $this->success($response, []);
        }

        return $this->success($response, []);
    }

    /**
     * 保存启用关系（兼容旧接口 Api/Flow/setFlowPageEnabled）。
     */
    public function setFlowPageEnabled(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $flowId = $this->getParam($request, 'flow_id', 0);
        $ids = $this->getParam($request, 'ids', '');
        $uid = (int) ($loginUser['uid'] ?? 0);

        if ($flowId <= 0 || empty($ids)) {
            return $this->error($response, 10101, '参数错误');
        }

        $flow = RunapiFlow::findById($flowId);
        if (!$flow) {
            return $this->error($response, 10101, '流程不存在');
        }

        if (!$this->checkItemEdit($uid, (int) ($flow->item_id ?? 0))) {
            return $this->error($response, 10303, '没有权限');
        }

        $dataArray = json_decode(htmlspecialchars_decode($ids), true);
        if ($dataArray) {
            RunapiFlowPage::setEnabled($flowId, $dataArray);
            return $this->success($response, []);
        }

        return $this->success($response, []);
    }

    /**
     * 获取页面标题（私有辅助方法）
     *
     * @param int $itemId 项目 ID
     * @param int $pageId 页面 ID
     * @return string|false 页面标题，不存在返回 false
     */
    private function getPageTitle(int $itemId, int $pageId)
    {
        if ($this->pages === null) {
            $ret = DB::table('page')
                ->where('item_id', $itemId)
                ->get()
                ->all();

            if ($ret) {
                $this->pages = [];
                foreach ($ret as $row) {
                    $this->pages[] = (array) $row;
                }
            } else {
                $this->pages = [];
                return false;
            }
        }

        foreach ($this->pages as $value) {
            if ((int) ($value['page_id'] ?? 0) === $pageId) {
                return $value['page_title'] ?? '';
            }
        }

        return false;
    }
}
