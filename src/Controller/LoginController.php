<?php

namespace App\Controller;

use App\Entity\OAuth2ClientProfile;
use App\Entity\OAuth2UserConsent;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Uid\UuidV1;

class LoginController extends AbstractController
{
    private UserPasswordHasherInterface  $passwordEncoder;
    private ManagerRegistry $em;

    public function __construct(UserPasswordHasherInterface $passwordEncoder, ManagerRegistry $doctrine)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->em = $doctrine;
    }

    #[Route('/login', name: 'app_login')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_index');
        }
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('login/index.html.twig', [
            'controller_name' => 'LoginController',
            'error' => $error,
            'last_username' => $lastUsername,
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout()
    {
        // controller can be blank: it will never be called!
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }

    #[Route('/consent', name: 'app_consent', methods: ['GET', 'POST'])]
    public function consent(Request $request): Response
    {
        $clientId = $request->query->get('client_id');
        if (!$clientId || !ctype_alnum($clientId) || !$this->getUser()) {
            return $this->redirectToRoute('app_index');
        }
        $appClient = $this->em->getRepository(Client::class)->findOneBy(['identifier' => $clientId]);
        if (!$appClient) {
            return $this->redirectToRoute('app_index');
        }
        $appProfile = $this->em->getRepository(OAuth2ClientProfile::class)->findOneBy(['client' => $appClient]);
        $appName = $appProfile->getName();

        // Get the client scopes
        $requestedScopes = explode(' ', $request->query->get('scope'));
        // Get the client scopes in the database
        $clientScopes = $appClient->getScopes();

        // Check all requested scopes are in the client scopes
        if (count(array_diff($requestedScopes, $clientScopes)) > 0) {
            return $this->redirectToRoute('app_index');
        }

        // Check if the user has already consented to the scopes
        /** @var User $user */
        $user = $this->getUser();
        $userScopes = $user->getOAuth2UserConsent()?->getScopes() ?? [];
        $hasExistingScopes = count($userScopes) > 0;

        // If user has already consented to the scopes, give consent
        if (count(array_diff($requestedScopes, $userScopes)) === 0) {
            $request->getSession()->set('consent_granted', true);
            return $this->redirectToRoute('oauth2_authorize', $request->query->all());
        }

        // Remove the scopes to which the user has already consented
        $requestedScopes = array_diff($requestedScopes, $userScopes);

        // Map the requested scopes to scope names
        $scopeNames = [
            'profile' => 'Your profile',
            'email' => 'Your email address',
            'openid' => 'Your OpenID',
        ];

        // Get all the scope names in the requested scopes.
        $requestedScopeNames = array_map(fn($scope) => $scopeNames[$scope], $requestedScopes);
        $existingScopes = array_map(fn($scope) => $scopeNames[$scope], $userScopes);

        if ($request->isMethod('POST')) {
            if ($request->request->get('consent') === 'yes') {
                $request->getSession()->set('consent_granted', true);
                // Add the requested scopes to the user's scopes
                $consents = $user->getOAuth2UserConsent() ?? new OAuth2UserConsent();;
                $consents->setScopes(array_merge($requestedScopes, $userScopes));
                $consents->setUser($user);
                $consents->setCreated(new \DateTimeImmutable());
                $consents->setExpires(new \DateTimeImmutable('+30 days'));
                $consents->setIpAddress($request->getClientIp());
                $this->em->getManager()->persist($consents);
                $this->em->getManager()->flush();
            }
            if ($request->request->get('consent') === 'no') {
                $request->getSession()->set('consent_granted', false);
            }
            return $this->redirectToRoute('oauth2_authorize', $request->query->all());
        }
        return $this->render('login/consent.html.twig', [
            'app_name' => $appName,
            'scopes' => $requestedScopeNames,
            'has_existing_scopes' => $hasExistingScopes,
            'existing_scopes' => $existingScopes,
        ]);
    }

    #[Route('/login-user/{userid}', name: 'app_prog_login', methods: ['GET'])]
    public function loginAsUser(int $userid, TokenStorageInterface $storage): Response
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $userid]);
        if ($user) {
            $storage->setToken(
                new UsernamePasswordToken(
                    $user,
                    'main',
                    $user->getRoles()
                )
            );
            return $this->redirectToRoute('app_index');
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route('/create-user', name: 'app_create_user', methods: ['GET'])]
    public function createUser()
    {
        $user = new User();
        $user->setEmail('me@davegebler.com');
        $user->setPassword($this->passwordEncoder->hashPassword(
            $user,
            'admin'
        ));
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $user->setUuid(new UuidV1());
        $entityManager = $this->em->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        return new Response('Saved new user with id '.$user->getId());
    }
}
