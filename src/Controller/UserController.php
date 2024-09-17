<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/user')]
class UserController extends AbstractController
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, PaginatorInterface $paginator, Request $request): Response
    {
        // Récupérer les utilisateurs avec pagination
        $queryBuilder = $userRepository->createQueryBuilder('u');

        // Utilisation de la pagination
        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(), // Requête DQL
            $request->query->getInt('page', 1), // Page actuelle, par défaut 1
            10 // Limite d'utilisateurs par page
        );

        return $this->render('user/index.html.twig', [
            'users' => $pagination, // Utilisateurs paginés
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, UrlGeneratorInterface $urlGenerator): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $user->getEmail();
            if ($email === null) {
                throw new \Exception('L\'email ne peut pas être nul.');
            }

            $hashedPassword = $this->passwordHasher->hashPassword($user, $form->get('plainPassword')->getData());
            $user->setPassword($hashedPassword);
            $user->setIsVerified(false);
            $user->setVerificationToken(bin2hex(random_bytes(32)));

            $entityManager->persist($user);
            $entityManager->flush();

            $emailMessage = (new Email())
                ->from('taher.benismail@gmail.com')
                ->to($email)
                ->subject('Confirmez votre inscription')
                ->html(
                    '<p>Pour confirmer votre inscription, veuillez cliquer sur le lien suivant :</p>' .
                    '<p><a href="' . $urlGenerator->generate('app_user_verify', ['token' => $user->getVerificationToken()], UrlGeneratorInterface::ABSOLUTE_URL) . '">Confirmer mon inscription</a></p>'
                );

            $mailer->send($emailMessage);

            $this->addFlash('success', 'Un email de confirmation a été envoyé. Veuillez vérifier votre boîte de réception.');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]

    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/verify/{token}', name: 'app_user_verify', methods: ['GET'])]
    public function verifyUser(string $token, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Le lien de vérification est invalide.');
            return $this->redirectToRoute('app_user_index');
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null); // Optionnel, supprime le token après vérification

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Votre compte a été vérifié avec succès. Vous pouvez maintenant vous connecter.');

        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/user/{id}/toggle-block', name: 'user_toggle_block')]
    #[IsGranted('ROLE_ADMIN')]

    public function toggleBlock(User $user, EntityManagerInterface $em): Response
    {
        // Vérifiez que l'utilisateur a les droits nécessaires pour modifier cet utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Inversez l'état de blocage
        $user->setIsBlocked(!$user->getIsBlocked());

        // Persist and flush changes
        $em->persist($user);
        $em->flush();

        // Ajouter un message flash
        $status = $user->getIsBlocked() ? 'bloqué' : 'débloqué';
        $this->addFlash('success', "L'utilisateur a été $status avec succès.");

        // Rediriger vers la page des utilisateurs ou ailleurs
        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/user/promote/{id}', name: 'user_promote')]
    public function promote(User $user, EntityManagerInterface $entityManager): RedirectResponse
    {
        // Ajouter le rôle d'admin à l'utilisateur s'il ne l'a pas déjà
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            $user->setRoles(array_merge($user->getRoles(), ['ROLE_ADMIN']));
            $entityManager->persist($user);
            $entityManager->flush(); // Sauvegarder dans la base de données
        }

        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/user/demote/{id}', name: 'user_demote')]
    public function demote(User $user, EntityManagerInterface $entityManager): RedirectResponse
    {
        // Retirer le rôle d'admin et s'assurer que le rôle d'utilisateur reste présent
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $user->setRoles(['ROLE_USER']);
            $entityManager->persist($user);
            $entityManager->flush(); // Sauvegarder dans la base de données
        }

        return $this->redirectToRoute('app_user_index');
    }
}
