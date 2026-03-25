<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Repository\OrderRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route; // Correction de l'import
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BillController extends AbstractController
{
    // LIGNE 13 : Ajout impératif du "/" au début
    #[Route('/editor/order/{id}/bill', name: 'app_bill')]
    public function index($id, OrderRepository $orderRepository): Response
    {
        // 1. Récupération de la commande
        $order = $orderRepository->find($id);
        
        if (!$order) {
            throw $this->createNotFoundException('La facture demandée n\'existe pas.');
        }

        // 2. Configuration de Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        // Option cruciale pour éviter les crashs d'images en local
        $pdfOptions->set('isRemoteEnabled', true); 

        $domPdf = new Dompdf($pdfOptions);

        // 3. Génération du HTML
        $html = $this->renderView('bill/index.html.twig', [
            'order' => $order,
        ]);

        // 4. Chargement et Rendu (On ne fait SURTOUT PAS de ->stream() ici)
        $domPdf->loadHtml($html);
        $domPdf->setPaper('A4', 'portrait');
        $domPdf->render();

        // 5. On récupère le contenu du PDF dans une variable
        $pdfContent = $domPdf->output();

        // 6. On retourne une réponse Symfony standard (Propre et stable)
        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Antarcthé-Facture-' . $order->getId() . '.pdf"'
        ]);
    }
}