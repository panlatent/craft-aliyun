<?php
/**
 * @link https://github.com/panlatent/craft-aliyun
 * @copyright Copyright (c) 2018 panlatent@gmail.com
 */

namespace panlatent\craft\aliyun\models;

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
     * @var bool|null Use .env file save accessKey and secretKey
     */
    public $useDotEnv;

    /**
     * @var string|null
     */
    private $_accessKey;

    /**
     * @var string|null
     */
    private $_secretKey;

    /**
     * @return array
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes[] = 'accessKey';
        $attributes[] = 'secretKey';

        return $attributes;
    }

    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        if ($this->useDotEnv) {
            unset($fields['accessKey'], $fields['secretKey']);
        }

        return $fields;
    }

    /**
     * @return null|string
     */
    public function getAccessKey()
    {
        if ($this->_accessKey !== null) {
            return $this->_accessKey;
        }

        return $this->_accessKey = $this->useDotEnv ? getenv('ALIYUN_ACCESS_KEY') : $this->_accessKey;
    }

    /**
     * @param null|string $accessKey
     */
    public function setAccessKey(string $accessKey)
    {
        $this->_accessKey = $accessKey;
    }

    /**
     * @return null|string
     */
    public function getSecretKey()
    {
        if ($this->_secretKey !== null) {
            return $this->_secretKey;
        }

        return $this->_secretKey = $this->useDotEnv ? getenv('ALIYUN_SECRET_KEY') : $this->_secretKey;
    }

    /**
     * @param null|string $secretKey
     */
    public function setSecretKey(string $secretKey)
    {
        $this->_secretKey = $secretKey;
    }
}