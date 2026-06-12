<?php

namespace App\Controller;

use App\Entity\CatalogItem;
use App\Entity\ItemPhoto;
use App\Service\PhotoStorage;
use App\Service\ProjectPresenter;
use App\Service\RealtimePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/projects/{code}/items/{itemId}/photos')]
class PhotoController extends ApiController
{
    #[Route('', methods: ['POST'])]
    public function upload(
        string $code,
        int $itemId,
        Request $request,
        EntityManagerInterface $entityManager,
        PhotoStorage $storage,
        ProjectPresenter $presenter,
        RealtimePublisher $publisher,
    ): JsonResponse {
        $item = $this->findItem($entityManager, $code, $itemId);
        if ($item === null) {
            return $this->notFound('Item not found.');
        }

        if ($item->getPhotos()->count() >= 3) {
            return $this->json([
                'error' => [
                    'code' => 'photo_limit_reached',
                    'message' => 'An item can have at most 3 photos.',
                ],
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $file = $request->files->get('photo');
        if (!$file instanceof UploadedFile) {
            return $this->validationError(new \InvalidArgumentException('photo is required.'));
        }

        if (!$file->isValid() || $file->getPathname() === '' || !is_readable($file->getPathname())) {
            return $this->validationError(new \InvalidArgumentException('photo must be a readable uploaded file.'));
        }

        $mimeType = $file->getMimeType() ?: 'application/octet-stream';
        if (!str_starts_with($mimeType, 'image/')) {
            return $this->validationError(new \InvalidArgumentException('photo must be an image.'));
        }

        try {
            $storageKey = $storage->upload($item->getProject()->getCode(), (int) $item->getId(), $file);
        } catch (\Throwable) {
            return $this->json([
                'error' => [
                    'code' => 'photo_upload_failed',
                    'message' => 'Photo upload failed.',
                ],
            ], JsonResponse::HTTP_BAD_GATEWAY);
        }

        $position = $item->getPhotos()->count() + 1;
        $photo = new ItemPhoto($item, $storageKey, $position, $mimeType, $file->getSize() ?: 0);
        $item->addPhoto($photo);
        $entityManager->persist($photo);
        $entityManager->flush();

        $data = $presenter->presentPhoto($photo);
        $publisher->publishProjectEvent($item->getProject()->getCode(), 'photo.created', [
            'itemId' => $item->getId(),
            'photo' => $data,
        ]);

        return $this->json($data, JsonResponse::HTTP_CREATED);
    }

    #[Route('/{photoId}', methods: ['GET'])]
    public function show(
        string $code,
        int $itemId,
        int $photoId,
        EntityManagerInterface $entityManager,
        PhotoStorage $storage,
    ): Response|JsonResponse {
        $photo = $this->findPhoto($entityManager, $code, $itemId, $photoId);
        if ($photo === null) {
            return $this->notFound('Photo not found.');
        }

        try {
            $content = $storage->download($photo->getStorageKey());
        } catch (\Throwable) {
            return $this->notFound('Photo file not found.');
        }

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => $photo->getContentType(),
            'Content-Length' => (string) strlen($content),
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    #[Route('/{photoId}', methods: ['DELETE'])]
    public function delete(
        string $code,
        int $itemId,
        int $photoId,
        EntityManagerInterface $entityManager,
        PhotoStorage $storage,
        RealtimePublisher $publisher,
    ): JsonResponse {
        $photo = $this->findPhoto($entityManager, $code, $itemId, $photoId);
        if ($photo === null) {
            return $this->notFound('Photo not found.');
        }

        $storageKey = $photo->getStorageKey();
        try {
            $storage->delete($storageKey);
        } catch (\Throwable) {
        }

        $entityManager->remove($photo);
        $entityManager->flush();

        $publisher->publishProjectEvent(strtoupper($code), 'photo.deleted', [
            'itemId' => $itemId,
            'photoId' => $photoId,
            'storageKey' => $storageKey,
        ]);

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    private function findItem(EntityManagerInterface $entityManager, string $code, int $itemId): ?CatalogItem
    {
        return $entityManager->createQueryBuilder()
            ->select('item')
            ->from(CatalogItem::class, 'item')
            ->join('item.project', 'project')
            ->andWhere('project.code = :code')
            ->andWhere('item.id = :itemId')
            ->setParameter('code', strtoupper($code))
            ->setParameter('itemId', $itemId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function findPhoto(EntityManagerInterface $entityManager, string $code, int $itemId, int $photoId): ?ItemPhoto
    {
        return $entityManager->createQueryBuilder()
            ->select('photo')
            ->from(ItemPhoto::class, 'photo')
            ->join('photo.item', 'item')
            ->join('item.project', 'project')
            ->andWhere('project.code = :code')
            ->andWhere('item.id = :itemId')
            ->andWhere('photo.id = :photoId')
            ->setParameter('code', strtoupper($code))
            ->setParameter('itemId', $itemId)
            ->setParameter('photoId', $photoId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
