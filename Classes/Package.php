<?php
namespace Onedrop\RestrictedFiles;

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Annotations as Flow;
use Onedrop\RestrictedFiles\Slot\FileAccess;
use Wwwision\PrivateResources\Http\Component\ProtectedResourceComponent;

/**
 * Class Package
 * @package Onedrop\RestrictedFiles
 * @Flow\Scope("singleton")
 */
class Package extends BasePackage
{
    /**
     * @param  Bootstrap $bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect(
            ProtectedResourceComponent::class,
            'resourceServed',
            FileAccess::class,
            'checkTrackFileAccess'
        );
    }
}
