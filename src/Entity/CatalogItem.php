<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_item')]
class CatalogItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\Column(length: 120)]
    private string $createdByDeviceId;

    #[ORM\Column(length: 160)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $buyPrice;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $soldPrice;

    #[ORM\Column(nullable: true)]
    private ?int $quantity = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, ItemPhoto> */
    #[ORM\OneToMany(mappedBy: 'item', targetEntity: ItemPhoto::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $photos;

    public function __construct(Project $project, string $createdByDeviceId, string $title, string $description, string $buyPrice, string $soldPrice, ?int $quantity)
    {
        $this->project = $project;
        $this->createdByDeviceId = $createdByDeviceId;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
        $this->photos = new ArrayCollection();
        $this->update($title, $description, $buyPrice, $soldPrice, $quantity);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getCreatedByDeviceId(): string
    {
        return $this->createdByDeviceId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getBuyPrice(): string
    {
        return $this->buyPrice;
    }

    public function getSoldPrice(): string
    {
        return $this->soldPrice;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return Collection<int, ItemPhoto> */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function update(string $title, string $description, string $buyPrice, string $soldPrice, ?int $quantity): void
    {
        $this->title = $title;
        $this->description = $description;
        $this->buyPrice = $buyPrice;
        $this->soldPrice = $soldPrice;
        $this->quantity = $quantity;
        $this->updatedAt = new \DateTimeImmutable();
        $this->project->touch();
    }

    public function addPhoto(ItemPhoto $photo): void
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
        }
        $this->updatedAt = new \DateTimeImmutable();
        $this->project->touch();
    }
}
