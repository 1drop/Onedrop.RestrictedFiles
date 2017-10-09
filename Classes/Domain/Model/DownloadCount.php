<?php
namespace Onedrop\RestrictedFiles\Domain\Model;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\Security\Account;
use Neos\Flow\Utility\Now;
use Neos\Media\Domain\Model\Asset;

/**
 * @Flow\Entity
 * @ORM\Table(
 * 	indexes={
 * 		@ORM\Index(name="resource",columns={"resource"})
 * 	}
 * )
 */
class DownloadCount
{
    /**
     * @var PersistentResource
     * @ORM\ManyToOne
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $resource;
    /**
     * @var \DateTime
     * @ORM\Column
     */
    protected $dateTime;
    /**
     * @var Account
     * @ORM\ManyToOne
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $account;

    /**
     * DownloadCount constructor.
     *
     * @param PersistentResource $resource
     * @param Account            $account
     */
    public function __construct(PersistentResource $resource, Account $account)
    {
        $this->dateTime = new Now();
        $this->resource = $resource;
        $this->account = $account;
    }

    /**
     * @return PersistentResource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param Asset $resource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return \DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * @param \DateTime $dateTime
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param Account $account
     */
    public function setAccount($account)
    {
        $this->account = $account;
    }
}
