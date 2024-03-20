<?php
/*
 * @link https://github.com/panlatent/craft-aliyun
 * @copyright Copyright (c) 2024 panlatent@gmail.com
 */

namespace panlatent\craft\aliyun\base;

use craft\helpers\App;

trait CredentialTrait
{
    /**
     * @var string|null
     */
    public ?string $accessKeyId = null;

    /**
     * @var string|null
     */
    public ?string $accessKeySecret = null;

    /**
     * Get the access key.
     *
     * @return string
     */
    public function getAccessKeyId(): string
    {
        return App::parseEnv($this->accessKeyId);
    }

    /**
     * Get the secret key.
     *
     * @return string
     */
    public function getAccessKeySecret(): string
    {
        return App::parseEnv($this->accessKeySecret);
    }
}