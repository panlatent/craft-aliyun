<?php
/*
 * @link https://github.com/panlatent/craft-aliyun
 * @copyright Copyright (c) 2024 panlatent@gmail.com
 */

namespace panlatent\craft\aliyun\web\twig;

use panlatent\craft\aliyun\Plugin;
use yii\base\Behavior;

/**
 * Class CraftVariableBehavior
 */
class CraftVariableBehavior extends Behavior
{
    public ?Plugin $aliyun = null;

    public function init(): void
    {
        $this->aliyun = Plugin::$plugin;
    }
}