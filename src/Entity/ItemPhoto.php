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

    #[ORM\Column]
    private int $position;

    #[ORM\Column(length: 120)]
    private string $contentType;

    #[ORM\Column]
    private int $size;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(CatalogItem $item, string $storageKey, int $position, string $contentType, int $size)
    {
        $this->item = $item;
        $this->storageKey = $storageKey;
        $this->position = $position;
        $this->contentType = $contentType;
        $this->size = $size;
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

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
