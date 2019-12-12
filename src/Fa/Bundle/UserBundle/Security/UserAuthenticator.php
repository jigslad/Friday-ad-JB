<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\SimpleFormAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\UserBundle\Encoder\Sha1PasswordEncoder;

/**
 * This is used as user authenticator.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserAuthenticator implements SimpleFormAuthenticatorInterface, AuthenticationSuccessHandlerInterface
{
    /**
     * EncoderFactoryInterface.
     *
     * @var object.
     */
    private $encoderFactory;

    /**
     * Container.
     *
     * @var object
     */
    private $container;

    /**
     * Translator.
     *
     * @var object
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param EncoderFactoryInterface $encoderFactory Object.
     * @param Container               $container      Object.
     */
    public function __construct(EncoderFactoryInterface $encoderFactory, Container $container)
    {
        $this->encoderFactory = $encoderFactory;
        $this->container      = $container;
        $this->translator     = $this->container->get('translator');
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Security\Core\Authentication\SimpleAuthenticatorInterface::authenticateToken()
     *
     * @param TokenInterface        $token         Token object.
     * @param UserProviderInterface $userProvider  Object.
     * @param string                $providerKey   Provider key.
     *
     * @throws AuthenticationException
     * @return UsernamePasswordToken object.
     */
    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        try {
            $user = $userProvider->loadUserByUsername($token->getUsername());
        } catch (UsernameNotFoundException $e) {
            throw new AuthenticationException($this->translator->trans('Invalid username or password.', array(), 'validators'));
        }

        $encoder = $this->encoderFactory->getEncoder($user);
        $passwordValid = $encoder->isPasswordValid(
            $user->getPassword(),
            $token->getCredentials(),
            $user->getSalt()
        );

        // Check for sha1 for older FAD account
        if (!$passwordValid) {
            $encoder = new Sha1PasswordEncoder();
            $passwordValid = $encoder->isPasswordValid($user->getPassword(), $token->getCredentials(), $user->getSalt());
        }

        if ($passwordValid) {
            if (($user && !$user->getStatus()) || ($user && $user->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID)) {
                throw new AuthenticationException($this->translator->trans('Your status is not active.', array(), 'messages'));
            }

            $userRolesArray = array();
            foreach ($user->getRoles() as $userRole) {
                $userRolesArray[] = $userRole->getName();
            }
            
            if(empty($userRolesArray)) {
                $userRolesArray[] = $user->getRole()->getName();
            }                
                
            $roleToCheck = array();
            if ($this->container->get('request_stack')->getCurrentRequest()->get('_route') == 'admin_login_check') {
                $roleToCheck = $this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:Role')->getRoleArrayByType('A');
            } else {
                $roleToCheck = $this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:Role')->getRoleArrayByType('C');
            }

            if (empty(array_intersect($roleToCheck, $userRolesArray))) {
                throw new AuthenticationException($this->translator->trans('You do not have enough credential to login.', array(), 'messages'));
            }

            //set flash message only if no save search agent flag is there
            /*if (!$this->container->get('request_stack')->getCurrentRequest()->cookies->has('save_search_agent_flag') && !$this->container->get('request_stack')->getCurrentRequest()->cookies->has('contact_seller_flag') && !$this->container->get('request_stack')->getCurrentRequest()->cookies->has('add_testimonial_flag')) {
                $this->container->get('session')->getFlashBag()->add('success', $this->translator->trans('You have successfully logged in.', array(), 'frontend'));
            }*/
            //to skip paa login step
            $this->container->get('session')->set('paa_skip_login_step', true);

            return new UsernamePasswordToken(
                $user,
                $user->getPassword(),
                $providerKey,
                $user->getRoles()
            );
        }

        throw new AuthenticationException($this->translator->trans('Invalid username or password.', array(), 'validators'));
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Security\Core\Authentication\SimpleAuthenticatorInterface::supportsToken()
     *
     * @param TokenInterface $token       Token object.
     * @param string         $providerKey Provider key.
     *
     * @return UsernamePasswordToken object.
     */
    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof UsernamePasswordToken
            && $token->getProviderKey() === $providerKey;
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Security\Core\Authentication\SimpleFormAuthenticatorInterface::createToken()
     *
     * @param Request $request     A Request object.
     * @param string  $username    Username.
     * @param string  $password    Password.
     * @param string  $providerKey Provider key.
     *
     * @return UsernamePasswordToken object.
     */
    public function createToken(Request $request, $username, $password, $providerKey)
    {
        return new UsernamePasswordToken($username, $password, $providerKey);
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface::onAuthenticationSuccess()
     *
     * @param Request        $request A Request object.
     * @param TokenInterface $token   Object.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $this->container->get('session')->set('extend_session', true);
        if ($this->container->get('request_stack')->getCurrentRequest()->cookies->has('redirect_path_info') && $this->container->get('request_stack')->getCurrentRequest()->get('_route') == 'admin_login_check') {
            $response = new RedirectResponse($this->container->get('request_stack')->getCurrentRequest()->cookies->get('redirect_path_info'));
            $response->headers->removeCookie('redirect_path_info');
        } else {
            if ($this->container->get('request_stack')->getCurrentRequest()->get('_route') == 'admin_login_check') {
                $response = new RedirectResponse($this->container->get('router')->generate('fa_admin_homepage'));
            } else {
                $response = new RedirectResponse($this->container->get('router')->generate('fa_frontend_homepage'));
            }
        }
        return $response;
    }
}
