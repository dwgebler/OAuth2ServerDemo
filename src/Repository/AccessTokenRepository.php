<?php

namespace App\Repository;

use League\Bundle\OAuth2ServerBundle\Repository\AccessTokenRepository as BaseAccessTokenRepository;
use App\Entity\AccessToken as AccessTokenEntity;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

final class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    private BaseAccessTokenRepository $baseAccessTokenRepository;

    public function __construct(BaseAccessTokenRepository $baseAccessTokenRepository)
    {
        $this->baseAccessTokenRepository = $baseAccessTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
    {
        /** @var int|string|null $userIdentifier */
        $accessToken = new AccessTokenEntity();
        $accessToken->setClient($clientEntity);
        $accessToken->setUserIdentifier($userIdentifier);

        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }

        return $accessToken;
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $this->baseAccessTokenRepository->persistNewAccessToken($accessTokenEntity);
    }

    /**
     * @param string $tokenId
     */
    public function revokeAccessToken($tokenId): void
    {
        $this->baseAccessTokenRepository->revokeAccessToken($tokenId);
    }

    /**
     * @param string $tokenId
     */
    public function isAccessTokenRevoked($tokenId): bool
    {
        return $this->baseAccessTokenRepository->isAccessTokenRevoked($tokenId);
    }
}
