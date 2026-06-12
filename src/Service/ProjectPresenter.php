<?php

namespace App\Service;

use App\Entity\CatalogItem;
use App\Entity\ItemPhoto;
use App\Entity\Participant;
use App\Entity\Project;

class ProjectPresenter
{
    /** @return array<string, mixed> */
    public function present(Project $project): array
    {
        return [
            'id' => $project->getId(),
            'code' => $project->getCode(),
            'name' => $project->getName(),
            'createdAt' => $project->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $project->getUpdatedAt()->format(DATE_ATOM),
            'participants' => array_map(fn (Participant $participant): array => $this->presentParticipant($participant), $project->getParticipants()->toArray()),
            'items' => array_map(fn (CatalogItem $item): array => $this->presentItem($item), $project->getItems()->toArray()),
        ];
    }

    /** @return array<string, mixed> */
    public function presentParticipant(Participant $participant): array
    {
        return [
            'id' => $participant->getId(),
            'deviceId' => $participant->getDeviceId(),
            'displayName' => $participant->getDisplayName(),
            'joinedAt' => $participant->getJoinedAt()->format(DATE_ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function presentItem(CatalogItem $item): array
    {
        return [
            'id' => $item->getId(),
            'createdByDeviceId' => $item->getCreatedByDeviceId(),
            'title' => $item->getTitle(),
            'description' => $item->getDescription(),
            'buyPrice' => $item->getBuyPrice(),
            'soldPrice' => $item->getSoldPrice(),
            'quantity' => $item->getQuantity(),
            'createdAt' => $item->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $item->getUpdatedAt()->format(DATE_ATOM),
            'photos' => array_map(fn (ItemPhoto $photo): array => $this->presentPhoto($photo), $item->getPhotos()->toArray()),
        ];
    }

    /** @return array<string, mixed> */
    public function presentPhoto(ItemPhoto $photo): array
    {
        return [
            'id' => $photo->getId(),
            'storageKey' => $photo->getStorageKey(),
            'position' => $photo->getPosition(),
            'contentType' => $photo->getContentType(),
            'size' => $photo->getSize(),
            'createdAt' => $photo->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
