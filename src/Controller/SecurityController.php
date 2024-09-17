<?php

namespace App\Controller;

use App\DTO\RegistrationDTO;
use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/security', name: 'app_security')]
    public function index(): Response
    {
        return $this->render('security/index.html.twig', [
            'controller_name' => 'SecurityController',
        ]);
    }

    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils, FormFactoryInterface $formFactory): Response
    {
        $loginForm = $formFactory->createBuilder()
            ->add('_username', TextType::class, ['label' => 'Email'])
            ->add('_password', PasswordType::class, ['label' => 'Password'])
            ->getForm();

        // Récupérer les erreurs de connexion
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('user/login.html.twig', [
            'loginForm' => $loginForm->createView(),
            'error' => $error,
            'last_username' => $lastUsername?:'',
        ]);
    }



    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, UrlGeneratorInterface $urlGenerator): Response
    {
        $registrationDTO = new RegistrationDTO();
        $form = $this->createForm(RegistrationFormType::class, $registrationDTO);
        $form->handleRequest($request);

        $errors = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $user = new User();
            $user->setEmail($registrationDTO->getEmail());
            $user->setPassword($this->passwordHasher->hashPassword($user, $registrationDTO->getPlainPassword()));
            $user->setIsVerified(false);
            $user->setVerificationToken(bin2hex(random_bytes(32)));

            $entityManager->persist($user);
            $entityManager->flush();

            $emailMessage = (new Email())
                ->from('taher.benismail@gmail.com')
                ->to('taher.benismail@gmail.com') // Recevoir l'email de confirmation
                ->subject('Nouvelle inscription')
                ->html(
                    '<p>Un nouvel utilisateur s\'est inscrit. Veuillez vérifier son compte en cliquant sur le lien suivant :</p>' .
                    '<p><a href="' . $urlGenerator->generate('app_user_verify', ['token' => $user->getVerificationToken()], UrlGeneratorInterface::ABSOLUTE_URL) . '">Activer le compte</a></p>'
                );

            $mailer->send($emailMessage);

            $this->addFlash('success', 'Un email de confirmation a été envoyé. Veuillez vérifier votre boîte de réception.');

            return $this->redirectToRoute('app_login');
        }

        // Si le formulaire est soumis mais invalide, récupérer les erreurs
        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = $form->getErrors(true, false);
        }

        return $this->render('user/register.html.twig', [
            'registrationForm' => $form->createView(),
            'errors' => $errors, // Passer les erreurs au template
        ]);
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
        $user->setVerificationToken(null); // Supprimer le token après vérification

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Votre compte a été vérifié avec succès. Vous pouvez maintenant vous connecter.');

        return $this->redirectToRoute('app_user_index');
    }


    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }
}
