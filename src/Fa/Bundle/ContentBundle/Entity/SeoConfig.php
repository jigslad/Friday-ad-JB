<?php

namespace Fa\Bundle\ContentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store information about static page.
 *
 * @author Nikhil Baby <nikhil.baby2000@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="seo_config")
 * @ORM\Entity(repositoryClass="Fa\Bundle\ContentBundle\Repository\SeoConfigRepository")
 * @ORM\HasLifecycleCallbacks
 */
class SeoConfig
{
    /**
     *
     * @var integer @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     *
     * @var string @ORM\Column(name="type", type="string", length=100)
     */
    private $type;

    /**
     *
     * @var string @ORM\Column(name="data", type="json_array")
     */
    private $data;

    /**
     *
     * @var boolean @ORM\Column(name="status", type="boolean", options={"default" = 1})
     */
    private $status;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Get id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return json_decode($this->data, true, 512);
    }

    /**
     * @param array $data
     */
    public function setData($data = [])
    {
        $this->data = json_encode($data);
    }

    /**
     * @return bool
     */
    public function getStatus()
    {
        return boolval($this->status);
    }

    /**
     * @param bool $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
}
