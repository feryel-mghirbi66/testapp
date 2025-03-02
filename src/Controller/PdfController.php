<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ConsultationRepository;

class PdfController extends AbstractController
{
    #[Route('/consultations/pdf', name: 'app_consultations_pdf')]
    public function generatePdf(ConsultationRepository $consultationRepository): Response
    {
        $consultations = $consultationRepository->findAll();
    
        // Absolute file path for Dompdf
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/imagesS/logo.png';
    
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
    
        $dompdf = new Dompdf($pdfOptions);
        $html = $this->renderView('pdf/consultations.html.twig', [
            'consultations' => $consultations,
            'logoPath' => $logoPath, // Pass logo path to Twig
        ]);
    
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
    
        return new Response(
            $dompdf->output(),
            200,
            ['Content-Type' => 'application/pdf']
        );
    }
    
}
