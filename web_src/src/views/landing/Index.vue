<template>
  <div class="landing-page">
    <!-- 1. 顶部导航栏 -->
    <LandingHeader />

    <!-- 2. 英雄区 Hero Section -->
      <section class="hero">
      <div class="hero-content">
        <div class="hero-text">
        <h1>
          <span class="subtitle">{{ $t('index.hero_subtitle') || '专为IT团队打造的' }}</span>
          <br class="title-break" />
          <span class="highlight">{{ $t('index.hero_title') || '技术文档协作平台' }}</span>
        </h1>
        <p class="hero-description">
          <span>{{ $t('index.api_docs') || 'API文档' }}</span>
          <span>{{ $t('index.data_dict') || '数据字典' }}</span>
          <span>{{ $t('index.tech_spec') || '技术规范' }}</span>
          <span>{{ $t('index.team_kb') || '团队知识库' }}</span>
        </p>

          <!-- CTA按钮 -->
          <div class="cta-buttons">
            <a :href="demoUrl" target="_blank" class="btn btn-landing-primary">
              {{ $t('index.view_example') || '查看示例' }}
            </a>
            <a :href="helpUrl" target="_blank" class="btn btn-landing-outline">
              {{ $t('index.learn_more') || '了解更多' }}
            </a>
          </div>

          <!-- 特性标签 -->
          <div class="feature-tags">
            <span class="tag">
              <FileOutlined />
              {{ $t('index.quick_start') || '5分钟上手' }}
            </span>
            <span class="tag">
              <TeamOutlined />
              {{ $t('index.team_collab') || '团队协作' }}
            </span>
          </div>
        </div>

        <!-- 代码绘制插画 -->
        <div class="hero-image">
          <HeroIllustration />
        </div>
      </div>
    </section>

    <!-- 3. 核心功能区 Features Section -->
    <section class="features-section">
      <div class="section-header">
        <h2>{{ $t('index.why_choose') || '为什么选择 ShowDoc？' }}</h2>
        <p>{{ $t('index.why_choose_desc') || '让API文档管理简单高效，团队协作如丝般顺滑' }}</p>
      </div>

      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">
            <ApiOutlined />
          </div>
          <h3>{{ $t('index.section_title2') || 'API文档' }}</h3>
          <p>{{ $t('index.api_card_desc') || '简单快速编写美观的API文档' }}</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">
            <DatabaseOutlined />
          </div>
          <h3>{{ $t('index.section_title3') || '数据字典' }}</h3>
          <p>{{ $t('index.data_dict_desc') || '清晰展示数据库结构' }}</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">
            <FileTextOutlined />
          </div>
          <h3>{{ $t('index.section_title4') || '说明文档' }}</h3>
          <p>{{ $t('index.manual_desc') || '工具说明书和技术规范文档' }}</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">
            <TeamOutlined />
          </div>
          <h3>{{ $t('index.section_title5') || '团队协作' }}</h3>
          <p>{{ $t('index.team_card_desc') || '权限管理机制，团队协同编写文档' }}</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">
            <RobotOutlined />
          </div>
          <h3>{{ $t('index.auto_doc_title') || '文档自动化' }}</h3>
          <p>{{ $t('index.auto_card_desc') || '代码注释自动生成文档' }}</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">
            <GithubOutlined />
          </div>
          <h3>{{ $t('index.section_title6') || '免费开源' }}</h3>
          <p>{{ $t('index.open_source_desc') || '可私有部署，数据自主可控' }}</p>
        </div>
      </div>
    </section>

    <!-- 4. 详细功能展示区 Feature Details -->
    <section class="feature-detail">
      <!-- API文档 -->
      <div class="detail-row">
        <div class="detail-content">
          <h2>{{ $t('index.api_doc_title') || '强大的API文档管理' }}</h2>
          <p class="detail-desc">{{ $t('index.api_doc_subtitle') || 'APP、web前端与服务器常用API交互，方便快速编写美观的API文档' }}</p>
          <ul class="detail-list">
            <li>{{ $t('index.api_feature_1') || '支持HTTP方法标识（GET/POST/PUT/DELETE）' }}</li>
            <li>{{ $t('index.api_feature_2') || '代码示例一键复制' }}</li>
            <li>{{ $t('index.api_feature_3') || '参数表格清晰展示' }}</li>
            <li>{{ $t('index.api_feature_4') || '支持Markdown格式化' }}</li>
          </ul>
        </div>
        <div class="detail-image">
          <ApiDocumentIllustration />
        </div>
      </div>

      <!-- 数据字典 -->
      <div class="detail-row reverse">
        <div class="detail-image">
          <DataDictionaryIllustration />
        </div>
        <div class="detail-content">
          <h2>{{ $t('index.data_dict_title') || '清晰展示数据库结构' }}</h2>
          <p class="detail-desc">{{ $t('index.data_dict_detail_desc') || '好的数据字典可以方便描述数据库结构，用ShowDoc编辑出美观的数据字典' }}</p>
          <ul class="detail-list">
            <li>{{ $t('index.data_feature_1') || '表结构可视化展示' }}</li>
            <li>{{ $t('index.data_feature_2') || '字段类型和说明清晰' }}</li>
            <li>{{ $t('index.data_feature_3') || '支持多数据库格式' }}</li>
            <li>{{ $t('index.data_feature_4') || '一键导出共享' }}</li>
          </ul>
        </div>
      </div>

      <!-- 团队协作 -->
      <div class="detail-row">
        <div class="detail-content">
          <h2>{{ $t('index.team_collab_title') || '高效的团队协作' }}</h2>
          <p class="detail-desc">{{ $t('index.team_collab_subtitle') || '团队权限管理机制让团队良好协同编写文档' }}</p>
          <ul class="detail-list">
            <li>{{ $t('index.team_feature_1') || '多种权限级别控制' }}</li>
            <li>{{ $t('index.team_feature_2') || '变更历史追溯' }}</li>
            <li>{{ $t('index.team_feature_3') || '评论反馈机制' }}</li>
          </ul>
        </div>
        <div class="detail-image">
          <TeamCollaborationIllustration />
        </div>
      </div>

      <!-- 自动生成文档 -->
      <div class="detail-row reverse">
        <div class="detail-image">
          <AutoGenerateIllustration />
        </div>
        <div class="detail-content">
          <h2>{{ $t('index.auto_gen_title') || '多种方式自动生成文档' }}</h2>
          <p class="detail-desc">{{ $t('index.auto_gen_subtitle') || '支持从代码注释、Swagger、Postman等多种方式自动生成API文档' }}</p>
          <ul class="detail-list">
            <li>{{ $t('index.auto_gen_feature_1') || '代码注释自动生成' }}</li>
            <li>{{ $t('index.auto_gen_feature_2') || 'Swagger/OpenAPI导入' }}</li>
            <li>{{ $t('index.auto_gen_feature_3') || 'Postman一键导入' }}</li>
            <li>{{ $t('index.auto_gen_feature_4') || 'Markdown压缩包导入' }}</li>
          </ul>
        </div>
      </div>

      <!-- RunAPI客户端 -->
      <div class="detail-row">
        <div class="detail-content">
          <h2>{{ $t('index.runapi_title') || '搭配RunAPI，提升接口开发效率' }}</h2>
          <p class="detail-desc">{{ $t('index.runapi_subtitle') || 'ShowDoc与RunAPI完美集成，接口调试与文档编写无缝衔接，让API开发更高效' }}</p>
          <ul class="detail-list">
            <li>{{ $t('index.runapi_feature_1') || '实时同步接口信息到文档' }}</li>
            <li>{{ $t('index.runapi_feature_2') || '在RunAPI调试，自动生成文档' }}</li>
            <li>{{ $t('index.runapi_feature_3') || 'Mock服务与文档联动' }}</li>
            <li>{{ $t('index.runapi_feature_4') || '前后端协作更顺畅' }}</li>
          </ul>
        </div>
        <div class="detail-image">
          <IntegrationIllustration />
        </div>
      </div>
    </section>

    <!-- 5. 客户案例 Trust Section -->
    <section class="trust-section">
      <div class="trust-content">
        <h2>
          {{ $t('index.over_prefix') || '超过' }}
          <span class="stat-highlight">
            <span class="num-digit">100000+</span>
          </span>
          {{ $t('index.trust_desc') || '互联网团队正在使用 ShowDoc' }}
        </h2>
        <p class="trust-subtitle">{{ $t('index.company_list') || '包括且不仅以下公司团队：' }}</p>

        <!-- 客户Logo展示 -->
        <div class="company-logos">
          <img src="/imgs/case-logo/tencent.png" alt="腾讯" />
          <img src="/imgs/case-logo/huawei_logo.png" alt="华为" />
          <img src="/imgs/case-logo/baidu.png" alt="百度" />
          <img src="/imgs/case-logo/jingdong.png" alt="京东" />
          <img src="/imgs/case-logo/zijie.png" alt="字节跳动" />
          <img src="/imgs/case-logo/shunfeng.png" alt="顺丰" />
        </div>

        <router-link to="/user/register" class="btn btn-primary btn-large">
          {{ $t('index.try_now') || '立即体验' }}
        </router-link>
      </div>
      </section>

    <!-- 6. 对比优势 Comparison -->
    <section class="comparison-section">
      <h2>{{ $t('index.comparison_title') || '告别传统方式，拥抱现代化文档管理' }}</h2>
      <div class="comparison-table">
        <div class="comparison-traditional">
          <h3>{{ $t('index.traditional_doc') || '传统文档管理方式' }}</h3>
          <ul>
            <li>{{ $t('index.comparison_1_no') || '通过IM或邮件沟通，信息零散，沟通成本高' }}</li>
            <li>{{ $t('index.comparison_2_no') || 'Word等离线文档，更新后需重新发送，无法实时同步' }}</li>
            <li>{{ $t('index.comparison_3_no') || '文件多次修改后版本混乱，难以找到最新版' }}</li>
            <li>{{ $t('index.comparison_4_no') || '无法搜索历史文档，查找信息困难' }}</li>
            <li>{{ $t('index.comparison_5_no') || '多人文档协作时容易冲突，权限管理弱' }}</li>
          </ul>
        </div>
        <div class="comparison-showdoc">
          <h3>ShowDoc</h3>
          <ul>
            <li>{{ $t('index.comparison_1_yes') || '集中管理API文档，信息一目了然' }}</li>
            <li>{{ $t('index.comparison_2_yes') || '在线实时更新，团队成员随时访问最新内容' }}</li>
            <li>{{ $t('index.comparison_3_yes') || '自动保存历史版本，可随时追溯和回滚' }}</li>
            <li>{{ $t('index.comparison_4_yes') || '强大的全文搜索，快速找到所需内容' }}</li>
            <li>{{ $t('index.comparison_5_yes') || '多人协作无缝衔接，精细权限控制' }}</li>
          </ul>
        </div>
      </div>

      <!-- 量化对比数据 -->
      <div class="comparison-stats">
        <div class="comparison-stat-item">
          <div class="stat-value">70%</div>
          <div class="stat-desc">{{ $t('index.comparison_communication_save') || '沟通时间节省' }}</div>
        </div>
        <div class="comparison-stat-item">
          <div class="stat-value">5倍</div>
          <div class="stat-desc">{{ $t('index.comparison_search_speed') || '文档查找速度提升' }}</div>
        </div>
        <div class="comparison-stat-item">
          <div class="stat-value">90%</div>
          <div class="stat-desc">{{ $t('index.comparison_update_efficiency') || '文档更新效率提升' }}</div>
        </div>
        <div class="comparison-stat-item">
          <div class="stat-value">100%</div>
          <div class="stat-desc">{{ $t('index.comparison_version_conflict') || '版本冲突减少' }}</div>
        </div>
      </div>
    </section>

    <!-- 7. CTA区 Call to Action -->
    <section class="cta-section">
      <h2>{{ $t('index.start_now_title') || '立即开始使用 ShowDoc' }}</h2>
      <p>
        {{ $t('index.join_teams') || '加入' }}
        <span class="stat-highlight">
          <span class="num-digit">100000+</span>
        </span>
        {{ $t('index.join_teams_suffix') || '团队，让文档协作更高效' }}
      </p>
      <div class="cta-buttons">
        <a :href="demoUrl" target="_blank" class="btn btn-landing-white">
          {{ $t('index.view_example') || '查看示例' }}
        </a>
        <a :href="helpUrl" target="_blank" class="btn btn-landing-transparent">
          {{ $t('index.learn_more') || '了解更多' }}
        </a>
      </div>
      <div class="feature-tags">
        <span class="tag">
          <FileOutlined />
          {{ $t('index.quick_start') || '5分钟上手' }}
        </span>
        <span class="tag">
          <TeamOutlined />
          {{ $t('index.team_collab') || '团队协作' }}
        </span>
      </div>
    </section>

    <!-- 8. 页脚 Footer -->
    <LandingFooter />

    <!-- 回到顶部按钮 -->
    <CommonTop :visibilityHeight="400" />
  </div>
</template>

<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/store/user'
import { useI18n } from 'vue-i18n'
import {
  ApiOutlined,
  DatabaseOutlined,
  FileTextOutlined,
  TeamOutlined,
  RobotOutlined,
  GithubOutlined,
  FileOutlined
} from '@ant-design/icons-vue'
import CommonTop from '@/components/CommonTop.vue'
import LandingHeader from './LandingHeader.vue'
import LandingFooter from './LandingFooter.vue'
import HeroIllustration from './illustrations/HeroIllustration.vue'
import ApiDocumentIllustration from './illustrations/ApiDocumentIllustration.vue'
import DataDictionaryIllustration from './illustrations/DataDictionaryIllustration.vue'
import TeamCollaborationIllustration from './illustrations/TeamCollaborationIllustration.vue'
import AutoGenerateIllustration from './illustrations/AutoGenerateIllustration.vue'
import IntegrationIllustration from './illustrations/IntegrationIllustration.vue'
import request from '@/utils/request'

// 用户状态管理
const userStore = useUserStore()
const router = useRouter()

// 获取当前语言
const { locale } = useI18n()

// 根据语言生成不同的链接
const demoUrl = computed(() => {
  return locale.value === 'en-US'
    ? 'https://www.showdoc.com.cn/demo-en'
    : 'https://www.showdoc.com.cn/demo'
})

const helpUrl = computed(() => {
  return locale.value === 'en-US'
    ? 'https://www.showdoc.com.cn/help-en'
    : 'https://www.showdoc.com.cn/help'
})

// 获取首页设置
const getHomePageSetting = async () => {
  try {
    const data = await request('/api/common/homePageSetting', {}, 'post', false)
    if (data.error_code === 0 && data.data) {
      // 处理首页跳转逻辑
      if (data.data.home_page == 2) {
        // 跳转到登录页面
        router.replace({
          path: '/user/login'
        })
      } else if (
        data.data.home_page == 3 &&
        data.data.home_item
      ) {
        // 跳转到指定项目
        router.replace({
          path: '/' + data.data.home_item
        })
      }
    }
  } catch (error) {
    console.error('获取首页设置失败:', error)
  }
}

// 组件挂载时获取数据
onMounted(() => {
  // 尝试获取用户信息，以便 Header 能正确显示登录状态
  userStore.fetchUserInfo().catch(() => {
    // 忽略获取失败的情况（可能用户未登录）
  })

  // 获取首页设置
  getHomePageSetting()
})
</script>

<style lang="scss" scoped>
@import './landing-common.scss';

.landing-page {
  @extend .landing-page-base;
}

// ========== 2. Hero Section ==========
.hero {
  padding: 140px 40px 100px;
  background: linear-gradient(135deg, var(--landing-hero-bg-start) 0%, var(--landing-hero-bg-end) 100%);
  position: relative;
  overflow: hidden;

  &::before {
    content: '';
    position: absolute;
    top: -100px;
    right: -100px;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, var(--landing-hero-accent-start) 0%, transparent 70%);
    border-radius: 50%;
  }

  &::after {
    content: '';
    position: absolute;
    bottom: -50px;
    left: -50px;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, var(--landing-hero-accent-end) 0%, transparent 70%);
    border-radius: 50%;
  }

  .hero-content {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
    position: relative;
    z-index: 1;
  }

  .hero-text {
    h1 {
      margin: 0 0 35px 0;
      line-height: 1.3;

      .subtitle {
        font-size: 22px;
        font-weight: 400;
        color: var(--color-grey);
        display: block;
        margin-bottom: 12px;
        letter-spacing: 0.5px;
        line-height: 1.5;
      }

      .highlight {
        color: var(--landing-hero-gradient-start);
        display: block;
        position: relative;
        font-size: 56px;
        font-weight: 700;
        background: linear-gradient(135deg, var(--landing-hero-gradient-start), var(--landing-hero-gradient-end));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1.2;
        letter-spacing: -1px;
        padding-bottom: 8px;
        
        &::after {
          content: '';
          position: absolute;
          bottom: 0;
          left: 0;
          width: 80px;
          height: 4px;
          background: linear-gradient(90deg, var(--landing-hero-gradient-start), var(--landing-hero-gradient-end));
          border-radius: 2px;
        }
      }
    }

    .hero-description {
      font-size: 18px;
      color: var(--color-grey);
      margin: 0 0 40px 0;
      font-weight: 400;
      line-height: 1.6;
      display: flex;
      align-items: center;
      justify-content: flex-start;
      flex-wrap: wrap;
      opacity: 0.85;

      span {
        display: inline-flex;
        align-items: center;
        padding: 2px 0;
        
        &:not(:last-child)::after {
          content: '·';
          margin: 0 14px;
          color: var(--color-border);
          font-weight: 300;
          font-size: 16px;
          opacity: 0.6;
        }
      }
    }
  }

  .cta-buttons {
    display: flex;
    gap: 16px;
    margin-bottom: 35px;
    align-items: center;

    .btn {
      padding: 15px 36px;
      font-size: 17px;
      font-weight: 600;
      border-radius: 12px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      letter-spacing: 0.3px;

      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 123, 255, 0.35);
      }

      &:active {
        transform: translateY(0px);
      }
    }

    .btn-landing-primary {
      box-shadow: 0 4px 16px rgba(0, 123, 255, 0.25);

      &::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.25);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
      }

      &:hover::before {
        width: 300px;
        height: 300px;
      }
    }

    .btn-landing-outline {
      &:hover {
        background: rgba(0, 123, 255, 0.08);
        border-color: var(--landing-hero-gradient-start);
      }
    }
  }

  .feature-tags {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;

    .tag {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 9px 16px;
      background: var(--landing-tag-bg);
      border-radius: 20px;
      color: var(--landing-tag-color);
      font-size: 13px;
      font-weight: 500;
      border: 1px solid var(--color-border);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      letter-spacing: 0.2px;

      .anticon {
        font-size: 15px;
        opacity: 0.8;
      }

      &:hover {
        transform: translateY(-2px);
        border-color: var(--color-primary);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.12);
        background: rgba(0, 123, 255, 0.02);
      }
    }
  }

  .hero-image {
    width: 100%;
    height: 500px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
}

// ========== 3. Features Section ==========
.features-section {
  padding: 100px 40px;
  background-color: var(--landing-features-bg);

  .section-header {
    max-width: 1400px;
    margin: 0 auto 60px;
    text-align: center;

    h2 {
      font-size: 42px;
      font-weight: 700;
      color: var(--color-primary);
      margin: 0 0 16px 0;
    }

    p {
      font-size: 20px;
      color: var(--color-grey);
      margin: 0;
    }
  }

  .features-grid {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;

    .feature-card {
      background: var(--landing-feature-card-bg);
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
      text-align: center;

      &:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
      }

      .feature-icon {
        width: 64px;
        height: 64px;
        background: var(--landing-feature-icon-bg);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;

        .anticon {
          font-size: 32px;
          color: white;
        }
      }

      // 为每个卡片设置不同的渐变色
      &:nth-child(1) .feature-icon {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      }
      
      &:nth-child(2) .feature-icon {
        background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
      }
      
      &:nth-child(3) .feature-icon {
        background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
      }
      
      &:nth-child(4) .feature-icon {
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
      }
      
      &:nth-child(5) .feature-icon {
        background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);
      }
      
      &:nth-child(6) .feature-icon {
        background: linear-gradient(135deg, #607d8b 0%, #455a64 100%);
      }

      &:hover .feature-icon {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
      }

      h3 {
        font-size: 24px;
        font-weight: 600;
        color: var(--color-primary);
        margin: 0 0 12px 0;
      }

      p {
        font-size: 16px;
        color: var(--color-grey);
        margin: 0;
        line-height: 1.6;
      }
    }
  }
}

// ========== 4. Feature Details ==========
.feature-detail {
  padding: 100px 40px;
  background-color: var(--landing-detail-bg);

  .detail-row {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 80px;
    align-items: center;

    &.reverse {
      .detail-content {
        order: 2;
      }

      .detail-image {
        order: 1;
      }
    }

    .detail-content {
      min-width: 0; // 允许内容缩小
      
      h2 {
        font-size: 36px;
        font-weight: 700;
        color: var(--color-primary);
        margin: 0 0 20px 0;
        word-wrap: break-word;
        overflow-wrap: break-word;
        hyphens: auto;
      }

      .detail-desc {
        font-size: 18px;
        color: var(--color-grey);
        margin: 0 0 30px 0;
        line-height: 1.6;
      }

      .detail-list {
        list-style: none;
        padding: 0;
        margin: 0;

        li {
          font-size: 16px;
          color: var(--color-grey);
          padding: 12px 0;
          border-bottom: 1px solid var(--color-border);
          display: flex;
          align-items: center;

          &::before {
            content: '✓';
            color: #28a745;
            font-weight: bold;
            margin-right: 12px;
            font-size: 18px;
          }
        }
      }
    }

    .detail-image {
      width: 100%;
      height: 400px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--landing-illustration-bg);
      border-radius: 20px;
      padding: 40px;
      transition: all 0.3s ease;

      &:hover {
        background: rgba(0, 123, 255, 0.02);
        transform: scale(1.02);
      }
    }
  }
}

// ========== 5. Trust Section ==========
.trust-section {
  padding: 100px 40px;
  background: var(--landing-trust-bg);

  .trust-content {
    max-width: 1400px;
    margin: 0 auto;
    text-align: center;

    h2 {
      font-size: 42px;
      font-weight: 700;
      color: var(--color-primary);
      margin: 0 0 20px 0;

      .stat-highlight {
        display: inline-flex;
        gap: 4px;
        margin: 0 8px;

        .num-digit {
          font-size: 52px;
          font-weight: bold;
          background: var(--landing-hero-number-color);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
        }
      }
    }

    .trust-subtitle {
      font-size: 20px;
      color: var(--color-grey);
      margin: 0 0 40px 0;
    }

    .company-logos {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 40px;
      flex-wrap: wrap;
      margin-bottom: 60px;

      img {
        max-width: 150px;
        max-height: 60px;
        opacity: 0.6;
        transition: all 0.3s ease;

        &:hover {
          opacity: 1;
          transform: scale(1.05);
        }
      }
    }
  }
}

.btn-large {
  padding: 18px 50px;
  font-size: 20px;
}

// ========== 6. Comparison Section ==========
.comparison-section {
  padding: 100px 40px;
  background-color: var(--landing-comparison-bg);

  h2 {
    max-width: 1400px;
    margin: 0 auto 60px;
    font-size: 42px;
    font-weight: 700;
    color: var(--color-primary);
    text-align: center;
  }

  .comparison-table {
    max-width: 1000px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;

    .comparison-traditional,
    .comparison-showdoc {
      padding: 40px;
      border-radius: 12px;

      h3 {
        font-size: 24px;
        font-weight: 600;
        margin: 0 0 30px 0;
        text-align: center;
      }

      ul {
        list-style: none;
        padding: 0;
        margin: 0;

        li {
          font-size: 16px;
          padding: 16px 0;
          border-bottom: 1px solid var(--color-border);
          display: flex;
          align-items: center;

          &::before {
            margin-right: 12px;
            font-size: 18px;
          }
        }
      }
    }

    .comparison-traditional {
      background-color: var(--landing-comparison-traditional-bg);

      h3 {
        color: var(--color-grey);
      }

      ul li::before {
        content: '✗';
        color: var(--landing-comparison-cross-color);
      }
    }

    .comparison-showdoc {
      background: var(--landing-comparison-showdoc-bg);

      h3 {
        color: white;
      }

      ul {
        li {
          color: white;
          border-color: rgba(255, 255, 255, 0.2);

          &::before {
            content: '✓';
            color: #28a745;
          }
        }
      }
    }
  }

  .comparison-stats {
    max-width: 1400px;
    margin: 60px auto 0;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;

    .comparison-stat-item {
      text-align: center;
      padding: 30px 20px;
      background: var(--landing-feature-card-bg);
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;

      &:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      }

      .stat-value {
        font-size: 48px;
        font-weight: 700;
        background: linear-gradient(135deg, var(--landing-hero-gradient-start), var(--landing-hero-gradient-end));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 10px;
        line-height: 1;
      }

      .stat-desc {
        font-size: 14px;
        color: var(--color-grey);
        font-weight: 500;
      }
    }
  }
}

// ========== 7. CTA Section ==========
.cta-section {
  padding: 100px 40px;
  background: var(--landing-cta-bg);
  text-align: center;

  h2 {
    font-size: 42px;
    font-weight: 700;
    color: white;
    margin: 0 0 20px 0;
  }

  > p {
    font-size: 20px;
    color: var(--landing-cta-text);
    margin: 0 0 40px 0;

    .stat-highlight {
      display: inline-flex;
      gap: 4px;
      margin: 0 8px;

      .num-digit {
        font-size: 32px;
        font-weight: bold;
        color: white;
      }
    }
  }

  .cta-buttons {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-bottom: 30px;

    .btn {
      &.btn-white {
        @extend .btn-white;
      }

      &.btn-transparent {
        @extend .btn-transparent;
      }
    }
  }

  .feature-tags {
    display: flex;
    justify-content: center;
    gap: 16px;

    .tag {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 8px 16px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 20px;
      color: white;
      font-size: 14px;
      font-weight: 500;
    }
  }
}

// ========== 8. Footer (使用公共样式) ==========


// ========== Responsive Design ==========
@media (max-width: 1200px) {
  .features-grid {
    grid-template-columns: repeat(2, 1fr) !important;
  }

  .recommended-items {
    grid-template-columns: repeat(3, 1fr) !important;
  }
}

@media (max-width: 992px) {
  .hero-content {
    grid-template-columns: 1fr !important;
    gap: 40px !important;
  }

  .hero-text {
    text-align: center;

    h1 {
      margin-bottom: 28px !important;
      
      .subtitle {
        font-size: 18px !important;
        margin-bottom: 10px !important;
      }

      .highlight {
        font-size: 40px !important;
        letter-spacing: -0.5px !important;
        
        &::after {
          width: 60px !important;
          height: 3px !important;
        }
      }
      
      .title-break {
        display: inline;
      }
    }

    .hero-description {
      font-size: 15px !important;
      margin: 0 0 32px 0 !important;
      justify-content: center;
      
      span:not(:last-child)::after {
        margin: 0 10px !important;
      }
    }
    }

    .cta-buttons {
      justify-content: center;
    }

    .feature-tags {
    justify-content: center;
  }

  .hero-image {
    display: none;
  }

  .detail-row {
    grid-template-columns: 1fr !important;
    gap: 40px !important;

    &.reverse {
      .detail-content {
        order: 0;
      }

      .detail-image {
        order: 0;
      }
    }
  }

  .comparison-table {
    grid-template-columns: 1fr !important;
    gap: 20px !important;
  }

  .comparison-stats {
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 20px !important;
  }

  .trust-stats {
    flex-direction: column;
    gap: 40px !important;
  }

  .company-logos {
    gap: 20px !important;
  }
}

@media (max-width: 768px) {
  .hero {
    padding: 100px 20px 60px !important;

    h1 {
      margin-bottom: 24px !important;
      
      .subtitle {
        font-size: 15px !important;
        margin-bottom: 8px !important;
        display: block;
      }

      .highlight {
        font-size: 32px !important;
        display: block;
        letter-spacing: -0.5px !important;
        
        &::after {
          width: 50px !important;
          height: 3px !important;
        }
      }
      
      .title-break {
        display: none;
      }
    }

    .hero-description {
      font-size: 13px !important;
      margin: 0 0 28px 0 !important;
      justify-content: center;
      
      span:not(:last-child)::after {
        margin: 0 8px !important;
        font-size: 14px !important;
      }
    }

    .cta-buttons {
      .btn {
        padding: 13px 28px !important;
        font-size: 15px !important;
      }
    }

    .feature-tags {
      justify-content: center;
      
      .tag {
        font-size: 12px !important;
        padding: 8px 14px !important;
      }
    }

    .stat-number {
      .num-digit {
        font-size: 28px !important;
      }
      
      .num-unit {
        font-size: 12px !important;
      }
    }
  }

  .features-grid {
    grid-template-columns: 1fr !important;
  }

  .comparison-stats {
    grid-template-columns: 1fr !important;
    gap: 15px !important;
    margin-top: 40px !important;

    .comparison-stat-item {
      padding: 20px 15px !important;

      .stat-value {
        font-size: 36px !important;
      }

      .stat-desc {
        font-size: 13px !important;
      }
    }
  }

  .cta-buttons {
    flex-direction: column;
    width: 100%;

    .btn {
      width: 100%;
      text-align: center;
    }
  }
}

@media (max-width: 480px) {
  .hero {
    h1 {
      .subtitle {
        font-size: 14px !important;
      }

      .highlight {
        font-size: 28px !important;
        
        &::after {
          width: 40px !important;
          height: 2px !important;
        }
      }
    }

    .hero-description {
      font-size: 12px !important;
      margin: 0 0 24px 0 !important;
      
      span:not(:last-child)::after {
        margin: 0 6px !important;
      }
    }

    .stat-number {
      .num-digit {
        font-size: 24px !important;
      }
    }

    .cta-buttons {
      .btn {
        padding: 12px 24px !important;
        font-size: 14px !important;
      }
    }

    .feature-tags {
      .tag {
        font-size: 11px !important;
        padding: 7px 12px !important;
      }
    }
  }
}
</style>
