<!--
  @文件: dashboard/index.vue
  @用途: 系统仪表盘首页
  @描述: 系统登录后的默认着陆页，展示系统核心统计数据（用户总数、角色总数、菜单总数、部门总数）
         和系统简介信息。统计数据通过后端 API 实时获取，支持暗色主题适配。
         当用户已认证但无菜单权限时，显示权限提示横幅。
  @核心逻辑:
    - 页面挂载时调用 getDashboardStats API 获取统计数据
    - 使用 a-statistic 组件展示带图标的统计卡片
    - 响应式布局：xs 单列、sm 双列、lg 四列
    - 无菜单权限时显示 Alert 提示，引导用户联系管理员
-->
<template>
  <div class="page-container dashboard">
    <!-- 无菜单权限提示：已登录但角色未分配菜单权限时显示 -->
    <a-alert v-if="userStore.noMenuPermission" type="warning" show-icon class="no-permission-alert">
      <template #message>
        <span>当前账户暂无可用菜单权限</span>
      </template>
      <template #description>
        <span>
          您的账户已成功登录，但所分配的角色尚未配置菜单访问权限。请联系系统管理员为您分配相应的菜单权限。
        </span>
      </template>
    </a-alert>

    <!-- 统计卡片区域：响应式栅格布局 -->
    <a-row :gutter="[16, 16]">
      <!-- 用户总数卡片 -->
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
              <!-- 加载中旋转指示器 -->
              <a-spin v-show="loading" size="small" />
            </template>
          </a-statistic>
        </a-card>
      </a-col>

      <!-- 角色总数卡片 -->
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

      <!-- 菜单总数卡片 -->
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

      <!-- 部门总数卡片 -->
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

    <!-- 系统简介内容区域 -->
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
import { useUserStore } from '@/stores/user'

/** 用户状态 store，用于检测无菜单权限状态 */
const userStore = useUserStore()

/** 统计数据：存储仪表盘四项核心指标 */
const stats = reactive({
  userTotal: 0, // 用户总数
  roleTotal: 0, // 角色总数
  menuTotal: 0, // 菜单总数
  deptTotal: 0 // 部门总数
})

/** 数据加载状态 */
const loading = ref(false)

/**
 * 获取仪表盘统计数据
 * @description 调用后端 API 获取系统核心统计指标，更新 stats 响应式对象。
 *              请求失败时显示警告提示，不中断页面渲染。
 */
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

/** 页面挂载时获取统计数据 */
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
  /* 无菜单权限提示横幅 */
  .no-permission-alert {
    margin-bottom: 16px;
  }

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
