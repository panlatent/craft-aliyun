<?php
/*
 * @link https://github.com/panlatent/craft-aliyun
 * @copyright Copyright (c) 2024 panlatent@gmail.com
 */

namespace panlatent\craft\aliyun\fs;

use Craft;
use craft\errors\FsException;
use craft\helpers\App;
use craft\models\FsListing;
use Generator;
use OSS\Core\OssException;
use panlatent\craft\aliyun\base\CredentialTrait;
use panlatent\craft\aliyun\models\Credential;
use panlatent\craft\aliyun\Plugin;
use yii\helpers\StringHelper;

/**
 * Aliyun OSS Filesystem
 */
class OSS extends OSSAbstract
{
    use CredentialTrait;

    const HTTP_SCHEME = 'http://';
    const HTTP_SECURE_SCHEME = 'https://';

    // Static Methods
    // =========================================================================

    /**
     * @return string
     */
    public static function displayName(): string
    {
        return Craft::t('aliyun', 'Aliyun OSS');
    }

    /**
     * @return array
     */
    public static function endpoints(): array
    {
        return require_once dirname(__DIR__) . '/config/endpoints.php';
    }

    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $endpoint = 'oss-cn-beijing.aliyuncs.com';

    /**
     * @var string
     */
    public string $bucket = '';

    /**
     * @var string
     */
    public string $root = '';

    /**
     * Credential UID
     *
     * @var string|null
     */
    public ?string $credential = null;

    /**
     * @var bool
     */
    public bool $isCustomCredential = false;

    /**
     * @var int
     */
    public int $clientDownloadExpires = 60;

    /**
     * @var bool
     */
    public bool $serverHttpsDownload = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [['endpoint', 'bucket'], 'required'],
            [['credential'], 'required', 'when' => fn() => !$this->isCustomCredential],
            [['accessKeyId', 'accessKeySecret'], 'required', 'when' => fn() => $this->isCustomCredential],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'credential' => Craft::t('aliyun', 'Credential'),
            'endpoint' => Craft::t('aliyun', 'Endpoint'),
            'bucket' => Craft::t('aliyun', 'Bucket'),
        ]);
    }

    public function getRootUrl(): ?string
    {
        $url = parent::getRootUrl();
        if ($url === null) {
            return null;
        }
        $root = $this->getRoot();
        return $root ? "$url$root/" : $url;
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return App::parseEnv($this->endpoint);
    }

    /**
     * @return string|null
     */
    public function getBucket(): ?string
    {
        return App::parseEnv($this->bucket);
    }

    /**
     * @return string|null
     */
    public function getRoot(): ?string
    {
        return App::parseEnv($this->root);
    }

    public function getCredential(): Credential
    {
        if ($this->isCustomCredential) {
            return new Credential(['accessKeyId' => $this->getAccessKeyId(), 'accessKeySecret' => $this->getAccessKeySecret()]);
        }
        $credential = Plugin::$plugin->getCredentials()->getCredentialByUid($this->credential);
        if (!$credential) {
            throw new FsException('No credential found with UID: ' . $this->credential);
        }
        return $credential;
    }

    public function getFileListArray(string $directory = '', bool $recursive = true): array
    {
        return iterator_to_array($this->getFileList($directory, $recursive));
    }

    /**
     * @inheritdoc
     */
    public function getFileList(string $directory = '', bool $recursive = true): Generator
    {
        foreach ($this->getObjectList($this->getBucket(), $this->resolveRemotePath($directory)) as $object) {
            $path = $this->resolveLocalPath($object['path']);
            $isDir = $object['type'] === 'dir';
            yield new FsListing([
                'dirname' => StringHelper::dirname($path),
                'basename' => StringHelper::basename($path),
                'type' => $isDir ? 'dir' : 'file',
                'fileSize' => $isDir ? null : $object['fileSize'],
                'dateModified' => $isDir ? null : $object['dateModified'],
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function getFileSize(string $uri): int
    {
        return $this->getFileMetadata($uri)['size'];
    }

    /**
     * @inheritdoc
     */
    public function getDateModified(string $uri): int
    {
        return $this->getFileMetadata($uri)['timestamp'];
    }

    /**
     * @inheritdoc
     */
    public function write(string $path, string $contents, array $config = []): void
    {
        $this->getClient()->putObject($this->getBucket(), $this->resolveRemotePath($path), $contents);
    }

    /**
     * @inheritdoc
     */
    public function read(string $path): string
    {
        return $this->getClient()->getObject($this->getBucket(), $this->resolveRemotePath($path));
    }

    /**
     * @inheritdoc
     */
    public function writeFileFromStream(string $path, $stream, array $config = []): void
    {
        $this->getClient()->putObject($this->getBucket(), $this->resolveRemotePath($path), stream_get_contents($stream));
    }

    /**
     * @inheritdoc
     */
    public function fileExists(string $path): bool
    {
        return $this->getClient()->doesObjectExist($this->getBucket(), $this->resolveRemotePath($path));
    }

    /**
     * @inheritdoc
     */
    public function deleteFile(string $path): void
    {
        $this->getClient()->deleteObject($this->getBucket(), $this->resolveRemotePath($path));
    }

    /**
     * @inheritdoc
     */
    public function renameFile(string $path, string $newPath, array $config = []): void
    {
        try {
            $this->internalRenameFile($this->getBucket(), $this->resolveRemotePath($path), $this->resolveRemotePath($newPath));
        } catch (OssException $exception) {
            throw new FsException($exception->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function copyFile(string $path, string $newPath): void
    {
        try {
            $this->getClient()->copyObject(
                $this->getBucket(),
                $this->resolveRemotePath($path),
                $this->getBucket(),
                $this->resolveRemotePath($newPath)
            );
        } catch (OssException $exception) {
            throw new FsException($exception->getErrorMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function getFileStream(string $uriPath)
    {
        if ($this->hasUrls) {
            $stream = fopen($this->getRemoteObjectUrl($uriPath), 'r');
        } else {
            $stream = tmpfile();
            $contents = $this->getClient()->getObject($this->getBucket(), $this->resolveRemotePath($uriPath));
            fwrite($stream, $contents);
            fseek($stream, 0);
        }

        if (!$stream) {
            throw new FsException('Could not open create the stream for “' . $uriPath . '”');
        }

        return $stream;
    }

    /**
     * @inheritdoc
     */
    public function directoryExists(string $path): bool
    {
        return $this->getClient()->doesObjectExist($this->getBucket(), $this->resolveRemotePath($path));
    }

    /**
     * @inheritdoc
     */
    public function createDirectory(string $path, array $config = []): void
    {
        $this->getClient()->createObjectDir($this->getBucket(), $this->resolveRemotePath($path));
    }

    /**
     * @inheritdoc
     */
    public function deleteDirectory(string $path): void
    {
        $this->internalDeleteDirectory($this->getBucket(), $path);
    }

    /**
     * @inheritdoc
     */
    public function renameDirectory(string $path, string $newName): void
    {
        $this->internalRenameDirectory($this->getBucket(), $this->resolveRemotePath($path), $this->resolveRemotePath($newName));
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('aliyun/_components/filesystems/OSS', ['fs' => $this]);
    }

    // Protected Methods
    // =========================================================================

    protected function getFileMetadata(string $uri): array
    {
        $path = $this->resolveRemotePath($uri);
        $result = $this->getClient()->getObjectMeta($this->getBucket(), $path);

        return [
            'mimetype' => $result['content-type'],
            'timestamp' => strtotime($result['last-modified']),
            'size' => $result['content-length'],
            'visibility' => $this->getClient()->getObjectAcl($this->getBucket(), $path),
        ];
    }

    protected function resolveRemotePath(string $path): string
    {
        $path = ltrim($path, $this->delimiter);
        $root = trim($this->getRoot(), ' ');
        if ($root !== '') {
            $path = $root . $this->delimiter . ltrim($path, '\\/');
        }
        return $path;
    }

    protected function getRemoteObjectUrl(string $path): string
    {
        if ($this->hasUrls) {
            return $this->_completeSchema($this->getRootUrl()) . $this->_encodeUriPath($path);
        }

        try {
            $url = $this->getClient()->signUrl($this->getBucket(), $this->resolveRemotePath($path), $this->clientDownloadExpires);
        } catch (OssException $exception) {
            throw new FsException($exception->getMessage());
        }

        return $url;
    }

    protected function resolveLocalPath(string $path): string
    {
        $path = trim($path, $this->delimiter);

        if ($this->getRoot() && str_starts_with($path, $this->getRoot())) {
            $path = substr($path, strlen($this->getRoot()));
        }

        return ltrim($path, $this->delimiter);
    }

    // Private Methods
    // =========================================================================

    /**
     * @param string $url
     * @return string
     */
    private function _completeSchema(string $url): string
    {
        if (strncmp($url, self::HTTP_SCHEME, 7) === 0 || strncmp($url, self::HTTP_SECURE_SCHEME, 8) === 0) {
            return $url;
        }

        $schema = $this->serverHttpsDownload ? self::HTTP_SECURE_SCHEME : self::HTTP_SCHEME;

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
        }, explode('/', $uriPath));

        return implode('/', $uri);
    }
}