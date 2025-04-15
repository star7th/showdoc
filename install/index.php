<?php
/**
 * ShowDoc安装脚本
 */
include("common.php");

// 执行环境检查
$checkResult = check_environment();
if (!$checkResult['status']) {
    echo implode('<br>', $checkResult['messages']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh">
  <head>
    <meta charset="utf-8">
    <title>ShowDoc</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ShowDoc installation page">
    <meta name="author" content="ShowDoc">
    <style>
      :root {
        --primary-color: #24292e;
        --text-white: #fff;
        --text-dark: #000;
        --transition-time: 0.5s;
      }
      
      * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }
      
      html, body {
        height: 100%;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      }
      
      .container {
        display: flex;
        height: 100%;
        width: 100%;
        position: absolute;
      }

      .flex-item {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        height: 100%;
        transition: all var(--transition-time) ease;
      }
        
      .left {
        width: 50%;
        background-color: var(--text-white);
      }
      
      .right {
        width: 50%;
        background-color: var(--primary-color);
        color: var(--text-white);
      }
      
      .lang-text {
        font-size: 30px;
        cursor: pointer;
        padding: 20px;
        border-radius: 4px;
        transition: all 0.3s ease;
      }
      
      .lang-text:hover {
        transform: scale(1.05);
      }
      
      .left a {
        color: var(--text-dark);
      }

      .right a {
        color: var(--text-white);
      }
      
      .en-tips, .zh-tips {
        display: none;
        font-size: 20px;
        line-height: 1.5;
        text-align: center;
        padding: 20px;
      }
      
      a {
        text-decoration: none;
        font-weight: bold;
      }
      
      a:hover {
        text-decoration: underline;
      }
      
      @media (max-width: 768px) {
        .container {
          flex-direction: column;
        }
        
        .left, .right {
          width: 100%;
          height: 50%;
        }
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="flex-item left">
        <div class="lang-text" id="en">
          Choose language: English &nbsp; →
        </div>

        <div class="en-tips">
          Initialization successful. The default administrator account password is showdoc / 123456.<br>
          After logging in, you can see the management background entrance in the upper right corner.<br>
          <a href="../web/">Click to enter the home page</a>
        </div>
      </div>
      
      <div class="flex-item right">
        <div class="lang-text" id="zh">
          选择语言：中文 &nbsp; →
        </div>
        
        <div class="zh-tips">
          初始化成功。默认管理员账户密码是showdoc/123456。<br>
          登录后，在右上角可以看到管理后台入口。<a href="../web/">点击进入首页</a>
        </div>
      </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // 获取DOM元素
        const enButton = document.getElementById('en');
        const zhButton = document.getElementById('zh');
        const leftPane = document.querySelector('.left');
        const rightPane = document.querySelector('.right');
        const enTips = document.querySelector('.en-tips');
        const zhTips = document.querySelector('.zh-tips');
        
        // 事件监听
        enButton.addEventListener('click', function() {
          handleInstall('en');
        });
        
        zhButton.addEventListener('click', function() {
          handleInstall('zh');
        });
        
        /**
         * 处理安装请求
         * @param {string} lang - 语言代码
         */
        function handleInstall(lang) {
          // 显示加载状态
          const button = lang === 'en' ? enButton : zhButton;
          const originalText = button.innerHTML;
          button.innerHTML = lang === 'en' ? 'Installing...' : '正在安装...';
          button.style.opacity = '0.7';
          
          // 创建XHR请求
          const xhr = new XMLHttpRequest();
          xhr.open('GET', `ajax.php?lang=${lang}`, true);
          xhr.responseType = 'json';
          
          xhr.onload = function() {
            if (xhr.status === 200) {
              const data = xhr.response;
              if (data.error_code === 0) {
                lang === 'en' ? showEnTips() : showZhTips();
              } else {
                button.innerHTML = originalText;
                button.style.opacity = '1';
                alert(data.error_message);
              }
            } else {
              button.innerHTML = originalText;
              button.style.opacity = '1';
              alert(lang === 'en' ? 'Request failed, please try again' : '请求失败，请重试');
            }
          };
          
          xhr.onerror = function() {
            button.innerHTML = originalText;
            button.style.opacity = '1';
            alert(lang === 'en' ? 'Network error, please check your connection' : '网络错误，请检查连接');
          };
          
          xhr.send();
        }
        
        /**
         * 显示英文提示
         */
        function showEnTips() {
          rightPane.style.display = 'none';
          leftPane.style.width = '100%';
          enButton.style.display = 'none';
          enTips.style.display = 'block';
        }
        
        /**
         * 显示中文提示
         */
        function showZhTips() {
          leftPane.style.display = 'none';
          rightPane.style.width = '100%';
          zhButton.style.display = 'none';
          zhTips.style.display = 'block';
        }
      });
    </script>
  </body>
</html>