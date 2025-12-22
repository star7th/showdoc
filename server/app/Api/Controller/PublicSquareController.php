<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Model\Options;
use App\Model\Item;
use App\Model\Page;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 公共广场相关 Api（新架构）。
 */
class PublicSquareController extends BaseController
{
    /**
     * 检查公开广场功能是否启用（兼容旧接口 Api/PublicSquare/checkEnabled）。
     */
    public function checkEnabled(Request $request, Response $response): Response
    {
        $enablePublicSquare = Options::get('enable_public_square', 0);

        return $this->success($response, [
            'enable' => $enablePublicSquare ? 1 : 0,
        ]);
    }

    /**
     * 获取公开项目列表（兼容旧接口 Api/PublicSquare/getPublicItems）。
     */
    public function getPublicItems(Request $request, Response $response): Response
    {
        // 检查是否启用了公开广场功能
        $enablePublicSquare = Options::get('enable_public_square', 0);
        if (!$enablePublicSquare) {
            return $this->error($response, 10501, '公开广场功能未启用');
        }

        // 检查是否需要强制登录
        $forceLogin = Options::get('force_login', 0);
        if ($forceLogin) {
            $loginUser = [];
            if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
                return $error;
            }
        }

        $page = $this->getParam($request, 'page', 1);
        $count = $this->getParam($request, 'count', 20);
        $keyword = $this->getParam($request, 'keyword', '');
        $searchType = $this->getParam($request, 'search_type', 'title');

        // 只获取公开项目
        $query = DB::table('item')
            ->where('password', '')
            ->where('is_del', 0);

        if (!empty($keyword)) {
            if ($searchType === 'content') {
                // 搜索项目内容需要连接 page 表
                $likeKeyword = $this->safeLike($keyword);
                $pageItems = DB::table('page')
                    ->select('item_id')
                    ->where('page_content', 'like', "%{$likeKeyword}%")
                    ->groupBy('item_id')
                    ->get()
                    ->all();

                if (!empty($pageItems)) {
                    $itemIds = [];
                    foreach ($pageItems as $value) {
                        $itemIds[] = (int) ($value->item_id ?? 0);
                    }
                    $query->whereIn('item_id', $itemIds);
                } else {
                    // 如果没有找到匹配的内容，返回空结果
                    return $this->success($response, [
                        'total' => 0,
                        'items' => [],
                    ]);
                }
            } else {
                // 默认搜索项目标题和描述
                $likeKeyword = $this->safeLike($keyword);
                $query->where(function ($q) use ($likeKeyword) {
                    $q->where('item_name', 'like', "%{$likeKeyword}%")
                        ->orWhere('item_description', 'like', "%{$likeKeyword}%");
                });
            }
        }

        // 获取总数
        $total = (clone $query)->count();

        // 分页查询
        $items = $query
            ->select([
                'item_id',
                'item_name',
                'item_description',
                'item_type',
                'addtime',
                'last_update_time',
                'item_domain',
            ])
            ->orderBy('last_update_time', 'desc')
            ->offset(($page - 1) * $count)
            ->limit($count)
            ->get()
            ->all();

        $result = [];
        $result['total'] = (int) $total;

        if (!empty($items)) {
            foreach ($items as $item) {
                $data = (array) $item;
                $data['addtime'] = date('Y-m-d H:i:s', (int) ($data['addtime'] ?? time()));
                $data['last_update_time'] = date('Y-m-d H:i:s', (int) ($data['last_update_time'] ?? time()));
                $data['item_domain'] = !empty($data['item_domain']) ? $data['item_domain'] : $data['item_id'];
                $result['items'][] = $data;
            }
        } else {
            $result['items'] = [];
        }

        return $this->success($response, $result);
    }
}
