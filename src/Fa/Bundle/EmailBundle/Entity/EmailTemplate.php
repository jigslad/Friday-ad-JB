<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Fa\Bundle\CoreBundle\Validator\Constraints\CustomEmail;

/**
 * Email template.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="email_template", indexes={@ORM\Index(name="fa_email_email_template_name_index", columns={"name"}), @ORM\Index(name="fa_email_email_template_subject_index", columns={"subject"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\EmailBundle\Repository\EmailTemplateRepository")
 * @Gedmo\TranslationEntity(class="Fa\Bundle\EmailBundle\Entity\EmailTemplateTranslation")
 * @UniqueEntity(fields="name", message="This email template name already exist in our database.")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class EmailTemplate
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="identifier", type="string", length=255, unique=true)
     * @Gedmo\Slug(fields={"name"}, updatable=false, separator="_")
     */
    private $identifier;

    /**
     * @var string
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="name", type="string", length=50)
     * @assert\NotBlank(message="Please enter email template name.")
     * @Gedmo\Versioned
     */
    private $name;

    /**
     * @var string
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="subject", type="string", length=100)
     * @assert\NotBlank(message="Please enter email subject.")
     * @Gedmo\Versioned
     */
    private $subject;

    /**
     * @var string
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="body_html", type="text")
     * @assert\NotBlank(message="Please enter email html body.")
     * @Gedmo\Versioned
     */
    private $body_html;

    /**
     * @var string
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="body_text", type="text")
     * @assert\NotBlank(message="Please enter email text body.")
     * @Gedmo\Versioned
     */
    private $body_text;

    /**
     * @var string
     *
     * @ORM\Column(name="variable", type="text", nullable=true)
     */
    private $variable;

    /**
     * @var string
     *
     * @ORM\Column(name="sender_email", type="string", length=255)
     * @assert\NotBlank(message="Please enter sender email.")
     * @CustomEmail(message="This email {{ value }} is not a valid email.")
     * @Gedmo\Versioned
     */
    private $sender_email;

    /**
     * @var string
     *
     * @ORM\Column(name="sender_name", type="string", length=50)
     * @assert\NotBlank(message="Please enter sender name.")
     * @Gedmo\Versioned
     */
    private $sender_name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     * @assert\NotBlank(message="Please select status.")
     * @Gedmo\Versioned
     */
    private $status;

    /**
    * @var integer
    *
    * @ORM\Column(name="created_at", type="integer", length=10)
    */
    private $created_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="updated_at", type="integer", length=10, nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\OneToMany(
     *   targetEntity="EmailTemplateTranslation",
     *   mappedBy="object",
     *   cascade={"persist", "remove"}
     * )
     */
    private $translations;

    /**
     * @var string
     *
     * @ORM\Column(name="params", type="text", nullable=true)
     */
    private $params;

    /**
     * @var string
     *
     * @ORM\Column(name="params_value", type="text", nullable=true)
     */
    private $params_value;

    /**
     * @var string
     *
     * @ORM\Column(name="params_help", type="text", nullable=true)
     */
    private $params_help;

    /**
     * @var boolean
     *
     * @ORM\Column(name="schedual", type="boolean", nullable=true, options={"default" = 0})
     */
    private $schedual;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="smallint", options={"default" = 0})
     * @Gedmo\Versioned
     */
    private $type = 0;

    /**
     * Construct.
     */
    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set updated at value.
     *
     * @ORM\PreUpdate()
     */
    public function setUpdatedAtValue()
    {
        $this->updated_at = time();
    }

    /**
     * Set id.
     *
     * @param integer $id
     *
     * @return EmailTemplate
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set identifier.
     *
     * @param string $identifier
     *
     * @return EmailTemplate
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return EmailTemplate
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set subject.
     *
     * @param string $subject
     *
     * @return EmailTemplate
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set body html.
     *
     * @param string $bodyHtml
     *
     * @return EmailTemplate
     */
    public function setBodyHtml($bodyHtml)
    {
        $this->body_html = $bodyHtml;

        return $this;
    }

    /**
     * Get body html.
     *
     * @return string
     */
    public function getBodyHtml()
    {
        return $this->body_html;
    }

    /**
     * Set body text.
     *
     * @param string $bodyText
     * @return EmailTemplate
     */
    public function setBodyText($bodyText)
    {
        $this->body_text = $bodyText;

        return $this;
    }

    /**
     * Get body text.
     *
     * @return string
     */
    public function getBodyText()
    {
        return $this->body_text;
    }

    /**
     * Set variable.
     *
     * @param string $variable
     *
     * @return EmailTemplate
     */
    public function setVariable($variable)
    {
        $this->variable = $variable;

        return $this;
    }

    /**
     * Get variable.
     *
     * @return string
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * Set sender email.
     *
     * @param string $senderEmail
     *
     * @return EmailTemplate
     */
    public function setSenderEmail($senderEmail)
    {
        $this->sender_email = $senderEmail;

        return $this;
    }

    /**
     * Get sender email.
     *
     * @return string
     */
    public function getSenderEmail()
    {
        return $this->sender_email;
    }

    /**
     * Set sender name.
     *
     * @param string $senderName
     *
     * @return EmailTemplate
     */
    public function setSenderName($senderName)
    {
        $this->sender_name = $senderName;

        return $this;
    }

    /**
     * Get sender name.
     *
     * @return string
     */
    public function getSenderName()
    {
        return $this->sender_name;
    }

    /**
     * Set status.
     *
     * @param boolean $status
     *
     * @return EmailTemplate
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set created at.
     *
     * @param integer $createdAt
     * @return Ad
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created at.
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updated at.
     *
     * @param integer $updatedAt
     *
     * @return Ad
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated at.
     *
     * @return integer
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Add translations.
     *
     * @param \Fa\Bundle\EmailBundle\Entity\EmailTemplateTranslation $translations
     *
     * @return EmailTemplate
     */
    public function addTranslation(\Fa\Bundle\EmailBundle\Entity\EmailTemplateTranslation $translations)
    {
        /*$this->translations[] = $translations;

        return $this;*/
        if (!$this->translations->contains($translations)) {
            $this->translations[] = $translations;
            $translations->setObject($this);
        }
    }

    /**
     * Remove translations.
     *
     * @param \Fa\Bundle\EmailBundle\Entity\EmailTemplateTranslation $translations
     */
    public function removeTranslation(\Fa\Bundle\EmailBundle\Entity\EmailTemplateTranslation $translations)
    {
        $this->translations->removeElement($translations);
    }

    /**
     * Get translations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * get email parameters
     *
     * @return string
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * set email parameters
     *
     * @param string $params
     *
     * @return \Fa\Bundle\EmailBundle\Entity\EmailTemplate
     */
    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * get parameter name
     *
     * @return string
     */
    public function getParamsValue()
    {
        return $this->params_value;
    }

    /**
     * set parameter values
     *
     * @param string $params_value
     *
     * @return \Fa\Bundle\EmailBundle\Entity\EmailTemplate
     */
    public function setParamsValue($params_value)
    {
        $this->params_value = $params_value;
        return $this;
    }

    /**
     * get parameter help
     *
     * @return string
     */
    public function getParamsHelp()
    {
        return $this->params_help;
    }

    /**
     * set parameter help
     *
     * @param string $params_help
     *
     * @return \Fa\Bundle\EmailBundle\Entity\EmailTemplate
     */
    public function setParamsHelp($params_help)
    {
        $this->params_help = $params_help;
        return $this;
    }

    /**
     * Get schedual.
     *
     * @return boolean
     */
    public function getSchedual()
    {
        return $this->schedual;
    }

    /**
     * Set schedual.
     *
     * @param string $schedual
     *
     * @return \Fa\Bundle\EmailBundle\Entity\EmailTemplate
     */
    public function setSchedual($schedual)
    {
        $this->schedual = $schedual;
        return $this;
    }

    /**
     * Set type.
     *
     * @param integer $type
     * @return EmailTemplate
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }
}
