<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Entity\Archive;
use App\Form\DossierType;
use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;
use PclZip;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Mime\Attachment;
use Knp\Component\Pager\PaginatorInterface;
use ZipArchive;

#[Route('/dossier')]
class DossierController extends AbstractController
{
    #[Route('/', name: 'app_dossier_index', methods: ['GET', 'POST'])]
    public function index(DossierRepository $dossierRepository, PaginatorInterface $paginator, Request $request): Response
    {
        // Récupérer tous les dossiers
        $queryBuilder = $dossierRepository->createQueryBuilder('d');

        // Paginer les résultats
        $pagination = $paginator->paginate(
            $queryBuilder, // Requête ou QueryBuilder
            $request->query->getInt('page', 1), // Numéro de la page actuelle
            10 // Nombre d'éléments par page
        );

        return $this->render('dossier/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_dossier_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $dossier = new Dossier();
        $form = $this->createForm(DossierType::class, $dossier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();

            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('dossier_directory'), // The directory where files are stored
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement du fichier.');
                    return $this->renderForm('dossier/new.html.twig', [
                        'dossier' => $dossier,
                        'form' => $form,
                    ]);
                }

                // Store the filename and URL
                $dossier->setFileName($newFilename);
                $dossier->setFilePath('/uploads/dossier/' . $newFilename); // Store the relative URL
            }

            $entityManager->persist($dossier);
            $entityManager->flush();

            return $this->redirectToRoute('app_dossier_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('dossier/new.html.twig', [
            'dossier' => $dossier,
            'form' => $form,
        ]);
    }
    #[Route('/{id}', name: 'app_dossier_show', methods: ['GET'])]
    public function show(Dossier $dossier): Response
    {
        return $this->render('dossier/show.html.twig', [
            'dossier' => $dossier,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_dossier_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]

    public function edit(Request $request, Dossier $dossier, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DossierType::class, $dossier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_dossier_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('dossier/edit.html.twig', [
            'dossier' => $dossier,
            'form' => $form,
        ]);
    }


    #[Route('/dossier/{id}/delete', name: 'app_dossier_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]

    public function delete(Request $request, Dossier $dossier, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $dossier->getId(), $request->request->get('_token'))) {
            $entityManager->remove($dossier);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_dossier_index');
    }

    #[Route('/dossier/send-email-all', name: 'app_dossier_send_email_all', methods: ['POST'])]
    public function sendEmailToAll(MailerInterface $mailer, LoggerInterface $logger, EntityManagerInterface $em): Response
    {
        $dossiers = $em->getRepository(Dossier::class)->findAll();

        foreach ($dossiers as $dossier) {
            // Construction de l'URL du dossier
            $dossierUrl = $this->generateUrl('app_dossier_show', ['id' => $dossier->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            // Création de l'email
            $email = (new Email())
                ->from('taher.benismail@gmail.com')
                ->to('taher.benismail@gmail.com')
                ->subject('OrderToCash')
                ->text("Consulter l'administration pour vérifier votre état. Votre dossier contient des anomalies.\n\nVoici le lien vers votre dossier : $dossierUrl");

            try {
                $mailer->send($email);
                $this->addFlash('success', 'Emails sent successfully!');
            } catch (TransportExceptionInterface $e) {
                $logger->error('Failed to send email: ' . $e->getMessage());
                $this->addFlash('error', 'Failed to send email.');
            }

            // Déplacer le dossier vers Archive
            $archive = new Archive();
            $archive->setNom($dossier->getNom());
            $archive->setFileName($dossier->getFileName());
            $archive->setFilePath($dossier->getFilePath());
            $archive->setUpdatedAt($dossier->getUpdatedAt());

            $em->remove($dossier); // Supprimer le dossier de la table principale
            $em->persist($archive); // Ajouter le dossier à l'archive
        }

        $em->flush();

        return $this->redirectToRoute('app_dossier_index');
    }

    #[Route('/dossier/{id}/choose-recipients', name: 'app_dossier_choose_recipients')]
    public function chooseRecipients(Dossier $dossier, Request $request): Response
    {
        // Créer un formulaire pour sélectionner les destinataires
        $form = $this->createFormBuilder()
            ->add('recipients', TextType::class, [
                'label' => 'Destinataires principaux (séparés par des virgules)',
                'required' => true,
            ])
            ->add('cc', TextType::class, [
                'label' => 'Destinataires en copie (séparés par des virgules)',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, ['label' => 'Envoyer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $recipients = explode(',', $data['recipients']);
            $cc = explode(',', $data['cc']);

            // Rediriger vers l'action d'envoi d'email avec les destinataires
            return $this->redirectToRoute('app_dossier_send_email', [
                'id' => $dossier->getId(),
                'recipients' => $recipients,
                'cc' => $cc
            ]);
        }

        return $this->render('parametrage/choose_recipients.html.twig', [
            'form' => $form->createView(),
            'dossier' => $dossier,
        ]);
    }
    #[Route('/dossier/{id}/archiver', name: 'app_dossier_archiver', methods: ['POST', 'GET'])]
    public function archiver(Dossier $dossier, EntityManagerInterface $em): Response
    {
        // Créer une nouvelle instance de l'Archive et copier les informations du dossier
        $archive = new Archive();
        $archive->setNom($dossier->getNom());
        $archive->setFileName($dossier->getFileName());
        $archive->setDescription($dossier->getDescription());  // Copier le chemin du fichier
        $archive->setFilePath($dossier->getFilePath());  // Copier le chemin du fichier
        $archive->setUpdatedAt($dossier->getUpdatedAt());  // Utiliser la date de mise à jour

        // Supprimer le dossier de la table 'Dossier'
        $em->remove($dossier);

        // Ajouter le dossier à la table 'Archive'
        $em->persist($archive);

        // Appliquer les changements dans la base de données
        $em->flush();

        // Ajouter un message flash pour indiquer que le dossier a été archivé avec succès
        $this->addFlash('success', 'Le dossier a été archivé avec succès.');

        // Rediriger vers la page d'index des dossiers
        return $this->redirectToRoute('app_dossier_index');
    }

}
