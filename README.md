Aliyun plugin for Craft 3
==========================
[![Build Status](https://travis-ci.org/panlatent/craft-aliyun.svg)](https://travis-ci.org/panlatent/craft-aliyun)
[![Coverage Status](https://coveralls.io/repos/github/panlatent/craft-aliyun/badge.svg?branch=master)](https://coveralls.io/github/panlatent/craft-aliyun?branch=master)
[![Latest Stable Version](https://poser.pugx.org/panlatent/craft-aliyun/v/stable.svg)](https://packagist.org/packages/panlatent/craft-aliyun)
[![Total Downloads](https://poser.pugx.org/panlatent/craft-aliyun/downloads.svg)](https://packagist.org/packages/panlatent/craft-aliyun) 
[![Latest Unstable Version](https://poser.pugx.org/panlatent/craft-aliyun/v/unstable.svg)](https://packagist.org/packages/panlatent/craft-aliyun)
[![License](https://poser.pugx.org/panlatent/craft-aliyun/license.svg)](https://packagist.org/packages/panlatent/craft-aliyun)
[![Craft CMS](https://img.shields.io/badge/Powered_by-Craft_CMS-orange.svg?style=flat)](https://craftcms.com/)
[![Yii2](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)

![Screenshot](resources/img/aliyun.png)

Aliyun plugin for Craft CMS 3. The plugin provide a `Aliyun OSS Volume` can save files in the [Aliyun OSS](https://www.aliyun.com/product/oss).

Features
---------

+ Aliyun OSS Volume

Requirements
------------

This plugin requires Craft CMS 3.0 or later.

Installation
------------

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require panlatent/craft-aliyun

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Aliyun.

Configuration
-------------

1. New a volume and set volume type: `Aliyun OSS Volume`

2. Set `Access Key` and `Secret Key`

3. Set `bucket` and the volume's `public URLs`, the value is URL from `bucket` bound URLs.

FAQ
---

A: 如何同步OSS中已存在（或通过其他途径添加的）资源？
Q：通过 CP -> 实用工具 -> 资源索引