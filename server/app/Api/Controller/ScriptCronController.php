<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Model\Item;
use App\Model\Page;
use App\Model\Recycle;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 由网站前台脚本触发的周期任务
 */
class ScriptCronController extends BaseController
{
    /**
     * 执行定时任务
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function run(Request $request, Response $response): Response
    {
        set_time_limit(100);
        ini_set('memory_limit', '800M');
        ignore_user_abort(true);

        // 定期清理已删除项目和已删除页面
        $this->cleanDeletedData();

        return $this->success($response, ['message' => '定时任务执行完成']);
    }

    /**
     * 定期清理已删除项目和已删除页面
     */
    private function cleanDeletedData(): void
    {
        $thirtyDaysAgo = time() - 30 * 24 * 60 * 60;

        // 30天前的已删除项目
        $items = DB::table('item')
            ->where('is_del', 1)
            ->where('last_update_time', '<', $thirtyDaysAgo)
            ->get();

        if ($items) {
            foreach ($items as $item) {
                Item::deleteItem((int) $item->item_id);
            }
        }

        // 30天前的已删除页面（旧版逻辑：只传 page_id）
        $pages = DB::table('page')
            ->where('is_del', 1)
            ->where('addtime', '<', $thirtyDaysAgo)
            ->get();

        if ($pages) {
            foreach ($pages as $page) {
                // 旧版只传 page_id，不传 item_id
                Page::deletePage((int) $page->page_id);
            }
        }

        // 30天前的回收站记录（旧版逻辑：直接调用 deletePage，不检查 page 是否存在）
        $recycles = DB::table('recycle')
            ->where('del_time', '<', $thirtyDaysAgo)
            ->get();

        if ($recycles) {
            foreach ($recycles as $recycle) {
                // 旧版逻辑：直接调用 deletePage，只传 page_id
                Page::deletePage((int) $recycle->page_id);
                DB::table('recycle')
                    ->where('id', $recycle->id)
                    ->delete();
            }
        }
    }
}

