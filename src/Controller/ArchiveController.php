<?php

namespace App\Controller;

use App\Entity\Archive;
use App\Entity\Dossier;
use App\Form\ArchiveType;
use App\Repository\ArchiveRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/archive')]
class ArchiveController extends AbstractController
{
    #[Route('/', name: 'app_archive_index', methods: ['GET'])]
    public function index(ArchiveRepository $archiveRepository, PaginatorInterface $paginator, Request $request): Response
    {
        // Récupérer toutes les archives
        $queryBuilder = $archiveRepository->createQueryBuilder('a');

        // Paginer les résultats
        $pagination = $paginator->paginate(
            $queryBuilder, // Requête ou QueryBuilder
            $request->query->getInt('page', 1), // Numéro de la page actuelle
            10 // Nombre d'éléments par page
        );

        return $this->render('archive/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_archive_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $archive = new Archive();
        $form = $this->createForm(ArchiveType::class, $archive);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($archive);
            $entityManager->flush();

            return $this->redirectToRoute('app_archive_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('archive/new.html.twig', [
            'archive' => $archive,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_archive_show', methods: ['GET'])]
    public function show(Archive $archive): Response
    {
        return $this->render('archive/show.html.twig', [
            'archive' => $archive,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_archive_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]

    public function edit(Request $request, Archive $archive, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ArchiveType::class, $archive);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_archive_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('archive/edit.html.twig', [
            'archive' => $archive,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_archive_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]

    public function delete(Request $request, Archive $archive, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$archive->getId(), $request->request->get('_token'))) {
            $entityManager->remove($archive);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_archive_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/archive/{id}/rendre', name: 'app_archive_rendre')]
    public function rendre(Archive $archive, EntityManagerInterface $em): Response
    {
        if (!$archive) {
            throw $this->createNotFoundException('Archive not found');
        }

        // Créer un nouvel objet Dossier avec les mêmes propriétés que l'archive
        $dossier = new Dossier();
        $dossier->setNom($archive->getNom());
        $dossier->setFileName($archive->getFileName());
        $dossier->setDescription($archive->getDescription());
        $dossier->setFilePath($archive->getFilePath());
        $dossier->setUpdatedAt($archive->getUpdatedAt());

        // Enlever l'objet Archive de la base de données
        $em->remove($archive);

        // Sauvegarder le nouvel objet Dossier dans la base de données
        $em->persist($dossier);
        $em->flush();

        // Rediriger vers la liste des archives ou des dossiers
        return $this->redirectToRoute('app_dossier_index');
    }
}
