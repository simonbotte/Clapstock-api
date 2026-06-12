<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'item_photo')]
class ItemPhoto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CatalogItem::class, inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CatalogItem $item;

    #[ORM\Column(length: 255)]
    private string $storageKey;

    #[ORM\Column(length: 255)]
    private string $thumbnailStorageKey;

    #[ORM\Column]
    private int $position;

    #[ORM\Column(length: 120)]
    private string $contentType;

    #[ORM\Column(length: 120)]
    private string $thumbnailContentType;

    #[ORM\Column]
    private int $size;

    #[ORM\Column]
    private int $thumbnailSize;

    #[ORM\Column]
    private int $width;

    #[ORM\Column]
    private int $height;

    #[ORM\Column]
    private int $thumbnailWidth;

    #[ORM\Column]
    private int $thumbnailHeight;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        CatalogItem $item,
        string $storageKey,
        string $thumbnailStorageKey,
        int $position,
        string $contentType,
        string $thumbnailContentType,
        int $size,
        int $thumbnailSize,
        int $width,
        int $height,
        int $thumbnailWidth,
        int $thumbnailHeight,
    ) {
        $this->item = $item;
        $this->storageKey = $storageKey;
        $this->thumbnailStorageKey = $thumbnailStorageKey;
        $this->position = $position;
        $this->contentType = $contentType;
        $this->thumbnailContentType = $thumbnailContentType;
        $this->size = $size;
        $this->thumbnailSize = $thumbnailSize;
        $this->width = $width;
        $this->height = $height;
        $this->thumbnailWidth = $thumbnailWidth;
        $this->thumbnailHeight = $thumbnailHeight;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItem(): CatalogItem
    {
        return $this->item;
    }

    public function getStorageKey(): string
    {
        return $this->storageKey;
    }

    public function getThumbnailStorageKey(): string
    {
        return $this->thumbnailStorageKey;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getThumbnailContentType(): string
    {
        return $this->thumbnailContentType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getThumbnailSize(): int
    {
        return $this->thumbnailSize;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getThumbnailWidth(): int
    {
        return $this->thumbnailWidth;
    }

    public function getThumbnailHeight(): int
    {
        return $this->thumbnailHeight;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
