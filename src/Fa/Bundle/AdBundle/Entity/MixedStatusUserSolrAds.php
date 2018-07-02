<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index as Index;

/**
 * Fa\Bundle\AdBundle\Entity\MixedStatusUserSolrAds
 *
 * This table is used to store Mixed status of user solr adverts.
 *
 * @author Vijay <vijay.namburi@fridaymediafroup.com>
 * @copyright  2018 Friday Media Group Ltd.
 *
 * @ORM\Table(name="mixed_status_user_solr_ads", indexes={@ORM\Index(name="fam_ad_id_index", columns={"ad_id"}), @ORM\Index(name="fam_user_id_index", columns={"user_id"}) })
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\MixedStatusUserSolrAdsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class MixedStatusUserSolrAds
{
    const ACTION_NOT_TAKEN = 0;
    const ACTION_TAKEN = 1;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="ad_id", type="integer")
     */
    private $adId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="user_status", type="integer")
     */
    private $userStatus;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="ad_status", type="integer")
     */
    private $adStatus;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer")
     */
    private $createdAt;
    
    /**
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->createdAt = time();
    }

    /**
     * Get id
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     * @param integer $id
     * @return InActiveUserSolrAds
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    
    /**
     * Set adId
     * @param integer $adId
     * @return InActiveUserSolrAds
     */
    public function setAdId($adId)
    {
        $this->adId = $adId;
        return $this;
    }
    
    /**
     * Get adId
     * @return integer
     */
    public function getAdId()
    {
        return $this->adId;
    }
    
    /**
     * Set userId
     *
     * @param integer $userId
     * @return InActiveUserSolrAds
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }
    
    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }
    
    /**
     * Set userStatus
     *
     * @param integer $userStatus
     * @return InActiveUserSolrAds
     */
    public function setUserStatus($userStatus)
    {
        $this->userStatus = $userStatus;
        return $this;
    }
    
    /**
     * Get userStatus
     *
     * @return integer
     */
    public function getUserStatus()
    {
        return $this->userStatus;
    }
    
    /**
     * Set adStatus
     *
     * @param integer $adStatus
     * @return InActiveUserSolrAds
     */
    public function setAdStatus($adStatus)
    {
        $this->adStatus = $adStatus;
        return $this;
    }
    
    /**
     * Get adStatus
     *
     * @return integer
     */
    public function getAdStatus()
    {
        return $this->adStatus;
    }
    
    /**
     * Set status
     *
     * @param integer $status
     * @return InActiveUserSolrAds
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
    
    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set createdAt
     *
     * @param integer $createdAt
     * @return InActiveUserSolrAds
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        
        return $this;
    }
    
    /**
     * Get createdAt
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
}
