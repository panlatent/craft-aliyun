<?php
/**
 * @link https://github.com/panlatent/craft-aliyun
 * @copyright Copyright (c) 2018 panlatent@gmail.com
 */

namespace panlatent\craft\aliyun\models;

use Craft;
use yii\base\Model;

/**
 * Class Settings
 *
 * @package panlatent\craft\aliyun\models
 * @author Panlatent <panlatent@gmail.com>
 */
class Settings extends Model
{
    /**
     * @var string|null
     */
    public $accessKey;

    /**
     * @var string|null
     */
    public $secretKey;

    /**
     * @var bool Allow volume set accessKey and secretKey
     */
    public $allowVolumeAuthSettings = false;

    /**
     * @return string
     */
    public function getAccessKey()
    {
        return Craft::parseEnv($this->accessKey);
    }

    /**
     * @return string
     */
    public function getSecretKey()
    {
        return Craft::parseEnv($this->secretKey);
    }
}