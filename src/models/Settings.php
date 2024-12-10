<?php
/*
 * @link https://github.com/panlatent/craft-aliyun
 * @copyright Copyright (c) 2024 panlatent@gmail.com
 */

namespace panlatent\craft\aliyun\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;

/**
 * Class Settings
 *
 * @package panlatent\craft\aliyun\models
 * @author Panlatent <panlatent@gmail.com>
 */
class Settings extends Model
{
    /**
     * @var bool
     * @since 1.1.0
     */
    public bool $ossDirectUpload = false;

    /**
     * @return bool
     * @since 1.1.0
     */
    public function getOssDirectUpload(): bool
    {
        return App::parseBooleanEnv($this->ossDirectUpload);
    }
}