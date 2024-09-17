<?php
namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Passport\Passport;
use Symfony\Component\Security\Core\Authentication\Token\Passport\UserBadge;
use Symfony\Component\Security\Core\Authentication\Token\Passport\Credentials\PasswordCredentials;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    private UserPasswordEncoderInterface $userPasswordEncoder;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UserPasswordEncoderInterface $userPasswordEncoder, UrlGeneratorInterface $urlGenerator)
    {
        $this->userPasswordEncoder = $userPasswordEncoder;
        $this->urlGenerator = $urlGenerator;
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('_username');
        $password = $request->request->get('_password');

        if (!$username || !$password) {
            throw new CustomUserMessageAuthenticationException('Missing credentials.');
        }

        $userProvider = $this->userProvider;
        $user = $userProvider->loadUserByIdentifier($username);

        if (!$this->userPasswordEncoder->isPasswordValid($user, $password)) {
            throw new CustomUserMessageAuthenticationException('Invalid credentials.');
        }

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($password)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('app_homepage'));
    }

    public function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login');
    }
}



