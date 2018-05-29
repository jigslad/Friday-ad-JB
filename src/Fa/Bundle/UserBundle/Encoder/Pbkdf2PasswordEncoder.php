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
class Pbkdf2PasswordEncoder extends BasePasswordEncoder
{

    //const PBKDF2_ITERATIONS = 1000;
    //const PBKDF2_SALT_BYTE_SIZE = 24;
    //const PBKDF2_HASH_BYTE_SIZE = 24;

    const HASH_SECTIONS = 3;
    const HASH_ALGORITHM_INDEX = 0;
    const HASH_ITERATION_INDEX = 1;
    const HASH_SALT_INDEX = 2;
    const HASH_PBKDF2_INDEX = 3;


    private $algorithm;
    private $encodeHashAsBase64;
    private $iterations;
    private $length;

    /**
     * Constructor.
     *
     * @param string $algorithm          The digest algorithm to use
     * @param bool   $encodeHashAsBase64 Whether to base64 encode the password hash
     * @param int    $iterations         The number of iterations to use to stretch the password hash
     * @param int    $length             Length of derived key to create
     */
    public function __construct($algorithm = 'sha1', $encodeHashAsBase64 = true, $iterations = 1000, $length = 24)
    {
        $this->algorithm = $algorithm;
        $this->encodeHashAsBase64 = $encodeHashAsBase64;
        $this->iterations = $iterations;
        $this->length = $length;
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

        if (!in_array($this->algorithm, hash_algos(), true)) {
            throw new \LogicException(sprintf('The algorithm "%s" is not supported.', $this->algorithm));
        }

        if (!$salt) {
            $salt = mcrypt_create_iv($this->length, MCRYPT_DEV_URANDOM);
        }

        if (function_exists('hash_pbkdf2')) {
            $digest = hash_pbkdf2($this->algorithm, $raw, $salt, $this->iterations, $this->length, true);
        } else {
            $digest = $this->hashPbkdf2($this->algorithm, $raw, $salt, $this->iterations, $this->length);
        }

        $hash = $this->encodeHashAsBase64 ? base64_encode($digest) : bin2hex($digest);

        return $this->iterations.":".base64_encode($salt).":".$hash;
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordValid($encoded, $raw, $salt)
    {
        $params = explode(":", $encoded);
        if(count($params) < self::HASH_SECTIONS) {
            return false;
        }

        if (!$salt) {
            $salt = $params[1];
            $salt = base64_decode($salt);
        }

        return !$this->isPasswordTooLong($raw) && $this->comparePasswords($encoded, $this->encodePassword($raw, $salt));
    }

    private function hashPbkdf2($algorithm, $password, $salt, $iterations, $length = 0)
    {
        // Number of blocks needed to create the derived key
        $blocks = ceil($length / strlen(hash($algorithm, null, true)));
        $digest = '';

        for ($i = 1; $i <= $blocks; ++$i) {
            $ib = $block = hash_hmac($algorithm, $salt.pack('N', $i), $password, true);

            // Iterations
            for ($j = 1; $j < $iterations; ++$j) {
                $ib ^= ($block = hash_hmac($algorithm, $block, $password, true));
            }

            $digest .= $ib;
        }

        return substr($digest, 0, $this->length);
    }
}
