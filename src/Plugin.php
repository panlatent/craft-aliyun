<?php
/**
 * Aliyun plugin for Craft 3
 *
 * @link      https://panlatent.com/
 * @copyright Copyright (c) 2018 panlatent@gmail.com
 */

namespace panlatent\craft\aliyun;

use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Volumes;
use panlatent\craft\aliyun\volumes\OssVolume;
use yii\base\Event;

/**
 * Plugin class.
 *
 * @author    Panlatent <panlatent@gmail.com>
 * @package   Aliyun
 * @since     0.1.0
 *
 */
class Plugin extends \craft\base\Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Aliyun::$plugin
     *
     * @var Plugin
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '0.1.0';

    /**
     * @var string
     */
    public $t9nCategory = 'aliyun';

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Aliyun::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Craft::setAlias('@aliyun', $this->getBasePath());

        // Register volume types
        Event::on(Volumes::class, Volumes::EVENT_REGISTER_VOLUME_TYPES, function (RegisterComponentTypesEvent $e) {
            $e->types[] = OssVolume::class;
        });

        Craft::info(
            Craft::t(
                'aliyun',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

}
