<?php
namespace App\Controller;

use App\Entity\Archive;
use App\Entity\Dossier;
use App\Entity\Mail;
use App\Form\MailType;
use App\Repository\DossierRepository;
use App\Repository\MailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;


class ParametrageController extends AbstractController
{

    #[Route('/parametrage', name: 'app_parametrage')]
    public function index(
        EntityManagerInterface $em,
        Request $request,
        MailerInterface $mailer,
        LoggerInterface $logger,
        Security $security,
        PaginatorInterface $paginator // Ajoutez le paginator ici
    ): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $security->getUser();

        // Vérifier que l'utilisateur a bien une adresse e-mail
        if (!$user || !$user->getEmail()) {
            throw new \Exception('Utilisateur non connecté ou adresse e-mail manquante.');
        }

        // Récupérer les dossiers
        $dossiersQuery = $em->getRepository(Dossier::class)->createQueryBuilder('d')
            ->getQuery();

        // Pagination
        $pagination = $paginator->paginate(
            $dossiersQuery, // Requête Doctrine
            $request->query->getInt('page', 1), // Numéro de la page
            10 // Nombre d'éléments par page
        );

        $forms = [];

        foreach ($pagination->getItems() as $dossier) {
            $mail = $dossier->getMail();
            if (!$mail) {
                $mail = new Mail();
                $dossier->setMail($mail);
            }

            $form = $this->createForm(MailType::class, $mail);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($mail);
                $em->flush();

                // Préparation de l'email avec l'utilisateur connecté
                $email = (new Email())
                    ->from($user->getEmail())  // Utilisation de l'email de l'utilisateur connecté
                    ->to($mail->getDestPrincipal())
                    ->subject($mail->getObject())
                    ->text($mail->getText())
                    ->html('<p>' . $mail->getText() . '</p><p><a href="' . $request->getUriForPath('/dossier/' . $dossier->getId()) . '">Voir Dossier</a></p>');

                if ($mail->getDestCopie()) {
                    $copie = explode(',', $mail->getDestCopie());
                    foreach ($copie as $destinataireCopie) {
                        $email->addCc(trim($destinataireCopie));
                    }
                }

                // Log l'adresse e-mail avant l'envoi
                $logger->info('Envoi de l\'email depuis : ' . $user->getEmail());

                try {
                    $mailer->send($email);
                    $this->addFlash('success', 'Email envoyé avec succès');

                    // Archiver le dossier
                    $archive = new Archive();
                    $archive->setNom($dossier->getNom());
                    $archive->setFilePath($dossier->getFilePath());
                    $archive->setUpdatedAt(new \DateTimeImmutable());

                    $logger->info('Création d\'une nouvelle archive: ' . $dossier->getNom());
                    $em->persist($archive);
                    $em->remove($dossier);
                    $em->flush();

                    $this->addFlash('success', 'Le dossier a été archivé avec succès');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
                }

                return $this->redirectToRoute('app_parametrage');
            }

            $forms[$dossier->getId()] = $form->createView();
        }

        return $this->render('parametrage/index.html.twig', [
            'pagination' => $pagination,
            'forms' => $forms,
        ]);
    }



    #[Route('/parametrages/liste', name: 'parametrages_liste')]
    public function listeParametrages(EntityManagerInterface $em,DossierRepository $dossierRepository, MailRepository $mailRepository): Response
    {
        $dossiers = $em->getRepository(Dossier::class)->findAll();

        return $this->render('parametrage/liste_parametrages.html.twig', [
            'dossiers' => $dossiers,
        ]);
    }
}
