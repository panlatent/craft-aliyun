<?php
/*
 * @link https://github.com/panlatent/craft-aliyun
 * @copyright Copyright (c) 2024 panlatent@gmail.com
 */

namespace panlatent\craft\aliyun\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;
use panlatent\craft\aliyun\base\CredentialTrait;

/**
 * Credential
 */
class Credential extends Model
{
    use CredentialTrait;

    /**
     * @var int|null
     */
    public ?int $id = null;

    /**
     * @var string|null
     */
    public ?string $name = null;

    /**
     * @var string|null
     */
    public ?string $handle = null;

    /**
     * @var string|null
     */
    public ?string $uid = null;

    /**
     * @var \DateTime|null
     */
    public ?\DateTime $dateCreated = null;

    /**
     * @var \DateTime|null
     */
    public ?\DateTime $dateUpdated = null;

}