/**
 * 页面
 */
export default {
  username: '用户名',
  name: '姓名',
  addtime: '添加时间',
  title: '标题',
  show: {
    title: '单页分享',
    load_failed: '加载失败',
    page_not_found: '页面不存在',
  },
  catalog: '目录',
  level_2_directory: '二级目录',
  level_3_directory: '三级目录',
  s_number: '序号',
  s_number_explain: '(可选)默认是99，数字越小越靠前',
  history_version: '历史版本',
  page_history_version: '历史版本',
  new_page: '新建页面',
  edit_page: '编辑页面',
  delete_page: '删除页面',
  copy_page: '复制页面',
  page_info: '页面信息',
  sort_page: '页面排序',
  sort_pages: '页面排序',
  update_time: '修改时间',
  update_by_who: '修改人',
  recover_to_this_version: '恢复到此版本',
  confirm_recover_version: '确认恢复到此版本吗？',
  edit_remark: '编辑备注',
  save_success: '保存成功',
  save_and_notify: '保存并通知',
  add_from_template: '从模板添加',
  insert_apidoc_template: 'API接口模板',
  insert_database_doc_template: '数据字典模板',
  insert_api_template: 'API接口模板',
  insert_database_template: '数据字典模板',
  more_templates: '更多模板',
  json_tools: 'JSON工具',
  format_tools: '格式工具',
  json_to_table: 'JSON转参数表格',
  beautify_json: 'JSON格式化',
  sql_to_markdown_table: 'SQL转表格',
  paste_insert_table: '粘贴插入表格',
  my_template: '我的模板',
  item_template: '共享到本项目的模板',
  select_template: '选择模板',
  doc_tools: '文档工具',
  attachment: '附件',
  select_catalog: '选择目录',
  notify_content: '通知内容',
  notify_content_placeholder: '请输入通知内容',
  click_to_edit_page_title: '点击编辑页面标题',
  input_page_title: '请输入页面标题',
  untitled: '未命名',
  save_to_template: '保存为模板',
  save_as_template: '保存为模板',
  lock_edit: '锁定编辑',
  cancel_lock: '取消锁定',
  unlock_edit: '解锁编辑',
  locked: '已锁定',
  unlocked: '已解锁',
  title_required: '页面标题不能为空',
  invalid_json: '无效的 JSON 格式',
  json_content: 'JSON 内容',
  json_content_placeholder: '请输入 JSON 内容',
  table_content: '表格内容',
  table_content_placeholder: '请粘贴表格内容（制表符分隔）',
  sql_content: 'SQL 内容',
  sql_content_placeholder: '请输入 SQL CREATE TABLE 语句',
  sql_to_markdown_table_description: '你可以在此输入一段创建表的SQL（通常是以CREATE TABLE开头），程序会根据SQL来自动插入一个markdown格式的表格，用以描述该表的数据字典',
  mock_config: 'Mock 配置',
  mock_url: 'Mock 地址',
  mock_url_placeholder: '请输入 Mock API 地址',
  mock_response: 'Mock 响应',
  mock_response_placeholder: '请输入 Mock 响应内容',
  mock_response_tips: '这里填写的是Mock接口的返回结果。你可以直接编辑/粘贴一段json字符串，支持使用MockJs语法（关于MockJs语法,可以查看下方的帮助说明按钮）。输入完毕后，点击保存，就会自动生成Mock地址',
  mock_url_and_path: 'Mock Url和路径',
  help_document: '帮助说明',
  beautify_json: 'json快速美化',
  beautify_success: '美化成功',
  json_format_error: 'JSON格式错误，请检查',
  please_input_content: '请先输入内容',
  please_save_page_first: '请先保存页面',
  copy_of: '{title} 的副本',
  fetch_content_failed: '获取页面内容失败',
  item_id_required: '项目ID不能为空',
  catalog_selected: '已选择目录',
  restore_success: '恢复成功',
  document_tools: '文档工具',
  // 其他编辑相关
  minimize: '最小化',
  finish: '完成',
  params: '参数',
  clear: '清除',
  result: '返回结果',
  // 模板相关
  save_to_templ: '另存为模板',
  more_templ: '更多模板',
  saved_templ_list: '保存的模板列表',
  cur_page_content: '当前最新版本',
  overview: '预览',
  save_templ_title: '请为要保存的模板设置标题',
  save_templ_text: '已经保存好模板。你以后新建或者编辑编辑页面时，点击"更多模板"按钮，便可以使用你保存的模板',
  welcome_use_showdoc: '欢迎使用ShowDoc！',
  templ_list: '模板列表',
  templ_title: '模板标题',
  no_templ_text: '你尚未保存过任何模板。你可以在编辑页面时，在"保存"按钮右边点击，在下拉菜单中选择"另存为模板"。把页面内容保存为模板后，你下次新建或者编辑页面时便可以使用你之前保存的模板',
  save_time: '保存时间',
  insert_templ: '插入此模板',
  delete_templ: '删除模板',
  // 工具提示
  paste_insert_table_tips: '你可以从网页或者excel中复制表格，然后粘贴在此处。粘贴并确定后，程序将自动把源表格转为markdown格式的表格。注：复制excel后，请鼠标右击，粘贴为纯文本。否则会当做图片上传。',
  http_test_api: '在线测试API',
  beautify_json_description: '请粘贴一段json，程序将自动以美观的方式格式化显示',
  json_to_table_description: '请粘贴一段json，程序将自动将json解析并生成参数表格。此功能适合用于快速编写API文档的返回参数表格',
  // AI 相关
  ai_assistant: 'AI 助手',
  ai_input_area: '输入区',
  ai_output_area: '输出区',
  ai_input_placeholder: '你可以在此输入描述，告诉AI你想生成什么，然后右边的输出区可以看到结果。更多使用说明可点击下方的帮助说明按钮',
  ai_output_placeholder: ' ',
  ai_generate: '生成',
  ai_help_url: 'https://www.showdoc.com.cn/p/b910aa406c168054994aa9250a23e398',
  ai_help_text: '帮助说明',
  ai_insert_to_editor: '插入到编辑器中',
  ai_generate_failed: 'AI 生成失败',
  // 版本对比相关
  version_comparison: '版本对比',
  cur_page_content: '当前最新版本',
  history_version: '历史版本',
  side_by_side: '并排',
  inline: '行内',
  // 模板相关补充
  template_list: '模板列表',
  template_title: '模板标题',
  template_content_preview: '模板内容预览',
  input_template_title: '请输入模板标题',
  template_title_required: '模板标题不能为空',
  share_template: '共享到项目',
  share_template_tips: '勾选后，可以将模板共享到其他项目，供其他成员使用',
  share_to_items: '共享到项目',
  share_template_to_items: '共享模板到项目',
  select_items_to_share: '选择要共享的项目',
  no_items_to_share: '暂无可共享的项目',
  insert_template: '插入模板',
  share_to_these_items: '共享到这些项目',
  share_items_tips: '被共享到的项目里的成员都可以看到此模板',
  no_my_template_text: '你尚未保存过任何模板',
  no_item_template_text: '该项目下暂无共享模板',
  sharer: '分享者',
  // 锁定和草稿相关
  unsaved_draft: '有未保存的草稿',
  recover_draft_confirm: '检测到本地保存的草稿（时间：{time}），是否恢复？',
  save: '保存',
  lock: '锁定',
  unlock: '解锁',
  lock_success: '锁定成功',
  unlock_success: '解锁成功',
  page_id_required: '页面ID不能为空',
  operation_failed: '操作失败',
  save_template_success: '保存模板成功',
  attachments: '附件',
  paste_html_tips: '检测到剪贴板内容是富文本格式，是否转换为Markdown格式？',
  past_html_markdown: '尝试 html 转 markdown',
  past_html_text: '粘贴纯文本',
  upload_failed: '上传失败',
  upload_image_failed: '图片上传失败',
  // 通知相关补充
  input_update_remark: '请输入修改备注',
  click_to_edit_member: '点此编辑本页面的通知人员名单',
  cur_setting_notify: '当前已设置通知',
  people: '人',
  notify_tips1: '处于通知名单里的人会收到当前页面被修改了的提醒，同时会附上修改备注',
  add_single_member: '单独添加人员',
  add_all_member: '一键添加全部项目成员',
  notify_add_member_tips1: '你只能从项目成员中选择人员。如果你想添加的人不在下拉选项内，请先联系项目管理员添加成员到项目或者相应绑定的团队中。',
  refresh_member_list: '刷新人员列表',
  // 排序相关（已废弃，保留文案）
  sort_pages_tips: '拖动调整页面顺序',
  // 锁定相关
  lock_edit_tips: '锁定后，其他人将无法编辑此页面，直到你解锁或超时自动解锁',
  // 模板内容
  api_template: `### 接口说明

**接口地址：** \`/api/xxx\`

**请求方式：** \`GET\` / \`POST\`

**请求参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | int | 是 | ID |
| name | string | 否 | 名称 |

**返回参数：**

| 参数名 | 类型 | 说明 |
|--------|------|------|
| code | int | 状态码 |
| msg | string | 消息 |
| data | object | 数据 |

**返回示例：**
\`\`\`json
{
  "code": 0,
  "msg": "success",
  "data": {}
}
\`\`\``,
  database_template: `### 数据库说明

**表名：** \`table_name\`

**表说明：** 表的描述

**字段说明：**

| 字段名 | 类型 | 必填 | 默认值 | 说明 |
|--------|------|------|--------|------|
| id | int(11) | 是 | 自增 | 主键ID |
| name | varchar(255) | 否 | '' | 名称 |
| created_at | datetime | 是 | current_timestamp | 创建时间 |

**索引：**
- PRIMARY KEY (\`id\`)
- INDEX \`idx_name\` (\`name\`)`,
  // 附件相关
  filehub: '文件库',
  from_filehub: '从文件库导入',
  file_name: '文件名',
  insert: '插入',
  no_attachments: '暂无附件',
  fetch_attachments_failed: '获取附件列表失败',
  insert_success: '插入成功',
  file_size_tips: '文件大小在20M内',
  import_file_tips2: '点击或拖拽文件至此区域上传',
  batch_upload_support: '支持批量上传',
  upload_queue_files: '待上传文件 ({count}个)',
  file_waiting: '等待上传',
  file_uploading: '上传中',
  file_upload_success: '上传成功',
  file_upload_failed: '上传失败',
  upload_progress: '上传进度',
  clear_queue: '清空队列',
  batch_upload_complete: '批量上传完成',
  upload_success_count: '成功上传 {count} 个文件',
  upload_failed_count: '{count} 个失败',
  bind_success: '绑定成功',
  bind_failed: '绑定失败',
  select: '选择',
  page_limit_exceeded: '该项目页面数量已达到上限',
  page_limit_exceeded_with_link: '该项目页面数量超出限制。项目创建者可以开通高级版以获取更多配额。<a href="/prices" target="_blank">点此查看不同账户类型的额度限制差异</a>，也可以<a href="/user/center" target="_blank">点此去升级账户类型</a>。',
}

