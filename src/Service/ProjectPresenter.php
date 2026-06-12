<?php

namespace App\Service;

use App\Entity\CatalogItem;
use App\Entity\ItemPhoto;
use App\Entity\Participant;
use App\Entity\Project;

class ProjectPresenter
{
    public function __construct(
        private readonly string $mercurePublicUrl,
    ) {
    }

    /** @return array<string, mixed> */
    public function present(Project $project): array
    {
        return [
            'id' => $project->getId(),
            'code' => $project->getCode(),
            'name' => $project->getName(),
            'mercureUrl' => $this->mercurePublicUrl !== '' ? $this->mercurePublicUrl : null,
            'createdAt' => $project->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $project->getUpdatedAt()->format(DATE_ATOM),
            'participants' => array_map(fn (Participant $participant): array => $this->presentParticipant($participant), $project->getParticipants()->toArray()),
            'items' => array_map(fn (CatalogItem $item): array => $this->presentItem($item), $project->getItems()->toArray()),
        ];
    }

    /** @return array<string, mixed> */
    public function presentSummary(Project $project): array
    {
        return [
            'id' => $project->getId(),
            'code' => $project->getCode(),
            'name' => $project->getName(),
            'mercureUrl' => $this->mercurePublicUrl !== '' ? $this->mercurePublicUrl : null,
            'updatedAt' => $project->getUpdatedAt()->format(DATE_ATOM),
            'participantCount' => $project->getParticipants()->count(),
            'itemCount' => $project->getItems()->count(),
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
        $item = $photo->getItem();

        return [
            'id' => $photo->getId(),
            'storageKey' => $photo->getStorageKey(),
            'thumbnailStorageKey' => $photo->getThumbnailStorageKey(),
            'url' => sprintf(
                '/api/projects/%s/items/%d/photos/%d',
                rawurlencode($item->getProject()->getCode()),
                $item->getId(),
                $photo->getId(),
            ),
            'thumbnailUrl' => sprintf(
                '/api/projects/%s/items/%d/photos/%d/thumbnail',
                rawurlencode($item->getProject()->getCode()),
                $item->getId(),
                $photo->getId(),
            ),
            'position' => $photo->getPosition(),
            'contentType' => $photo->getContentType(),
            'thumbnailContentType' => $photo->getThumbnailContentType(),
            'size' => $photo->getSize(),
            'thumbnailSize' => $photo->getThumbnailSize(),
            'width' => $photo->getWidth(),
            'height' => $photo->getHeight(),
            'thumbnailWidth' => $photo->getThumbnailWidth(),
            'thumbnailHeight' => $photo->getThumbnailHeight(),
            'createdAt' => $photo->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
