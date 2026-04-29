<?php

namespace App\Mcp;

use App\Mcp\Handler\ItemHandler;
use App\Mcp\Handler\CatalogHandler;
use App\Mcp\Handler\PageHandler;
use App\Mcp\Handler\AttachmentHandler;
use App\Mcp\Handler\OpenApiHandler;
use App\Mcp\Handler\KanbanHandler;
use App\Mcp\Handler\RunapiPageHandler;
use App\Model\UserAiToken;
use App\Common\Helper\IpHelper;

/**
 * MCP Server 核心类
 * 
 * 处理 MCP 协议的 JSON-RPC 请求，路由到对应的 Handler
 */
class McpServer
{
  /**
   * 请求 ID
   *
   * @var int|string|null
   */
  private $requestId = null;

  /**
   * Token 信息
   *
   * @var array|null
   */
  private ?array $tokenInfo = null;

  /**
   * 已注册的 Tools
   *
   * @var array
   */
  private array $tools = [];

  /**
   * 已注册的 Resources
   *
   * @var array
   */
  private array $resources = [];

  /**
   * 已注册的 Prompts
   *
   * @var array
   */
  private array $prompts = [];

  /**
   * Handler 实例缓存
   *
   * @var array
   */
  private array $handlers = [];

  /**
   * 构造函数
   */
  public function __construct()
  {
    $this->registerDefaultTools();
    $this->registerDefaultResources();
    $this->registerDefaultPrompts();
  }

  /**
   * 注册默认 Tools
   *
   * @return void
   */
  private function registerDefaultTools(): void
  {
    // 项目管理
    $this->tools['list_items'] = [
      'name' => 'list_items',
      'description' => '列出用户可访问的所有项目',
      'inputSchema' => [
        'type' => 'object',
        'properties' => (object) [],
        'required' => [],
      ],
      'handler' => 'item',
    ];

    $this->tools['get_item'] = [
      'name' => 'get_item',
      'description' => '获取项目详情',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
        ],
        'required' => ['item_id'],
      ],
      'handler' => 'item',
    ];

    $this->tools['create_item'] = [
      'name' => 'create_item',
      'description' => '创建新项目',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_name' => [
            'type' => 'string',
            'description' => '项目名称',
          ],
          'item_type' => [
            'type' => 'integer',
            'description' => '项目类型：1=普通文档（默认），3=RunApi项目，6=看板',
          ],
          'item_description' => [
            'type' => 'string',
            'description' => '项目描述',
          ],
        ],
        'required' => ['item_name'],
      ],
      'handler' => 'item',
    ];

    $this->tools['update_item'] = [
      'name' => 'update_item',
      'description' => '更新项目信息',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
          'item_name' => [
            'type' => 'string',
            'description' => '项目名称',
          ],
          'item_description' => [
            'type' => 'string',
            'description' => '项目描述',
          ],
        ],
        'required' => ['item_id'],
      ],
      'handler' => 'item',
    ];

    $this->tools['delete_item'] = [
      'name' => 'delete_item',
      'description' => '删除项目（软删除）',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
        ],
        'required' => ['item_id'],
      ],
      'handler' => 'item',
    ];

    // 目录管理
    $this->tools['list_catalogs'] = [
      'name' => 'list_catalogs',
      'description' => '获取项目的目录树',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
        ],
        'required' => ['item_id'],
      ],
      'handler' => 'catalog',
    ];

    $this->tools['get_catalog'] = [
      'name' => 'get_catalog',
      'description' => '获取目录详情',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'cat_id' => [
            'type' => 'integer',
            'description' => '目录ID',
          ],
        ],
        'required' => ['cat_id'],
      ],
      'handler' => 'catalog',
    ];

    $this->tools['create_catalog'] = [
      'name' => 'create_catalog',
      'description' => '创建目录',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
          'cat_name' => [
            'type' => 'string',
            'description' => '目录名称',
          ],
          'parent_cat_id' => [
            'type' => 'integer',
            'description' => '父目录ID（可选，默认为根目录）',
          ],
          's_number' => [
            'type' => 'integer',
            'description' => '排序号（可选）',
          ],
        ],
        'required' => ['item_id', 'cat_name'],
      ],
      'handler' => 'catalog',
    ];

    $this->tools['update_catalog'] = [
      'name' => 'update_catalog',
      'description' => '更新目录',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'cat_id' => [
            'type' => 'integer',
            'description' => '目录ID',
          ],
          'cat_name' => [
            'type' => 'string',
            'description' => '目录名称',
          ],
          's_number' => [
            'type' => 'integer',
            'description' => '排序号',
          ],
        ],
        'required' => ['cat_id'],
      ],
      'handler' => 'catalog',
    ];

    $this->tools['delete_catalog'] = [
      'name' => 'delete_catalog',
      'description' => '删除目录（含子目录和页面）',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'cat_id' => [
            'type' => 'integer',
            'description' => '目录ID',
          ],
        ],
        'required' => ['cat_id'],
      ],
      'handler' => 'catalog',
    ];

    // 页面管理
    $this->tools['list_pages'] = [
      'name' => 'list_pages',
      'description' => '获取项目/目录下的页面列表（分页，不含内容）。注意：当项目 item_type=6（看板）时，请使用 kanban_get_board 获取板面，kanban_list_tasks 筛选任务；当项目 item_type=3（RunApi）时，请使用 get_runapi_page 获取接口详情',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
          'cat_id' => [
            'type' => 'integer',
            'description' => '目录ID（可选）',
          ],
          'page' => [
            'type' => 'integer',
            'description' => '页码（默认1）',
          ],
          'page_size' => [
            'type' => 'integer',
            'description' => '每页数量（默认50，最大100）',
          ],
        ],
        'required' => ['item_id'],
      ],
      'handler' => 'page',
    ];

    $this->tools['get_page'] = [
      'name' => 'get_page',
      'description' => '获取页面详情。注意：当项目 item_type=6（看板）时，请使用 kanban_get_task 获取任务详情；当项目 item_type=3（RunApi）时，请使用 get_runapi_page 获取接口详情',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'page_id' => [
            'type' => 'integer',
            'description' => '页面ID',
          ],
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID（与page_title配合使用）',
          ],
          'page_title' => [
            'type' => 'string',
            'description' => '页面标题（与item_id配合使用）',
          ],
        ],
      ],
      'handler' => 'page',
    ];

    $this->tools['batch_get_pages'] = [
      'name' => 'batch_get_pages',
      'description' => '批量获取页面详情（最多10个）',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'page_ids' => [
            'type' => 'array',
            'items' => ['type' => 'integer'],
            'description' => '页面ID数组（最多10个）',
          ],
        ],
        'required' => ['page_ids'],
      ],
      'handler' => 'page',
    ];

    $this->tools['search_pages'] = [
      'name' => 'search_pages',
      'description' => '搜索页面（按关键字搜索，默认只搜索标题）。注意：看板项目(item_type=6)请使用 kanban_search_tasks 搜索任务；RunApi项目(item_type=3)的接口也可通过此工具搜索',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'query' => [
            'type' => 'string',
            'description' => '搜索关键字',
          ],
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID（可选，限定搜索范围）',
          ],
          'search_mode' => [
            'type' => 'string',
            'description' => '搜索模式：title（默认，只搜索标题）、content（只搜索内容）、all（搜索标题和内容）',
            'enum' => ['title', 'content', 'all'],
          ],
        ],
        'required' => ['query'],
      ],
      'handler' => 'page',
    ];

    $this->tools['get_page_template'] = [
      'name' => 'get_page_template',
      'description' => '获取文档模板（api/runapi_comment/database/general）',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'type' => [
            'type' => 'string',
            'description' => '模板类型：api、runapi_comment、database、general',
          ],
        ],
        'required' => [],
      ],
      'handler' => 'page',
    ];

    $this->tools['create_page'] = [
      'name' => 'create_page',
      'description' => '创建页面（Markdown内容）。注意：当项目 item_type=6（看板）时，请使用 kanban_create_task 创建任务；当项目 item_type=3（RunApi）时，请使用 create_runapi_page 创建接口页面',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
          'page_title' => [
            'type' => 'string',
            'description' => '页面标题',
          ],
          'page_content' => [
            'type' => 'string',
            'description' => '页面内容（Markdown格式）',
          ],
          'cat_name' => [
            'type' => 'string',
            'description' => '目录名称（可选，不存在则自动创建）',
          ],
          's_number' => [
            'type' => 'integer',
            'description' => '排序号（可选）',
          ],
        ],
        'required' => ['item_id', 'page_title', 'page_content'],
      ],
      'handler' => 'page',
    ];

    $this->tools['create_page_by_comment'] = [
      'name' => 'create_page_by_comment',
      'description' => '通过代码注释（showdoc格式）创建页面。注意：不适用于看板项目(item_type=6)；主要适用于普通文档项目(item_type=1)。对于RunApi项目(item_type=3)，推荐使用专门的 create_runapi_page / upsert_runapi_page 工具来创建接口页面',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
          'comment_content' => [
            'type' => 'string',
            'description' => '代码注释内容（showdoc格式）',
          ],
        ],
        'required' => ['item_id', 'comment_content'],
      ],
      'handler' => 'page',
    ];

    $this->tools['update_page'] = [
      'name' => 'update_page',
      'description' => '更新页面。注意：当项目 item_type=6（看板）时，请使用 kanban_update_task 更新任务；当项目 item_type=3（RunApi）时，请使用 update_runapi_page 更新接口页面',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'page_id' => [
            'type' => 'integer',
            'description' => '页面ID',
          ],
          'page_content' => [
            'type' => 'string',
            'description' => '页面内容（Markdown格式）',
          ],
          'page_title' => [
            'type' => 'string',
            'description' => '页面标题（可选）',
          ],
          'expected_hash' => [
            'type' => 'string',
            'description' => '期望的当前内容哈希（乐观锁，可选）',
          ],
        ],
        'required' => ['page_id', 'page_content'],
      ],
      'handler' => 'page',
    ];

    $this->tools['upsert_page'] = [
      'name' => 'upsert_page',
      'description' => '按标题智能匹配：存在则更新，不存在则创建。注意：当项目 item_type=6（看板）时，请使用 kanban_create_task 或 kanban_update_task；当项目 item_type=3（RunApi）时，请使用 upsert_runapi_page',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
          'page_title' => [
            'type' => 'string',
            'description' => '页面标题',
          ],
          'page_content' => [
            'type' => 'string',
            'description' => '页面内容（Markdown格式）',
          ],
          'cat_name' => [
            'type' => 'string',
            'description' => '目录名称（可选）',
          ],
          's_number' => [
            'type' => 'integer',
            'description' => '排序号（可选）',
          ],
        ],
        'required' => ['item_id', 'page_title', 'page_content'],
      ],
      'handler' => 'page',
    ];

    $this->tools['batch_upsert_pages'] = [
      'name' => 'batch_upsert_pages',
      'description' => '批量创建/更新页面（最多50个页面对象）。注意：不适用于看板项目(item_type=6)，请使用 kanban_create_task；不适用于RunApi项目(item_type=3)，请使用 create_runapi_page 或 upsert_runapi_page',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
          'pages' => [
            'type' => 'array',
            'items' => [
              'type' => 'object',
              'properties' => [
                'page_title' => ['type' => 'string'],
                'page_content' => ['type' => 'string'],
                'cat_name' => ['type' => 'string'],
                's_number' => ['type' => 'integer'],
              ],
              'required' => ['page_title', 'page_content'],
            ],
            'description' => '页面数组（最多50个）',
          ],
        ],
        'required' => ['item_id', 'pages'],
      ],
      'handler' => 'page',
    ];

    $this->tools['delete_page'] = [
      'name' => 'delete_page',
      'description' => '删除页面（软删除）。注意：当项目 item_type=6（看板）时，请使用 kanban_delete_task 删除任务；RunApi项目(item_type=3)的接口页面也可使用此工具删除',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'page_id' => [
            'type' => 'integer',
            'description' => '页面ID',
          ],
        ],
        'required' => ['page_id'],
      ],
      'handler' => 'page',
    ];

    // 页面历史管理
    $this->tools['get_page_history'] = [
      'name' => 'get_page_history',
      'description' => '获取页面的修改历史列表（兼容看板任务页面）',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'page_id' => [
            'type' => 'integer',
            'description' => '页面ID',
          ],
          'limit' => [
            'type' => 'integer',
            'description' => '返回数量限制（默认20，最大100）',
          ],
        ],
        'required' => ['page_id'],
      ],
      'handler' => 'page',
    ];

    $this->tools['get_page_version'] = [
      'name' => 'get_page_version',
      'description' => '获取页面指定历史版本的内容（兼容看板任务页面）',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'page_id' => [
            'type' => 'integer',
            'description' => '页面ID',
          ],
          'version_id' => [
            'type' => 'integer',
            'description' => '历史版本ID',
          ],
        ],
        'required' => ['page_id', 'version_id'],
      ],
      'handler' => 'page',
    ];

    $this->tools['diff_page_versions'] = [
      'name' => 'diff_page_versions',
      'description' => '对比两个页面版本的差异（兼容看板任务页面）',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'page_id' => [
            'type' => 'integer',
            'description' => '页面ID',
          ],
          'version_id_1' => [
            'type' => 'integer',
            'description' => '第一个版本ID',
          ],
          'version_id_2' => [
            'type' => 'integer',
            'description' => '第二个版本ID',
          ],
        ],
        'required' => ['page_id', 'version_id_1', 'version_id_2'],
      ],
      'handler' => 'page',
    ];

    $this->tools['restore_page_version'] = [
      'name' => 'restore_page_version',
      'description' => '恢复页面到指定的历史版本（兼容看板任务页面）',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'page_id' => [
            'type' => 'integer',
            'description' => '页面ID',
          ],
          'version_id' => [
            'type' => 'integer',
            'description' => '要恢复到的历史版本ID',
          ],
        ],
        'required' => ['page_id', 'version_id'],
      ],
      'handler' => 'page',
    ];

    // 附件管理
    $this->tools['upload_attachment'] = [
      'name' => 'upload_attachment',
      'description' => '上传附件（通过Base64编码的文件内容）。兼容看板任务页面',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
          'page_id' => [
            'type' => 'integer',
            'description' => '页面ID（可选）',
          ],
          'file_base64' => [
            'type' => 'string',
            'description' => 'Base64编码的文件内容（必填）。请先下载文件，然后将文件内容编码为Base64后传入',
          ],
          'file_name' => [
            'type' => 'string',
            'description' => '文件名（可选，包含扩展名，如 image.png）',
          ],
        ],
        'required' => ['item_id', 'file_base64'],
      ],
      'handler' => 'attachment',
    ];

    $this->tools['list_attachments'] = [
      'name' => 'list_attachments',
      'description' => '获取项目或页面的附件列表（兼容看板任务页面）',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
          'page_id' => [
            'type' => 'integer',
            'description' => '页面ID（可选，用于筛选特定页面的附件）',
          ],
        ],
        'required' => [],
      ],
      'handler' => 'attachment',
    ];

    $this->tools['delete_attachment'] = [
      'name' => 'delete_attachment',
      'description' => '删除附件',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'file_id' => [
            'type' => 'integer',
            'description' => '文件ID（与sign二选一）',
          ],
          'sign' => [
            'type' => 'string',
            'description' => '文件签名（与file_id二选一）',
          ],
        ],
        'required' => [],
      ],
      'handler' => 'attachment',
    ];

    // OpenAPI 导入
    $this->tools['import_openapi'] = [
      'name' => 'import_openapi',
      'description' => '导入 OpenAPI/Swagger 文档，批量创建/更新 API 页面。注意：不适用于看板项目(item_type=6)',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID（可选，不传则创建新项目）',
          ],
          'openapi_content' => [
            'type' => 'string',
            'description' => 'OpenAPI/Swagger JSON 内容（与 openapi_url 二选一）',
          ],
          'openapi_url' => [
            'type' => 'string',
            'description' => 'OpenAPI/Swagger 文档 URL（与 openapi_content 二选一）',
          ],
          'format' => [
            'type' => 'string',
            'description' => '导入格式：markdown（普通文档，默认）或 runapi（RunApi 项目）',
          ],
        ],
        'required' => [],
      ],
      'handler' => 'openapi',
    ];

    // 看板管理
    $this->tools['kanban_get_board'] = [
      'name' => 'kanban_get_board',
      'description' => '获取看板完整板面（包含所有列表及全部任务详情）。如果只需了解列表概览，请优先使用 kanban_get_lists，它更轻量且不返回任务详情。当项目的 item_type=6（看板项目）时，使用此工具获取板面，而非 list_pages。返回列表定义、每个列表的任务列表及已归档列表',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
        ],
        'required' => ['item_id'],
      ],
      'handler' => 'kanban',
    ];

    $this->tools['kanban_get_lists'] = [
      'name' => 'kanban_get_lists',
      'description' => '获取看板的所有列表，只返回列表定义和任务数量，不返回任务详情。比 kanban_get_board 更轻量，适合只需了解列表结构的场景',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
        ],
        'required' => ['item_id'],
      ],
      'handler' => 'kanban',
    ];

    $this->tools['kanban_get_task'] = [
      'name' => 'kanban_get_task',
      'description' => '获取看板任务详情。当项目的 item_type=6（看板项目）时，使用此工具获取任务详情，而非 get_page',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'page_id' => [
            'type' => 'integer',
            'description' => '任务ID（即任务的page_id，可通过 kanban_get_board 或 kanban_list_tasks 获取）',
          ],
        ],
        'required' => ['page_id'],
      ],
      'handler' => 'kanban',
    ];

    $this->tools['kanban_list_tasks'] = [
      'name' => 'kanban_list_tasks',
      'description' => '列出看板任务，支持按列表、负责人、创建者、标签、优先级、截止日期筛选。默认不显示已完成的任务，传 show_completed=true 可显示。当项目 item_type=6 时使用此工具筛选任务',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
          'list_id' => [
            'type' => 'string',
            'description' => '列表ID（可选）',
          ],
          'assignee_uid' => [
            'type' => 'string',
            'description' => '负责人UID（可选，传UID数字字符串如"123"，或传"none"筛选无负责人的任务）',
          ],
          'creator_uid' => [
            'type' => 'integer',
            'description' => '创建者UID（可选）',
          ],
          'tag' => [
            'type' => 'string',
            'description' => '标签文本（可选）',
          ],
          'priority' => [
            'type' => 'string',
            'description' => '优先级（可选）：high/medium/low',
          ],
          'due_date_start' => [
            'type' => 'string',
            'description' => '截止日期起始（可选，格式：YYYY-MM-DD）',
          ],
          'due_date_end' => [
            'type' => 'string',
            'description' => '截止日期结束（可选，格式：YYYY-MM-DD）',
          ],
          'show_completed' => [
            'type' => 'boolean',
            'description' => '是否显示已完成的任务（可选，默认false）',
          ],
        ],
        'required' => ['item_id'],
      ],
      'handler' => 'kanban',
    ];

    $this->tools['kanban_search_tasks'] = [
      'name' => 'kanban_search_tasks',
      'description' => '按关键字搜索看板任务。当项目 item_type=6 时使用此工具搜索任务',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
          'query' => [
            'type' => 'string',
            'description' => '搜索关键字',
          ],
        ],
        'required' => ['item_id', 'query'],
      ],
      'handler' => 'kanban',
    ];

    $this->tools['kanban_create_task'] = [
      'name' => 'kanban_create_task',
      'description' => '在看板指定列表中创建新任务，自动更新板面排序。当项目 item_type=6 时使用此工具，而非 create_page',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
          'list_id' => [
            'type' => 'string',
            'description' => '目标列表ID',
          ],
          'title' => [
            'type' => 'string',
            'description' => '任务标题（最多100字符）',
          ],
          'description' => [
            'type' => 'string',
            'description' => '任务描述（可选）',
          ],
          'due_date' => [
            'type' => 'string',
            'description' => '截止日期（可选，格式：YYYY-MM-DD）',
          ],
          'tags' => [
            'type' => 'array',
            'items' => [
              'type' => 'object',
              'properties' => [
                'color' => ['type' => 'string', 'description' => '标签颜色：red/orange/yellow/green/blue/purple/gray'],
                'text' => ['type' => 'string', 'description' => '标签文本，最多20字符'],
              ],
            ],
            'description' => '标签列表（可选，最多3个，每项含color和text）',
          ],
          'priority' => [
            'type' => 'string',
            'description' => '优先级（可选）：high/medium/low',
          ],
          'linked_pages' => [
            'type' => 'array',
            'items' => [
              'type' => 'object',
              'properties' => [
                'item_id' => ['type' => 'string', 'description' => '关联页面所属项目ID'],
                'page_id' => ['type' => 'string', 'description' => '关联页面ID'],
                'page_title' => ['type' => 'string', 'description' => '关联页面标题'],
              ],
            ],
            'description' => '关联页面列表（可选）',
          ],
        ],
        'required' => ['item_id', 'list_id', 'title'],
      ],
      'handler' => 'kanban',
    ];

    $this->tools['kanban_update_task'] = [
      'name' => 'kanban_update_task',
      'description' => '更新看板任务信息，只传需要修改的字段。支持设置 completed 字段来标记完成状态。当项目 item_type=6 时使用此工具，而非 update_page',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'page_id' => [
            'type' => 'integer',
            'description' => '任务页面ID',
          ],
          'title' => [
            'type' => 'string',
            'description' => '任务标题（可选，最多100字符）',
          ],
          'description' => [
            'type' => 'string',
            'description' => '任务描述（可选）',
          ],
          'assignee_uid' => [
            'type' => 'string',
            'description' => '负责人UID（可选，传入UID数字字符串如"123"）',
          ],
          'assignee_username' => [
            'type' => 'string',
            'description' => '负责人用户名（可选，建议与assignee_uid同时传入以保持数据一致）',
          ],
          'due_date' => [
            'type' => 'string',
            'description' => '截止日期（可选，格式：YYYY-MM-DD）',
          ],
          'tags' => [
            'type' => 'array',
            'items' => [
              'type' => 'object',
              'properties' => [
                'color' => ['type' => 'string', 'description' => '标签颜色：red/orange/yellow/green/blue/purple/gray'],
                'text' => ['type' => 'string', 'description' => '标签文本，最多20字符'],
              ],
            ],
            'description' => '标签列表（可选，最多3个，每项含color和text）',
          ],
          'priority' => [
            'type' => 'string',
            'description' => '优先级（可选）：high/medium/low',
          ],
          'linked_pages' => [
            'type' => 'array',
            'items' => [
              'type' => 'object',
              'properties' => [
                'item_id' => ['type' => 'string', 'description' => '关联页面所属项目ID'],
                'page_id' => ['type' => 'string', 'description' => '关联页面ID'],
                'page_title' => ['type' => 'string', 'description' => '关联页面标题'],
              ],
            ],
            'description' => '关联页面列表（可选）',
          ],
          'completed' => [
            'type' => 'boolean',
            'description' => '完成状态（可选）：true=已完成，false=未完成',
          ],
        ],
        'required' => ['page_id'],
      ],
      'handler' => 'kanban',
    ];

    $this->tools['kanban_move_task'] = [
      'name' => 'kanban_move_task',
      'description' => '移动看板任务到指定列表，自动更新任务 list_id 和板面 tasks_order',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'page_id' => [
            'type' => 'integer',
            'description' => '任务页面ID',
          ],
          'target_list_id' => [
            'type' => 'string',
            'description' => '目标列表ID',
          ],
        ],
        'required' => ['page_id', 'target_list_id'],
      ],
      'handler' => 'kanban',
    ];

    $this->tools['kanban_delete_task'] = [
      'name' => 'kanban_delete_task',
      'description' => '删除看板任务，同时清理板面排序。当项目 item_type=6 时使用此工具，而非 delete_page',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'page_id' => [
            'type' => 'integer',
            'description' => '任务页面ID',
          ],
        ],
        'required' => ['page_id'],
      ],
      'handler' => 'kanban',
    ];

    $this->tools['kanban_add_list'] = [
      'name' => 'kanban_add_list',
      'description' => '在看板中添加新列表',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
          'title' => [
            'type' => 'string',
            'description' => '列表标题',
          ],
        ],
        'required' => ['item_id', 'title'],
      ],
      'handler' => 'kanban',
    ];

    $this->tools['kanban_update_list'] = [
      'name' => 'kanban_update_list',
      'description' => '更新看板列表标题',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
          'list_id' => [
            'type' => 'string',
            'description' => '列表ID',
          ],
          'title' => [
            'type' => 'string',
            'description' => '新列表标题',
          ],
        ],
        'required' => ['item_id', 'list_id', 'title'],
      ],
      'handler' => 'kanban',
    ];

    $this->tools['kanban_delete_list'] = [
      'name' => 'kanban_delete_list',
      'description' => '删除看板列表，列表内任务自动标记为完成。唯一列表不允许删除',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
          'list_id' => [
            'type' => 'string',
            'description' => '列表ID',
          ],
        ],
        'required' => ['item_id', 'list_id'],
      ],
      'handler' => 'kanban',
    ];

    $this->tools['kanban_archive_list'] = [
      'name' => 'kanban_archive_list',
      'description' => '归档看板列表（含其下所有任务）。归档后列表从看板中移除，可通过 kanban_restore_list 恢复',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
          'list_id' => [
            'type' => 'string',
            'description' => '要归档的列表ID',
          ],
        ],
        'required' => ['item_id', 'list_id'],
      ],
      'handler' => 'kanban',
    ];

    $this->tools['kanban_restore_list'] = [
      'name' => 'kanban_restore_list',
      'description' => '恢复已归档的看板列表（含其下所有任务），列表和任务恢复到看板活跃区',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
          'list_id' => [
            'type' => 'string',
            'description' => '要恢复的已归档列表ID',
          ],
        ],
        'required' => ['item_id', 'list_id'],
      ],
      'handler' => 'kanban',
    ];

    $this->tools['kanban_list_archived_lists'] = [
      'name' => 'kanban_list_archived_lists',
      'description' => '列出看板中所有已归档的列表（含每个列表的任务数量）',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
        ],
        'required' => ['item_id'],
      ],
      'handler' => 'kanban',
    ];

    $this->tools['kanban_get_activity'] = [
      'name' => 'kanban_get_activity',
      'description' => '查询看板活动日志，可按事件类型和时间范围筛选。支持按周、按月或自定义时间段查看任务完成情况',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID',
          ],
          'event_types' => [
            'type' => 'array',
            'items' => ['type' => 'string'],
            'description' => '事件类型过滤（可选）：task_created/task_completed/task_uncompleted/task_moved/task_deleted/task_updated/list_created/list_updated/list_deleted/list_archived/list_restored',
          ],
          'start_time' => [
            'type' => 'integer',
            'description' => '开始时间戳（可选）',
          ],
          'end_time' => [
            'type' => 'integer',
            'description' => '结束时间戳（可选）',
          ],
          'page' => [
            'type' => 'integer',
            'description' => '页码（默认1）',
          ],
          'page_size' => [
            'type' => 'integer',
            'description' => '每页数量（默认50，最大100）',
          ],
        ],
        'required' => ['item_id'],
      ],
      'handler' => 'kanban',
    ];

    // RunApi 页面管理
    $this->tools['get_runapi_page'] = [
      'name' => 'get_runapi_page',
      'description' => '获取RunApi项目中的接口详情，返回原始RunApi JSON结构。仅适用于item_type=3的RunApi项目。返回的page_content是RunApi JSON对象，可直接修改后通过update_runapi_page写回。',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'page_id' => [
            'type' => 'integer',
            'description' => '页面ID',
          ],
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID（与page_title配合使用）',
          ],
          'page_title' => [
            'type' => 'string',
            'description' => '页面标题（与item_id配合使用）',
          ],
        ],
      ],
      'handler' => 'runapi_page',
    ];

    $this->tools['create_runapi_page'] = [
      'name' => 'create_runapi_page',
      'description' => '在RunApi项目中创建接口页面。仅适用于item_type=3的RunApi项目。page_content必须是RunApi JSON对象，结构如下：' . "\n\n" . '{' . "\n" . '  "info": {' . "\n" . '    "from": "runapi",' . "\n" . '    "type": "api",' . "\n" . '    "method": "post",' . "\n" . '    "url": "/api/path",' . "\n" . '    "description": "接口描述",' . "\n" . '    "remark": "备注"' . "\n" . '  },' . "\n" . '  "request": {' . "\n" . '    "params": {' . "\n" . '      "mode": "json",' . "\n" . '      "json": "{\\"key\\\":\\"value\\\"}",' . "\n" . '      "jsonDesc": [{"name":"key","type":"string","require":"1","remark":"说明"}],' . "\n" . '      "formdata": [],' . "\n" . '      "urlencoded": []' . "\n" . '    },' . "\n" . '    "headers": [{"name":"Content-Type","type":"string","value":"application/json","require":"1","remark":"" }],' . "\n" . '    "query": [{"name":"page","type":"int","value":"1","require":"0","remark":"页码"}],' . "\n" . '    "pathVariable": [{"name":"id","type":"int","value":"1","require":"1","remark":"资源ID"}],' . "\n" . '    "cookies": [],' . "\n" . '    "auth": {"type":"bearer","bearer":[{"key":"token","value":"{{token}}","type":"string"}]}' . "\n" . '  },' . "\n" . '  "response": {' . "\n" . '    "responseExample": "{\\"code\\\":0}",' . "\n" . '    "responseParamsDesc": [{"name":"code","type":"int","remark":"状态码" }],' . "\n" . '    "responseFailExample": "",' . "\n" . '    "responseFailParamsDesc": []' . "\n" . '  },' . "\n" . '  "scripts": {"pre": "", "post": ""},' . "\n" . '  "apiStatus": "0"' . "\n" . '}' . "\n\n" . 'Key rules:' . "\n" . '- method: lowercase, e.g. get/post/put/delete/patch' . "\n" . '- require: "1" = required, "0" = optional (string, not boolean)' . "\n" . '- mode: "json" | "formdata" | "urlencoded"' . "\n" . '- When mode=json: put raw JSON string in params.json, field descriptions in params.jsonDesc[]' . "\n" . '- When mode=formdata: put fields in params.formdata[]' . "\n" . '- apiStatus: "0"=none, "1"=开发中, "2"=测试中, "3"=已完成, "4"=需修改, "5"=已废弃' . "\n" . '- auth.type: "bearer" | "basic" | "digest" | "none"' . "\n" . '- scripts.pre/post: JavaScript code executed before/after request',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID（item_type必须为3）',
          ],
          'page_title' => [
            'type' => 'string',
            'description' => '接口名称',
          ],
          'page_content' => [
            'type' => 'object',
            'description' => 'RunApi JSON对象（参见description中的格式说明）',
          ],
          'cat_name' => [
            'type' => 'string',
            'description' => '目录名称（可选，不存在则自动创建，支持/分隔多级目录）',
          ],
          's_number' => [
            'type' => 'integer',
            'description' => '排序号（可选，默认99）',
          ],
        ],
        'required' => ['item_id', 'page_title', 'page_content'],
      ],
      'handler' => 'runapi_page',
    ];

    $this->tools['update_runapi_page'] = [
      'name' => 'update_runapi_page',
      'description' => '更新RunApi项目中的接口页面。仅适用于item_type=3的RunApi项目。page_content必须是完整的RunApi JSON对象（从get_runapi_page获取后修改再传回），不支持部分更新。支持乐观锁：传入expected_hash可检测版本冲突。',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'page_id' => [
            'type' => 'integer',
            'description' => '页面ID',
          ],
          'page_title' => [
            'type' => 'string',
            'description' => '接口名称（可选）',
          ],
          'page_content' => [
            'type' => 'object',
            'description' => '完整的RunApi JSON对象（必须是完整对象，不支持部分更新）',
          ],
          'expected_hash' => [
            'type' => 'string',
            'description' => '期望的当前内容哈希（乐观锁，可选，从get_runapi_page返回的content_hash获取）',
          ],
        ],
        'required' => ['page_id'],
      ],
      'handler' => 'runapi_page',
    ];

    $this->tools['upsert_runapi_page'] = [
      'name' => 'upsert_runapi_page',
      'description' => '按标题智能匹配：存在则更新，不存在则创建。仅适用于item_type=3的RunApi项目。page_content格式与create_runapi_page相同。',
      'inputSchema' => [
        'type' => 'object',
        'properties' => [
          'item_id' => [
            'type' => 'integer',
            'description' => '项目ID（item_type必须为3）',
          ],
          'page_title' => [
            'type' => 'string',
            'description' => '接口名称',
          ],
          'page_content' => [
            'type' => 'object',
            'description' => 'RunApi JSON对象',
          ],
          'cat_name' => [
            'type' => 'string',
            'description' => '目录名称（可选，不存在则自动创建）',
          ],
          's_number' => [
            'type' => 'integer',
            'description' => '排序号（可选，默认99）',
          ],
        ],
        'required' => ['item_id', 'page_title', 'page_content'],
      ],
      'handler' => 'runapi_page',
    ];
  }

  /**
   * 注册默认 Resources
   *
   * @return void
   */
  private function registerDefaultResources(): void
  {
    $this->resources = [
      'showdoc://items' => [
        'uri' => 'showdoc://items',
        'name' => '用户可访问的所有项目',
        'description' => '列出用户有权限访问的所有项目',
        'mimeType' => 'application/json',
      ],
      'showdoc://items/{item_id}' => [
        'uri' => 'showdoc://items/{item_id}',
        'name' => '项目详情',
        'description' => '获取指定项目的详细信息',
        'mimeType' => 'application/json',
      ],
      'showdoc://items/{item_id}/catalogs' => [
        'uri' => 'showdoc://items/{item_id}/catalogs',
        'name' => '项目目录树',
        'description' => '获取指定项目的目录结构',
        'mimeType' => 'application/json',
      ],
      'showdoc://items/{item_id}/pages' => [
        'uri' => 'showdoc://items/{item_id}/pages',
        'name' => '项目页面列表',
        'description' => '获取指定项目的页面列表',
        'mimeType' => 'application/json',
      ],
      'showdoc://pages/{page_id}' => [
        'uri' => 'showdoc://pages/{page_id}',
        'name' => '页面详情',
        'description' => '获取指定页面的详细内容',
        'mimeType' => 'application/json',
      ],
      'showdoc://pages/{page_id}/history' => [
        'uri' => 'showdoc://pages/{page_id}/history',
        'name' => '页面修改历史',
        'description' => '获取指定页面的修改历史列表',
        'mimeType' => 'application/json',
      ],
      'showdoc://pages/{page_id}/versions/{version_id}' => [
        'uri' => 'showdoc://pages/{page_id}/versions/{version_id}',
        'name' => '页面指定版本',
        'description' => '获取指定页面的特定历史版本内容',
        'mimeType' => 'application/json',
      ],
      'showdoc://catalogs/{cat_id}' => [
        'uri' => 'showdoc://catalogs/{cat_id}',
        'name' => '目录详情',
        'description' => '获取指定目录的详细信息',
        'mimeType' => 'application/json',
      ],
    ];
  }

  /**
   * 注册默认 Prompts
   *
   * @return void
   */
  private function registerDefaultPrompts(): void
  {
    $this->prompts = [
      'generate_client_code' => [
        'name' => 'generate_client_code',
        'description' => '根据ShowDoc接口文档生成客户端调用代码',
        'arguments' => [
          [
            'name' => 'page_id',
            'description' => '页面ID，可通过list_pages或search_pages获取',
            'required' => true,
          ],
          [
            'name' => 'language',
            'description' => '目标语言：javascript、typescript、python、java、go、php',
            'required' => false,
          ],
          [
            'name' => 'framework',
            'description' => '目标框架：axios、fetch、react-query、axios-ts等',
            'required' => false,
          ],
        ],
      ],
      'generate_server_code' => [
        'name' => 'generate_server_code',
        'description' => '根据ShowDoc接口文档生成服务端接口代码',
        'arguments' => [
          [
            'name' => 'page_id',
            'description' => '页面ID，可通过list_pages或search_pages获取',
            'required' => true,
          ],
          [
            'name' => 'language',
            'description' => '目标语言：nodejs、python、java、go、php',
            'required' => false,
          ],
          [
            'name' => 'framework',
            'description' => '目标框架：express、koa、fastapi、spring-boot、gin、laravel等',
            'required' => false,
          ],
        ],
      ],
      'generate_docs_from_code' => [
        'name' => 'generate_docs_from_code',
        'description' => '根据代码片段生成接口文档',
        'arguments' => [
          [
            'name' => 'code_snippet',
            'description' => '代码片段',
            'required' => true,
          ],
          [
            'name' => 'doc_type',
            'description' => '文档类型：markdown、runapi',
            'required' => false,
          ],
        ],
      ],
      'sync_api_docs' => [
        'name' => 'sync_api_docs',
        'description' => '扫描代码库，同步整个项目的API文档',
        'arguments' => [
          [
            'name' => 'item_id',
            'description' => '项目ID',
            'required' => true,
          ],
          [
            'name' => 'code_base_path',
            'description' => '代码库路径（可选，用于定位代码文件）',
            'required' => false,
          ],
        ],
      ],
      'compare_impl_and_doc' => [
        'name' => 'compare_impl_and_doc',
        'description' => '对比代码实现与文档描述的差异',
        'arguments' => [
          [
            'name' => 'page_id',
            'description' => '页面ID',
            'required' => true,
          ],
          [
            'name' => 'code_path',
            'description' => '代码文件路径',
            'required' => true,
          ],
        ],
      ],
      'suggest_doc_structure' => [
        'name' => 'suggest_doc_structure',
        'description' => '分析项目文档结构，给出优化建议',
        'arguments' => [
          [
            'name' => 'item_id',
            'description' => '项目ID',
            'required' => true,
          ],
        ],
      ],
      'find_outdated_docs' => [
        'name' => 'find_outdated_docs',
        'description' => '找出长期未更新的文档',
        'arguments' => [
          [
            'name' => 'item_id',
            'description' => '项目ID',
            'required' => true,
          ],
          [
            'name' => 'days',
            'description' => '未更新天数阈值（默认30天）',
            'required' => false,
          ],
        ],
      ],
      'kanban_pick_task' => [
        'name' => 'kanban_pick_task',
        'description' => '从看板中选择一个待办任务',
        'arguments' => [
          [
            'name' => 'item_id',
            'description' => '项目ID',
            'required' => true,
          ],
          [
            'name' => 'list_id',
            'description' => '指定列表ID（可选，不传则从第一个列表选取）',
            'required' => false,
          ],
        ],
      ],
      'kanban_report_progress' => [
        'name' => 'kanban_report_progress',
        'description' => '汇报看板任务的工作进展',
        'arguments' => [
          [
            'name' => 'page_id',
            'description' => '任务页面ID',
            'required' => true,
          ],
          [
            'name' => 'status',
            'description' => '进展状态：done、in_progress、blocked',
            'required' => true,
          ],
          [
            'name' => 'note',
            'description' => '进展说明（可选）',
            'required' => false,
          ],
        ],
      ],
    ];
  }

  /**
   * 设置 Token 信息
   *
   * @param array $tokenInfo Token 信息
   * @return void
   */
  public function setTokenInfo(array $tokenInfo): void
  {
    $this->tokenInfo = $tokenInfo;
  }

  /**
   * 处理 MCP 请求
   *
   * @param array $request JSON-RPC 请求数组
   * @return array JSON-RPC 响应数组
   */
  public function handleRequest(array $request): array
  {
    // 保存请求 ID
    $this->requestId = $request['id'] ?? null;

    try {
      // 验证 JSON-RPC 版本
      if (($request['jsonrpc'] ?? '') !== '2.0') {
        return McpError::createResponse(
          McpError::INVALID_REQUEST,
          '无效的 JSON-RPC 版本',
          null,
          $this->requestId
        );
      }

      $method = $request['method'] ?? '';
      $params = $request['params'] ?? [];

      // 路由到对应的处理方法
      switch ($method) {
        case 'initialize':
          return $this->handleInitialize($params);

        case 'notifications/initialized':
          return $this->handleInitializedNotification();

        case 'tools/list':
          return $this->handleToolsList();

        case 'tools/call':
          return $this->handleToolsCall($params);

        case 'resources/list':
          return $this->handleResourcesList();

        case 'resources/read':
          return $this->handleResourcesRead($params);

        case 'resources/templates/list':
          return $this->handleResourcesTemplatesList();

        case 'prompts/list':
          return $this->handlePromptsList();

        case 'prompts/get':
          return $this->handlePromptsGet($params);

        case 'ping':
          return McpError::createSuccessResponse((object) [], $this->requestId);

        default:
          return McpError::createResponse(
            McpError::METHOD_NOT_FOUND,
            "方法不存在: {$method}",
            null,
            $this->requestId
          );
      }
    } catch (McpException $e) {
      return $e->toResponse($this->requestId);
    } catch (\Throwable $e) {
      return McpError::createResponse(
        McpError::INTERNAL_ERROR,
        '服务器内部错误: ' . $e->getMessage(),
        ['trace' => $e->getTraceAsString()],
        $this->requestId
      );
    }
  }

  /**
   * 处理 initialize 请求
   *
   * @param array $params 参数
   * @return array
   */
  private function handleInitialize(array $params): array
  {
    return McpError::createSuccessResponse([
      'protocolVersion' => '2024-11-05',
      'capabilities' => [
        'tools' => [
          'listChanged' => false,
        ],
        'resources' => [
          'subscribe' => false,
          'listChanged' => false,
        ],
        'prompts' => [
          'listChanged' => false,
        ],
      ],
      'serverInfo' => [
        'name' => 'showdoc-mcp',
        'version' => '1.0.0',
      ],
    ], $this->requestId);
  }

  /**
   * 处理 notifications/initialized 通知
   *
   * Streamable HTTP 客户端会在 initialize 成功后发送该通知。
   *
   * @return array
   */
  private function handleInitializedNotification(): array
  {
    return McpError::createSuccessResponse((object) [], $this->requestId);
  }

  /**
   * 处理 tools/list 请求
   *
   * @return array
   */
  private function handleToolsList(): array
  {
    $tools = [];
    foreach ($this->tools as $name => $tool) {
      $tools[] = [
        'name' => $tool['name'],
        'description' => $tool['description'],
        'inputSchema' => $tool['inputSchema'],
      ];
    }

    return McpError::createSuccessResponse([
      'tools' => $tools,
    ], $this->requestId);
  }

  /**
   * 处理 tools/call 请求
   *
   * @param array $params 参数
   * @return array
   */
  private function handleToolsCall(array $params): array
  {
    $toolName = $params['name'] ?? '';
    $arguments = $params['arguments'] ?? [];

    if (!isset($this->tools[$toolName])) {
      return McpError::createResponse(
        McpError::METHOD_NOT_FOUND,
        "Tool 不存在: {$toolName}",
        null,
        $this->requestId
      );
    }

    $tool = $this->tools[$toolName];
    $handlerName = $tool['handler'];

    try {
      $handler = $this->getHandler($handlerName);
      $handler->setTokenInfo($this->tokenInfo);
      $result = $handler->execute($toolName, $arguments);
      $result = $this->convertLargeIntegersToString($result);

      return McpError::createSuccessResponse([
        'content' => [
          [
            'type' => 'text',
            'text' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
          ],
        ],
      ], $this->requestId);
    } catch (McpException $e) {
      return $e->toResponse($this->requestId);
    }
  }

  /**
   * 处理 resources/list 请求
   *
   * @return array
   */
  private function handleResourcesList(): array
  {
    $resources = array_values($this->resources);
    return McpError::createSuccessResponse([
      'resources' => $resources,
    ], $this->requestId);
  }

  /**
   * 处理 resources/read 请求
   *
   * @param array $params 参数
   * @return array
   */
  private function handleResourcesRead(array $params): array
  {
    $uri = $params['uri'] ?? '';

    // 解析 URI
    if (strpos($uri, 'showdoc://') !== 0) {
      return McpError::createResponse(
        McpError::INVALID_PARAMS,
        '无效的 URI 格式',
        null,
        $this->requestId
      );
    }

    // 解析资源路径
    $path = substr($uri, 10); // 移除 'showdoc://'

    try {
      if (preg_match('#^pages/(\d+)/versions/(\d+)$#', $path, $matches)) {
        // showdoc://pages/{page_id}/versions/{version_id} -> 重定向到 get_page_version
        $handler = $this->getHandler('page');
        $handler->setTokenInfo($this->tokenInfo);
        $result = $handler->execute('get_page_version', [
          'page_id' => (int) $matches[1],
          'version_id' => (int) $matches[2],
        ]);
      } elseif (preg_match('#^pages/(\d+)/history$#', $path, $matches)) {
        // showdoc://pages/{page_id}/history -> 重定向到 get_page_history
        $handler = $this->getHandler('page');
        $handler->setTokenInfo($this->tokenInfo);
        $result = $handler->execute('get_page_history', ['page_id' => (int) $matches[1]]);
      } elseif (preg_match('#^catalogs/(\d+)$#', $path, $matches)) {
        // showdoc://catalogs/{cat_id} -> 重定向到 get_catalog
        $handler = $this->getHandler('catalog');
        $handler->setTokenInfo($this->tokenInfo);
        $result = $handler->execute('get_catalog', ['cat_id' => (int) $matches[1]]);
      } elseif (preg_match('#^items/(\d+)/pages/(\d+)$#', $path, $matches)) {
        // showdoc://items/{item_id}/pages/{page_id} -> 重定向到 get_page
        $handler = $this->getHandler('page');
        $handler->setTokenInfo($this->tokenInfo);
        $result = $handler->execute('get_page', ['page_id' => (int) $matches[2]]);
      } elseif (preg_match('#^items/(\d+)/catalogs$#', $path, $matches)) {
        // showdoc://items/{item_id}/catalogs -> 重定向到 list_catalogs
        $handler = $this->getHandler('catalog');
        $handler->setTokenInfo($this->tokenInfo);
        $result = $handler->execute('list_catalogs', ['item_id' => (int) $matches[1]]);
      } elseif (preg_match('#^items/(\d+)/pages$#', $path, $matches)) {
        // showdoc://items/{item_id}/pages -> 重定向到 list_pages
        $handler = $this->getHandler('page');
        $handler->setTokenInfo($this->tokenInfo);
        $result = $handler->execute('list_pages', ['item_id' => (int) $matches[1]]);
      } elseif (preg_match('#^items/(\d+)$#', $path, $matches)) {
        // showdoc://items/{item_id} -> 重定向到 get_item
        $handler = $this->getHandler('item');
        $handler->setTokenInfo($this->tokenInfo);
        $result = $handler->execute('get_item', ['item_id' => (int) $matches[1]]);
      } elseif ($path === 'items') {
        // showdoc://items -> 重定向到 list_items
        $handler = $this->getHandler('item');
        $handler->setTokenInfo($this->tokenInfo);
        $result = $handler->execute('list_items', []);
      } elseif (preg_match('#^pages/(\d+)$#', $path, $matches)) {
        // showdoc://pages/{page_id} -> 重定向到 get_page
        $handler = $this->getHandler('page');
        $handler->setTokenInfo($this->tokenInfo);
        $result = $handler->execute('get_page', ['page_id' => (int) $matches[1]]);
      } else {
        return McpError::createResponse(
          McpError::RESOURCE_NOT_FOUND,
          "资源不存在: {$uri}",
          null,
          $this->requestId
        );
      }

      $result = $this->convertLargeIntegersToString($result);

      return McpError::createSuccessResponse([
        'contents' => [
          [
            'uri' => $uri,
            'mimeType' => 'application/json',
            'text' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
          ],
        ],
      ], $this->requestId);
    } catch (McpException $e) {
      return $e->toResponse($this->requestId);
    }
  }

  /**
   * 处理 resources/templates/list 请求
   *
   * @return array
   */
  private function handleResourcesTemplatesList(): array
  {
    $templates = [];
    foreach ($this->resources as $resource) {
      if (strpos($resource['uri'], '{') !== false) {
        $templates[] = [
          'uriTemplate' => $resource['uri'],
          'name' => $resource['name'],
          'description' => $resource['description'],
          'mimeType' => $resource['mimeType'],
        ];
      }
    }

    return McpError::createSuccessResponse([
      'resourceTemplates' => $templates,
    ], $this->requestId);
  }

  /**
   * 处理 prompts/list 请求
   *
   * @return array
   */
  private function handlePromptsList(): array
  {
    $prompts = [];
    foreach ($this->prompts as $prompt) {
      $prompts[] = [
        'name' => $prompt['name'],
        'description' => $prompt['description'],
        'arguments' => $prompt['arguments'],
      ];
    }

    return McpError::createSuccessResponse([
      'prompts' => $prompts,
    ], $this->requestId);
  }

  /**
   * 处理 prompts/get 请求
   *
   * @param array $params 参数
   * @return array
   */
  private function handlePromptsGet(array $params): array
  {
    $promptName = $params['name'] ?? '';
    $arguments = $params['arguments'] ?? [];

    if (!isset($this->prompts[$promptName])) {
      return McpError::createResponse(
        McpError::METHOD_NOT_FOUND,
        "Prompt 不存在: {$promptName}",
        null,
        $this->requestId
      );
    }

    $prompt = $this->prompts[$promptName];
    $messages = $this->buildPromptMessages($promptName, $arguments);

    return McpError::createSuccessResponse([
      'description' => $prompt['description'],
      'messages' => $messages,
    ], $this->requestId);
  }

  /**
   * 构建 Prompt 消息
   *
   * @param string $promptName Prompt 名称
   * @param array $arguments 参数
   * @return array
   */
  private function buildPromptMessages(string $promptName, array $arguments): array
  {
    switch ($promptName) {
      case 'generate_client_code':
        $pageId = $arguments['page_id'] ?? '';
        $language = $arguments['language'] ?? 'javascript';
        $framework = $arguments['framework'] ?? 'axios';

        return [
          [
            'role' => 'user',
            'content' => [
              'type' => 'text',
              'text' => "请根据 ShowDoc 接口文档生成 {$language} 客户端调用代码。\n\n" .
                "页面ID: {$pageId}\n" .
                "目标语言: {$language}\n" .
                "目标框架: {$framework}\n\n" .
                "请先使用 get_page 工具获取接口文档内容，然后生成对应的客户端代码。",
            ],
          ],
        ];

      case 'generate_docs_from_code':
        $codeSnippet = $arguments['code_snippet'] ?? '';
        $docType = $arguments['doc_type'] ?? 'markdown';

        return [
          [
            'role' => 'user',
            'content' => [
              'type' => 'text',
              'text' => "请根据以下代码片段生成接口文档。\n\n" .
                "文档类型: {$docType}\n\n" .
                "代码片段:\n```\n{$codeSnippet}\n```\n\n" .
                "请分析代码并生成符合 ShowDoc 格式的接口文档。",
            ],
          ],
        ];

      case 'generate_server_code':
        $pageId = $arguments['page_id'] ?? '';
        $language = $arguments['language'] ?? 'nodejs';
        $framework = $arguments['framework'] ?? 'express';

        return [
          [
            'role' => 'user',
            'content' => [
              'type' => 'text',
              'text' => "请根据 ShowDoc 接口文档生成 {$language} 服务端接口代码。\n\n" .
                "页面ID: {$pageId}\n" .
                "目标语言: {$language}\n" .
                "目标框架: {$framework}\n\n" .
                "请先使用 get_page 工具获取接口文档内容，然后生成对应的服务端接口代码。",
            ],
          ],
        ];

      case 'sync_api_docs':
        $itemId = $arguments['item_id'] ?? '';
        $codeBasePath = $arguments['code_base_path'] ?? '当前目录';

        return [
          [
            'role' => 'user',
            'content' => [
              'type' => 'text',
              'text' => "请扫描代码库并同步项目的 API 文档。\n\n" .
                "项目ID: {$itemId}\n" .
                "代码库路径: {$codeBasePath}\n\n" .
                "请执行以下步骤：\n" .
                "1. 使用 list_pages 工具获取当前项目的所有页面\n" .
                "2. 扫描代码库中的 API 接口定义\n" .
                "3. 对比代码与文档的差异\n" .
                "4. 使用 upsert_page 工具更新或创建文档",
            ],
          ],
        ];

      case 'compare_impl_and_doc':
        $pageId = $arguments['page_id'] ?? '';
        $codePath = $arguments['code_path'] ?? '';

        return [
          [
            'role' => 'user',
            'content' => [
              'type' => 'text',
              'text' => "请对比代码实现与文档描述的差异。\n\n" .
                "页面ID: {$pageId}\n" .
                "代码路径: {$codePath}\n\n" .
                "请执行以下步骤：\n" .
                "1. 使用 get_page 工具获取接口文档内容\n" .
                "2. 读取代码文件内容\n" .
                "3. 对比接口定义（URL、参数、返回值等）\n" .
                "4. 列出差异并给出更新建议",
            ],
          ],
        ];

      case 'suggest_doc_structure':
        $itemId = $arguments['item_id'] ?? '';

        return [
          [
            'role' => 'user',
            'content' => [
              'type' => 'text',
              'text' => "请分析项目文档结构并给出优化建议。\n\n" .
                "项目ID: {$itemId}\n\n" .
                "请执行以下步骤：\n" .
                "1. 使用 list_catalogs 工具获取目录结构\n" .
                "2. 使用 list_pages 工具获取页面列表\n" .
                "3. 分析文档的组织结构、命名规范、完整性\n" .
                "4. 给出具体的优化建议",
            ],
          ],
        ];

      case 'find_outdated_docs':
        $itemId = $arguments['item_id'] ?? '';
        $days = $arguments['days'] ?? 30;

        return [
          [
            'role' => 'user',
            'content' => [
              'type' => 'text',
              'text' => "请找出项目中长期未更新的文档。\n\n" .
                "项目ID: {$itemId}\n" .
                "未更新天数阈值: {$days} 天\n\n" .
                "请执行以下步骤：\n" .
                "1. 使用 list_pages 工具获取页面列表\n" .
                "2. 检查每个页面的更新时间\n" .
                "3. 列出超过 {$days} 天未更新的页面\n" .
                "4. 按更新时间排序，优先展示最久未更新的文档",
            ],
          ],
        ];

      case 'kanban_pick_task':
        $itemId = $arguments['item_id'] ?? '';
        $listId = $arguments['list_id'] ?? '';

        $listHint = $listId !== '' ? "指定列表ID: {$listId}\n" : '';

        return [
          [
            'role' => 'user',
            'content' => [
              'type' => 'text',
              'text' => "请从看板中选择一个待办任务。\n\n" .
                "项目ID: {$itemId}\n" .
                $listHint .
                "\n请执行以下步骤：\n" .
                "1. 使用 kanban_get_board 工具获取看板面板\n" .
                "2. 从指定列表（或第一个列表）中选择一个优先级最高的任务\n" .
                "3. 使用 kanban_get_task 获取任务详情\n" .
                "4. 如果选择了任务，使用 kanban_move_task 将其移到进行中的列表\n" .
                "5. 报告选中的任务信息",
            ],
          ],
        ];

      case 'kanban_report_progress':
        $pageId = $arguments['page_id'] ?? '';
        $status = $arguments['status'] ?? 'in_progress';
        $note = $arguments['note'] ?? '';

        return [
          [
            'role' => 'user',
            'content' => [
              'type' => 'text',
                'text' => "请汇报看板任务的工作进展。\n\n" .
                "任务页面ID: {$pageId}\n" .
                "进展状态: {$status}\n" .
                ($note !== '' ? "进展说明: {$note}\n" : '') .
                "\n请执行以下步骤：\n" .
                "1. 使用 kanban_get_task 获取任务详情\n" .
                "2. 根据状态更新任务信息：\n" .
                "   - 如果状态是 done，使用 kanban_update_task 设置 completed: true\n" .
                "   - 如果状态是 blocked，使用 kanban_update_task 在描述中标注阻塞原因\n" .
                "   - 如果状态是 in_progress，使用 kanban_move_task 将任务移到进行中的列表\n" .
                "3. 如果有进展说明，追加到任务描述中",
            ],
          ],
        ];

      default:
        return [];
    }
  }

  /**
   * 获取 Handler 实例
   *
   * @param string $name Handler 名称
   * @return McpHandler
   */
  private function getHandler(string $name): McpHandler
  {
    if (!isset($this->handlers[$name])) {
      switch ($name) {
        case 'item':
          $this->handlers[$name] = new ItemHandler();
          break;
        case 'catalog':
          $this->handlers[$name] = new CatalogHandler();
          break;
        case 'page':
          $this->handlers[$name] = new PageHandler();
          break;
        case 'attachment':
          $this->handlers[$name] = new AttachmentHandler();
          break;
        case 'openapi':
          $this->handlers[$name] = new OpenApiHandler();
          break;
        case 'kanban':
          $this->handlers[$name] = new KanbanHandler();
          break;
        case 'runapi_page':
          $this->handlers[$name] = new RunapiPageHandler();
          break;
        default:
          throw new \RuntimeException("Handler 不存在: {$name}");
      }
    }

    return $this->handlers[$name];
  }

  private function convertLargeIntegersToString($data, int $depth = 0)
  {
    $maxSafeInteger = 9007199254740991;

    if (is_int($data) && $data > $maxSafeInteger) {
      return (string) $data;
    }

    if ($depth >= 6) {
      return $data;
    }

    if (is_array($data)) {
      $result = [];
      foreach ($data as $key => $value) {
        $result[$key] = $this->convertLargeIntegersToString($value, $depth + 1);
      }
      return $result;
    } elseif (is_object($data)) {
      $result = new \stdClass();
      foreach ($data as $key => $value) {
        $result->$key = $this->convertLargeIntegersToString($value, $depth + 1);
      }
      return $result;
    } else {
      return $data;
    }
  }
}
