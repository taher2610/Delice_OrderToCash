<?php

namespace App\Controller;

use App\Repository\DossierRepository;
use App\Repository\ArchiveRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChartsController extends AbstractController
{
    #[Route('/charts', name: 'app_charts')]
    public function indexA(): Response
    {
        return $this->render('charts/index.html.twig', [
            'controller_name' => 'ChartsController',
        ]);
    }

    #[Route('/chart/dossier-archive', name: 'chart_dossier_archive')]
    public function index(DossierRepository $dossierRepository, ArchiveRepository $archiveRepository): Response
    {
        // Récupérer le nombre de dossiers et d'archives
        $dossierCount = $dossierRepository->count([]);
        $archiveCount = $archiveRepository->count([]);

        return $this->render('charts/dossier_archive.html.twig', [
            'dossierCount' => $dossierCount,
            'archiveCount' => $archiveCount,
        ]);
    }

    #[Route('/stats', name: 'app_user_stats', methods: ['GET'])]
    public function stats(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        $adminCount = 0;
        $userCount = 0;

        foreach ($users as $user) {
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $adminCount++;
            } elseif (in_array('ROLE_USER', $user->getRoles())) {
                $userCount++;
            }
        }

        return $this->render('charts/admin_user.html.twig', [
            'adminCount' => $adminCount,
            'userCount' => $userCount,
        ]);
    }
}
