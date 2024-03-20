<?php
/**
 * @link https://github.com/panlatent/craft-aliyun
 * @copyright Copyright (c) 2018 panlatent@gmail.com
 */

namespace panlatent\craft\aliyun;

use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Fs;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use panlatent\craft\aliyun\fs\OSS;
use panlatent\craft\aliyun\models\Settings;
use panlatent\craft\aliyun\services\Credentials;
use panlatent\craft\aliyun\web\twig\CraftVariableBehavior;
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
    // Traits
    // =========================================================================

    use Services;

    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Aliyun::$plugin
     *
     * @var Plugin
     */
    public static Plugin $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public string $schemaVersion = '1.0';

    /**
     * @inheritdoc
     */
    public ?string $t9nCategory = 'aliyun';

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

        $this->_setServices();
        $this->_registerFs();
        $this->_registerProjectConfig();
        $this->_registerCpRoutes();
        $this->_registerVariables();
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect('aliyun/settings/general');
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    // Private Methods
    // =========================================================================

    /**
     * Register filesystem types.
     */
    private function _registerFs(): void
    {
        Event::on(Fs::class, Fs::EVENT_REGISTER_FILESYSTEM_TYPES, function (RegisterComponentTypesEvent $e) {
            $e->types[] = OSS::class;
        });
    }

    private function _registerProjectConfig(): void
    {
        Craft::$app->getProjectConfig()
            ->onAdd('aliyun.credentials.{uid}', [$this->getCredentials(), 'handleChangeCredential'])
            ->onUpdate('aliyun.credentials.{uid}', [$this->getCredentials(), 'handleChangeCredential'])
            ->onRemove('aliyun.credentials.{uid}', [$this->getCredentials(), 'handleDeleteCredential']);
    }

    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'aliyun/settings/credentials/new' => 'aliyun/credentials/edit-credential',
                'aliyun/settings/credentials/<credentialId:\d+>' => 'aliyun/credentials/edit-credential',
            ]);
        });
    }

    private function _registerVariables(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            /** @var CraftVariable $variable */
            $variable = $event->sender;
            $variable->attachBehavior('aliyun', CraftVariableBehavior::class);
        });
    }
}
