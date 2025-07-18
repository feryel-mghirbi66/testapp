<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AppCustomAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator, 
        private Security $security
    ) {}

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $csrfToken = $request->request->get('_csrf_token', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        // Vérifier si un utilisateur est bien authentifié
        if (!$user) {
            return new RedirectResponse($this->urlGenerator->generate('app_login'));
        }

        $roles = $user->getRoles();

        return match (true) {
            in_array('ROLE_ADMIN', $roles, true) => new RedirectResponse($this->urlGenerator->generate('dashboard_admin')),
            in_array('ROLE_PATIENT', $roles, true) => new RedirectResponse($this->urlGenerator->generate('dashboard_patient')),
            in_array('ROLE_PHARMACIEN', $roles, true) => new RedirectResponse($this->urlGenerator->generate('dashboard_pharmacien')),
            in_array('ROLE_MEDECIN', $roles, true) => new RedirectResponse($this->urlGenerator->generate('dashboard_medecin')),
            in_array('ROLE_STAFF', $roles, true) => new RedirectResponse($this->urlGenerator->generate('dashboard_staff')),
            in_array('ROLE_USER', $roles, true) => new RedirectResponse($this->urlGenerator->generate('dashboard_user')),
            default => new RedirectResponse($this->urlGenerator->generate('app_clinique')),
        };
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
