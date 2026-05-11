---
name: find-skills
description: 当用户询问"如何做X"、"查找X的技能"、"是否有技能可以..."，或表达扩展能力兴趣时，帮助用户发现和安装Agent技能。当用户寻找可能作为可安装技能存在的功能时，应使用此技能。
---

# 查找技能

此技能帮助您从开放的Agent技能生态中发现和安装技能。

## 何时使用此技能

当用户：

- 询问"如何做X"，其中X可能是已有技能的常见任务
- 说"查找X的技能"或"是否有X的技能"
- 询问"你能做X吗"，其中X是专业能力
- 表达扩展Agent能力的兴趣
- 想要搜索工具、模板或工作流
- 提到希望在某特定领域获得帮助（设计、测试、部署等）

## 什么是Skills CLI？

Skills CLI（`npx skills`）是开放Agent技能生态的包管理器。技能是模块化包，用专业知识、工作流和工具扩展Agent能力。

**关键命令：**

- `npx skills find [query]` — 交互式或按关键词搜索技能
- `npx skills add <package>` — 从GitHub或其他来源安装技能
- `npx skills check` — 检查技能更新
- `npx skills update` — 更新所有已安装的技能

**浏览技能：** https://skills.sh/

## 如何帮助用户查找技能

### 步骤1：理解用户需求

当用户寻求帮助时，识别：

1. 领域（如React、测试、设计、部署）
2. 具体任务（如编写测试、创建动画、审查PR）
3. 这是否是足够常见的任务，很可能存在技能

### 步骤2：先检查排行榜

在运行CLI搜索之前，检查 [skills.sh排行榜](https://skills.sh/) 看是否已有知名技能覆盖该领域。排行榜按总安装量排名技能，展示最受欢迎和经过实战检验的选项。

例如，Web开发的热门技能包括：
- `vercel-labs/agent-skills` — React、Next.js、Web设计（各10万+安装）
- `anthropics/skills` — 前端设计、文档处理（10万+安装）

### 步骤3：搜索技能

如果排行榜没有覆盖用户需求，运行查找命令：

```bash
npx skills find [query]
```

例如：

- 用户问"如何让我的React应用更快？" → `npx skills find react performance`
- 用户问"你能帮我审查PR吗？" → `npx skills find pr review`
- 用户问"我需要创建变更日志" → `npx skills find changelog`

### 步骤4：推荐前验证质量

**不要仅基于搜索结果推荐技能。** 始终验证：

1. **安装量** — 优先选择1K+安装的技能。100以下的要谨慎。
2. **来源声誉** — 官方来源（`vercel-labs`、`anthropics`、`microsoft`）比未知作者更可信。
3. **GitHub星标** — 检查源仓库。来自少于100星标的仓库的技能应持怀疑态度。

### 步骤5：向用户展示选项

找到相关技能时，向用户展示：

1. 技能名称和功能
2. 安装量和来源
3. 安装命令
4. 在skills.sh上了解更多信息的链接

示例响应：

```
我找到了一个可能有帮助的技能！"react-best-practices"技能提供了来自Vercel Engineering的React和Next.js性能优化指南。
（18.5万安装）

安装命令：
npx skills add vercel-labs/agent-skills@react-best-practices

了解更多：https://skills.sh/vercel-labs/agent-skills/react-best-practices
```

### 步骤6：提供安装

如果用户想继续，可以为他们安装技能：

```bash
npx skills add <owner/repo@skill> -g -y
```

`-g` 标志全局安装（用户级别），`-y` 跳过确认提示。

## 常见技能类别

搜索时，考虑以下常见类别：

| 类别 | 示例查询 |
| ---- | ---------------------------------------- |
| Web开发 | react、nextjs、typescript、css、tailwind |
| 测试 | testing、jest、playwright、e2e |
| DevOps | deploy、docker、kubernetes、ci-cd |
| 文档 | docs、readme、changelog、api-docs |
| 代码质量 | review、lint、refactor、best-practices |
| 设计 | ui、ux、design-system、accessibility |
| 生产力 | workflow、automation、git |

## 有效搜索技巧

1. **使用具体关键词**："react testing"比仅"testing"更好
2. **尝试替代术语**：如果"deploy"不行，试试"deployment"或"ci-cd"
3. **检查热门来源**：许多技能来自 `vercel-labs/agent-skills` 或 `ComposioHQ/awesome-claude-skills`

## 未找到技能时

如果没有找到相关技能：

1. 确认未找到现有技能
2. 提供使用通用能力直接帮助完成任务
3. 建议用户可以创建自己的技能：`npx skills init my-xyz-skill`

示例：

```
我搜索了与"xyz"相关的技能，但没有找到匹配项。
我仍然可以直接帮助您完成此任务！是否继续？

如果这是您经常做的事情，可以创建自己的技能：
npx skills init my-xyz-skill
```

## 使用场景

- **探索未知的技能**：当希望Agent帮忙处理某个特定领域的任务，但不确定Agent是否具备相应能力时，使用此技能进行探索。
- **查找特定的技能**：当明确需要一个技能来解决特定问题，但不知道具体是哪个技能时，主动调用此技能进行精确查找。
- **提供可执行的技能安装建议**：找到一个或多个匹配的技能后，自动整理并输出标准化的推荐信息，包含技能名称、功能简介、一键安装指令及官方链接。
