# Craft Aliyun Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased
### Added
- 添加插件设置与
- 添加插件级别的密钥设置并支持将使用环境变量存取插件密钥
- 在配置了插件级密钥时 OSS Volume 的 Bucket 支持列表选择
- 提供了一些预定义地域节点
- 在配置了插件级密钥时，可以直接在 OSS Volume 中创建 Bucket

### Fixed
- 修复了 OSS Volume 模版文件路径不规范的问题

## 0.1.1 - 2018-09-19
### Changed
- 改变插件类名为 `Plugin`
- 使用插件内置的本地化属性

### Fixed
- 修复在根路径被设置时，卷资源产生了错误的URL的问题

## 0.1.0 - 2018-09-04
### Added
- 初始项目
- 支持 OSS Volume
