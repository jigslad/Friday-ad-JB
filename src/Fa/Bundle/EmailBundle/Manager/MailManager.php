<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EmailBundle\Manager;

use Fa\Bundle\EmailBundle\Entity\EmailTemplate;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\CoreBundle\Twig\CoreExtension;

/**
 * This class is used to send mail.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class MailManager
{
    /**
     * Mailer service class object.
     *
     * @var object
     */
    protected $mailer;

    /**
     * Entity Manager class object.
     *
     * @var object
     */
    protected $em;

    /**
     * Swift_Message class object.
     *
     * @var object
     */
    protected $message;

    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * History entity manager
     *
     * @var object
     */
    private $historyEntityManager;

    /**
     * Constructor.
     *
     * @param object $mailer
     * @param object $em
     * @param object $container
     */
    public function __construct(\Swift_Mailer $mailer, ContainerInterface $container)
    {
        $this->mailer = $mailer;

        $this->container = $container;

        $this->em = $this->container->get('doctrine')->getManager();

        $this->historyEntityManager = $this->container->get('doctrine')->getManager('history');
    }

    /**
     * Send mail.
     *
     * @param string $to
     * @param string $emailIdentifier
     * @param string $mailVars
     * @param string $locale
     * @param string $attachment
     * @param string $from
     * @param string $cc
     * @param string $bcc
     * @param string $sender
     * @param string $replyTo
     * @param number $priority
     *
     * @throws Exception
     */
    public function send($to, $emailIdentifier, $mailVars = array(), $locale = 'en_GB', $attachment = array(), $from = array(), $cc = array(), $bcc = array(), $sender = null, $replyTo = null, $priority = 1, $pixelTrack = 1)
    {
        $this->message = \Swift_Message::newInstance();
        $sendMailFlag = $this->container->hasParameter('fa.send.other.mails') ? $this->container->getParameter('fa.send.other.mails') : false;
        $defaultBcc =  $this->getEmailTemplate($emailIdentifier)->getBccEmails();
        $bcc = array();
        if($defaultBcc) {
            $defaultBcc = explode(',', $defaultBcc);
            if($to){
                if (($key = array_search($to, $defaultBcc)) !== false) {
                    unset($defaultBcc[$key]);
                }
            }
            $bcc = array_merge($bcc, $defaultBcc);
        }
        if ($to) {
            try {
                $trackId = $this->historyEntityManager->getRepository('FaReportBundle:AutomatedEmailReportLog')->updateEmailLog($emailIdentifier, $to);
                $date = strtotime(date('Y-m-d'));
                if($pixelTrack) {
                    $mailVars['pixel_track'] = '<img src="'.$this->container->get('router')->generate('pixel_track', array('gif' => CommonManager::encryptDecrypt('10101', $trackId)), true).'"></img>';
                } else {
                    $mailVars['pixel_track'] = '';
                }
                $this->setTo($to);
                $this->setFrom($from);
                $this->setCc($cc);
                if(!empty($bcc)) {
                    $this->setBcc($bcc);
                }

                $this->setSender($sender);
                $this->setReplyTo($replyTo);
                $this->setPriority($priority);
                $this->renderMail($emailIdentifier, $mailVars, $locale, $to);
                $this->setAttachment($attachment);
                if($sendMailFlag) {
                     $this->getMailer()->send($this->getMessage());
                } else {
                    if (preg_match("~\b@fridaymediagroup\.com\b~", $to)) {
                        $this->getMailer()->send($this->getMessage());
                    }
                }
            } catch (\Exception $e) {
                CommonManager::sendErrorMail($this->container, 'Error: Mail manager send', $e->getMessage(), $e->getTraceAsString());
            }
            //add to automated email report daily.
            try {
                $this->historyEntityManager->getRepository('FaReportBundle:AutomatedEmailReportDaily')->updateAutomatedEmailCounter($emailIdentifier);
                //$this->historyEntityManager->getRepository('FaReportBundle:AutomatedEmailReportDaily')->updateAutomatedEmailCounterInRedis($emailIdentifier, $this->container);
            } catch (\Exception $e) {
                //handle exception
            }
        }
    }

    /**
     * Set to.
     *
     * @param string $addresses
     * @param string $name
     */
    protected function setTo($addresses, $name = null)
    {
        $this->getMessage()->setTo($addresses, $name);
    }

    /**
     * Set from.
     *
     * @param string $addresses
     * @param string $name
     */
    protected function setFrom($addresses, $name = null)
    {
        $this->getMessage()->setFrom($addresses, $name);
    }

    /**
     * Set cc.
     *
     * @param unknown $addresses
     * @param string $name
     */
    protected function setCc($addresses, $name = null)
    {
        $this->getMessage()->setCc($addresses, $name);
    }

    /**
     * Set bcc.
     *
     * @param string $addresses
     * @param string $name
     */
    protected function setBcc($addresses, $name = null)
    {
        $this->getMessage()->setBcc($addresses, $name);
    }

    /**
     * Set sender.
     *
     * @param string $address
     * @param string $name
     */
    protected function setSender($address, $name = null)
    {
        $this->getMessage()->setSender($address, $name);
    }

    /**
     * Set reply to.
     *
     * @param string $addresses
     * @param string $name
     */
    protected function setReplyTo($addresses, $name = null)
    {
        $this->getMessage()->setReplyTo($addresses);
    }

    /**
     * Set priority.
     *
     * @param string $priority
     */
    protected function setPriority($priority)
    {
        $this->getMessage()->setPriority($priority);
    }

    /**
     * Get mailer.
     *
     * @return object
     */
    protected function getMailer()
    {
        return $this->mailer;
    }

    /**
     * Get message.
     *
     * @return object
     */
    protected function getMessage()
    {
        return $this->message;
    }

    /**
     * Set attachment.
     *
     * @param string $attachment
     */
    protected function setAttachment($attachment)
    {
        if (!empty($attachment)) {
            foreach ($attachment as $filename => $filepath) {
                $attachment = \Swift_Attachment::fromPath($filepath);

                if ($filename && !is_numeric($filename) && false !== strpos($filename, '.')) {
                    $attachment->setFilename($filename);
                }

                $this->getMessage()->attach($attachment);
            }
        }
    }

    /**
     * Set body.
     *
     * @param string $body
     */
    protected function setBody($body)
    {
        $this->getMessage()->setBody($body, 'text/html');
    }

    /**
     * Set alternate body.
     *
     * @param string $alternateBody
     */
    protected function setAlternateBody($alternateBody = null)
    {
        $this->getMessage()->addPart($alternateBody, 'text/plain');
    }

    /**
     * Set subject.
     *
     * @param string $subject
     */
    protected function setSubject($subject)
    {
        $this->getMessage()->setSubject($subject);
    }

    /**
     * Render mail.
     *
     * @param string $emailIdentifier
     * @param string $mailVars
     * @param string $locale
     * @param string $to
     *
     * @throws Exception
     */
    protected function renderMail($emailIdentifier, $mailVars, $locale = 'en_GB', $to)
    {
        try {
            //set layout parameters
            $mailVars['service']               = $this->container->getParameter('service_name');
            $mailVars['site_url']              = $this->container->getParameter('base_url');
            $mailVars['url_account_dashboard'] = $this->container->get('router')->generate('dashboard_home', array(), true);
            $mailVars['support_phone_number']  = '01646 689360';//support email address.
            $mailVars['business_support_phone']  = '01273 837846';//business email address.
            $mailVars['date_timestamp']  = date('d/m/Y').'T'.date('H:i:s');//For email banner
            $mailVars['user_email']  = $to;//For email banner
            $mailVars['template_name']  = $emailIdentifier;//For email banner

            //ex-TI user checking
            $coreExtObj = new CoreExtension($this->container);
            $mailVars['site_logo_url'] = 'http:'.$coreExtObj->asset_url('fafrontend/images/fad-logo-new.svg');
            $userObj = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('email' => $to));

            $emailTemplateLayoutHtml = '';
            $emailTemplateLayoutText = '';

            //get layout
            $emailTemplateLayout = $this->getEmailTemplate('email_template_layout', $locale);

            if ($emailTemplateLayout) {
                $emailTemplateLayoutHtml = $emailTemplateLayout->getBodyHtml();
                $emailTemplateLayoutText = $emailTemplateLayout->getBodyText();
            }
            //
            $emailTemplate          = $this->getEmailTemplate($emailIdentifier, $locale);
            $mailVars['email_body'] = $this->renderTwig($emailTemplate->getBodyHtml(), $mailVars);
            $mailVars['email_body'] = '<base href="'.$mailVars['site_url'].'" target="_blank">'.$mailVars['email_body'];
            
            if (!$this->getMessage()->getFrom()) {
                $this->setFrom($emailTemplate->getSenderEmail(), $emailTemplate->getSenderName());
            }

            $subject = $this->renderTwig($emailTemplate->getSubject(), $mailVars);
            $body    = $this->renderTwig($emailTemplateLayoutHtml, $mailVars);

            $this->setSubject($subject);
            $this->setBody($body);

            $mailVars['email_body'] = $this->renderTwig($emailTemplate->getBodyText(), $mailVars);

            $alternateBody          = $this->renderTwig($emailTemplateLayoutText, $mailVars);
            $this->setAlternateBody($alternateBody);
        } catch (\Exception $e) {
            CommonManager::sendErrorMail($this->container, 'Error: Mail manager render', $e->getMessage(), $e->getTraceAsString());
        }
    }

    /**
     * Get entity manager.
     *
     * @return object
     */
    protected function getEntityManager()
    {
        return $this->em;
    }

    /**
     * Get email template.
     *
     * @param string $emailIdentifier
     * @param string $locale
     *
     * @throws \Exception
     * @throws Exception
     *
     * @return string
     */
    protected function getEmailTemplate($emailIdentifier, $locale = 'en_GB')
    {
        try {
            $emailTemplate = $this->getEntityManager()->getRepository('Fa\Bundle\EmailBundle\Entity\EmailTemplate')->findOneByIdentifierAndLocale($emailIdentifier, $locale);

            if (!$emailTemplate) {
                throw new \Exception('Email template not found');
            }

            return $emailTemplate;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Render twig.
     *
     * @param string $text
     * @param string $vars
     *
     * @return string
     */
    protected function renderTwig($text, $vars)
    {
        /* $loader = new \Twig_Loader_Array(); // new \Twig_Loader_String(); symfony 3.4 this class is removed
        $twig   = new \Twig_Environment($loader);

        $template = $twig->loadTemplate($text); */
        
        $template = $this->container->get('twig')->createTemplate($text);

        return $template->render($vars);
    }
}
