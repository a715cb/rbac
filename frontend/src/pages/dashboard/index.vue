<template>
  <div class="page-container dashboard">
    <!-- 统计卡片区域 -->
    <a-row :gutter="[16, 16]">
      <a-col :xs="24" :sm="12" :lg="6">
        <a-card :bordered="false" class="stat-card">
          <a-statistic
            title="用户总数"
            :value="stats.userTotal"
            :precision="0"
            :value-style="{ color: '#3f8600' }"
          >
            <template #prefix>
              <UserOutlined />
            </template>
            <template #suffix>
              <a-spin v-show="loading" size="small" />
            </template>
          </a-statistic>
        </a-card>
      </a-col>

      <a-col :xs="24" :sm="12" :lg="6">
        <a-card :bordered="false" class="stat-card">
          <a-statistic
            title="角色总数"
            :value="stats.roleTotal"
            :precision="0"
            :value-style="{ color: '#cf1322' }"
          >
            <template #prefix>
              <TeamOutlined />
            </template>
          </a-statistic>
        </a-card>
      </a-col>

      <a-col :xs="24" :sm="12" :lg="6">
        <a-card :bordered="false" class="stat-card">
          <a-statistic
            title="菜单总数"
            :value="stats.menuTotal"
            :precision="0"
            :value-style="{ color: '#4073fa' }"
          >
            <template #prefix>
              <MenuOutlined />
            </template>
          </a-statistic>
        </a-card>
      </a-col>

      <a-col :xs="24" :sm="12" :lg="6">
        <a-card :bordered="false" class="stat-card">
          <a-statistic
            title="部门总数"
            :value="stats.deptTotal"
            :precision="0"
            :value-style="{ color: '#faad14' }"
          >
            <template #prefix>
              <WifiOutlined />
            </template>
          </a-statistic>
        </a-card>
      </a-col>
    </a-row>

    <!-- 欢迎内容区域 -->
    <a-card class="welcome-card" :bordered="false">
      <div class="welcome-content">
        <h2>系统简介</h2>
        <p>
          RBAC（Role-Based Access Control）权限管理系统是一种基于角色的访问控制系统。
          通过将权限分配给角色，再将角色分配给用户，实现灵活的权限管理。
        </p>

        <h3>主要功能</h3>
        <ul>
          <li>用户管理：创建、编辑、删除用户</li>
          <li>角色管理：定义角色并分配权限</li>
          <li>菜单管理：配置系统菜单和权限</li>
          <li>权限控制：基于角色的细粒度权限控制</li>
        </ul>
      </div>
    </a-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { UserOutlined, TeamOutlined, MenuOutlined, WifiOutlined } from '@ant-design/icons-vue'
import { getDashboardStats } from '@/api/dashboard'
import { message } from 'ant-design-vue'

// 统计数据响应式对象
const stats = reactive({
  userTotal: 0,
  roleTotal: 0,
  menuTotal: 0,
  deptTotal: 0
})

const loading = ref(false)

// 获取仪表盘统计数据
const fetchStats = async () => {
  loading.value = true
  try {
    const res = await getDashboardStats()
    if (res.data) {
      stats.userTotal = res.data.user_total
      stats.roleTotal = res.data.role_total
      stats.menuTotal = res.data.menu_total
      stats.deptTotal = res.data.dept_total
    }
  } catch (error) {
    message.warning('统计数据加载失败')
  } finally {
    loading.value = false
  }
}

// 组件挂载时获取数据
onMounted(() => {
  fetchStats()
})
</script>

<style lang="less" scoped>
/*
 * 页面容器统一规范：
 * - 不设置 padding（由 DefaultLayout 统一提供 16px）
 * - 不设置背景色（由 DefaultLayout 统一控制）
 */
.dashboard {
  /* 统计卡片样式 */
  .stat-card {
    margin-bottom: 0;

    :deep(.ant-statistic-title) {
      font-size: 14px;
      color: var(--text-color-secondary);
    }

    :deep(.ant-statistic-content) {
      font-size: 24px;
      font-weight: 600;
    }

    :deep(.ant-card-body) {
      padding: 20px 24px;
    }
  }

  /* 欢迎内容卡片 */
  .welcome-card {
    margin-top: 16px;

    :deep(.ant-card-body) {
      padding: 24px;
    }
  }

  /* 欢迎内容文本样式 */
  .welcome-content {
    h2 {
      font-size: 20px;
      margin-bottom: 16px;
      color: var(--text-color);
      font-weight: 500;
    }

    h3 {
      font-size: 16px;
      margin: 24px 0 12px;
      color: var(--text-color);
      font-weight: 500;
    }

    p {
      color: var(--text-color-secondary);
      line-height: 1.8;
    }

    ul {
      list-style: disc;
      padding-left: 20px;

      li {
        color: var(--text-color-secondary);
        line-height: 1.8;
      }
    }
  }
}

/*
 * 暗黑主题适配
 * 参考 Speed CRM 暗黑模式设计规范
 */
[data-theme='dark'] {
  .dashboard {
    /* 统计卡片在暗黑模式下增加背景对比 */
    .stat-card {
      :deep(.ant-card-body) {
        background: #1f1f1f;
      }
    }

    /* 欢迎卡片在暗黑模式下增加背景对比 */
    .welcome-card {
      :deep(.ant-card-body) {
        background: #1f1f1f;
      }
    }

    /* 确保文本在暗黑模式下使用正确的颜色变量 */
    .welcome-content {
      h2,
      h3 {
        color: rgba(255, 255, 255, 0.85);
      }

      p,
      ul li {
        color: rgba(255, 255, 255, 0.65);
      }
    }
  }
}
</style>
