<?php

namespace Onedrop\RestrictedFiles\Slot;


use Doctrine\Common\Persistence\ObjectManager;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Request as HttpRequest;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\AccessDeniedException;
use Onedrop\RestrictedFiles\Domain\Model\DownloadCount;

class FileAccess
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;
    /**
     * @Flow\Inject
     * @var ObjectManager
     */
    protected $entityManager;
    /**
     * @Flow\InjectConfiguration(path="trackProtectedDownloadsByAccount")
     * @var boolean
     */
    protected $trackProtectedDownloadsByAccount;
    /**
     * @var PrivilegeManagerInterface
     * @Flow\Inject
     */
    protected $privilegeManager;

    /**
     * @param PersistentResource $resource
     * @param HttpRequest        $httpRequest
     *
     * @throws AccessDeniedException
     */
    public function checkTrackFileAccess(PersistentResource $resource, HttpRequest $httpRequest)
    {
        /** @var $actionRequest ActionRequest */
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $actionRequest = $this->objectManager->get(ActionRequest::class, $httpRequest);
        $this->securityContext->setRequest($actionRequest);
        // Deny access to the requested file if permission is missing
        if (!$this->privilegeManager->isPrivilegeTargetGranted('Onedrop.RestrictedFiles:Download')) {
            $this->emitDownloadDenied($resource, $httpRequest);
            throw new AccessDeniedException(
                sprintf('You are not allowed to access the file %s', $resource->getFilename()),
                1485716879
            );
        }
        // Track download if configured and user is authenticated
        if ($this->trackProtectedDownloadsByAccount && $this->securityContext->getAccount() instanceof Account) {
            $downloadCount = new DownloadCount($resource, $this->securityContext->getAccount());
            $this->entityManager->persist($downloadCount);
            $this->entityManager->flush();
        }
    }

    /**
     * This is just a dummy method to have a methodPrivilege to check the permission
     */
    public function download()
    {

    }

    /**
     * @Flow\Signal
     * @param PersistentResource $resource
     * @param HttpRequest        $httpRequest
     * @return void
     */
    protected function emitDownloadDenied(PersistentResource $resource, HttpRequest $httpRequest)
    {
    }

}
