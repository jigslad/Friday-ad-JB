<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index as Index;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Fa\Bundle\AdBundle\Entity\AdArchive
 *
 * This table is used to store ad information.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="ad_main", indexes={@ORM\Index(name="fa_ad_main_trans_id",  columns={"trans_id"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdMainRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AdMain
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set trans_id
     *
     * @param string $trans_id
     * @return Ad
     */
    public function setTransId($trans_id)
    {
        $this->trans_id = $trans_id;

        return $this;
    }

    /**
     * Get trans id
     *
     * @return string
     */
    public function getTransId()
    {
        return $this->trans_id;
    }

    /**
     * set update_type
     *
     * @return string
     */
    public function setUpdateType($update_type)
    {
        return $this->update_type = $update_type;
    }

    /**
    * Get update_type
    *
    * @return string
    */
    public function getUpdateType()
    {
        return $this->update_type;
    }
}
