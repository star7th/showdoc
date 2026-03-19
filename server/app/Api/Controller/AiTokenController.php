<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Model\UserAiToken;
use App\Model\Item;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * AI Token 管理控制器
 * 
 * 用于用户管理自己的 AI 访问 Token
 */
class AiTokenController extends BaseController
{
  /**
   * 获取用户的 Token 列表
   *
   * @param Request $request 请求对象
   * @param Response $response 响应对象
   * @return Response
   */
  public function list(Request $request, Response $response): Response
  {
    // 获取当前用户
    $user = [];
    if ($error = $this->requireLoginUser($request, $response, $user)) {
      return $error;
    }

    $uid = (int) $user['uid'];
    $tokens = UserAiToken::getTokensByUid($uid);

    return $this->success($response, [
      'tokens' => $tokens,
      'total' => count($tokens),
    ]);
  }

  /**
   * 获取单个 Token 详情
   *
   * @param Request $request 请求对象
   * @param Response $response 响应对象
   * @return Response
   */
  public function detail(Request $request, Response $response): Response
  {
    // 获取当前用户
    $user = [];
    if ($error = $this->requireLoginUser($request, $response, $user)) {
      return $error;
    }

    $uid = (int) $user['uid'];
    $id = (int) $this->getParam($request, 'id', 0);

    if ($id <= 0) {
      return $this->error($response, 10101, 'Token ID 不能为空');
    }

    $token = UserAiToken::getTokenById($id, $uid);
    if (!$token) {
      return $this->error($response, 10101, 'Token 不存在');
    }

    // 隐藏部分 Token
    $token['token_preview'] = UserAiToken::maskToken($token['token']);
    unset($token['token']);

    // 获取允许访问的项目名称
    if ($token['scope'] === 'selected' && !empty($token['allowed_items'])) {
      $allowedIds = json_decode($token['allowed_items'], true) ?: [];
      if (!empty($allowedIds)) {
        $items = \Illuminate\Database\Capsule\Manager::table('item')
          ->whereIn('item_id', $allowedIds)
          ->where('is_del', 0)
          ->get(['item_id', 'item_name'])
          ->all();
        $token['allowed_items_detail'] = array_map(function ($item) {
          return [
            'item_id' => (int) $item->item_id,
            'item_name' => $item->item_name,
          ];
        }, $items);
      }
    }

    return $this->success($response, $token);
  }

  /**
   * 创建新 Token
   *
   * @param Request $request 请求对象
   * @param Response $response 响应对象
   * @return Response
   */
  public function create(Request $request, Response $response): Response
  {
    // 获取当前用户
    $user = [];
    if ($error = $this->requireLoginUser($request, $response, $user)) {
      return $error;
    }

    $uid = (int) $user['uid'];

    // 获取参数
    $name = trim($this->getParam($request, 'name', ''));
    $permission = $this->getParam($request, 'permission', 'write');
    $scope = $this->getParam($request, 'scope', 'all');
    $allowedItemsRaw = $this->getParam($request, 'allowed_items', '');
    $canCreateItem = $this->getParam($request, 'can_create_item', 1);
    $canDeleteItem = $this->getParam($request, 'can_delete_item', 0);
    $autoAddCreatedItem = $this->getParam($request, 'auto_add_created_item', 1);
    $expiresAt = trim($this->getParam($request, 'expires_at', ''));

    // 解析 allowed_items：支持逗号分隔字符串或数组
    $allowedItems = [];
    if (is_string($allowedItemsRaw) && $allowedItemsRaw !== '') {
      $allowedItems = array_map('intval', array_filter(explode(',', $allowedItemsRaw)));
    } elseif (is_array($allowedItemsRaw)) {
      $allowedItems = array_map('intval', array_filter($allowedItemsRaw));
    }

    // 验证参数
    if (!in_array($permission, ['read', 'write'])) {
      return $this->error($response, 10101, '权限类型无效');
    }

    if (!in_array($scope, ['all', 'selected'])) {
      return $this->error($response, 10101, '范围类型无效');
    }

    if ($scope === 'selected' && empty($allowedItems)) {
      return $this->error($response, 10101, '指定项目范围时必须选择至少一个项目');
    }

    // 验证过期时间
    if ($expiresAt !== '' && strtotime($expiresAt) === false) {
      return $this->error($response, 10101, '过期时间格式无效');
    }

    // 创建 Token
    $options = [
      'name' => $name ?: 'AI Token',
      'permission' => $permission,
      'scope' => $scope,
      'allowed_items' => is_array($allowedItems) ? $allowedItems : [],
      'can_create_item' => (int) $canCreateItem,
      'can_delete_item' => (int) $canDeleteItem,
      'auto_add_created_item' => (int) $autoAddCreatedItem,
      'expires_at' => $expiresAt !== '' ? $expiresAt : null,
    ];

    $token = UserAiToken::createToken($uid, $options);

    if (!$token) {
      return $this->error($response, 10101, 'Token 创建失败');
    }

    return $this->success($response, [
      'token' => $token,
      'message' => 'Token 创建成功，请妥善保管',
    ]);
  }

  /**
   * 更新 Token
   *
   * @param Request $request 请求对象
   * @param Response $response 响应对象
   * @return Response
   */
  public function update(Request $request, Response $response): Response
  {
    // 获取当前用户
    $user = [];
    if ($error = $this->requireLoginUser($request, $response, $user)) {
      return $error;
    }

    $uid = (int) $user['uid'];
    $id = (int) $this->getParam($request, 'id', 0);

    if ($id <= 0) {
      return $this->error($response, 10101, 'Token ID 不能为空');
    }

    // 检查 Token 是否存在
    $existingToken = UserAiToken::getTokenById($id, $uid);
    if (!$existingToken) {
      return $this->error($response, 10101, 'Token 不存在');
    }

    // 准备更新数据
    $updateData = [];

    $name = $this->getParam($request, 'name', null);
    if ($name !== null) {
      $updateData['name'] = trim($name);
    }

    $permission = $this->getParam($request, 'permission', null);
    if ($permission !== null && in_array($permission, ['read', 'write'])) {
      $updateData['permission'] = $permission;
    }

    $scope = $this->getParam($request, 'scope', null);
    if ($scope !== null && in_array($scope, ['all', 'selected'])) {
      $updateData['scope'] = $scope;
    }

    $allowedItemsRaw = $this->getParam($request, 'allowed_items', null);
    if ($allowedItemsRaw !== null) {
      // 解析 allowed_items：支持逗号分隔字符串或数组
      $allowedItems = [];
      if (is_string($allowedItemsRaw) && $allowedItemsRaw !== '') {
        $allowedItems = array_map('intval', array_filter(explode(',', $allowedItemsRaw)));
      } elseif (is_array($allowedItemsRaw)) {
        $allowedItems = array_map('intval', array_filter($allowedItemsRaw));
      }
      $updateData['allowed_items'] = $allowedItems;
    }

    $canCreateItem = $this->getParam($request, 'can_create_item', null);
    if ($canCreateItem !== null) {
      $updateData['can_create_item'] = (int) $canCreateItem;
    }

    $canDeleteItem = $this->getParam($request, 'can_delete_item', null);
    if ($canDeleteItem !== null) {
      $updateData['can_delete_item'] = (int) $canDeleteItem;
    }

    $autoAddCreatedItem = $this->getParam($request, 'auto_add_created_item', null);
    if ($autoAddCreatedItem !== null) {
      $updateData['auto_add_created_item'] = (int) $autoAddCreatedItem;
    }

    $expiresAt = $this->getParam($request, 'expires_at', null);
    if ($expiresAt !== null) {
      if ($expiresAt !== '' && strtotime($expiresAt) === false) {
        return $this->error($response, 10101, '过期时间格式无效');
      }
      $updateData['expires_at'] = $expiresAt !== '' ? $expiresAt : null;
    }

    if (empty($updateData)) {
      return $this->error($response, 10101, '没有需要更新的内容');
    }

    $ok = UserAiToken::updateToken($id, $uid, $updateData);

    if (!$ok) {
      return $this->error($response, 10101, 'Token 更新失败');
    }

    return $this->success($response, [
      'message' => 'Token 更新成功',
    ]);
  }

  /**
   * 重置 Token（生成新的 Token 字符串）
   *
   * @param Request $request 请求对象
   * @param Response $response 响应对象
   * @return Response
   */
  public function reset(Request $request, Response $response): Response
  {
    // 获取当前用户
    $user = [];
    if ($error = $this->requireLoginUser($request, $response, $user)) {
      return $error;
    }

    $uid = (int) $user['uid'];
    $id = (int) $this->getParam($request, 'id', 0);

    if ($id <= 0) {
      return $this->error($response, 10101, 'Token ID 不能为空');
    }

    $newToken = UserAiToken::resetToken($id, $uid);

    if (!$newToken) {
      return $this->error($response, 10101, 'Token 重置失败');
    }

    return $this->success($response, [
      'token' => $newToken,
      'message' => 'Token 已重置，请妥善保管新 Token',
    ]);
  }

  /**
   * 撤销 Token
   *
   * @param Request $request 请求对象
   * @param Response $response 响应对象
   * @return Response
   */
  public function revoke(Request $request, Response $response): Response
  {
    // 获取当前用户
    $user = [];
    if ($error = $this->requireLoginUser($request, $response, $user)) {
      return $error;
    }

    $uid = (int) $user['uid'];
    $id = (int) $this->getParam($request, 'id', 0);

    if ($id <= 0) {
      return $this->error($response, 10101, 'Token ID 不能为空');
    }

    $ok = UserAiToken::revokeToken($id, $uid);

    if (!$ok) {
      return $this->error($response, 10101, 'Token 撤销失败');
    }

    return $this->success($response, [
      'message' => 'Token 已撤销',
    ]);
  }

  /**
   * 删除 Token
   *
   * @param Request $request 请求对象
   * @param Response $response 响应对象
   * @return Response
   */
  public function delete(Request $request, Response $response): Response
  {
    // 获取当前用户
    $user = [];
    if ($error = $this->requireLoginUser($request, $response, $user)) {
      return $error;
    }

    $uid = (int) $user['uid'];
    $id = (int) $this->getParam($request, 'id', 0);

    if ($id <= 0) {
      return $this->error($response, 10101, 'Token ID 不能为空');
    }

    $ok = UserAiToken::deleteToken($id, $uid);

    if (!$ok) {
      return $this->error($response, 10101, 'Token 删除失败');
    }

    return $this->success($response, [
      'message' => 'Token 已删除',
    ]);
  }

  /**
   * 获取用户可选的项目列表（用于 Token 配置）
   *
   * @param Request $request 请求对象
   * @param Response $response 响应对象
   * @return Response
   */
  public function availableItems(Request $request, Response $response): Response
  {
    // 获取当前用户
    $user = [];
    if ($error = $this->requireLoginUser($request, $response, $user)) {
      return $error;
    }

    $uid = (int) $user['uid'];

    // 获取用户创建的项目
    $createdItems = \Illuminate\Database\Capsule\Manager::table('item')
      ->where('uid', $uid)
      ->where('is_del', 0)
      ->get(['item_id', 'item_name'])
      ->all();

    // 获取用户作为成员的项目
    $memberItems = \Illuminate\Database\Capsule\Manager::table('item_member')
      ->join('item', 'item_member.item_id', '=', 'item.item_id')
      ->where('item_member.uid', $uid)
      ->where('item.is_del', 0)
      ->select('item.item_id', 'item.item_name')
      ->get()
      ->all();

    // 获取用户所在团队的项目
    $teamItems = \Illuminate\Database\Capsule\Manager::table('team_item_member')
      ->join('item', 'team_item_member.item_id', '=', 'item.item_id')
      ->where('team_item_member.member_uid', $uid)
      ->where('item.is_del', 0)
      ->select('item.item_id', 'item.item_name')
      ->get()
      ->all();

    // 合并并去重
    $itemsMap = [];
    foreach (array_merge($createdItems, $memberItems, $teamItems) as $item) {
      $itemsMap[$item->item_id] = [
        'item_id' => (int) $item->item_id,
        'item_name' => $item->item_name,
      ];
    }

    return $this->success($response, [
      'items' => array_values($itemsMap),
      'total' => count($itemsMap),
    ]);
  }
}
