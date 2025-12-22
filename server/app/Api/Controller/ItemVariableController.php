<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * 项目环境变量相关 Api（新架构）。
 */
class ItemVariableController extends BaseController
{
    /**
     * 保存环境变量（兼容旧接口 Api/ItemVariable/save）。
     *
     * 功能：
     * - 新增或更新项目的环境变量
     * - 权限检查（checkItemEdit）
     */
    public function save(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        $itemId = $this->getParam($request, 'item_id', 0);
        $envId = $this->getParam($request, 'env_id', 0);
        $varName = trim($this->getParam($request, 'var_name', ''));
        $varValue = trim($this->getParam($request, 'var_value', ''));

        // 检查编辑权限
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '您没有编辑权限');
        }

        // 保存环境变量
        $id = \App\Model\ItemVariable::save($itemId, $envId, $varName, $varValue, $uid);

        if ($id > 0) {
            // 兼容旧接口：返回 id 值（不是数组）
            return $this->json($response, [
                'error_code' => 0,
                'data' => $id,
            ]);
        } else {
            return $this->error($response, 10101, '保存失败');
        }
    }

    /**
     * 获取环境变量列表（兼容旧接口 Api/ItemVariable/getList）。
     *
     * 功能：
     * - 获取项目的环境变量列表
     * - 权限检查（checkItemEdit）
     * - 支持按环境 ID 筛选
     */
    public function getList(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        $itemId = $this->getParam($request, 'item_id', 0);
        $envId = $this->getParam($request, 'env_id', 0);

        // 检查编辑权限
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '您没有编辑权限');
        }

        // 获取环境变量列表
        $ret = \App\Model\ItemVariable::getList($itemId, $envId);

        // 按旧版行为，对 addtime 做格式化，避免前端直接展示时间戳
        if (!empty($ret) && is_array($ret)) {
            foreach ($ret as &$row) {
                if (isset($row['addtime']) && is_numeric($row['addtime'])) {
                    $row['addtime'] = date('Y-m-d H:i:s', (int) $row['addtime']);
                }
            }
            unset($row);
        }

        return $this->success($response, $ret ?: []);
    }

    /**
     * 删除环境变量（兼容旧接口 Api/ItemVariable/delete）。
     *
     * 功能：
     * - 删除指定的环境变量
     * - 权限检查（checkItemEdit）
     */
    public function delete(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        $itemId = $this->getParam($request, 'item_id', 0);
        $id = $this->getParam($request, 'id', 0);

        // 检查编辑权限
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '您没有编辑权限');
        }

        // 删除环境变量
        $ret = \App\Model\ItemVariable::delete($itemId, $id);

        if ($ret) {
            // 兼容旧接口：返回删除结果（true 或删除的行数）
            return $this->json($response, [
                'error_code' => 0,
                'data' => $ret,
            ]);
        } else {
            return $this->error($response, 10101, '删除失败');
        }
    }

    /**
     * 根据变量名删除环境变量（兼容旧接口 Api/ItemVariable/deleteByName）。
     *
     * 功能：
     * - 根据变量名删除环境变量
     * - 权限检查（checkItemEdit）
     */
    public function deleteByName(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        $itemId = $this->getParam($request, 'item_id', 0);
        $envId = $this->getParam($request, 'env_id', 0);
        $varName = $this->getParam($request, 'var_name', '');

        // 检查编辑权限
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '您没有编辑权限');
        }

        // 删除环境变量
        $ret = \App\Model\ItemVariable::deleteByName($itemId, $envId, $varName);

        if ($ret) {
            // 兼容旧接口：返回删除结果（true 或删除的行数）
            return $this->json($response, [
                'error_code' => 0,
                'data' => $ret,
            ]);
        } else {
            return $this->error($response, 10101, '删除失败');
        }
    }
}
