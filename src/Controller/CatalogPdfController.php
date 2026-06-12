<?php

namespace App\Controller;

use App\Service\CatalogPdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CatalogPdfController extends ApiController
{
    #[Route('/api/projects/{code}/catalog.pdf', methods: ['POST'])]
    public function generate(string $code, EntityManagerInterface $entityManager, CatalogPdfGenerator $generator): Response
    {
        $project = $this->findProject($entityManager, $code);
        if ($project === null) {
            return $this->notFound('Project not found.');
        }

        $pdf = $generator->generate($project);

        return new Response($pdf, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="clapstock-%s.pdf"', $project->getCode()),
        ]);
    }
}
