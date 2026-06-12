<?php

namespace App\Service;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PhotoStorage
{
    private bool $bucketChecked = false;

    public function __construct(
        private readonly S3Client $s3,
        private readonly string $bucket,
    ) {
    }

    public function upload(string $projectCode, int $itemId, UploadedFile $file): string
    {
        $this->ensureBucketExists();

        $extension = $file->guessExtension() ?: 'bin';
        $key = sprintf('projects/%s/items/%d/%s.%s', $projectCode, $itemId, bin2hex(random_bytes(16)), $extension);

        $this->s3->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'Body' => fopen($file->getPathname(), 'rb'),
            'ContentType' => $file->getMimeType() ?: 'application/octet-stream',
        ]);

        return $key;
    }

    public function delete(string $storageKey): void
    {
        $this->s3->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $storageKey,
        ]);
    }

    public function downloadToTempFile(string $storageKey): string
    {
        $this->ensureBucketExists();

        $target = tempnam(sys_get_temp_dir(), 'clapstock-photo-');
        if ($target === false) {
            throw new \RuntimeException('Unable to create a temporary file.');
        }

        $this->s3->getObject([
            'Bucket' => $this->bucket,
            'Key' => $storageKey,
            'SaveAs' => $target,
        ]);

        return $target;
    }

    private function ensureBucketExists(): void
    {
        if ($this->bucketChecked) {
            return;
        }

        try {
            $this->s3->headBucket(['Bucket' => $this->bucket]);
        } catch (S3Exception) {
            $this->s3->createBucket(['Bucket' => $this->bucket]);
        }

        $this->bucketChecked = true;
    }
}
