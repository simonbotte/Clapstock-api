<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Project;
use App\Service\ProjectCodeGenerator;
use App\Service\ProjectPresenter;
use App\Service\RealtimePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/projects')]
class ProjectController extends ApiController
{
    #[Route('', methods: ['GET'])]
    public function list(
        Request $request,
        EntityManagerInterface $entityManager,
        ProjectPresenter $presenter,
    ): JsonResponse {
        $deviceId = trim((string) $request->query->get('deviceId', ''));
        if ($deviceId === '') {
            return $this->validationError(new \InvalidArgumentException('deviceId is required.'));
        }

        $projects = $entityManager->createQueryBuilder()
            ->select('project')
            ->from(Project::class, 'project')
            ->join('project.participants', 'participant')
            ->andWhere('participant.deviceId = :deviceId')
            ->setParameter('deviceId', $deviceId)
            ->orderBy('project.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->json(array_map(
            fn (Project $project): array => $presenter->presentSummary($project),
            $projects
        ));
    }

    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ProjectCodeGenerator $codeGenerator,
        ProjectPresenter $presenter,
        RealtimePublisher $publisher,
    ): JsonResponse {
        try {
            $payload = $this->jsonPayload($request);
            $project = new Project(
                $codeGenerator->generate(),
                $this->requiredString($payload, 'name', 120),
            );
            $participant = new Participant(
                $project,
                $this->requiredString($payload, 'deviceId', 120),
                $this->requiredString($payload, 'displayName', 120),
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->validationError($exception);
        }

        $project->addParticipant($participant);
        $entityManager->persist($project);
        $entityManager->persist($participant);
        $entityManager->flush();

        $data = $presenter->present($project);
        $publisher->publishProjectEvent($project->getCode(), 'project.created', $data);

        return $this->json($data, JsonResponse::HTTP_CREATED);
    }

    #[Route('/join', methods: ['POST'])]
    public function join(
        Request $request,
        EntityManagerInterface $entityManager,
        ProjectPresenter $presenter,
        RealtimePublisher $publisher,
    ): JsonResponse {
        try {
            $payload = $this->jsonPayload($request);
            $code = strtoupper($this->requiredString($payload, 'code', 12));
            $deviceId = $this->requiredString($payload, 'deviceId', 120);
            $displayName = $this->requiredString($payload, 'displayName', 120);
        } catch (\InvalidArgumentException $exception) {
            return $this->validationError($exception);
        }

        $project = $this->findProject($entityManager, $code);
        if ($project === null) {
            return $this->notFound('Project not found.');
        }

        $participant = $entityManager->getRepository(Participant::class)->findOneBy([
            'project' => $project,
            'deviceId' => $deviceId,
        ]);

        if ($participant instanceof Participant) {
            $participant->rename($displayName);
        } else {
            $participant = new Participant($project, $deviceId, $displayName);
            $project->addParticipant($participant);
            $entityManager->persist($participant);
        }

        $project->touch();
        $entityManager->flush();

        $data = $presenter->present($project);
        $publisher->publishProjectEvent($project->getCode(), 'participant.joined', $presenter->presentParticipant($participant));

        return $this->json($data);
    }

    #[Route('/{code}', methods: ['GET'])]
    public function show(string $code, EntityManagerInterface $entityManager, ProjectPresenter $presenter): JsonResponse
    {
        $project = $this->findProject($entityManager, $code);
        if ($project === null) {
            return $this->notFound('Project not found.');
        }

        return $this->json($presenter->present($project));
    }
}
