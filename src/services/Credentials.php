<?php
/*
 * @link https://github.com/panlatent/craft-aliyun
 * @copyright Copyright (c) 2024 panlatent@gmail.com
 */

namespace panlatent\craft\aliyun\services;

use Craft;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use panlatent\craft\aliyun\db\Table;
use panlatent\craft\aliyun\events\CredentialEvent;
use panlatent\craft\aliyun\models\Credential;
use yii\base\Component;

class Credentials extends Component
{
    // Events
    // -------------------------------------------------------------------------

    /**
     * @event CredentialEvent The event that is triggered before a credential is saved.
     */
    const EVENT_BEFORE_SAVE_CREDENTIAL = 'beforeSaveCredential';

    /**
     * @event CredentialEvent The event that is triggered before a credential is saved.
     */
    const EVENT_AFTER_SAVE_CREDENTIAL = 'afterSaveCredential';

    /**
     * @event CredentialEvent The event that is triggered before a credential is deleted.
     */
    const EVENT_BEFORE_APPLY_DELETE_CREDENTIAL = 'beforeApplyDeleteCredential';

    /**
     * @event CredentialEvent The event that is triggered after a credential is deleted.
     */
    const EVENT_AFTER_DELETE_CREDENTIAL = 'afterDeleteCredential';


    // Properties
    // =========================================================================

    /**
     * @var Credential[]|null
     */
    private ?array $_credentials = null;

    // Public Methods
    // =========================================================================

    /**
     * @return Credential[]
     */
    public function getAllCredentials(): array
    {
        if (!isset($this->_credentials)) {
            $this->_credentials = [];
            $results = $this->_createQuery()->all();
            foreach ($results as $result) {
                $this->_credentials[] = new Credential($result);
            }
        }

        return $this->_credentials;
    }

    public function getCredentialById($id): ?Credential
    {
        return ArrayHelper::firstWhere($this->getAllCredentials(), 'id', $id);
    }

    public function getCredentialByHandle($handle): ?Credential
    {
        return ArrayHelper::firstWhere($this->getAllCredentials(), 'handle', $handle);
    }

    public function getPrimaryCredential($primary): ?Credential
    {
        return ArrayHelper::firstWhere($this->getAllCredentials(), 'primary', $primary);
    }

    public function getCredentialByUid(string $uid): ?Credential
    {
        return ArrayHelper::firstWhere($this->getAllCredentials(), 'uid', $uid);
    }

    public function saveCredential(Credential $credential, bool $runValidation = true): bool
    {
        $isNew = empty($credential->id);

        // Ensure the product type has a UID:
        if ($isNew) {
            $credential->uid = StringHelper::UUID();
        } else if (!$credential->uid) {
            $credential->uid = Db::uidById(Table::Credentials, $credential->id);
        }

        if ($runValidation && !$credential->validate()) {
            return false;
        }

        $path = "aliyun.credentials.{$credential->uid}";

        Craft::$app->getProjectConfig()->set($path, [
            'name' => $credential->name,
            'handle' => $credential->handle,
            'accessKeyId' => $credential->accessKeyId,
            'accessKeySecret' => $credential->accessKeySecret,
        ]);

        if ($isNew) {
            $credential->id = Db::idByUid(Table::Credentials, $credential->uid);
        }

        return true;
    }

    public function deleteCredential($credential): void
    {
        $path = "aliyun.credentials.{$credential->uid}";
        Craft::$app->getProjectConfig()->remove($path);
    }

    public function handleChangeCredential(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];

        $id = Db::idByUid(Table::Credentials, $uid);
        $isNew = empty($id);

        if ($isNew) {
            Db::insert(Table::Credentials, [
                'name' => $event->newValue['name'],
                'handle' => $event->newValue['handle'],
                'accessKeyId' => $event->newValue['accessKeyId'],
                'accessKeySecret' => $event->newValue['accessKeySecret'],
                'uid' => $uid,
            ]);
        } else {
            Db::update(Table::Credentials, [
                'name' => $event->newValue['name'],
                'handle' => $event->newValue['handle'],
                'accessKeyId' => $event->newValue['accessKeyId'],
                'accessKeySecret' => $event->newValue['accessKeySecret'],
            ], ['id' => $id]);
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_CREDENTIAL)) {
            $this->trigger(self::EVENT_AFTER_SAVE_CREDENTIAL, new CredentialEvent([
                'credential' => $this->getCredentialByUid($uid),
                'isNew' => $isNew,
            ]));
        }
    }

    public function handleDeleteCredential(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];

        $credential = $this->getCredentialByUid($uid);
        if (!$credential) {
            return;
        }

        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_DELETE_CREDENTIAL)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_DELETE_CREDENTIAL, new CredentialEvent([
                'credential' => $credential,
            ]));
        }

        Db::delete(Table::Credentials, ['id' => $credential->id]);

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_CREDENTIAL)) {
            $this->trigger(self::EVENT_AFTER_DELETE_CREDENTIAL, new CredentialEvent([
                'credential' => $credential,
            ]));
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * @return Query
     */
    private function _createQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'accessKeyId',
                'accessKeySecret',
                'uid',
                'dateCreated',
                'dateUpdated',
            ])
            ->from(Table::Credentials);
    }
}