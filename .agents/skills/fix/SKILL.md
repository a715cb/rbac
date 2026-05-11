---
name: fix
description: 当存在lint错误、格式问题，或在提交代码前确保通过CI时使用。
---

# 修复Lint和格式问题

## 指令

1. 运行 `yarn prettier`（或项目等效的格式化命令）修复格式
2. 运行 `yarn linc`（或项目等效的lint检查命令）检查剩余的lint问题
3. 报告任何需要手动修复的问题

## 自适应项目配置

此技能需要根据项目的包管理器和配置自动适配：

### 检测包管理器

```bash
# 检测项目使用的包管理器
if [ -f "yarn.lock" ]; then
  PKG_MANAGER="yarn"
elif [ -f "pnpm-lock.yaml" ]; then
  PKG_MANAGER="pnpm"
elif [ -f "bun.lockb" ]; then
  PKG_MANAGER="bun"
else
  PKG_MANAGER="npm"
fi
```

### 检测格式化和Lint工具

```bash
# 检查package.json中的脚本
cat package.json | grep -E '"(format|lint|prettier|eslint|stylelint)"'
```

### 常见命令映射

| 包管理器 | 格式化命令 | Lint检查命令 |
|---------|-----------|-------------|
| yarn | `yarn prettier` / `yarn format` | `yarn linc` / `yarn lint` |
| npm | `npm run format` / `npx prettier --write .` | `npm run lint` |
| pnpm | `pnpm format` / `pnpm prettier` | `pnpm lint` |
| bun | `bun run format` | `bun run lint` |

## 工作流程

### 步骤1：检测项目配置

1. 检查项目根目录的 `package.json`，识别可用的格式化和lint脚本
2. 检查是否存在 `.prettierrc*`、`.eslintrc*`、`biome.json` 等配置文件
3. 确定包管理器（yarn/npm/pnpm/bun）

### 步骤2：运行格式化

```bash
# 优先使用package.json中定义的脚本
npm run format   # 或 yarn format / pnpm format

# 如果没有format脚本，直接使用prettier
npx prettier --write "src/**/*.{ts,tsx,js,jsx,json,css,md}"
```

### 步骤3：运行Lint检查

```bash
# 优先使用package.json中定义的lint脚本
npm run lint     # 或 yarn lint / pnpm lint

# 如果需要仅检查变更文件
npx eslint $(git diff --name-only --diff-filter=d HEAD | grep -E '\.(ts|tsx|js|jsx)$')
```

### 步骤4：报告结果

格式化完成后，报告：
- 已格式化的文件数量
- 剩余的lint错误（这些需要手动修复）
- 每个lint错误的文件路径、行号和描述

## 常见错误

- **对错误的文件运行prettier** — 确保只格式化项目配置中指定的文件
- **忽略lint错误** — 这些会导致CI失败，提交前必须修复
- **忘记检查暂存文件** — 提交前应确保暂存的文件也通过检查

## 修复策略

### 可自动修复的问题

- 格式不一致（缩进、空格、换行）
- 缺少分号
- 引号风格不一致
- 尾随逗号

### 需要手动修复的问题

- 未使用的变量
- 缺少类型定义
- 逻辑错误
- 缺少依赖项导入
- 复杂度超标的函数

## 使用场景

- **提交代码前的预防性检查**：完成编码执行git commit之前，运行此技能自动清理代码格式，并提示任何需要手动修复的lint错误。
- **修复已发现的lint或格式问题**：编码过程中或接手他人代码时，发现当前工作区内存在明显的格式混乱或lint错误提示，立即运行此技能快速解决代码质量问题。
- **解决持续集成（CI）失败问题**：CI流水线报告因lint或格式错误导致的失败时，在本地对应分支上运行此技能自动修复格式问题，并列出需要手动更正的lint错误。
