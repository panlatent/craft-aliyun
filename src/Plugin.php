<?php
/**
 * @link https://github.com/panlatent/craft-aliyun
 * @copyright Copyright (c) 2018 panlatent@gmail.com
 */

namespace panlatent\craft\aliyun;

use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Volumes;
use panlatent\craft\aliyun\models\Settings;
use panlatent\craft\aliyun\volumes\OssVolume;
use yii\base\Event;

/**
 * Plugin class.
 *
 * @author    Panlatent <panlatent@gmail.com>
 * @package   Aliyun
 * @method Settings getSettings()
 * @property-read Settings $setting
 * @since     0.1.0
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
    public $schemaVersion = '0.1.8';

    /**
     * @var string
     */
    public $t9nCategory = 'aliyun';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Craft::setAlias('@aliyun', $this->getBasePath());

        $this->_registerVolumes();
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse()
    {
        return Craft::$app->getResponse()->refresh('aliyun/settings');
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    // Private Methods
    // =========================================================================

    /**
     * Register volume types.
     */
    private function _registerVolumes()
    {
        Event::on(Volumes::class, Volumes::EVENT_REGISTER_VOLUME_TYPES, function (RegisterComponentTypesEvent $e) {
            $e->types[] = OssVolume::class;
        });
    }
}
