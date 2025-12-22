<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Model\RunapiEnv;
use App\Model\RunapiEnvSelectd;
use App\Model\RunapiGlobalParam;
use App\Model\RunapiDbConfig;
use App\Model\ItemVariable;
use App\Model\Runapi;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class RunapiController extends BaseController
{
    /**
     * 添加/更新环境
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function addEnv(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $envId = $this->getParam($request, 'env_id', 0);
        $envName = $this->getParam($request, 'env_name', '');
        $itemId = $this->getParam($request, 'item_id', 0);

        // 权限检查
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '没有权限');
        }

        if (empty($envName)) {
            return $this->error($response, 10101, '环境名称不能为空');
        }

        if ($envId > 0) {
            // 更新
            $res = RunapiEnv::update($envId, $itemId, [
                'env_name'        => $envName,
                'uid'             => $uid,
                'last_update_time' => date('Y-m-d H:i:s'),
            ]);
            if ($res) {
                return $this->success($response, ['env_id' => $envId]);
            }
        } else {
            // 新建
            $envId = RunapiEnv::add([
                'env_name'        => $envName,
                'item_id'         => $itemId,
                'uid'             => $uid,
                'addtime'          => date('Y-m-d H:i:s'),
                'last_update_time' => date('Y-m-d H:i:s'),
            ]);
            if ($envId) {
                return $this->success($response, ['env_id' => $envId]);
            }
        }

        return $this->error($response, 10500, '操作失败');
    }

    /**
     * 更新环境（兼容旧接口）
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function updateEnv(Request $request, Response $response): Response
    {
        return $this->addEnv($request, $response);
    }

    /**
     * 获取环境列表
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getEnvList(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $itemId = $this->getParam($request, 'item_id', 0);

        // 权限检查
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '没有权限');
        }

        $res = RunapiEnv::getListByItemId($itemId);
        if (!empty($res)) {
            return $this->success($response, $res);
        } else {
            // 如果尚未有环境，则帮其创建一个默认环境
            $envId = RunapiEnv::add([
                'env_name'        => '默认环境',
                'item_id'         => $itemId,
                'uid'             => $uid,
                'addtime'          => date('Y-m-d H:i:s'),
                'last_update_time' => date('Y-m-d H:i:s'),
            ]);
            if ($envId) {
                // 并且把项目变量都绑定到该默认环境中
                ItemVariable::updateEnvIdForItem($itemId, $envId);
                sleep(1);
                return $this->getEnvList($request, $response);
            }
        }

        return $this->success($response, []);
    }

    /**
     * 删除环境
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function delEnv(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $envId = $this->getParam($request, 'env_id', 0);

        $env = RunapiEnv::findById($envId);
        if (!$env) {
            return $this->error($response, 10101, '环境不存在');
        }

        $itemId = (int) $env['item_id'];

        // 权限检查
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '没有权限');
        }

        // 删除选中的环境记录
        RunapiEnvSelectd::deleteByEnvId($envId);
        // 删除环境
        RunapiEnv::delete($envId);
        // 删除该环境下的变量
        ItemVariable::deleteByEnvId($envId);

        return $this->success($response, []);
    }

    /**
     * 设置某个环境变量为选中
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function selectEnv(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $envId = $this->getParam($request, 'env_id', 0);

        $env = RunapiEnv::findById($envId);
        if (!$env) {
            return $this->error($response, 10101, '环境不存在');
        }

        $itemId = (int) $env['item_id'];

        // 权限检查
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '没有权限');
        }

        // 先删除旧的选中记录
        RunapiEnvSelectd::deleteByItemIdAndUid($itemId, $uid);
        // 添加新的选中记录
        $res = RunapiEnvSelectd::add([
            'item_id' => $itemId,
            'uid'     => $uid,
            'env_id'  => $envId,
        ]);

        if ($res) {
            return $this->success($response, ['id' => $res]);
        } else {
            return $this->success($response, []);
        }
    }

    /**
     * 获取用户选中的环境
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getSelectEnv(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $itemId = $this->getParam($request, 'item_id', 0);

        // 权限检查
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '没有权限');
        }

        $res = RunapiEnvSelectd::findByItemIdAndUid($itemId, $uid);
        if ($res) {
            return $this->success($response, $res);
        } else {
            return $this->success($response, ['env_id' => 0]);
        }
    }

    /**
     * 获取全局参数
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getGlobalParam(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $itemId = $this->getParam($request, 'item_id', 0);

        // 权限检查
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '没有权限');
        }

        $return = Runapi::getGlobalParam($itemId);
        return $this->success($response, $return);
    }

    /**
     * 修改全局参数
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function updateGlobalParam(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $itemId = $this->getParam($request, 'item_id', 0);
        $paramType = $this->getParam($request, 'param_type', '');
        $contentJsonStr = $this->getParam($request, 'content_json_str', '');

        // 权限检查
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '没有权限');
        }

        $res = RunapiGlobalParam::update($itemId, $paramType, $contentJsonStr);
        if ($res) {
            return $this->success($response, ['result' => true]);
        } else {
            return $this->success($response, []);
        }
    }

    /**
     * 获取数据库连接列表（按项目+环境）
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getDbConfigList(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $itemId = $this->getParam($request, 'item_id', 0);
        $envId = $this->getParam($request, 'env_id', 0);

        // 权限检查
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '没有权限');
        }

        if (!$envId) {
            return $this->error($response, 10101, '缺少 env_id');
        }

        $list = RunapiDbConfig::getListByItemIdAndEnvId($itemId, $envId);
        return $this->success($response, $list ?: []);
    }

    /**
     * 新增/更新数据库连接配置
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function saveDbConfig(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $configId = $this->getParam($request, 'config_id', 0);
        $itemId = $this->getParam($request, 'item_id', 0);
        $envId = $this->getParam($request, 'env_id', 0);
        $configName = $this->getParam($request, 'config_name', '默认');
        $dbType = $this->getParam($request, 'db_type', 'mysql');
        $host = $this->getParam($request, 'host', '');
        $port = $this->getParam($request, 'port', 0);
        $username = $this->getParam($request, 'username', '');
        $password = $this->getParam($request, 'password', '');
        $database = $this->getParam($request, 'database', '');
        $options = $this->getParam($request, 'options', '');
        $isDefault = $this->getParam($request, 'is_default', 0);

        // 权限检查
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '没有权限');
        }

        if (!$envId) {
            return $this->error($response, 10101, '缺少 env_id');
        }

        // 开源版保留旧版支持的数据库类型：mysql / postgresql / sqlite
        $allowType = ['mysql', 'postgresql', 'sqlite'];
        if (!in_array($dbType, $allowType)) {
            return $this->error($response, 10101, '不支持的数据库类型');
        }

        if (empty($configName)) {
            $configName = '默认';
        }

        $data = [
            'item_id'          => $itemId,
            'env_id'           => $envId,
            'config_name'      => $configName,
            'db_type'          => $dbType,
            'host'             => $host,
            'port'              => $port,
            'username'          => $username,
            'password'          => $password,
            'database'          => $database,
            'options'           => $options,
            'is_default'        => $isDefault ? 1 : 0,
            'last_update_time' => date('Y-m-d H:i:s'),
            'uid'               => $uid,
        ];

        if ($configId > 0) {
            // 更新
            $row = RunapiDbConfig::findById($configId);
            if (!$row || (int) $row['item_id'] !== $itemId) {
                return $this->error($response, 10101, '配置不存在');
            }
            RunapiDbConfig::update($configId, $data);
        } else {
            // 新建
            $data['addtime'] = date('Y-m-d H:i:s');
            $configId = RunapiDbConfig::add($data);
        }

        if ($isDefault && $configId) {
            // 取消其他配置的默认状态
            RunapiDbConfig::unsetDefaultOthers($itemId, $envId, $configId);
        }

        return $this->success($response, ['config_id' => $configId]);
    }

    /**
     * 删除数据库连接配置
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function delDbConfig(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $configId = $this->getParam($request, 'config_id', 0);

        $row = RunapiDbConfig::findById($configId);
        if (!$row) {
            return $this->error($response, 10101, '配置不存在');
        }

        $itemId = (int) $row['item_id'];

        // 权限检查
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '没有权限');
        }

        RunapiDbConfig::delete($configId);
        return $this->success($response, []);
    }

    // 说明：开源版不提供 Runapi 的 AI 辅助接口（如 mdToRunapi、generateScript、generateTestData），
    // 相关能力由主站 showdoc.cc 统一提供，Runapi 客户端仍会固定访问主站进行此类 AI 能力调用。
}
