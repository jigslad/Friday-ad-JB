<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Fa\Bundle\AdBundle\Entity\AdJobs
 *
 * This table is used to store ad other information.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="ad_jobs")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdJobsRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class AdJobs
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
     * @var \Fa\Bundle\AdBundle\Entity\Ad
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\Ad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $ad;

    /**
     * @var string
     *
     * @ORM\Column(name="contract_type_id", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $contract_type_id;

    /**
     * @var string
     *
     * @ORM\Column(name="trans_id", type="string", length=255, nullable=true)
     */
    private $trans_id;

    /**
     * @var string
     *
     * @ORM\Column(name="update_type", type="string", length=50, nullable=true)
     */
    private $update_type;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_data", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $meta_data;

    /**
     * @var integer
     *
     * @ORM\Column(name="salary_band_id", type="integer", nullable=true)
     * @Gedmo\Versioned
     */
    private $salary_band_id;

    /**
     * @var string
     *
     * @ORM\Column(name="feed_ad_salary", type="string", length=255, nullable=true)
     */
    private $feed_ad_salary;

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
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdAnimals
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * get ad
     *
     * @return \Fa\Bundle\AdBundle\Entity\Ad
     */
    public function getAd()
    {
        return $this->ad;
    }

    /**
     * set ad
     *
     * @param \Fa\Bundle\AdBundle\Entity\Ad $ad
     * @return \Fa\Bundle\AdBundle\Entity\AdAnimals
     */
    public function setAd($ad)
    {
        $this->ad = $ad;
        return $this;
    }

    /**
     * Set contract_type_id
     *
     * @param integer $contract_type_id
     * @return AdJobs
     */
    public function setContractTypeId($contract_type_id)
    {
        $this->contract_type_id = $contract_type_id;

        return $this;
    }

    /**
     * Get contract_type_id
     *
     * @return integer
     */
    public function getContractTypeId()
    {
        return $this->contract_type_id ;
    }

    /**
     * get meta data
     *
     * @return string
     */
    public function getMetaData()
    {
        return $this->meta_data;
    }

    /**
     * set meta data
     *
     * @param string $meta_data
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdAnimals
     */
    public function setMetaData($meta_data)
    {
        $this->meta_data = $meta_data;
        return $this;
    }

    /**
     * Check ad has home page featured.
     *
     * @param object $container Container instance.
     *
     * @return boolean
     */
    public function isJobOfWeekAd($container)
    {
        if ($container->get('doctrine')->getManager()->getRepository('FaAdBundle:AdUserPackageUpsell')->getAdPackageUpsellCountByIdAndType($this->getAd()->getId(), \Fa\Bundle\PromotionBundle\Repository\UpsellRepository::UPSELL_TYPE_JOB_LANDING_PAGE_JOB_OF_WEEK_ID)) {
            return true;
        }

        return false;
    }


    /**
     * Set salary_band_id.
     *
     * @param integer $salary_band_id
     * @return AdJobs
     */
    public function setSalaryBandId($salary_band_id)
    {
        $this->salary_band_id = $salary_band_id;

        return $this;
    }

    /**
     * Get salary_band_id.
     *
     * @return integer
     */
    public function getSalaryBandId()
    {
        return $this->salary_band_id;
    }

    /**
     * Set feed_ad_salary.
     *
     * @param string $feed_ad_salary
     * @return AdJobs
     */
    public function setFeedAdSalary($feed_ad_salary)
    {
        $this->feed_ad_salary = $feed_ad_salary;

        return $this;
    }

    /**
     * Get feed_ad_salary.
     *
     * @return string
     */
    public function getFeedAdSalary()
    {
        return $this->feed_ad_salary;
    }
}
