<?php

namespace Onedrop\RestrictedFiles\Aspect;

use Doctrine\Common\Collections\Collection;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetCollection;

/**
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class ProtectedResourceAspect
{
    /** @var array */
    protected $settings;
    /**
     * @var ResourceManager
     * @Flow\Inject
     */
    protected $resourceManager;
    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param JoinPointInterface $joinPoint
     * @Flow\Before("method(Neos\Media\Browser\Controller\AssetController->uploadAction())")
     * @Flow\Before("method(Neos\Media\Browser\Controller\AssetController->createAction())")
     * @return void
     */
    public function makeResourceProtected(JoinPointInterface $joinPoint) {
        /** @var Asset $asset */
        $asset = $joinPoint->getMethodArgument('asset');
        if ($this->checkAssetCollectionProtected($asset->getAssetCollections())) {
            $publicTarget = $this->resourceManager->getCollection(ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME)->getTarget();
            $publicResource = $asset->getResource();
            $publicTarget->unpublishResource($publicResource);
            $localCopy = $publicResource->createTemporaryLocalCopy();
            $protectedResource = $this->resourceManager->importResource($localCopy, $this->settings['protectedCollection']);
            $asset->setResource($protectedResource);
            $this->resourceManager->deleteResource($publicResource);
        }
        $joinPoint->setMethodArgument('asset', $asset);
    }

    /**
     * @param Collection $collections
     * @return bool
     */
    protected function checkAssetCollectionProtected($collections)
    {
        $protected = false;
        /** @var AssetCollection $collection */
        foreach ($collections as $collection) {
            if (in_array($collection->getTitle(), $this->settings['collectionNames'])) {
                $protected = true;
            }
        }
        return $protected;
    }

}
