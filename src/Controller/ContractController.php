<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ContractRepository;

class ContractController extends AbstractController
{
    /**
     * @Route("/contracts", name="contracts")
     */
    public function index(Request $request, ContractRepository $contractRepository)
    {
        $filters = $request->query->all()['filters'] ?? [];
        $sort = $request->query->all()['sort'] ?? [];
        $limit = $request->query->all()['limit'] ?? null;

        $contracts = $contractRepository->findByFiltersAndSort($filters, $sort, $limit);

        return $this->render('contracts/index.html.twig', [
            'contracts' => $contracts,
        ]);
    }
}
