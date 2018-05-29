<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EmailBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\EmailBundle\Entity\EmailTemplate;
use Fa\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Fa\Bundle\EmailBundle\Repository\EmailTemplateRepository;

/**
 * Load email template data.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LoadEmailTemplateData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * Container.
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Load data fixtures with the passed entity manager.
     *
     * @param Doctrine\Common\Persistence\ObjectManager $em
     */
    public function load(ObjectManager $em)
    {
        $translatable = $this->container->get('gedmo.listener.translatable');
        $translatable->setTranslatableLocale($this->container->getParameter('locale'));

        $bodyHtml = <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title>Friday Ad</title>
</head>
<body>
<table bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td align="center" valign="top">
      <table border="0" cellpadding="0" cellspacing="1" width="530" style="border:10px solid #e4e4e4; color:#000000;">
        <tr>
          <td style="background-color:#459ADB;font-size:12px; font-family: arial, tahoma, verdana; font-weight:normal;"><a style="padding:8px; display:block; font-size:20px; color:#ffffff;" href="{{ site_url }}" target="_blank">{{ service }}</a></td>
        </tr>
        <tr>
          <td valign="top" style="padding:15px;line-height:17px;font-size:12px; font-family: arial, tahoma, verdana; font-weight:normal;">
          {{ email_body|raw }}
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
EOD;

        // set email template
        $variableArray = array('{{ site_url }}', '{{ service }}', '{{ email_body|raw }}');
        $emailtemplate = new EmailTemplate();
        $emailtemplate->setName('Email Template Layout');
        $emailtemplate->setSubject('Email Template Layout');
        $emailtemplate->setBodyHtml($bodyHtml);
        $emailtemplate->setVariable(serialize($variableArray));
        $emailtemplate->setBodyText('{{ email_body }}');
        $emailtemplate->setSenderEmail('noreply@fridayad.com');
        $emailtemplate->setSenderName('Friday Ad');
        $emailtemplate->setStatus(1);
        $em->persist($emailtemplate);
        $em->flush();

        $bodyHtml = <<<EOD
<table border="0" cellpadding="0" cellspacing="1" width="530">
<tr>
  <td>Hello {{user_first_name}} {{user_last_name}},</td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr>
<td>Someone requested a password reset for your Friday-Ad.co.uk account {{user_email_address}}.</td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr>
<td>If this wasn't you, there's nothing to worry about - simply ignore this email and your password will remain unchanged. </td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr>
<td>
    If you DID ask to reset the password on your Friday-Ad.co.uk account, just click the button below.<br />
    <a href="{{ url_password_reset }}">Reset your password</a>
</td>
</tr>
<tr>
<td>Problems?</td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr><td>Please note that this link is only active for 3 hours after receipt. After this time, the link will expire and you will need to resubmit the password change request.</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>If you have problems, you can also copy and paste the URL into your browser:</td></tr>
 <tr><td>{{ url_password_reset }}</td></tr>
</td>
</tr>
</table>
EOD;

        $variableArray = array('{{ user_first_name }}', '{{ user_last_name }}', '{{ user_email_address }}', '{{ url_password_reset }}');
        $emailtemplate = new EmailTemplate();
        $emailtemplate->setName('Reset Password Link');
        $emailtemplate->setSubject('Reset your password');
        $emailtemplate->setBodyHtml($bodyHtml);
        $emailtemplate->setVariable(serialize($variableArray));
        $emailtemplate->setBodyText("Hello {{user_first_name}} {{user_last_name}},

Someone requested a password reset for your Friday-Ad.co.uk account {{user_email_address}}.

If this wasn't you, there's nothing to worry about - simply ignore this email and your password will remain unchanged.

If you DID ask to reset the password on your Friday-Ad.co.uk account, just click the button below.
Reset your password

Problems?

Please note that this link is only active for 3 hours after receipt. After this time, the link will expire and you will need to resubmit the password change request.

If you have problems, you can also copy and paste the URL into your browser:
{{url_password_reset}}");
        $emailtemplate->setSenderEmail('noreply@fridayad.com');
        $emailtemplate->setSenderName('Friday Ad');
        $emailtemplate->setStatus(1);
        $em->persist($emailtemplate);
        $em->flush();

        $bodyHtml = <<<EOD
<table border="0" cellpadding="0" cellspacing="1" width="530">
<tr>
  <td>Hello {{user_first_name}} {{user_last_name}},</td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr>
<td>You have registered on Friday-Ad.co.uk with email {{user_email_address}}. You can create your new password.</td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr>
<tr>
<td>
    <a href="{{ url_password_reset }}">Create password</a>
</td>
</tr>
<tr>
<td>Problems?</td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr><td>Please note that this link is only active for 3 hours after receipt. After this time, the link will expire and you will need to resubmit the password change request.</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>If you have problems, you can also copy and paste the URL into your browser:</td></tr>
 <tr><td>{{ url_password_reset }}</td></tr>
</td>
</tr>
</table>
EOD;

        $variableArray = array('{{ user_first_name }}', '{{ user_last_name }}', '{{ user_email_address }}', '{{ url_password_reset }}');
        $emailtemplate = new EmailTemplate();
        $emailtemplate->setName('Create Password Link');
        $emailtemplate->setSubject('Create password');
        $emailtemplate->setBodyHtml($bodyHtml);
        $emailtemplate->setVariable(serialize($variableArray));
        $emailtemplate->setBodyText("Hello {{user_first_name}} {{user_last_name}},

You have registered on Friday-Ad.co.uk with email {{user_email_address}}. You can create your new password.

Problems?

Please note that this link is only active for 3 hours after receipt. After this time, the link will expire and you will need to resubmit the password change request.

If you have problems, you can also copy and paste the URL into your browser:
{{url_password_reset}}");
        $emailtemplate->setSenderEmail('noreply@fridayad.com');
        $emailtemplate->setSenderName('Friday Ad');
        $emailtemplate->setStatus(1);
        $em->persist($emailtemplate);
        $em->flush();

        $bodyHtml = <<<EOD
<table border="0" cellpadding="0" cellspacing="1" width="530">
<tr>
  <td>Hello {{ customer_name }},</td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr>
<td>Your status has been changed by webmaster at Friday-Ad.co.uk account.</td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr>
<td>Previous Status : {{ previous_status }}</td>
</tr>
<tr>
<td>Current Status : {{ current_status }}</td>
</tr>
</table>
EOD;

        $variableArray = array('{{ customer_name }}', '{{ previous_status }}', '{{ current_status }}');
        $emailtemplate = new EmailTemplate();
        $emailtemplate->setName('User Status Change');
        $emailtemplate->setSubject('Notification of status change');
        $emailtemplate->setVariable(serialize($variableArray));
        $emailtemplate->setBodyHtml($bodyHtml);
        $emailtemplate->setBodyText("Hello {{customer_name}},

Your status has been changed by webmaster at Friday-Ad.co.uk account.

Previous Status : {{previous_status}}
Current Status : {{current_status}}");
        $emailtemplate->setSenderEmail('noreply@fridayad.com');
        $emailtemplate->setSenderName('Friday Ad');
        $emailtemplate->setStatus(1);
        $em->persist($emailtemplate);
        $em->flush();

        $file_handle = fopen(__DIR__."/emailTemplate.csv", "r");

        while (!feof($file_handle)) {
            $line_of_text = fgetcsv($file_handle, 1024);
            $variableArray = isset($line_of_text[2]) ? explode(',', $line_of_text[2]) : array();
            $emailtemplate = new EmailTemplate();
            $emailtemplate->setName($line_of_text[0]);
            $emailtemplate->setSubject($line_of_text[1]);
            $emailtemplate->setVariable(serialize($variableArray));
            $emailtemplate->setBodyHtml('HTML Body');
            $emailtemplate->setBodyText("Text Body");
            $emailtemplate->setSenderEmail('noreply@fridayad.com');
            $emailtemplate->setSenderName('Friday Ad');
            $emailtemplate->setStatus(1);
            $emailtemplate->setSchedual($line_of_text[3]);
            $emailtemplate->setParams($line_of_text[4]);
            $emailtemplate->setParamsValue($line_of_text[5]);
            $emailtemplate->setParamsHelp($line_of_text[6]);
            $emailtemplate->setType($line_of_text[7]);
            $em->persist($emailtemplate);
            $em->flush();

        }

        fclose($file_handle);
    }

    /**
     * Get order.
     *
     * @return integer
     */
    public function getOrder()
    {
        return 1; // the order in which fixtures will be loaded
    }
}
