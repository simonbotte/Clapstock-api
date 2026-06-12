<?php

namespace App\Controller;

use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class ApiController extends AbstractController
{
    /** @return array<string, mixed> */
    protected function jsonPayload(Request $request): array
    {
        if ($request->getContent() === '') {
            return [];
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            throw new \InvalidArgumentException('Invalid JSON payload.');
        }

        return $payload;
    }

    protected function requiredString(array $payload, string $field, int $maxLength = 255): string
    {
        $value = $payload[$field] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new \InvalidArgumentException(sprintf('%s is required.', $field));
        }

        $value = trim($value);
        if (mb_strlen($value) > $maxLength) {
            throw new \InvalidArgumentException(sprintf('%s is too long.', $field));
        }

        return $value;
    }

    protected function optionalInt(array $payload, string $field): ?int
    {
        if (!array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
            return null;
        }

        if (!is_int($payload[$field])) {
            throw new \InvalidArgumentException(sprintf('%s must be an integer.', $field));
        }

        return $payload[$field];
    }

    protected function requiredMoney(array $payload, string $field): string
    {
        $value = $payload[$field] ?? null;
        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }

        if (!is_string($value) || !preg_match('/^\d{1,8}(\.\d{1,2})?$/', $value)) {
            throw new \InvalidArgumentException(sprintf('%s must be a decimal amount.', $field));
        }

        return number_format((float) $value, 2, '.', '');
    }

    protected function findProject(EntityManagerInterface $entityManager, string $code): ?Project
    {
        return $entityManager->getRepository(Project::class)->findOneBy(['code' => strtoupper($code)]);
    }

    protected function validationError(\Throwable $exception): JsonResponse
    {
        return $this->json([
            'error' => [
                'code' => 'validation_failed',
                'message' => $exception->getMessage(),
            ],
        ], JsonResponse::HTTP_BAD_REQUEST);
    }

    protected function notFound(string $message): JsonResponse
    {
        return $this->json([
            'error' => [
                'code' => 'not_found',
                'message' => $message,
            ],
        ], JsonResponse::HTTP_NOT_FOUND);
    }
}
