<?php

namespace panlatent\craft\aliyun\migrations;

use Craft;
use craft\db\Migration;
use panlatent\craft\aliyun\db\Table;
use panlatent\craft\aliyun\Plugin;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTable(Table::Credentials, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'accessKeyId' => $this->string()->notNull(),
            'accessKeySecret' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists(Table::Credentials);

        return true;
    }
}
