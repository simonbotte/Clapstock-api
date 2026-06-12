<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'participant')]
#[ORM\UniqueConstraint(name: 'uniq_participant_project_device', columns: ['project_id', 'device_id'])]
class Participant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\Column(length: 120)]
    private string $deviceId;

    #[ORM\Column(length: 120)]
    private string $displayName;

    #[ORM\Column]
    private \DateTimeImmutable $joinedAt;

    public function __construct(Project $project, string $deviceId, string $displayName)
    {
        $this->project = $project;
        $this->deviceId = $deviceId;
        $this->displayName = $displayName;
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDeviceId(): string
    {
        return $this->deviceId;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getJoinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function rename(string $displayName): void
    {
        $this->displayName = $displayName;
    }
}
