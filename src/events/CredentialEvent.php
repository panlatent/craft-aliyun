<?php
/*
 * @link https://github.com/panlatent/craft-aliyun
 * @copyright Copyright (c) 2024 panlatent@gmail.com
 */

namespace panlatent\craft\aliyun\events;

use panlatent\craft\aliyun\models\Credential;
use yii\base\Event;

class CredentialEvent extends Event
{
    public ?Credential $credential = null;

    public bool $isNew = false;
}