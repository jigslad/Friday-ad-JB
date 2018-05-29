<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fa\Bundle\AdBundle\Entity\AdModerate
 *
 * This table is used to store ad image information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="ad_moderate")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdModerateRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AdModerate
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
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private $value;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Entity
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Entity")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="moderation_result_id", type="integer", length=2, nullable=true)
     */
    private $moderation_result_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="moderation_result", type="string", length=100, nullable=true)
     */
    private $moderation_result;

    /**
     * @var integer
     *
     * @ORM\Column(name="moderation_response", type="text", nullable=true)
     */
    private $moderation_response;

    /**
     * @var integer
     *
     * @ORM\Column(name="moderation_queue", type="smallint", options={"default" = 0})
     */
    private $moderation_queue;

    /**
     * @var \Fa\Bundle\AdBundle\Entity\Ad
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\Ad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $ad;

    /**
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
     * Set createdAt
     *
     * @param integer $createdAt
     *
     * @return AdModerate
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return AdModerate
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set status
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Entity $status
     *
     * @return AdModerate
     */
    public function setStatus(\Fa\Bundle\EntityBundle\Entity\Entity $status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Entity
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set ad
     *
     * @param \Fa\Bundle\AdBundle\Entity\Ad $ad
     *
     * @return AdModerate
     */
    public function setAd(\Fa\Bundle\AdBundle\Entity\Ad $ad = null)
    {
        $this->ad = $ad;

        return $this;
    }

    /**
     * Get ad
     *
     * @return \Fa\Bundle\AdBundle\Entity\Ad
     */
    public function getAd()
    {
        return $this->ad;
    }

    /**
     * Set moderationResultId
     *
     * @param integer $moderationResultId
     *
     * @return AdModerate
     */
    public function setModerationResultId($moderationResultId)
    {
        $this->moderation_result_id = $moderationResultId;

        return $this;
    }

    /**
     * Get moderationResultId
     *
     * @return integer
     */
    public function getModerationResultId()
    {
        return $this->moderation_result_id;
    }

    /**
     * Set moderationResult
     *
     * @param string $moderationResult
     *
     * @return AdModerate
     */
    public function setModerationResult($moderationResult)
    {
        $this->moderation_result = $moderationResult;

        return $this;
    }

    /**
     * Get moderationResult
     *
     * @return string
     */
    public function getModerationResult()
    {
        return $this->moderation_result;
    }

    /**
     * Set moderationResponse
     *
     * @param string $moderationResponse
     *
     * @return AdModerate
     */
    public function setModerationResponse($moderationResponse)
    {
        $this->moderation_response = $moderationResponse;

        return $this;
    }

    /**
     * Get moderationResponse
     *
     * @return string
     */
    public function getModerationResponse()
    {
        return $this->moderation_response;
    }

    /**
     * Set moderationQueue
     *
     * @param integer $moderationQueue
     *
     * @return AdModerate
     */
    public function setModerationQueue($moderationQueue)
    {
        $this->moderation_queue = $moderationQueue;

        return $this;
    }

    /**
     * Get moderationQueue
     *
     * @return integer
     */
    public function getModerationQueue()
    {
        return $this->moderation_queue;
    }
}
