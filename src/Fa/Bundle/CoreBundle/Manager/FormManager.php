<?php

namespace Fa\Bundle\CoreBundle\Manager;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\FormFactory;

/**
 * Fa\Bundle\CoreBundle\Manager\FormManager
 *
 * This manager is used to handle delete object for desktop and api.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class FormManager
{
    /**
     * where to use as api
     *
     * @var boolean
     */
    protected $isApi;

    /**
     * The request instance
     *
     * @var Request
     */
    protected $request;

    /**
     * The doctrine instance
     *
     * @var Doctrine
     */
    protected $doctrine;


    /**
     * The form factory instance
     *
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * Constructor.
     *
     * @param string $isApi
     */
    public function __construct($isApi = false)
    {
        $this->isApi = $isApi;
    }

    /**
     * This method is to create form.
     *
     * @param Object $type form type object
     * @param string $data string of data
     * @param array $options parameter array
     */
    public function createForm($type, $data = null, array $options = array())
    {
        return $this->formFactory->create($type, $data, $options);
    }

    /**
     * This method is used to check whether form is valid or not
     *
     * @param Object $form form object
     *
     * @return boolean
     */
    public function isValid($form)
    {
        $form->handleRequest($this->request);

        if ($form->isValid()) {
            return true;
        } elseif ($this->isApi) {
            // handle error handling
        }
        return false;
    }

    /**
     * This method is used do save object.
     *
     * @param Object $entity entity to delete
     *
     * @return Object
     */
    public function save($entity)
    {
        $em = $this->doctrine->getManager();
        $em->persist($entity);
        $em->flush($entity);
        return $entity;
    }

    /**
     * Debug the service.
     */
    public function debug()
    {
        echo "<br />Inside vendor debug<br />";
    }

    /**
     * Set form factory object.
     *
     * @param FormFactory $formFactory formFactory object
     */
    public function setFormFactory(FormFactory $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * Set request instance.
     *
     * @param RequestStack $requestStack RequestStack instance
     */
    public function setRequest(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * Set doctrine object.
     *
     * @param Object $doctrine doctrine object
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Get form error messages.
     *
     * @param Object $form form object
     * @param String $fieldNameOrLabel field name or label
     *
     * @return array
     */
    public function getFormSimpleErrors($form, $fieldNameOrLabel = 'name')
    {
        $errors = array();
        foreach ($form->all() as $name => $child) {
            $ei    = $child->getErrors(true);
            $error = array();

            for ($i = 0; $i < $ei->count(); $i++) {
                $fe      = $ei->current();
                $error[] = $fe->getMessage();
                $ei->next();
            }

            if (count($error)) {
                if ($fieldNameOrLabel == 'label') {
                    $name = $child->getConfig()->getOptions()['label'];
                }
                $errors[$name] = $error;
            }
        }

        return $errors;
    }
}
