<?php

namespace Fa\Bundle\AdFeedBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index as Index;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Fa\Bundle\AdFeedBundle\Entity\AdFeedSiteUser
 *
 * This table is used to store ad information.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="ad_feed_site_user")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdFeedBundle\Repository\AdFeedSiteUserRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AdFeedSiteUser
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Fa\Bundle\AdFeedBundle\Entity\AdFeedSite
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdFeedBundle\Entity\AdFeedSite")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_feed_site_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $ad_feed_site;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $user;

    /**
     * get id
     *
     * @return number
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * set id
     *
     * @param integer $id
     * @return \Fa\Bundle\AdFeedBundle\Entity\AdFeedSiteUser
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * get ad feed site
     *
     * @return \Fa\Bundle\AdFeedBundle\Entity\AdFeedSite
     */
    public function getAdFeedSite()
    {
        return $this->ad_feed_site;
    }

    /**
     * Set ad
     *
     * @param \Fa\Bundle\AdFeedBundle\Entity\AdFeedSite $ad
     * @return AdFeed
     */
    public function setAdFeedSite(\Fa\Bundle\AdFeedBundle\Entity\AdFeedSite $ad_feed_site = null)
    {
        $this->ad_feed_site = $ad_feed_site;

        return $this;
    }

    /**
     * get user
     *
     * @return \Fa\Bundle\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set user
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     * @return AdFeed
     */
    public function setUser(\Fa\Bundle\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }
}
