<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * This table is used to store search agent email sent details.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="user_search_agent_email_ad")
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\UserSearchAgentEmailAdRepository")
 * @ORM\HasLifecycleCallbacks
 */
class UserSearchAgentEmailAd
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
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $user;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\UserSearchAgent
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\UserSearchAgent")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="search_agent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $search_agent;

    /**
     * @var text
     *
     * @ORM\Column(name="ad_ids", type="text")
     */
    private $ad_ids;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    /**
     * Set created at value.
     *
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ad_ids
     *
     * @param string $ad_ids
     * @return UserSearchAgentEmailAd
     */
    public function setAdIds($ad_ids)
    {
        $this->ad_ids = $ad_ids;

        return $this;
    }

    /**
     * Get ad_ids
     *
     * @return string
     */
    public function getAdIds()
    {
        return $this->ad_ids;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return UserSearchAgent
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created_at
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set user
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     * @return UserSearchAgent
     */
    public function setUser(\Fa\Bundle\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
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
     * @param \Fa\Bundle\UserBundle\Entity\UserSearchAgent $userSearchAgent
     * @return UserSearchAgent
     */
    public function setSearchAgent(\Fa\Bundle\UserBundle\Entity\UserSearchAgent $userSearchAgent = null)
    {
        $this->search_agent = $userSearchAgent;

        return $this;
    }

    /**
     * Get search_agent
     *
     * @return \Fa\Bundle\UserBundle\Entity\UserSearchAgent
     */
    public function getSearchAgent()
    {
        return $this->search_agent;
    }
}
