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

    /** @return array{storageKey: string, thumbnailStorageKey: string, width: int, height: int, thumbnailWidth: int, thumbnailHeight: int, size: int, thumbnailSize: int, thumbnailContentType: string} */
    public function upload(string $projectCode, int $itemId, UploadedFile $file): array
    {
        $this->ensureBucketExists();

        $extension = $file->guessExtension() ?: 'bin';
        $basename = bin2hex(random_bytes(16));
        $key = sprintf('projects/%s/items/%d/%s.%s', $projectCode, $itemId, $basename, $extension);
        $thumbnailKey = sprintf('projects/%s/items/%d/%s-thumb.jpg', $projectCode, $itemId, $basename);
        $thumbnail = $this->createThumbnail($file->getPathname());

        $this->s3->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'Body' => fopen($file->getPathname(), 'rb'),
            'ContentType' => $file->getMimeType() ?: 'application/octet-stream',
        ]);

        $this->s3->putObject([
            'Bucket' => $this->bucket,
            'Key' => $thumbnailKey,
            'Body' => $thumbnail['data'],
            'ContentType' => 'image/jpeg',
        ]);

        return [
            'storageKey' => $key,
            'thumbnailStorageKey' => $thumbnailKey,
            'width' => $thumbnail['originalWidth'],
            'height' => $thumbnail['originalHeight'],
            'thumbnailWidth' => $thumbnail['width'],
            'thumbnailHeight' => $thumbnail['height'],
            'size' => $file->getSize() ?: filesize($file->getPathname()) ?: 0,
            'thumbnailSize' => strlen($thumbnail['data']),
            'thumbnailContentType' => 'image/jpeg',
        ];
    }

    public function delete(string $storageKey): void
    {
        $this->s3->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $storageKey,
        ]);
    }

    /** @return array{data: string, originalWidth: int, originalHeight: int, width: int, height: int} */
    private function createThumbnail(string $path): array
    {
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('The GD extension is required to create thumbnails.');
        }

        $imageSize = getimagesize($path);
        if ($imageSize === false) {
            throw new \RuntimeException('Unable to read image dimensions.');
        }

        [$originalWidth, $originalHeight] = $imageSize;
        $source = match ($imageSize[2]) {
            IMAGETYPE_GIF => imagecreatefromgif($path),
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default => false,
        };

        if ($source === false) {
            throw new \RuntimeException('Unsupported image type.');
        }

        $targetWidth = min(600, $originalWidth);
        $targetHeight = max(1, (int) round($originalHeight * ($targetWidth / $originalWidth)));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $originalWidth, $originalHeight);

        ob_start();
        imagejpeg($target, null, 82);
        $data = ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        if ($data === false) {
            throw new \RuntimeException('Unable to encode thumbnail.');
        }

        return [
            'data' => $data,
            'originalWidth' => $originalWidth,
            'originalHeight' => $originalHeight,
            'width' => $targetWidth,
            'height' => $targetHeight,
        ];
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

    public function download(string $storageKey): string
    {
        $this->ensureBucketExists();

        $result = $this->s3->getObject([
            'Bucket' => $this->bucket,
            'Key' => $storageKey,
        ]);

        return (string) $result['Body'];
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
