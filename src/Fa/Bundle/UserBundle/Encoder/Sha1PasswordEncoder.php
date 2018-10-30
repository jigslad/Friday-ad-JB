<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Encoder;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;

/**
 * Pbkdf2PasswordEncoder uses the PBKDF2 (Password-Based Key Derivation Function 2).
 *
 * Providing a high level of Cryptographic security,
 *  PBKDF2 is recommended by the National Institute of Standards and Technology (NIST).
 *
 * But also warrants a warning, using PBKDF2 (with a high number of iterations) slows down the process.
 * PBKDF2 should be used with caution and care.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 * @author Andrew Johnson
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Sha1PasswordEncoder extends BasePasswordEncoder
{
    const HASH_SECTIONS = 3;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     *
     * @throws \LogicException when the algorithm is not supported
     */
    public function encodePassword($raw, $salt)
    {
        if ($this->isPasswordTooLong($raw)) {
            throw new BadCredentialsException('Invalid password.');
        }

        if (!in_array('sha1', hash_algos(), true)) {
            throw new \LogicException(sprintf('The algorithm "%s" is not supported.', 'sha1'));
        }

        if (!$salt) {
            $salt = substr(base64_encode(md5(mcrypt_create_iv(40, MCRYPT_DEV_URANDOM))), 0, 5);
        }

        $hash = sha1(utf8_encode($salt.$raw));

        return 'sha1'.'$'.$salt.'$'.$hash;
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordValid($encoded, $raw, $salt)
    {
        $params = explode("$", $encoded);
        if (count($params) < self::HASH_SECTIONS) {
            return false;
        }

        if (!$salt) {
            $salt = $params[1];
        }

        return !$this->isPasswordTooLong($raw) && $this->comparePasswords($encoded, $this->encodePassword($raw, $salt));
    }
}
