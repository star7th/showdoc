<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Model\Template;
use App\Model\TemplateItem;
use App\Common\Helper\Security;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 模板相关 Api（新架构）。
 */
class TemplateController extends BaseController
{
    /**
     * 保存模板（兼容旧接口 Api/Template/save）。
     */
    public function save(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $templateTitle = $this->getParam($request, 'template_title', '');
        $templateContent = $this->getParam($request, 'template_content', '');

        if (empty($templateTitle)) {
            return $this->error($response, 10101, '模板标题不能为空');
        }

        $data = [
            'username'        => $loginUser['username'] ?? '',
            'uid'            => (int) ($loginUser['uid'] ?? 0),
            'template_title' => $templateTitle,
            'template_content' => $templateContent,
            'addtime'        => time(),
        ];

        $id = Template::add($data);
        if (!$id) {
            return $this->error($response, 10103, '保存失败');
        }

        return $this->success($response, ['id' => $id]);
    }

    /**
     * 获取我的模板列表（兼容旧接口 Api/Template/getList）。
     */
    public function getList(Request $request, Response $response): Response
    {
        return $this->getMyList($request, $response);
    }

    /**
     * 获取我的模板列表（兼容旧接口 Api/Template/getMyList）。
     */
    public function getMyList(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        if ($uid <= 0) {
            return $this->success($response, []);
        }

        $ret = Template::getListByUid($uid);
        if (!empty($ret)) {
            foreach ($ret as $key => &$value) {
                $value['addtime'] = date('Y-m-d H:i:s', (int) ($value['addtime'] ?? time()));
                $value['template_content'] = htmlspecialchars_decode($value['template_content'] ?? '');

                // 获取当前模板被共享到哪些项目中
                $templateId = (int) ($value['id'] ?? 0);
                $shareItems = TemplateItem::getListByTemplateId($templateId);
                $value['share_item'] = $shareItems ?: [];
                $value['share_item_count'] = count($value['share_item']);
            }
            return $this->success($response, $ret);
        }

        return $this->success($response, []);
    }

    /**
     * 获取当前项目的模板列表（兼容旧接口 Api/Template/getItemList）。
     */
    public function getItemList(Request $request, Response $response): Response
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
            return $this->error($response, 10103, '没有权限');
        }

        $res = TemplateItem::getListByItemId($itemId);
        if (!empty($res)) {
            foreach ($res as $key => &$value) {
                $value['addtime'] = date('Y-m-d H:i:s', (int) ($value['addtime'] ?? time()));
                $value['template_content'] = htmlspecialchars_decode($value['template_content'] ?? '');
            }
            return $this->success($response, $res);
        }

        return $this->success($response, []);
    }

    /**
     * 删除模板（兼容旧接口 Api/Template/delete）。
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
            return $this->error($response, 10101, '模板ID不能为空');
        }

        // 验证模板是否属于当前用户
        $template = Template::findById($id);
        if (!$template || (int) ($template->uid ?? 0) !== $uid) {
            return $this->error($response, 10103, '没有权限');
        }

        // 删除模板及其关联
        Template::delete($id);
        TemplateItem::deleteByTemplateId($id);

        return $this->success($response, []);
    }

    /**
     * 把模板分享给项目（兼容旧接口 Api/Template/shareToItem）。
     */
    public function shareToItem(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        $itemIdStr = $this->getParam($request, 'item_id', '');
        $templateId = $this->getParam($request, 'template_id', 0);

        if ($templateId <= 0 || empty($itemIdStr)) {
            return $this->error($response, 10101, '参数错误');
        }

        // 验证模板是否属于当前用户
        $template = Template::findById($templateId);
        if (!$template || (int) ($template->uid ?? 0) !== $uid) {
            return $this->error($response, 10103, '没有权限');
        }

        // 转义并分割项目ID
        $itemIdStr = Security::safeLike($itemIdStr, false);
        $itemIdArray = explode(',', $itemIdStr);

        // 先删除旧的关联
        TemplateItem::deleteByTemplateId($templateId);

        // 添加新的关联
        foreach ($itemIdArray as $value) {
            $itemId = (int) trim($value);
            if ($itemId <= 0) {
                continue;
            }

            // 检查项目编辑权限
            if (!$this->checkItemEdit($uid, $itemId)) {
                return $this->error($response, 10103, "项目 {$itemId} 没有权限");
            }

            // 如果该模板已经分享到该项目中了，跳过
            if (TemplateItem::exists($templateId, $itemId)) {
                continue;
            }

            TemplateItem::add([
                'template_id' => $templateId,
                'item_id'     => $itemId,
                'uid'         => $uid,
                'username'    => $loginUser['username'] ?? '',
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->success($response, []);
    }

    /**
     * 获取"某个模板已经被共享到什么项目中了"的列表（兼容旧接口 Api/Template/getShareItemList）。
     */
    public function getShareItemList(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        $templateId = $this->getParam($request, 'template_id', 0);

        if ($templateId <= 0) {
            return $this->error($response, 10101, '模板ID不能为空');
        }

        // 验证模板是否属于当前用户
        $template = Template::findById($templateId);
        if (!$template || (int) ($template->uid ?? 0) !== $uid) {
            return $this->error($response, 10103, '没有权限');
        }

        $res = TemplateItem::getListByTemplateId($templateId);
        if (!empty($res)) {
            return $this->success($response, $res);
        }

        return $this->success($response, []);
    }
}
