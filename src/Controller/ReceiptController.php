<?php

namespace App\Controller;

use App\Exception\ReceiptNotFoundException;
use App\Repository\ReceiptRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ReceiptController extends AbstractController
{
    /**
     * @Route("/receipt/{id}", name="receipt_show")
     */
    public function show(int $id, ReceiptRepository $receiptRepository)
    {
        $receipt = $receiptRepository->findById($id);

        if (!$receipt) {
            throw new ReceiptNotFoundException($id);
        }

        return $this->render('receipts/show.html.twig', [
            'receipt' => $receipt,
        ]);
    }
}
