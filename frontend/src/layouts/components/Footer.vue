<template>
  <a-layout-footer class="layout-footer">
    <div class="copyright">
      <span>Copyright &copy; {{ dateYear }}</span>
      <!-- 有链接时渲染 a 标签，无链接时渲染 span 避免空链接 -->
      <template v-if="copyright.company">
        <a-divider class="footer-divider" type="vertical" />
        <a
          v-if="copyright.link"
          :href="copyright.link"
          target="_blank"
          rel="noopener noreferrer"
          class="company-link"
        >
          {{ copyright.company }}
        </a>
        <span v-else class="company-text">{{ copyright.company }}</span>
      </template>
      <template v-if="copyright.icp">
        <a-divider class="footer-divider" type="vertical" />
        <a href="https://beian.miit.gov.cn" target="_blank" rel="noopener noreferrer">
          {{ copyright.icp }}
        </a>
      </template>
      <a-divider class="footer-divider" type="vertical" />
      <span>v{{ config.version }}</span>
    </div>
  </a-layout-footer>
</template>

<script setup lang="ts">
import config from '@/config'

// 版权信息配置，提供默认值防止 undefined
const copyright = config.copyright || { company: 'RBAC 权限管理系统', link: '', icp: '' }
// 当前年份，动态生成
const dateYear = new Date().getFullYear()
</script>

<style lang="less" scoped>
.layout-footer {
  padding: 8px 16px;
  text-align: center;
  flex-shrink: 0;
  background: var(--ant-color-bg-layout);

  .copyright {
    color: @layout-footer-light-text;
    font-size: 12px;
    line-height: 1.5;

    a {
      color: @layout-footer-light-text;
      transition: color 0.3s;

      &:hover {
        color: @layout-footer-light-link-hover;
      }
    }

    .company-text {
      color: @layout-footer-light-text;
    }
  }

  .footer-divider {
    margin: 0 8px;
    border-color: @layout-footer-light-divider;
  }
}

html[data-theme='dark'] {
  .layout-footer {
    background: var(--ant-color-bg-layout);

    .copyright {
      color: @layout-footer-dark-text;

      a {
        color: @layout-footer-dark-text;

        &:hover {
          color: @layout-footer-dark-link-hover;
        }
      }

      .company-text {
        color: @layout-footer-dark-text;
      }
    }

    .footer-divider {
      border-color: @layout-footer-dark-divider;
    }
  }
}
</style>
