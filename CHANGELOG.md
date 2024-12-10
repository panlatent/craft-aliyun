# Craft Aliyun Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased
### Updated
### Fixed

## [1.1.0] - 2024-12-10

### Added

- Add client direct upload to OSS

## [1.0.2] - 2024-05-29

### Updated

- Support Craft CMS 5

## [1.0.1] - 2024-05-08

### Fixed

- Fixed root url error with oss root config

## [1.0] - 2024-03-19

### Added

- Support Craft 4
- Credential manager
- OSS Filesystem Type

### Deprecated

- Craft 3 is not supported

### Removed

- OSS Volume type

## [0.1.8.1] - 2022-05-17

### Fixed

- 修复插件设置页面无法载入的问题

## [0.1.8] - 2021-04-08

### Updated

- 提升 PHP 和 CraftCMS 最低版本要求: PHP(7.2.5) ,CraftCMS(3.6.0)

## [0.1.7.2] - 2019-08-21

### Fixed

- 修复了具有公开 URL 地址(CDN)的私有 Bucket 不能正确获取文件对象的问题

## [0.1.7.1] - 2019-02-24

### Updated

- 移除了 OSS Volume 获取 Bucket 建议列表

### Fixed

- 修复了未设置正确的插件 AK、SK 导致无法创建任何卷的问题

## [0.1.7] - 2019-02-21

### Updated

- 为 OSS Volume 设置添加了已有 Bucket 的自动完成功能

### Fixed

- 修复创建新 OSS Volume 时因为缺少节点导致获取 Bucket 列表异常

## [0.1.6] - 2019-02-20

### Updated

- 支持 OSS Volume 从环境中获取 Bucket

### Fixed

- 修复 OSS Volume Object URL 生成错误

## [0.1.5] - 2019-02-20

### Updated

- 移除了 OSS Volume 的 isPublic 属性

### Fixed

- 修复了公开卷 URL 路径问题
- 修复了目录下文件为空时，无法删除目录的问题
- 修复了不显示 Bucket 列表的问题

## [0.1.4] - 2019-02-20

### Updated

- 插件设置页面支持环境变量自动完成
- OSS Volume 根路径设置支持环境变量及自动完成

## [0.1.3] - 2019-02-19

### Fixed

- 修复了 OSS Volume 不能读取私有 Bucket 文件的问题

## [0.1.2] - 2019-02-19

### Fixed

- 修复了 OSS Volume 不能正确列出目录索引的问题
- 修复了 OSS Volume 重命名目录未删除原目录的问题

## [0.1.2-alpha.1] - 2018-12-09

### Added

- 添加插件设置面版
- 支持将使用环境变量存取插件密钥
- OSS Volume 的 Bucket 支持列表选择（在配置了插件级密钥时）
- 提供了预定义地域节点列表
- 在配置了插件级密钥时，可以直接在 OSS Volume 中选取已创建的 Bucket

### Fixed

- 修复了 OSS Volume 模版文件路径不规范的问题

## [0.1.1] - 2018-09-19

### Changed

- 改变插件类名为 `Plugin`
- 使用插件内置的本地化属性

### Fixed

- 修复在根路径被设置时，卷资源产生了错误的 URL 的问题

## 0.1.0 - 2018-09-04

### Added

- 初始项目
- 支持 OSS Volume
