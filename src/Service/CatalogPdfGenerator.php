<?php

namespace App\Service;

use App\Entity\CatalogItem;
use App\Entity\Project;
use TCPDF;

class CatalogPdfGenerator
{
    public function __construct(private readonly PhotoStorage $photoStorage)
    {
    }

    public function generate(Project $project): string
    {
        $pdf = new TCPDF();
        $pdf->SetCreator('Clapstock');
        $pdf->SetAuthor('Clapstock');
        $pdf->SetTitle($project->getName());
        $pdf->SetMargins(14, 16, 14);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 10, $project->getName(), 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 8, 'Code: '.$project->getCode().' - '.(new \DateTimeImmutable())->format('d/m/Y'), 0, 1);
        $pdf->Ln(4);

        foreach ($project->getItems() as $item) {
            $this->renderItem($pdf, $item);
        }

        return $pdf->Output('', 'S');
    }

    private function renderItem(TCPDF $pdf, CatalogItem $item): void
    {
        if ($pdf->GetY() > 235) {
            $pdf->AddPage();
        }

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 7, $item->getTitle(), 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 5, $item->getDescription(), 0, 'L');
        $quantity = $item->getQuantity() === null ? '' : ' - Quantite: '.$item->getQuantity();
        $pdf->Cell(0, 6, sprintf('Prix achat: %s - Prix vente: %s%s', $item->getBuyPrice(), $item->getSoldPrice(), $quantity), 0, 1);

        $x = $pdf->GetX();
        $y = $pdf->GetY() + 2;
        $tempFiles = [];

        foreach ($item->getPhotos() as $index => $photo) {
            try {
                $tempFile = $this->photoStorage->downloadToTempFile($photo->getStorageKey());
                $tempFiles[] = $tempFile;
                $pdf->Image($tempFile, $x + ($index * 58), $y, 52, 38, '', '', '', true, 150, '', false, false, 1);
            } catch (\Throwable) {
                $pdf->SetXY($x + ($index * 58), $y);
                $pdf->Cell(52, 38, 'Photo indisponible', 1, 0, 'C');
            }
        }

        foreach ($tempFiles as $tempFile) {
            @unlink($tempFile);
        }

        $pdf->SetY($item->getPhotos()->isEmpty() ? $pdf->GetY() + 5 : $y + 44);
        $pdf->Ln(4);
    }
}
