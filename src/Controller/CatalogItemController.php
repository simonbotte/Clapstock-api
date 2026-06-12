<?php

namespace App\Controller;

use App\Entity\CatalogItem;
use App\Entity\ItemPhoto;
use App\Service\PhotoStorage;
use App\Service\ProjectPresenter;
use App\Service\RealtimePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/projects/{code}/items')]
class CatalogItemController extends ApiController
{
    #[Route('', methods: ['POST'])]
    public function create(
        string $code,
        Request $request,
        EntityManagerInterface $entityManager,
        ProjectPresenter $presenter,
        RealtimePublisher $publisher,
    ): JsonResponse {
        $project = $this->findProject($entityManager, $code);
        if ($project === null) {
            return $this->notFound('Project not found.');
        }

        try {
            $payload = $this->jsonPayload($request);
            $item = new CatalogItem(
                $project,
                $this->requiredString($payload, 'deviceId', 120),
                $this->requiredString($payload, 'title', 160),
                $this->requiredString($payload, 'description', 5000),
                $this->requiredMoney($payload, 'buyPrice'),
                $this->requiredMoney($payload, 'soldPrice'),
                $this->optionalInt($payload, 'quantity'),
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->validationError($exception);
        }

        $project->addItem($item);
        $entityManager->persist($item);
        $entityManager->flush();

        $data = $presenter->presentItem($item);
        $publisher->publishProjectEvent($project->getCode(), 'item.created', $data);

        return $this->json($data, JsonResponse::HTTP_CREATED);
    }

    #[Route('/{itemId}', methods: ['PATCH'])]
    public function update(
        string $code,
        int $itemId,
        Request $request,
        EntityManagerInterface $entityManager,
        ProjectPresenter $presenter,
        RealtimePublisher $publisher,
    ): JsonResponse {
        $item = $this->findItem($entityManager, $code, $itemId);
        if ($item === null) {
            return $this->notFound('Item not found.');
        }

        try {
            $payload = $this->jsonPayload($request);
            $item->update(
                $this->requiredString($payload, 'title', 160),
                $this->requiredString($payload, 'description', 5000),
                $this->requiredMoney($payload, 'buyPrice'),
                $this->requiredMoney($payload, 'soldPrice'),
                $this->optionalInt($payload, 'quantity'),
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->validationError($exception);
        }

        $entityManager->flush();

        $data = $presenter->presentItem($item);
        $publisher->publishProjectEvent($item->getProject()->getCode(), 'item.updated', $data);

        return $this->json($data);
    }

    #[Route('/{itemId}', methods: ['DELETE'])]
    public function delete(
        string $code,
        int $itemId,
        EntityManagerInterface $entityManager,
        PhotoStorage $storage,
        RealtimePublisher $publisher,
    ): JsonResponse {
        $item = $this->findItem($entityManager, $code, $itemId);
        if ($item === null) {
            return $this->notFound('Item not found.');
        }

        $project = $item->getProject();
        $photoKeys = array_map(fn (ItemPhoto $photo): string => $photo->getStorageKey(), $item->getPhotos()->toArray());
        foreach ($photoKeys as $photoKey) {
            try {
                $storage->delete($photoKey);
            } catch (\Throwable) {
            }
        }

        $entityManager->remove($item);
        $project->touch();
        $entityManager->flush();

        $publisher->publishProjectEvent($project->getCode(), 'item.deleted', [
            'id' => $itemId,
            'photoKeys' => $photoKeys,
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
}
