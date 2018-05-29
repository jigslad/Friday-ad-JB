<?php
namespace Fa\Bundle\CoreBundle\Manager;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Fa\Bundle\CoreBundle\Manager\MessageManager
 *
 * This manager is used to handle various display messages.
 *
 * @author Hiren Chhatbar <hiren@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class MessageManager
{
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_NOTICE = 'notice';
    const TYPE_ERROR = 'error';

    protected $session;

    public function __construct(Session $session)
    {
        $this->session = $session;

        $this->session->start();
    }

    protected function getSession()
    {
        return $this->session;
    }

    protected function getMessageSentence($messageNo)
    {
        $sentences = array(
                0 => 'You don\'t have permission to access resource. Please contact administrator for further assistance.',
                1 => '%s was successfully saved.',
                2 => '%s was successfully added.',
                3 => '%s was successfully deleted.',
                4 => '%s not found.',
                5 => 'Some problem occurred with action you performed. Please contact administrator for further assitance.',
                6 => 'You can not delete parent with children menus. Please remove all children first and then try.',
                7 => 'Username and password do not match, please try again.',
        );

        return isset($sentences[$messageNo]) ? $sentences[$messageNo] : null;
    }

    public function setFlashMessage($message, $type = self::TYPE_SUCCESS)
    {
        $this->getSession()->getFlashBag()->add($type, $message);
    }

    public function setAccessDeniedMessage()
    {
        $this->setFlashMessage($this->getMessageSentence(0), self::TYPE_ERROR);
    }

    protected function setMessage($messageNo, $args = null, $type = self::TYPE_SUCCESS)
    {
        $message = $this->getMessageSentence($messageNo);

        if ($args) {
            $message = vsprintf($message, $args);
        }

        $this->setFlashMessage($message, $type);
    }

    public function setSaveMessage($args)
    {
        if (!is_array($args)) {
            $args = array($args);
        }

        $this->setMessage(1, $args);
    }

    public function setAddMessage($args)
    {
        if (!is_array($args)) {
            $args = array($args);
        }

        $this->setMessage(2, $args);
    }

    public function setDeleteMessage($args)
    {
        if (!is_array($args)) {
            $args = array($args);
        }

        $this->setMessage(3, $args);
    }

    public function setNotFoundMessage($args)
    {
        if (!is_array($args)) {
            $args = array($args);
        }

        $this->setMessage(4, $args, self::TYPE_ERROR);
    }

    public function setExceptionMessage()
    {
        $this->setFlashMessage($this->getMessageSentence(5), self::TYPE_ERROR);
    }

    public function setSuccessMessage($messageNo, $args = null, $type = self::TYPE_SUCCESS)
    {
        $this->setMessage($messageNo, $args, $type);
    }

    public function setErrorMessage($messageNo, $args = null, $type = self::TYPE_ERROR)
    {
        $this->setMessage($messageNo, $args, $type);
    }

    public function setNoticeMessage($messageNo, $args = null, $type = self::TYPE_NOTICE)
    {
        $this->setMessage($messageNo, $args, $type);
    }

    public function setInfoMessage($messageNo, $args = null, $type = self::TYPE_INFO)
    {
        $this->setMessage($messageNo, $args, $type);
    }
}
