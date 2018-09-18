<?php
/**
 * Aliyun plugin for Craft 3
 *
 * @link      https://panlatent.com/
 * @copyright Copyright (c) 2018 panlatent@gmail.com
 */

namespace panlatent\craft\aliyun\volumes;

use Craft;
use craft\base\Volume;
use craft\errors\VolumeException;
use OSS\Core\OssException;
use OSS\OssClient;
use yii\helpers\StringHelper;

/**
 * Class OssVolume
 *
 * @package panlatent\craft\aliyun\volumes
 * @property-read OssClient $client
 * @author Panlatent <panlatent@gmail.com>
 */
class OssVolume extends Volume
{
    /**
     * @var string
     */
    public $accessKey = '';

    /**
     * @var string
     */
    public $secretKey = '';

    /**
     * @var string
     */
    public $endpoint = '';

    /**
     * @var string
     */
    public $bucket = '';

    /**
     * @var string
     */
    public $isPublic = true;

    /**
     * @var string
     */
    public $root = '';

    /**
     * @var string
     */
    public $delimiter = '/';

    /**
     * @var int
     */
    public $clientDownloadExpires = 60;

    private $_client;

    // Static
    // =========================================================================

    /**
     * @return string
     */
    public static function displayName(): string
    {
        return Craft::t('aliyun', 'Aliyun OSS Volume');
    }

    /**
     * Init.
     */
    public function init()
    {
        parent::init();

        $this->root = trim($this->root, '/');
    }

    /**
     * @param string $path
     * @return string
     * @throws OssException
     */
    public function grantClientPrivateDownload(string $path): string
    {
        if ($this->isPublic) {
            return $this->url . '/' . $this->resolvePath($path);
        }

        return $this->getClient()->signUrl($this->bucket, $path, $this->clientDownloadExpires);
    }

    // Public Methods
    // =========================================================================

    /**
     * @param string $directory
     * @param bool $recursive
     * @return array
     * @throws OssException
     */
    public function getFileList(string $directory, bool $recursive): array
    {
        $results = [];

        $listInfo = $this->getClient()->listObjects($this->bucket, [
            'prefix' => $this->resolvePath($directory),
            'delimiter' => $this->delimiter
        ]);

        foreach ($listInfo->getObjectList() as $object) {
                $results[$object->getKey()] = [
                    'path' => $object->getKey(),
                    'dirname' => StringHelper::dirname($object->getKey()),
                    'basename' => StringHelper::basename($object->getKey()),
                    'size' => $object->getSize(),
                    'timestamp' => $object->getLastModified(),
                    'mimeType' => $object->getType(),
                ];
        }

        return $results;
    }

    /**
     * @param string $uri
     * @return array
     */
    public function getFileMetadata(string $uri): array
    {
        return $this->getClient()->getObjectMeta($this->bucket, $this->resolvePath($uri));
    }

    /**
     * @param string $path
     * @param resource $stream
     * @param array $config
     * @return array
     */
    public function createFileByStream(string $path, $stream, array $config): array
    {
        return $this->getClient()->putObject($this->bucket, $this->resolvePath($path), stream_get_contents($stream));
    }

    /**
     * @param string $path
     * @param resource $stream
     * @param array $config
     * @return array
     */
    public function updateFileByStream(string $path, $stream, array $config): array
    {
        return $this->getClient()->putObject($this->bucket, $this->resolvePath($path), stream_get_contents($stream));
    }

    /**
     * @param string $path
     * @return bool
     */
    public function fileExists(string $path): bool
    {
        $result = $this->getClient()->doesObjectExist($this->bucket, $this->resolvePath($path));

        return $result;
    }

    /**
     * @param string $path
     */
    public function deleteFile(string $path)
    {
        $this->getClient()->deleteObject($this->bucket, $this->resolvePath($path));
    }

    /**
     * @param string $path
     * @param string $newPath
     * @throws OssException
     */
    public function renameFile(string $path, string $newPath)
    {
        $this->getClient()->copyObject(
            $this->bucket,
            $this->resolvePath($path),
            $this->bucket,
            $this->resolvePath($newPath)
        );

        $this->getClient()->deleteObject($this->bucket, $this->resolvePath($path));
    }

    /**
     * @param string $path
     * @param string $newPath
     * @throws OssException
     */
    public function copyFile(string $path, string $newPath)
    {
        $this->getClient()->copyObject(
            $this->bucket,
            $this->resolvePath($path),
            $this->bucket,
            $this->resolvePath($newPath)
        );
    }

    /**
     * @param string $uriPath
     * @param string $targetPath
     * @return int
     * @throws OssException
     */
    public function saveFileLocally(string $uriPath, string $targetPath): int
    {
        $url = $this->getRootUrl() . $uriPath;
        if (!$this->isPublic) {
            $url = $this->grantClientPrivateDownload($url);
        }
        copy($url, $targetPath);

        return filesize($targetPath);
    }

    public function getFileStream(string $uriPath)
    {

    }

    /**
     * @param string $path
     * @return bool
     */
    public function folderExists(string $path): bool
    {
        return true;
    }

    public function createDir(string $path)
    {
        $this->getClient()->createObjectDir($this->bucket, $this->resolvePath($path));
    }

    /**
     * @inheritdoc
     */
    public function deleteDir(string $path)
    {
        $this->getClient()->deleteObject($this->bucket, $this->resolvePath($path));
    }

    /**
     * @inheritdoc
     */
    public function renameDir(string $path, string $newName)
    {
        throw new VolumeException('No support remame folder');
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        $templateString = file_get_contents(__DIR__ . '/../templates/volumen-settings.twig');

        return Craft::$app->getView()->renderString($templateString, [
            'volume' => $this,
        ]);
    }

    /**
     * @return OssClient|null
     */
    public function getClient()
    {
        if ($this->_client !== null) {
            return $this->_client;
        }

        try {
            $this->_client = new OssClient($this->accessKey, $this->secretKey, $this->endpoint);
        } catch (OssException $exception) {
            Craft::error("Aliyun Oss client not created. {$exception->getMessage()}");
        }

        return $this->_client;
    }


    /**
     * @param string $path
     * @return string
     */
    protected function resolvePath(string $path): string
    {
        if ($this->root == '') {
            return $path;
        }

        return $this->root . '/' . ltrim($path, '/');
    }
}