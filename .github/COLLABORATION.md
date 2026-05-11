# 团队协作规范

## 1. 角色与职责

### 1.1 团队角色

| 角色 | 主要职责 | 产出物 |
|------|---------|-------|
| 项目经理 (PM) | 项目规划、进度跟踪、需求管理 | 项目计划、里程碑、燃尽图 |
| 技术负责人 (Tech Lead) | 技术方案设计、代码审查、架构决策 | 技术方案、架构图、代码规范 |
| 后端开发工程师 | 后端 API 开发、业务逻辑实现 | PHP 代码、接口文档、数据库设计 |
| 前端开发工程师 | 前端页面开发、组件封装、UI 实现 | Vue 组件、TypeScript 代码 |
| 测试工程师 | 测试用例设计、测试执行、质量保障 | 测试计划、测试报告、缺陷报告 |
| 全栈/AI 代理 | 辅助开发、代码审查、自动化任务 | 代码实现、审查意见 |

### 1.2 职责矩阵 (RACI)

```
任务                    PM   TechLead   Backend   Frontend   Tester
需求分析                A     R          C         C          I
技术方案设计            I     R/A        C         C          I
数据库设计             C     R/A        R         -          -
API 接口设计           C     R          R/A       C          I
前端页面开发           I     C          C         R/A        I
后端逻辑开发           I     C          R/A       -          I
代码审查               I     R/A        R         R          C
单元测试编写           I     C          R         R          A
集成测试执行            I     C         C         C          R/A
部署上线               C     R/A        R         R          I
```

> R = 负责 (Responsible), A = 审批 (Accountable), C = 咨询 (Consulted), I = 知情 (Informed)

---

## 2. 沟通规范

### 2.1 沟通渠道

| 渠道 | 用途 | 响应时间 |
|------|------|---------|
| 即时通讯 (Slack/飞书/钉钉) | 日常沟通、紧急问题 | < 30 分钟 |
| 邮件 | 正式通知、会议纪要 | < 4 小时 |
| GitHub Issues | Bug 反馈、功能需求 | < 24 小时 |
| 项目 Wiki | 文档沉淀、知识共享 | 不紧急 |
| 周会 | 进度同步、问题讨论 | 每周固定时间 |

### 2.2 会议规范

```markdown
## 每日站会 (Daily Standup)
- 时间：每天 9:30
- 时长：≤ 15 分钟
- 内容：
  1. 昨天完成了什么
  2. 今天计划做什么
  3. 遇到什么阻碍

## 周会 (Weekly Meeting)
- 时间：每周一 10:00
- 时长：≤ 60 分钟
- 内容：
  1. 上周工作回顾
  2. 本周工作计划
  3. 技术分享/讨论

## 评审会 (Review Meeting)
- 时间：功能完成后
- 内容：
  1. 代码演示
  2. 审查反馈
  3. 质量评估
```

### 2.3 沟通原则

- **及时响应**：重要消息 24 小时内响应
- **信息完整**：提问时提供足够的上下文
- **结论明确**：避免模糊的表达
- **记录沉淀**：重要讨论结果写入文档

---

## 3. Issue 管理

### 3.1 Issue 类型

```markdown
## Issue 类型
- **feature**: 新功能需求
- **bug**: 缺陷报告
- **improvement**: 功能改进
- **question**: 问题咨询
- **documentation**: 文档改进
```

### 3.2 Issue 模板

```markdown
## Bug 报告模板
### 问题描述
<!-- 清晰描述遇到的问题 -->

### 复现步骤
1. 访问页面
2. 点击按钮
3. ...

### 预期行为
<!-- 描述期望的结果 -->

### 实际行为
<!-- 描述实际的结果 -->

### 环境信息
- 操作系统：
- 浏览器版本：
- Node 版本：
- PHP 版本：

### 截图/日志
<!-- 相关截图或错误日志 -->

---

## 功能需求模板
### 需求描述
<!-- 清晰描述需要的功能 -->

### 需求背景
<!-- 为什么需要这个功能 -->

### 验收标准
1. 完成的定义
2. 可测试的标准
3. ...

### 设计稿/参考
<!-- 相关设计稿或参考链接 -->
```

### 3.3 Issue 状态

```
待处理 (Open) → 进行中 (In Progress) → 已完成 (Closed)
     ↓
   延期 (Postponed)
     ↓
  无法复现 (Cannot Reproduce)
     ↓
   不是 Bug (Not a Bug)
```

---

## 4. 协作流程

### 4.1 功能开发流程

```
需求确认 → 技术方案设计 → 开发实施 → 代码审查 → 测试 → 部署
   ↓            ↓              ↓           ↓        ↓        ↓
  PM/产品    Tech Lead      开发者     团队成员   QA      DevOps
```

#### 4.1.1 需求确认
- 产品/PM 发起需求评审
- 技术负责人评估可行性
- 团队成员理解需求

#### 4.1.2 技术方案设计
- Tech Lead 组织技术评审
- 输出技术方案文档
- 确定 API 接口设计

#### 4.1.3 开发实施
```bash
# 1. 从 develop 创建功能分支
git checkout develop
git pull origin develop
git checkout -b feature/user-authentication

# 2. 开发过程中定期提交
git add .
git commit -m "feat(auth): 实现基础登录功能"

# 3. 推送远程
git push origin feature/user-authentication

# 4. 创建 Pull Request
```

#### 4.1.4 代码审查
- 至少 1 人审查通过
- 解决所有审查意见
- 确保 CI 检查通过

#### 4.1.5 测试
- 单元测试覆盖
- 集成测试通过
- 手动测试验证

#### 4.1.6 部署
- 合并到 develop
- 自动部署到测试环境
- 验证通过后部署生产

### 4.2 Bug 修复流程

```
Bug 报告 → 确认 Bug → 创建分支 → 修复 → 审查 → 合并
    ↓          ↓          ↓         ↓       ↓       ↓
  任何人    Tech Lead   开发者    开发者   团队    自动
```

### 4.3 紧急问题处理

```
发现问题 → 评估影响 → 创建 Hotfix → 修复 → 审查 → 合并 → 部署
    ↓          ↓           ↓          ↓       ↓       ↓       ↓
  任何人    Tech Lead    开发者    开发者   快速    优先    立即
                                        审查   审查
```

---

## 5. Code Review 规范

### 5.1 审查范围

- [ ] 代码风格符合规范
- [ ] 命名清晰有意义
- [ ] 逻辑正确无遗漏
- [ ] 错误处理完善
- [ ] 安全性考虑
- [ ] 性能影响
- [ ] 测试覆盖
- [ ] 文档更新

### 5.2 审查标准

| 等级 | 严重程度 | 处理方式 |
|------|---------|---------|
| Must Fix | 阻断性问题 | 必须修复才能合并 |
| Should Fix | 重要问题 | 强烈建议修复 |
| Consider | 建议改进 | 可以选择性采纳 |
| Nitpick | 轻微问题 | 小技巧，可忽略 |

### 5.3 审查反馈模板

```markdown
## 审查反馈示例

### Must Fix
[文件: user/service/UserService.php:45]
变量命名不清晰，建议修改为更有意义的名字
```php
// ❌ 当前
$u = User::find($id);

// ✅ 建议
$user = User::find($id);
```

### Should Fix
[文件: user/controller/UserController.php:78]
建议添加参数验证
```php
// 当前代码没有验证 userId
public function getUserInfo($userId) {
    // 建议添加
    if (!$userId || !is_numeric($userId)) {
        throw new AppException('Invalid user ID');
    }
}
```

### Consider
可以考虑使用缓存减少数据库查询
```

### 5.4 审查检查清单

**提交者**：
- [ ] 代码自审查通过
- [ ] 提交信息清晰
- [ ] 相关测试已通过
- [ ] 文档已更新

**审查者**：
- [ ] 理解代码意图
- [ ] 检查所有变更
- [ ] 验证测试覆盖
- [ ] 给出明确反馈

---

## 6. 文档协作规范

### 6.1 文档存放位置

```
.github/                      # 团队协作规范
├── CODE_STYLE_GUIDE.md     # 代码风格指南
├── DIRECTORY_STRUCTURE.md   # 目录结构规范
├── NAMING_CONVENTIONS.md    # 命名约定
├── VERSION_CONTROL.md       # 版本控制流程
├── COLLABORATION.md         # 本文档
│
.trae/wiki/                   # 项目 Wiki
├── 01-项目概述.md
├── 02-架构设计.md
├── ...
│
backend/README.md             # 后端文档
frontend/README.md            # 前端文档
```

### 6.2 文档更新流程

```markdown
1. 发现文档问题或需要更新
   ↓
2. 创建文档更新分支
   git checkout -b docs/update-api-doc
   ↓
3. 更新文档
   ↓
4. 提交 Pull Request
   ↓
5. Tech Lead 审查
   ↓
6. 合并到主分支
```

### 6.3 文档维护原则

- **及时更新**：代码变更后及时更新相关文档
- **版本对应**：文档版本与代码版本对应
- **专人负责**：每个文档指定维护人
- **定期审查**：每季度审查文档有效性

---

## 7. 权限与安全规范

### 7.1 仓库权限

| 角色 | 权限 |
|------|------|
| Admin | 完全控制 |
| Maintainer | 管理分支和标签 |
| Developer | 推送和合并代码 |
| Reporter | 只读访问 |

### 7.2 敏感信息管理

```bash
# ✅ 正确做法
# 1. 使用环境变量
DATABASE_PASSWORD=$DB_PASSWORD

# 2. 使用密钥管理服务
# AWS Secrets Manager
# HashiCorp Vault

# ❌ 禁止做法
# 1. 硬编码密码
$password = "123456";

# 2. 提交 .env 文件
git add .env  # 禁止！

# 3. 在代码注释中写密码
// password: admin123  禁止！
```

### 7.3 依赖安全

```bash
# 定期检查依赖漏洞
npm audit                    # Node.js
composer audit               # PHP

# 更新依赖前检查 changelog
npm outdated                 # 检查过期依赖
```

---

## 8. 持续集成/部署规范

### 8.1 CI/CD 流程

```yaml
# .github/workflows/ci.yml
name: CI

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Run ESLint
        run: npm run lint

  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Run Tests
        run: npm run test

  build:
    runs-on: ubuntu-latest
    needs: [lint, test]
    steps:
      - uses: actions/checkout@v4
      - name: Build
        run: npm run build
```

### 8.2 部署环境

| 环境 | 用途 | 部署触发 |
|------|------|---------|
| Development | 开发调试 | 自动部署 |
| Testing | 功能测试 | PR 合并后 |
| Staging | 预发布 | 手动部署 |
| Production | 正式环境 | 版本发布 |

---

**版本**: v1.0
**最后更新**: 2026-05-10
**维护人**: 开发团队