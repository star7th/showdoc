<?php

namespace App\Mcp\Handler;

use App\Mcp\McpHandler;
use App\Mcp\McpError;
use App\Mcp\McpException;
use App\Model\Item;
use App\Common\Helper\Convert;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * MCP OpenAPI 导入 Handler
 */
class OpenApiHandler extends McpHandler
{
  /**
   * OpenAPI 数据
   *
   * @var array
   */
  private array $jsonArray = [];

  /**
   * URL 前缀
   *
   * @var string
   */
  private string $urlPre = '';

  /**
   * 获取支持的操作列表
   *
   * @return array
   */
  public function getSupportedOperations(): array
  {
    return [
      'import_openapi',
    ];
  }

  /**
   * 执行操作
   *
   * @param string $operation 操作名称
   * @param array $params 参数
   * @return mixed
   * @throws McpException
   */
  public function execute(string $operation, array $params = [])
  {
    switch ($operation) {
      case 'import_openapi':
        return $this->importOpenApi($params);

      default:
        McpError::throw(McpError::METHOD_NOT_FOUND, "操作不存在: {$operation}");
    }
  }

  /**
   * 导入 OpenAPI/Swagger 文档
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function importOpenApi(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    $openapiContent = trim($params['openapi_content'] ?? '');
    $openapiUrl = trim($params['openapi_url'] ?? '');
    $format = trim($params['format'] ?? 'markdown'); // markdown 或 runapi

    // 获取 OpenAPI 内容
    if ($openapiContent !== '') {
      $jsonContent = $openapiContent;
    } elseif ($openapiUrl !== '') {
      $jsonContent = $this->fetchUrl($openapiUrl);
    } else {
      McpError::throw(McpError::INVALID_PARAMS, '请提供 openapi_content 或 openapi_url');
    }

    // 解析 JSON
    $jsonArray = json_decode($jsonContent, true);
    if (empty($jsonArray) || !isset($jsonArray['info'])) {
      McpError::throw(McpError::INVALID_PARAMS, '无效的 OpenAPI/Swagger 文档格式');
    }

    // 检查 OpenAPI 文档大小限制（防止超大文档导致问题）
    $maxContentSize = 150 * 1024 * 1024; // 150MB
    if (strlen($jsonContent) > $maxContentSize) {
      McpError::throw(McpError::OPERATION_FAILED, 'OpenAPI 文档大小超出限制（150MB），请拆分后分批导入');
    }

    // 检查接口数量限制（防止超大文档导入过多页面）
    $paths = $jsonArray['paths'] ?? [];
    $endpointCount = 0;
    foreach ($paths as $url => $methods) {
      $endpointCount += count($methods);
    }
    $maxEndpoints = 25000; // 最多 25000 个接口
    if ($endpointCount > $maxEndpoints) {
      McpError::throw(McpError::OPERATION_FAILED, "OpenAPI 文档包含 {$endpointCount} 个接口，超出单次导入限制（{$maxEndpoints}个），请拆分后分批导入");
    }

    // 检查项目权限
    if ($itemId > 0) {
      $this->requireWritePermission($itemId);
    }
    // 如果没有指定项目，Item::import 会创建新项目

    $this->jsonArray = $jsonArray;

    // 确定 Swagger/OpenAPI 版本
    $swaggerVersion = '';
    if (isset($jsonArray['swagger'])) {
      $swaggerVersion = $jsonArray['swagger'];
    } elseif (isset($jsonArray['openapi'])) {
      $swaggerVersion = $jsonArray['openapi'];
    }

    // 设置 URL 前缀
    $this->setUrlPrefix($jsonArray, $swaggerVersion);

    // 转换引用定义（最多10次）
    for ($i = 0; $i < 10; $i++) {
      $this->jsonArray = $this->transferDefinition($this->jsonArray);
    }

    // 导入数据
    $result = $this->importFromSwagger($this->jsonArray, $itemId, $swaggerVersion, $format);

    return $result;
  }

  /**
   * 从 URL 获取内容
   *
   * @param string $url URL 地址
   * @return string
   * @throws McpException
   */
  private function fetchUrl(string $url): string
  {
    // 验证 URL 格式
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
      McpError::throw(McpError::INVALID_PARAMS, '无效的 URL 格式');
    }

    // 使用 cURL 获取内容
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
      McpError::throw(McpError::OPERATION_FAILED, '获取 OpenAPI 文档失败: ' . $error);
    }

    if ($httpCode !== 200) {
      McpError::throw(McpError::OPERATION_FAILED, "获取 OpenAPI 文档失败: HTTP {$httpCode}");
    }

    return $content;
  }

  /**
   * 设置 URL 前缀
   *
   * @param array $jsonArray OpenAPI 数据
   * @param string $swaggerVersion 版本
   */
  private function setUrlPrefix(array $jsonArray, string $swaggerVersion): void
  {
    if (strstr($swaggerVersion, '2.')) {
      // Swagger 2.0 格式
      $scheme = $jsonArray['schemes'][0] ?? 'http';
      if (!empty($jsonArray['host'])) {
        $this->urlPre = $scheme . "://" . $jsonArray['host'] . ($jsonArray['basePath'] ?? '');
      }
    } else {
      // OpenAPI 3.0 格式
      if (!empty($jsonArray['servers'][0]['url'])) {
        $this->urlPre = $jsonArray['servers'][0]['url'];
      }
    }
  }

  /**
   * 从 Swagger 导入
   *
   * @param array $jsonArray OpenAPI 数据
   * @param int $itemId 项目ID
   * @param string $swaggerVersion 版本
   * @param string $format 格式
   * @return array
   */
  private function importFromSwagger(array $jsonArray, int $itemId, string $swaggerVersion, string $format): array
  {
    $uid = $this->getUid();

    $itemArray = [
      'item_id'         => $itemId,
      'item_name'       => $jsonArray['info']['title'] ?? 'from swagger',
      'item_type'       => ($format === 'runapi') ? '3' : '1',
      'item_description' => $jsonArray['info']['description'] ?? '',
      'password'        => time() . rand(),
      'members'         => [],
      'pages'           => [
        'pages'    => [],
        'catalogs' => $this->getAllTagsLogs($jsonArray, $swaggerVersion, $format),
      ],
    ];

    $newItemId = Item::import(json_encode($itemArray), $uid, $itemId);

    // 统计导入结果
    $catalogCount = count($itemArray['pages']['catalogs']);
    $pageCount = 0;
    foreach ($itemArray['pages']['catalogs'] as $catalog) {
      $pageCount += count($catalog['pages'] ?? []);
    }

    return [
      'item_id' => $newItemId,
      'item_name' => $itemArray['item_name'],
      'is_new_item' => $itemId === 0,
      'swagger_version' => $swaggerVersion,
      'format' => $format,
      'catalog_count' => $catalogCount,
      'page_count' => $pageCount,
      'message' => $itemId > 0 ? 'OpenAPI 文档已导入到现有项目' : '已创建新项目并导入 OpenAPI 文档',
    ];
  }

  /**
   * 获取所有标签（目录）
   *
   * @param array $jsonArray OpenAPI 数据
   * @param string $swaggerVersion 版本
   * @param string $format 格式
   * @return array
   */
  private function getAllTagsLogs(array $jsonArray, string $swaggerVersion, string $format): array
  {
    $catalogsMap = [
      'fromSwagger' => ['cat_name' => 'from swagger', 'pages' => []],
    ];

    $paths = $jsonArray['paths'] ?? [];
    foreach ($paths as $url => $value) {
      foreach ($value as $method => $value2) {
        $tags = $value2['tags'] ?? [];
        if (empty($tags)) {
          $page = $this->requestToDoc($method, $url, $value2, $jsonArray, $swaggerVersion, $format);
          if (!empty($page['page_title'])) {
            $catalogsMap['fromSwagger']['pages'][] = $page;
          }
        } else {
          foreach ($tags as $tag) {
            if (!key_exists($tag, $catalogsMap)) {
              $page = $this->requestToDoc($method, $url, $value2, $jsonArray, $swaggerVersion, $format);
              if (!empty($page['page_title']) && !empty($page['page_content'])) {
                $catalogsMap[$tag] = ['cat_name' => $tag, 'pages' => [$page]];
              }
            } else {
              $page = $this->requestToDoc($method, $url, $value2, $jsonArray, $swaggerVersion, $format);
              if (!empty($page['page_title']) && !empty($page['page_content'])) {
                $catalogsMap[$tag]['pages'][] = $page;
              }
            }
          }
        }
      }
    }

    $catalogs = [];
    foreach ($catalogsMap as $value) {
      $catalogs[] = $value;
    }

    return $catalogs;
  }

  /**
   * 请求转文档
   *
   * @param string $method HTTP 方法
   * @param string $url URL
   * @param array $request 请求数据
   * @param array $jsonArray OpenAPI 数据
   * @param string $swaggerVersion 版本
   * @param string $format 格式
   * @return array
   */
  private function requestToDoc(string $method, string $url, array $request, array $jsonArray, string $swaggerVersion, string $format): array
  {
    $res = $this->requestToApi($method, $url, $request, $jsonArray, $swaggerVersion);
    if ($format === 'runapi') {
      return $res;
    } else {
      $convert = new Convert();
      $res['page_content'] = $convert->runapiToMd($res['page_content']);
      return $res;
    }
  }

  /**
   * 请求转 API 格式
   *
   * @param string $method HTTP 方法
   * @param string $url URL
   * @param array $request 请求数据
   * @param array $jsonArray OpenAPI 数据
   * @param string $swaggerVersion 版本
   * @return array
   */
  private function requestToApi(string $method, string $url, array $request, array $jsonArray, string $swaggerVersion): array
  {
    $return = [];
    $pageTitle = $request['summary'] ?? $request['description'] ?? '';
    if (empty($pageTitle) && !empty($request['operationId'])) {
      $pageTitle = $request['operationId'];
    }
    if (empty($pageTitle)) {
      $pageTitle = strtoupper($method) . ' ' . $url;
    }
    $pageTitle = mb_substr($pageTitle, 0, 50, 'utf-8');
    $return['page_title'] = $pageTitle;
    $return['s_number'] = 99;
    $return['page_comments'] = '';

    $contentArray = [
      'info'     => [
        'from'        => 'runapi',
        'type'       => 'api',
        'title'      => $request['summary'] ?? $request['description'] ?? '',
        'description' => $request['description'] ?? '',
        'method'     => strtolower($method),
        'url'        => $this->urlPre . $url,
        'remark'     => '',
      ],
      'request'  => [
        'params'  => [
          'mode'      => 'formdata',
          'json'      => '',
          'jsonDesc'  => [],
          'urlencoded' => [],
          'formdata'  => [],
        ],
        'query'   => [],
        'headers' => [],
        'cookies' => [],
        'auth'    => [],
      ],
      'response' => [],
      'extend'   => [],
    ];

    // 根据版本处理请求体
    if (strstr($swaggerVersion, '2.')) {
      $this->processSwagger2Request($request, $contentArray);
    } else {
      $this->processOpenAPI3Request($request, $contentArray);
    }

    // 根据版本处理响应
    if (strstr($swaggerVersion, '2.')) {
      $this->processSwagger2Response($request, $contentArray);
    } else {
      $this->processOpenAPI3Response($request, $contentArray);
    }

    $return['page_content'] = json_encode($contentArray);
    return $return;
  }

  /**
   * 处理 Swagger 2.0 请求
   *
   * @param array $request 请求数据
   * @param array $contentArray 内容数组
   */
  private function processSwagger2Request(array $request, array &$contentArray): void
  {
    // 处理参数
    $parameters = $request['parameters'] ?? [];
    foreach ($parameters as $param) {
      $paramType = $param['in'] ?? '';
      $paramName = $param['name'] ?? '';
      $paramDesc = $param['description'] ?? '';
      $paramRequired = isset($param['required']) && $param['required'] ? '1' : '0';
      $paramValueType = $param['type'] ?? 'string';
      $paramValue = $param['default'] ?? $param['example'] ?? '';

      if ($paramType === 'query') {
        $contentArray['request']['query'][] = [
          'name'    => $paramName,
          'type'    => $paramValueType,
          'value'   => $paramValue,
          'require' => $paramRequired,
          'remark'  => $paramDesc,
        ];
      } elseif ($paramType === 'header') {
        $contentArray['request']['headers'][] = [
          'name'    => $paramName,
          'type'    => $paramValueType,
          'value'   => $paramValue,
          'require' => $paramRequired,
          'remark'  => $paramDesc,
        ];
      } elseif ($paramType === 'formData') {
        $contentArray['request']['params']['formdata'][] = [
          'name'    => $paramName,
          'type'    => $paramValueType,
          'value'   => $paramValue,
          'require' => $paramRequired,
          'remark'  => $paramDesc,
        ];
      } elseif ($paramType === 'body') {
        $schema = $param['schema'] ?? [];
        if (!empty($schema['$ref'])) {
          $refArray = $this->getDefinition($schema['$ref']);
          if ($refArray) {
            $contentArray['request']['params']['json'] = json_encode($this->definitionToJson($refArray), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $contentArray['request']['params']['jsonDesc'] = $this->definitionToJsonArray($refArray);
          }
        } elseif (!empty($schema)) {
          $contentArray['request']['params']['json'] = json_encode($this->schemaToJson($schema), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
      }
    }
  }

  /**
   * 处理 OpenAPI 3.0 请求
   *
   * @param array $request 请求数据
   * @param array $contentArray 内容数组
   */
  private function processOpenAPI3Request(array $request, array &$contentArray): void
  {
    // 处理参数
    $parameters = $request['parameters'] ?? [];
    foreach ($parameters as $param) {
      $paramType = $param['in'] ?? '';
      $paramName = $param['name'] ?? '';
      $paramDesc = $param['description'] ?? '';
      $paramRequired = isset($param['required']) && $param['required'] ? '1' : '0';
      $schema = $param['schema'] ?? [];
      $paramValueType = $schema['type'] ?? 'string';
      $paramValue = $schema['default'] ?? $schema['example'] ?? '';

      if ($paramType === 'query') {
        $contentArray['request']['query'][] = [
          'name'    => $paramName,
          'type'    => $paramValueType,
          'value'   => $paramValue,
          'require' => $paramRequired,
          'remark'  => $paramDesc,
        ];
      } elseif ($paramType === 'header') {
        $contentArray['request']['headers'][] = [
          'name'    => $paramName,
          'type'    => $paramValueType,
          'value'   => $paramValue,
          'require' => $paramRequired,
          'remark'  => $paramDesc,
        ];
      }
    }

    // 处理请求体
    $requestBody = $request['requestBody'] ?? [];
    if (!empty($requestBody)) {
      $content = $requestBody['content'] ?? [];
      if (isset($content['application/json'])) {
        $schema = $content['application/json']['schema'] ?? [];
        if (!empty($schema['$ref'])) {
          $refArray = $this->getDefinition($schema['$ref']);
          if ($refArray) {
            $contentArray['request']['params']['json'] = json_encode($this->definitionToJson($refArray), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $contentArray['request']['params']['jsonDesc'] = $this->definitionToJsonArray($refArray);
          }
        } elseif (!empty($schema)) {
          $contentArray['request']['params']['json'] = json_encode($this->schemaToJson($schema), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
      } elseif (isset($content['multipart/form-data'])) {
        $schema = $content['multipart/form-data']['schema'] ?? [];
        if (!empty($schema['properties'])) {
          foreach ($schema['properties'] as $propName => $propValue) {
            $contentArray['request']['params']['formdata'][] = [
              'name'    => $propName,
              'type'    => $propValue['type'] ?? 'string',
              'value'   => $propValue['default'] ?? $propValue['example'] ?? '',
              'require' => in_array($propName, $schema['required'] ?? []) ? '1' : '0',
              'remark'  => $propValue['description'] ?? '',
            ];
          }
        }
      }
    }
  }

  /**
   * 处理 Swagger 2.0 响应
   *
   * @param array $request 请求数据
   * @param array $contentArray 内容数组
   */
  private function processSwagger2Response(array $request, array &$contentArray): void
  {
    $responses = $request['responses'] ?? [];
    foreach ($responses as $code => $response) {
      $schema = $response['schema'] ?? [];
      if (!empty($schema['$ref'])) {
        $refArray = $this->getDefinition($schema['$ref']);
        if ($refArray) {
          $contentArray['response'][] = [
            'name'    => (string) $code,
            'type'    => 'object',
            'value'   => json_encode($this->definitionToJson($refArray), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'require' => '1',
            'remark'  => $response['description'] ?? '',
          ];
        }
      } elseif (!empty($schema)) {
        $contentArray['response'][] = [
          'name'    => (string) $code,
          'type'    => 'object',
          'value'   => json_encode($this->schemaToJson($schema), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
          'require' => '1',
          'remark'  => $response['description'] ?? '',
        ];
      } else {
        $contentArray['response'][] = [
          'name'    => (string) $code,
          'type'    => 'string',
          'value'   => '',
          'require' => '1',
          'remark'  => $response['description'] ?? '',
        ];
      }
    }
  }

  /**
   * 处理 OpenAPI 3.0 响应
   *
   * @param array $request 请求数据
   * @param array $contentArray 内容数组
   */
  private function processOpenAPI3Response(array $request, array &$contentArray): void
  {
    $responses = $request['responses'] ?? [];
    foreach ($responses as $code => $response) {
      $content = $response['content'] ?? [];
      if (isset($content['application/json'])) {
        $schema = $content['application/json']['schema'] ?? [];
        if (!empty($schema['$ref'])) {
          $refArray = $this->getDefinition($schema['$ref']);
          if ($refArray) {
            $contentArray['response'][] = [
              'name'    => (string) $code,
              'type'    => 'object',
              'value'   => json_encode($this->definitionToJson($refArray), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
              'require' => '1',
              'remark'  => $response['description'] ?? '',
            ];
          }
        } elseif (!empty($schema)) {
          $contentArray['response'][] = [
            'name'    => (string) $code,
            'type'    => 'object',
            'value'   => json_encode($this->schemaToJson($schema), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'require' => '1',
            'remark'  => $response['description'] ?? '',
          ];
        }
      } else {
        $contentArray['response'][] = [
          'name'    => (string) $code,
          'type'    => 'string',
          'value'   => '',
          'require' => '1',
          'remark'  => $response['description'] ?? '',
        ];
      }
    }
  }

  /**
   * 获取引用定义
   *
   * @param string $refStr 引用字符串
   * @return array|null
   */
  private function getDefinition(string $refStr): ?array
  {
    $jsonArray = $this->jsonArray;
    $strArray = explode('/', $refStr);

    $targetArray = null;
    if (isset($strArray[2])) {
      $targetArray = $jsonArray[$strArray[1]][$strArray[2]] ?? null;
    }
    if (isset($strArray[3]) && $targetArray) {
      $targetArray = $targetArray[$strArray[3]] ?? null;
    }

    return $targetArray ?: null;
  }

  /**
   * 转换引用定义（递归替换 $ref）
   *
   * @param array $data 数据
   * @return array
   */
  private function transferDefinition(array $data): array
  {
    if (is_array($data)) {
      foreach ($data as $key => $value) {
        if (is_array($value)) {
          if (isset($value['$ref'])) {
            $refArray = $this->getDefinition($value['$ref']);
            if ($refArray) {
              $data[$key] = $refArray;
            }
          } else {
            $data[$key] = $this->transferDefinition($value);
          }
        }
      }
    }
    return $data;
  }

  /**
   * 定义转 JSON 数组（用于参数描述）
   *
   * @param array $refArray 引用数组
   * @return array
   */
  private function definitionToJsonArray(array $refArray): array
  {
    $res = [];
    if (!isset($refArray['properties'])) {
      return $res;
    }

    foreach ($refArray['properties'] as $key => $value) {
      $remark = $value['title'] ?? $value['description'] ?? '';
      $exampleValue = $value['example'] ?? '';
      $required = '0';
      if (isset($refArray['required']) && is_array($refArray['required']) && in_array($key, $refArray['required'])) {
        $required = '1';
      }

      $paramType = $value['type'] ?? 'string';
      if ($paramType === 'int') {
        $paramType = 'integer';
      }

      $res[] = [
        'name'    => $key,
        'type'    => $paramType,
        'value'   => $exampleValue,
        'require' => $required,
        'remark'  => $remark,
      ];
    }

    return $res;
  }

  /**
   * 定义转 JSON（用于示例值）
   *
   * @param array $refArray 引用数组
   * @return array
   */
  private function definitionToJson(array $refArray): array
  {
    $res = [];
    if (!isset($refArray['properties'])) {
      return $res;
    }

    foreach ($refArray['properties'] as $key => $value) {
      $paramType = $value['type'] ?? 'string';

      if ($paramType === 'array') {
        $items = $value['items'] ?? [];
        if (isset($items['$ref'])) {
          $refItems = $this->getDefinition($items['$ref']);
          if ($refItems) {
            $res[$key] = [$this->definitionToJson($refItems)];
          } else {
            $res[$key] = [];
          }
        } elseif (isset($items['type'])) {
          $res[$key] = [$this->getDefaultValue($items['type'])];
        } else {
          $res[$key] = [];
        }
      } elseif ($paramType === 'object') {
        $res[$key] = $this->definitionToJson($value);
      } else {
        $res[$key] = $value['example'] ?? $this->getDefaultValue($paramType);
      }
    }

    return $res;
  }

  /**
   * Schema 转 JSON
   *
   * @param array $schema Schema
   * @return mixed
   */
  private function schemaToJson(array $schema)
  {
    $type = $schema['type'] ?? 'object';

    if ($type === 'array') {
      $items = $schema['items'] ?? [];
      if (isset($items['$ref'])) {
        $refArray = $this->getDefinition($items['$ref']);
        if ($refArray) {
          return [$this->definitionToJson($refArray)];
        }
      } elseif (isset($items['type'])) {
        return [$this->schemaToJson($items)];
      }
      return [];
    } elseif ($type === 'object') {
      $properties = $schema['properties'] ?? [];
      $res = [];
      foreach ($properties as $key => $value) {
        $res[$key] = $this->schemaToJson($value);
      }
      return $res;
    } else {
      return $schema['example'] ?? $this->getDefaultValue($type);
    }
  }

  /**
   * 获取类型默认值
   *
   * @param string $type 类型
   * @return mixed
   */
  private function getDefaultValue(string $type)
  {
    switch ($type) {
      case 'string':
        return '';
      case 'integer':
      case 'number':
        return 0;
      case 'boolean':
        return false;
      case 'array':
        return [];
      case 'object':
        return new \stdClass();
      default:
        return '';
    }
  }
}
