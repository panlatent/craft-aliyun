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

    /**
     * @var bool
     */
    public $serverHttpsDownload = false;

    /**
     * @var OssClient|null
     */
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
     * @throws VolumeException
     */
    public function grantClientPrivateDownload(string $path): string
    {
        if ($this->isPublic) {
            return $this->url . '/' . $this->resolvePath($path);
        }

        try {
            return $this->getClient()->signUrl($this->bucket, $this->resolvePath($path), $this->clientDownloadExpires);
        } catch (OssException $exception) {
            throw new VolumeException($exception->getMessage());
        }
    }

    // Public Methods
    // =========================================================================

    /**
     * @param string $directory
     * @param bool $recursive
     * @return array
     * @throws VolumeException
     */
    public function getFileList(string $directory, bool $recursive): array
    {
        $results = [];

        try {
            $listInfo = $this->getClient()->listObjects($this->bucket, [
                'prefix' => $this->resolvePath($directory),
                'delimiter' => $this->delimiter
            ]);
        } catch (OssException $exception) {
            throw new VolumeException($exception->getMessage());
        }

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
     * @throws VolumeException
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
     * @throws VolumeException
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
     * @throws VolumeException
     */
    public function updateFileByStream(string $path, $stream, array $config): array
    {
        return $this->getClient()->putObject($this->bucket, $this->resolvePath($path), stream_get_contents($stream));
    }

    /**
     * @param string $path
     * @return bool
     * @throws VolumeException
     */
    public function fileExists(string $path): bool
    {
        $result = $this->getClient()->doesObjectExist($this->bucket, $this->resolvePath($path));

        return $result;
    }

    /**
     * @param string $path
     * @throws VolumeException
     */
    public function deleteFile(string $path)
    {
        $this->getClient()->deleteObject($this->bucket, $this->resolvePath($path));
    }

    /**
     * @param string $path
     * @param string $newPath
     * @throws VolumeException
     */
    public function renameFile(string $path, string $newPath)
    {
        try {
            $this->getClient()->copyObject(
                $this->bucket,
                $this->resolvePath($path),
                $this->bucket,
                $this->resolvePath($newPath)
            );

            $this->getClient()->deleteObject($this->bucket, $this->resolvePath($path));
        } catch (OssException $exception) {
            throw new VolumeException($exception->getMessage());
        }
    }

    /**
     * @param string $path
     * @param string $newPath
     * @throws VolumeException
     */
    public function copyFile(string $path, string $newPath)
    {
        try {
            $this->getClient()->copyObject(
                $this->bucket,
                $this->resolvePath($path),
                $this->bucket,
                $this->resolvePath($newPath)
            );
        } catch (OssException $exception) {
            throw new VolumeException($exception->getErrorMessage());
        }
    }

    /**
     * @param string $uriPath
     * @param string $targetPath
     * @return int
     * @throws VolumeException
     */
    public function saveFileLocally(string $uriPath, string $targetPath): int
    {
        if ($this->hasUrls) {
            if ($this->isPublic) {
                $rootUrl = $this->_completeSchema($this->getRootUrl());
                $url = $rootUrl . $this->_encodeUriPath($uriPath);
                \Craft::info($uriPath, __METHOD__);
                \Craft::info($url, __METHOD__);
            } else {
                $url = $this->grantClientPrivateDownload($this->resolvePath($uriPath));
            }
            if (!copy($url, $targetPath)) {
                throw new VolumeException("Save asset {$url} to {$targetPath} failed");
            }
        } else {
            $data = $this->getClient()->getObject($this->bucket, $this->resolvePath($uriPath));
            file_put_contents($targetPath, $data);
        }

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
        throw new VolumeException('No support rename folder');
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
     * @throws VolumeException
     */
    public function getClient()
    {
        if ($this->_client !== null) {
            return $this->_client;
        }

        try {
            $this->_client = new OssClient($this->accessKey, $this->secretKey, $this->endpoint);
        } catch (OssException $exception) {
            Craft::error("Aliyun Oss client not created: {$exception->getMessage()}");

            throw new VolumeException($exception->getMessage());
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

    /**
     * @param string $url
     * @return string
     */
    private function _completeSchema(string $url): string
    {
        if (strncmp($url, 'http://', 7) === 0 || strncmp($url, 'https://', 8) === 0) {
            return $url;
        }

        $schema = $this->serverHttpsDownload ? 'https://' : 'http://';

        return  $schema . ltrim($url, '/');
    }

    /**
     * @param string $uriPath
     * @return string
     */
    private function _encodeUriPath(string $uriPath): string
    {
        $uri = array_map(function($value) {
            return rawurlencode($value);
        }, (array)explode('/', $uriPath));

        return implode('/', $uri);
    }
}