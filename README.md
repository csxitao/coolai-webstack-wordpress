# CoolAI WebStack WordPress 导航系统 v2.4.5

一个集成到 WordPress 的网址收藏与导航系统，采用 WebStack 风格的左侧分类 + 右侧卡片布局。项目当前保留原有数据与后端结构，重点优化前端展示、移动端体验、卡片交互、搜索和批量管理。

## 当前状态

- 当前可用版本：v2.4.5
- 主要前端文件：`themes/justnews/coolai-nav.html`
- 预览/参考副本：`coolai-webstack-preview/`
- GitHub 仓库：https://github.com/csxitao/coolai-webstack-wordpress
- 文档只保留根目录一份，避免多处重复导致内容不一致。

## 核心功能

- WebStack 风格布局：左侧分类导航，右侧按分类展示网址卡片。
- 分类管理：新增、重命名、删除、排序、锁定分类。
- 网址管理：新增、编辑、删除、复制链接、刷新图标、访问统计。
- 文件夹/集合：可将多个网址收纳到文件夹，并支持文件夹内卡片操作。
- 搜索：支持本地收藏搜索，也支持跳转到搜索引擎搜索关键词。
- 视图模式：大卡片、小卡片，支持卡片悬浮信息提示。
- 批量操作：批量选择、批量移动、批量删除，也支持删除整个分类。
- 侧边栏模式：标准、窄模式、自动隐藏模式，自动隐藏时支持浮动分类菜单。
- 手机端适配：横向分类栏、紧凑卡片、减少滑动误触拖拽。
- 主题与显示：背景主题、暗黑模式、计数显示、访问统计显示等开关。
- 数据管理：JSON 导入/导出，兼容旧格式和当前分类结构。
- WordPress 集成：登录态、REST API 云同步、后台管理页面。
- Favicon：多服务获取、缓存、手动刷新、后台管理。

## 文件结构

```text
.
├── README.md                 # 项目说明
├── 部署指南.md               # WordPress 部署与更新说明
├── 技术文档.md               # 架构、接口和维护说明
├── mu-plugins/               # 上传到 /wp-content/mu-plugins/
│   ├── coolai-integration.php # REST API、同步、favicon、网页信息抓取
│   ├── coolai-admin.php       # WordPress 后台管理入口
│   └── views/                 # 后台页面视图
│       ├── dashboard.php
│       ├── bookmarks.php
│       ├── my-bookmarks.php
│       ├── favicons.php
│       ├── logs.php
│       └── settings.php
├── themes/justnews/           # 上传到 /wp-content/themes/justnews/
│   ├── coolai-nav.html        # 前端导航主页面
│   └── page-coolai.php        # WordPress 页面模板
└── coolai-webstack-preview/   # 完整预览/部署参考副本
    ├── mu-plugins/
    └── themes/justnews/
```

## 核心上线文件

```text
mu-plugins/
├── coolai-integration.php
├── coolai-admin.php
└── views/

themes/justnews/
├── coolai-nav.html
└── page-coolai.php
```

## 技术栈

- 前端：原生 HTML/CSS/JavaScript
- Tooltip：Tippy.js / Popper.js
- 后端：WordPress REST API / PHP
- 存储：localStorage + WordPress user_meta + WordPress 上传目录/媒体库
- 版本管理：Git / GitHub

## 维护原则

- 数据结构和后端接口保持稳定，优先做显示与体验优化。
- 前端修改优先同步两份文件：`coolai-webstack-preview/themes/justnews/coolai-nav.html` 和 `themes/justnews/coolai-nav.html`。
- 上线前至少检查：导入/导出、搜索、分类切换、手机端显示、文件夹内卡片、tooltip、登录同步。
- 每次稳定版本提交到 GitHub，必要时再同步 Notion 项目说明。
