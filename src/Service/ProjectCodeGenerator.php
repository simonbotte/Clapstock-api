<?php

namespace App\Service;

use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;

class ProjectCodeGenerator
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function generate(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < 6; ++$i) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while ($this->entityManager->getRepository(Project::class)->findOneBy(['code' => $code]) !== null);

        return $code;
    }
}
