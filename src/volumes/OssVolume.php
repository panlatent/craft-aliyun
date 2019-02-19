<?php
/**
 * @link https://github.com/panlatent/craft-aliyun
 * @copyright Copyright (c) 2018 panlatent@gmail.com
 */

namespace panlatent\craft\aliyun\volumes;

use Craft;
use craft\base\Volume;
use craft\errors\AssetException;
use craft\errors\VolumeException;
use craft\errors\VolumeObjectNotFoundException;
use OSS\Core\OssException;
use OSS\OssClient;
use panlatent\craft\aliyun\Plugin;
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
     * @var bool|null
     */
    public $useGlobalSettings;

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
     * @return array
     */
    public static function endpoints(): array
    {
        return require_once dirname(__DIR__) . '/config/endpoints.php';
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
     * @return array
     */
    public function getAllBuckets(): array
    {
        return $this->getClient()->listBuckets()->getBucketList();
    }

    /**
     * @param string $path
     * @return string
     * @throws VolumeException
     */
    public function grantClientPrivateDownload(string $path): string
    {
        if ($this->isPublic) {
            return $this->url . '/' . $this->resolveLocalPath($path);
        }

        try {
            return $this->getClient()->signUrl($this->bucket, $this->resolveLocalPath($path), $this->clientDownloadExpires);
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
     */
    public function getFileList(string $directory, bool $recursive): array
    {
        $results = [];
        $prefix = rtrim($this->resolveLocalPath($directory), '\\/') . $this->delimiter;

        for ($nextMarker = null; $nextMarker !== ''; ) {
            $listInfo = $this->getClient()->listObjects($this->bucket, [
                'prefix' => $prefix,
                'delimiter' => $this->delimiter,
                'max-keys'  => 1000,
                'marker'    => $nextMarker ?? '',
            ]);

            foreach ($listInfo->getPrefixList() as $prefix) {
                $path = rtrim($this->resolveRemotePath($prefix->getPrefix()), $this->delimiter);

                $results[$path] = [
                    'type' => 'dir',
                    'path' => $path,
                    'basename' => StringHelper::basename($path),
                ];

                if ($recursive) {
                    $results = $results + $this->getFileList($path, $recursive);
                }
            }

            foreach ($listInfo->getObjectList() as $object) {
                if (($object->getSize() === 0) && ($object->getKey() === $prefix || $object->getKey() === $this->root . $this->delimiter)) {
                    continue;
                }

                $path = $this->resolveRemotePath($object->getKey());

                $results[$path] = [
                    'type' => 'file',
                    'path' => $path,
                    'size' => $object->getSize(),
                    'timestamp' => strtotime($object->getLastModified()),
                    'dirname' => StringHelper::dirname($path),
                    'basename' => StringHelper::basename($path),
                    'mimeType' => $object->getType(),
                ];
            }

            $nextMarker = $listInfo->getNextMarker();
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
        return $this->getClient()->getObjectMeta($this->bucket, $this->resolveLocalPath($uri));
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
        return $this->getClient()->putObject($this->bucket, $this->resolveLocalPath($path), stream_get_contents($stream));
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
        return $this->getClient()->putObject($this->bucket, $this->resolveLocalPath($path), stream_get_contents($stream));
    }

    /**
     * @param string $path
     * @return bool
     * @throws VolumeException
     */
    public function fileExists(string $path): bool
    {
        $result = $this->getClient()->doesObjectExist($this->bucket, $this->resolveLocalPath($path));

        return $result;
    }

    /**
     * @param string $path
     * @throws VolumeException
     */
    public function deleteFile(string $path)
    {
        $this->getClient()->deleteObject($this->bucket, $this->resolveLocalPath($path));
    }

    /**
     * @param string $path
     * @param string $newPath
     * @throws VolumeException
     */
    public function renameFile(string $path, string $newPath)
    {
        try {
            $this->getClient()->copyObject($this->bucket, $this->resolveLocalPath($path), $this->bucket, $this->resolveLocalPath($newPath));
            $this->getClient()->deleteObject($this->bucket, $this->resolveLocalPath($path));
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
                $this->resolveLocalPath($path),
                $this->bucket,
                $this->resolveLocalPath($newPath)
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
            } else {
                $url = $this->grantClientPrivateDownload($this->resolveLocalPath($uriPath));
            }
            if (!copy($url, $targetPath)) {
                throw new VolumeException("Save asset {$url} to {$targetPath} failed");
            }
        } else {
            $data = $this->getClient()->getObject($this->bucket, $this->resolveLocalPath($uriPath));
            file_put_contents($targetPath, $data);
        }

        return filesize($targetPath);
    }

    /**
     * @param string $uriPath
     * @return resource
     */
    public function getFileStream(string $uriPath)
    {
        $stream = fopen($this->_completeSchema($uriPath), 'r');

        if (!$stream) {
            throw new AssetException('Could not open create the stream for “' . $uriPath . '”');
        }

        return $stream;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function folderExists(string $path): bool
    {
        return $this->getClient()->doesObjectExist($this->bucket, $this->resolveLocalPath($path));
    }

    /**
     * @param string $path
     */
    public function createDir(string $path)
    {
        $this->getClient()->createObjectDir($this->bucket, $this->resolveLocalPath($path));
    }

    /**
     * @inheritdoc
     */
    public function deleteDir(string $path)
    {
        $this->getClient()->deleteObject($this->bucket, $this->resolveLocalPath($path));

        $lists = $this->getFileList($path, true);
        if (empty($lists)) {
            return false;
        }

        $objectList = [];
        foreach ($lists as $value) {
            $objectList[] = $this->resolveRemotePath($value['path']);
        }

        $this->getClient()->deleteObjects($this->bucket, $objectList);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function renameDir(string $path, string $newName)
    {
        // Get the list of dir contents
        $fileList = $this->getFileList($path, true);
        $directoryList = [$path];

        $parts = explode('/', $path);

        array_pop($parts);
        $parts[] = $newName;

        $newPath = implode('/', $parts);

        $pattern = '/^' . preg_quote($path, '/') . '/';

        // Rename every file and build a list of directories
        foreach ($fileList as $object) {
            if ($object['type'] !== 'dir') {
                $objectPath = preg_replace($pattern, $newPath, $object['path']);
                $this->renameFile($object['path'], $objectPath);
            } else {
                $directoryList[] = $object['path'];
            }
        }

        // It's possible for a folder object to not exist on remote volumes, so to throw an exception
        // we must make sure that there are no files AS WELL as no folder.
        if (empty($fileList) && !$this->folderExists($path)) {
            throw new VolumeObjectNotFoundException('No folder exists at path: ' . $path);
        }

        // The files are moved, but the directories remain. Delete them.
        foreach ($directoryList as $dir) {
            try {
                $this->deleteDir($dir);
            } catch (\Throwable $e) {
                // This really varies between volume types and whether folders are virtual or real
                // So just in case, catch the exception, log it and then move on
                Craft::warning($e->getMessage());
                continue;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('aliyun/_components/volumes/OssVolume', [
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
            if ($this->useGlobalSettings) {
                $settings = Plugin::$plugin->getSettings();
                $accessKey = $settings->getAccessKey();
                $secretKey = $settings->getSecretKey();
            } else {
                $accessKey = $this->accessKey;
                $secretKey = $this->secretKey;
            }

            $this->_client = new OssClient($accessKey, $secretKey, $this->endpoint);
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
    protected function resolveLocalPath(string $path): string
    {
        if ($this->root == '') {
            return ltrim($path, '\\/');
        }

        return $this->root . $this->delimiter . ltrim($path, '\\/');
    }

    /**
     * @param string $path
     * @return string
     */
    protected function resolveRemotePath(string $path): string
    {
        if ($this->root == '') {
            return ltrim($path, '\\/');
        }

        if (strpos($path, $this->root) === 0) {
            $path = substr($path, strlen($this->root));
        }

        return ltrim($path, $this->delimiter);
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

        return $schema . ltrim($url, '/');
    }

    /**
     * @param string $uriPath
     * @return string
     */
    private function _encodeUriPath(string $uriPath): string
    {
        $uri = array_map(function ($value) {
            return rawurlencode($value);
        }, (array)explode('/', $uriPath));

        return implode('/', $uri);
    }
}