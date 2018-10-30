<?php

namespace Fa\Bundle\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Entity\LogEntry;
use Doctrine\ORM\Mapping\Index as Index;

/**
 * Gedmo\Loggable\Entity\LogEntry
 *
 * @ORM\Table(
 *     name="fa_entity_log",
 *  indexes={
 *      @Index(name="log_class_lookup_idx", columns={"object_class"}),
 *      @Index(name="log_date_lookup_idx", columns={"logged_at"}),
 *      @Index(name="log_user_lookup_idx", columns={"username"}),
 *      @Index(name="log_md5_lookup_idx", columns={"md5"}),
 *      @Index(name="log_status_lookup_idx", columns={"status"}),
 *      @Index(name="log_version_lookup_idx", columns={"object_id", "object_class", "version"})
 *  }
 * )
 * @ORM\Entity(repositoryClass="Fa\Bundle\EntityBundle\Repository\FaEntityLogRepository")
 * @ORM\EntityListeners({ "Fa\Bundle\EntityBundle\Listener\FaEntityLogListener" })
 */
class FaEntityLog extends \Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry
{
    /**
     * All required columns are mapped through inherited superclass
     */
    /**
     * @var string $loggedAt
     *
     * @ORM\Column(name="logged_at", type="integer")
     */
    protected $loggedAt;

    /**
     * @var string $md5
     *
     * @ORM\Column(name="md5", type="string", nullable=true)
     */
    protected $md5;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     */
    private $status = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="creation_ip", type="string", length=100, nullable=true)
     */
    private $creation_ip;

    /**
     * Set loggedAt to "now"
     */
    public function setLoggedAt()
    {
        $this->loggedAt = time();
    }

    /**
     * Set md5.
     *
     * @param string $md5
     * @return FaEntityLog
     */
    public function setMd5($md5)
    {
        $this->md5 = $md5;

        return $this;
    }

    /**
     * Get md5.
     *
     * @return string
     */
    public function getMd5()
    {
        return $this->md5;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return Package
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set creation_ip
     *
     * @param string $creationIp
     * @return Ad
     */
    public function setCreationIp($creationIp)
    {
        $this->creation_ip = $creationIp;

        return $this;
    }

    /**
     * Get creation_ip
     *
     * @return string
     */
    public function getCreationIp()
    {
        return $this->creation_ip;
    }
}
