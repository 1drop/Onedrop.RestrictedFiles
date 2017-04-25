<?php

namespace Onedrop\RestrictedFiles\Domain\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\LoggerInterface;
use Neos\Flow\Persistence\Doctrine\Service as DoctrineService;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\Thumbnail;
use Neos\Media\Domain\Repository\ThumbnailRepository;

/**
 * @Flow\Scope("singleton")
 */
class AssetEventListener
{
    /** @var array */
    protected $resourcesToProtect = [];
    /** @var array */
    protected $settings;
    /**
     * @var ResourceManager
     * @Flow\Inject
     */
    protected $resourceManager;
    /**
     * @var PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;
    /**
     * @Flow\Inject
     * @var DoctrineService
     */
    protected $doctrineService;
    /**
     * @var LoggerInterface
     * @Flow\Inject
     */
    protected $logger;
    /**
     * @var ThumbnailRepository
     * @Flow\Inject
     */
    protected $thumbnailRepository;


    /**
     * @param array $settings
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Hook into the doctrine lifecycle before a new asset should be persisted
     *
     * @param LifecycleEventArgs $eventArgs
     *
     * @return void
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->checkEntityForResourcesToProtect($eventArgs);
    }

    /**
     * Hook into the doctrine lifecycle before an existing asset should be updated
     *
     * @param LifecycleEventArgs $eventArgs
     *
     * @return void
     */
    public function preUpdate(LifecycleEventArgs $eventArgs)
    {
        $this->checkEntityForResourcesToProtect($eventArgs);
    }

    /**
     * This hook will unpublish the resources that should be protected from the public
     * publishingTarget and changes the collectionName of the persistent resource in the database.
     * It reads from the local transient queue that has been filled by the pre-hooks.
     *
     * @param PostFlushEventArgs $eventArgs
     *
     * @return void
     */
    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        foreach ($this->resourcesToProtect as $resourceIdentifier => $resourceToProtect) {
            $this->protectResource($resourceToProtect, $resourceIdentifier);
        }
    }

    /**
     * Handle Asset or AssetCollection entities as those are the two entities carrying
     * the reference to an AssetCollection that is in the collectionNames that should be protected
     * in our settings.
     *
     * @param LifecycleEventArgs $eventArgs
     */
    protected function checkEntityForResourcesToProtect(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        if ($entity instanceof Asset) {
            $this->checkAssetHasProtectedCollection($entity);
        }
        if ($entity instanceof AssetCollection) {
            if (in_array($entity->getTitle(), $this->settings['collectionNames'])) {
                /** @var Asset $asset */
                foreach ($entity->getAssets() as $asset) {
                    if ($asset->getResource()->getCollectionName() !== $this->settings['protectedCollection']) {
                        $this->addAllResourcesFromAssetToProtectedQueue($asset);
                    }
                }
            }
        }
    }

    /**
     * As an asset can not only have its original resource but multiple thumbnail
     * variants, we protect the thumbnails as well.
     *
     * @param Asset $asset
     *
     * @return QueryResultInterface
     */
    protected function getAllThumbnailsForAsset(Asset $asset)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->thumbnailRepository->findByOriginalAsset($asset);
    }

    /**
     * Check if the given asset is assigned to a collection that is configured to be protected
     * and add all related resources of that asset to the queue.
     *
     * @param Asset $asset
     */
    protected function checkAssetHasProtectedCollection(Asset $asset)
    {
        $protected = false;
        if ($asset->getResource()->getCollectionName() === $this->settings['protectedCollection']) {
            return;
        }
        $collections = $asset->getAssetCollections();
        if (!$collections instanceof ArrayCollection) {
            return;
        }
        /** @var AssetCollection $collection */
        foreach ($collections as $collection) {
            if (in_array($collection->getTitle(), $this->settings['collectionNames'])) {
                $protected = true;
            }
        }
        if ($protected) {
            $this->addAllResourcesFromAssetToProtectedQueue($asset);
        }
    }

    /**
     * Unpublish the given resource from the default public target and change the collectionName
     * of that resource to the protected collection.
     * Therefore Flow will use the protectedPublishTarget to generate URLs for that resource.
     *
     * @param PersistentResource $resource
     * @param string             $resourceIdentifier
     */
    protected function protectResource(PersistentResource $resource, $resourceIdentifier)
    {
        $publicTarget = $this->resourceManager->getCollection(ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME)->getTarget();
        // The resource must be unpublished from the current target or it's still publicly accessible
        $publicTarget->unpublishResource($resource);
        // todo: check if resource must be moved to different storage, currently only local disk is supported
        $this->doctrineService->runDql(
            'UPDATE Neos\Flow\ResourceManagement\PersistentResource r ' .
            'SET r.collectionName = \'' . $this->settings['protectedCollection'] . '\'  ' .
            'WHERE r.Persistence_Object_Identifier = \'' . $resourceIdentifier . '\''
        );
    }

    /**
     * Add all related resources of the given asset to the protection queue.
     *
     * @param Asset $asset
     */
    protected function addAllResourcesFromAssetToProtectedQueue(Asset $asset)
    {
        $resource = $asset->getResource();
        $resourceIdentifier = $this->persistenceManager->getIdentifierByObject($resource);
        $this->resourcesToProtect[$resourceIdentifier] = $resource;
        /** @var Thumbnail $thumbnail */
        foreach ($this->getAllThumbnailsForAsset($asset) as $thumbnail) {
            $thumbResource = $thumbnail->getResource();
            if ($thumbResource instanceof PersistentResource) {
                $thumbResourceIdentifier = $this->persistenceManager->getIdentifierByObject($resource);
                $this->resourcesToProtect[$thumbResourceIdentifier] = $thumbResource;
            }
        }
    }


}
