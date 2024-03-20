<?php
/*
 * @link https://github.com/panlatent/craft-aliyun
 * @copyright Copyright (c) 2024 panlatent@gmail.com
 */

namespace panlatent\craft\aliyun\fs;

use Craft;
use craft\base\Fs;
use craft\errors\FsException;
use craft\helpers\App;
use craft\helpers\StringHelper;
use craft\models\FsListing;
use OSS\OssClient;
use panlatent\craft\aliyun\models\Credential;
use panlatent\craft\aliyun\Plugin;
use yii\helpers\ArrayHelper;

/**
 *
 */
abstract class OSSAbstract extends Fs
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $delimiter = '/';

    /**
     * @var OssClient|null
     */
    private ?OssClient $_client = null;

    // Public Methods
    // =========================================================================

    abstract public function getCredential(): Credential;

    /**
     * @return array
     */
    public function getAllBuckets(): array
    {
        return $this->getClient()->listBuckets()->getBucketList();
    }

    /**
     * @return OssClient
     * @throws FsException
     */
    public function getClient(): OssClient
    {
        if ($this->_client === null) {
            try {
                $credential = $this->getCredential();
                $this->_client = new OssClient($credential->getAccessKeyId(), $credential->getAccessKeySecret(), $this->getEndpoint());
            } catch (\Throwable $exception) {
                Craft::error("Aliyun Oss client not created: {$exception->getMessage()}");
                throw new FsException($exception->getMessage());
            }
        }
        return $this->_client;
    }

    protected function getObjectList(string $bucket, string $prefix, bool $recursive = true): \Generator
    {
        for ($nextMarker = null; $nextMarker !== '';) {
            $listInfo = $this->getClient()->listObjects($bucket, [
                'prefix' => $prefix,
                'delimiter' => $this->delimiter,
                'max-keys' => 1000,
                'marker' => $nextMarker ?? '',
            ]);

            foreach ($listInfo->getObjectList() as $object) {
                if (($object->getSize() === 0) && ($object->getKey() === $prefix)) {
                    continue;
                }
                yield [
                    'path' => $object->getKey(),
                    'type' => 'file',
                    'fileSize' => $object->getSize(),
                    'dateModified' => strtotime($object->getLastModified()),
                ];
            }
            foreach ($listInfo->getPrefixList() as $prefix) {
                yield [
                    'path' => $prefix->getPrefix(),
                    'type' => 'dir',
                    'fileSize' => null,
                    'dateModified' => null,
                ];

                if ($recursive) {
                    yield from $this->getObjectList($bucket, $prefix->getPrefix(), $recursive);
                }
            }
            $nextMarker = $listInfo->getNextMarker();
        }
    }

    protected function internalRenameFile(string $bucket, string $path, string $newPath): void
    {
        $this->getClient()->copyObject($bucket, $path, $bucket, $newPath);
        $this->getClient()->deleteObject($bucket, $path);
    }

    protected function internalDeleteDirectory(string $bucket, string $path): void
    {
        $paths = [$path];
        $objects = iterator_to_array($this->getObjectList($bucket, $path));
        if (!empty($objects)) {
            $paths = array_merge($paths,ArrayHelper::getColumn($objects, 'path'));
        }

        $this->getClient()->deleteObjects($bucket, $paths);
    }

    protected function internalRenameDirectory(string $bucket, string $path, string $newPath): void
    {
        foreach ($this->getObjectList($bucket, $path) as $object) {
            $newName = rtrim($newPath, $this->delimiter) . $this->delimiter . ltrim(StringHelper::removeLeft($object['path'], $path), $this->delimiter);
            $this->internalRenameFile($bucket, $object['path'], $newName);
        }
    }
}