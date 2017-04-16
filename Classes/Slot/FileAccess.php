<?php

namespace Onedrop\RestrictedFiles\Slot;


use Doctrine\Common\Persistence\ObjectManager;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\Http\Request as HttpRequest;
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
     * @Flow\InjectConfiguration(path="trackProtectedDownloads")
     * @var boolean
     */
    protected $trackProtectedDownloads;

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
        // todo: check with settings
        if (false && !$this->securityContext->hasRole('Neos.Neos:Editor')) {
            throw new AccessDeniedException(
                sprintf('You are not allowed to access the file %s', $resource->getFilename()),
                1485716879
            );
        } elseif ($this->trackProtectedDownloads) {
            $downloadCount = new DownloadCount($resource, $this->securityContext->getAccount());
            $this->entityManager->persist($downloadCount);
            $this->entityManager->flush();
        }
    }

}
