<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Storage;

/**
 * Selecciona driver de storage según FISCAL_STORAGE_DRIVER.
 */
class StorageDriverFactory
{
    private string $driver;
    private string $localBasePath;
    private string $localPublicUrl;
    private string $s3Region;
    private string $s3Endpoint;
    private string $s3Bucket;
    private string $s3AccessKey;
    private string $s3SecretKey;
    private string $s3PublicUrl;
    private string $s3KeyPrefix;

    public function __construct(
        string $driver,
        string $localBasePath,
        string $localPublicUrl,
        ?string $s3Region = '',
        ?string $s3Endpoint = '',
        ?string $s3Bucket = '',
        ?string $s3AccessKey = '',
        ?string $s3SecretKey = '',
        ?string $s3PublicUrl = '',
        ?string $s3KeyPrefix = ''
    ) {
        $this->driver = strtolower(trim($driver !== '' ? $driver : 'local'));
        $this->localBasePath = $localBasePath;
        $this->localPublicUrl = $localPublicUrl;
        $this->s3Region = $s3Region ?? '';
        $this->s3Endpoint = $s3Endpoint ?? '';
        $this->s3Bucket = $s3Bucket ?? '';
        $this->s3AccessKey = $s3AccessKey ?? '';
        $this->s3SecretKey = $s3SecretKey ?? '';
        $this->s3PublicUrl = $s3PublicUrl ?? '';
        $this->s3KeyPrefix = $s3KeyPrefix ?? '';
    }

    public function create(): StorageDriverInterface
    {
        if (in_array($this->driver, ['s3', 'r2', 'minio'], true)) {
            if ($this->s3Bucket === '' || $this->s3AccessKey === '') {
                throw new \RuntimeException('S3/R2 requiere FISCAL_S3_BUCKET y credenciales');
            }
            return new S3CompatibleStorageDriver(
                $this->s3Region,
                $this->s3Endpoint,
                $this->s3Bucket,
                $this->s3AccessKey,
                $this->s3SecretKey,
                $this->s3PublicUrl !== '' ? $this->s3PublicUrl : $this->s3Endpoint . '/' . $this->s3Bucket,
                $this->s3KeyPrefix
            );
        }
        return new LocalStorageDriver($this->localBasePath, $this->localPublicUrl);
    }
}
