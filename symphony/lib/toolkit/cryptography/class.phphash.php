<?php

/**
 * @package cryptography
 */
/**
 * PHPHash is a cryptography class for hashing and comparing messages
 * using the `PASSWORD_BCRYPT` algorithm.
 * This is one of the most advanced hashing algorithm PHP provides for passwords.
 * It also uses PHP's password_* functions
 *
 * @link http://php.net/manual/en/ref.password.php
 * @since Symphony 3.0.0
 * @see toolkit.Cryptography
 */
class PHPHash extends Cryptography
{
    /**
     * Cost factor length
     */
    const COST = 12;

    /**
     * The hash algorithm to be used
     */
    const ALGORITHM = PASSWORD_BCRYPT;

    /**
     * Uses PHP's `password_hash()` function to create a hash based on some input.
     *
     * @uses password_hash()
     * @link http://php.net/manual/en/function.password-hash.php
     * @param string $input
     *  the string to be hashed
     * @param integer $options.cost
     *  the bcrypt cost factor
     * @return string
     * the hashed string
     */
    public static function hash($input, array $options = [])
    {
        if (empty($options['cost'])) {
            $options['cost'] = self::COST;
        }

        return password_hash($input, self::ALGORITHM, $options);
    }

    /**
     * Compares a given hash with a clean text password or a hash.
     *
     * @uses hash_equals()
     * @uses password_verify()
     * @param string $input
     *  the clear text password
     * @param string $hash
     *  the hash the password should be checked against
     * @param bool $isHash
     *  if the $input is already a hash
     * @return boolean
     *  the result of the comparison
     */
    public static function compare($input, $hash, $isHash = false)
    {
        if ($isHash) {
            return hash_equals($hash, $input);
        }

        return password_verify($input, $hash);
    }

    /**
     * Checks if provided hash has been computed by most recent algorithm.
     *
     * @uses password_needs_rehash()
     * @param string $hash
     *  the hash to be checked
     * @return boolean
     *  whether the hash should be re-computed
     */
    public static function requiresMigration($hash)
    {
        return password_needs_rehash($hash, self::ALGORITHM, [
            'cost' => self::COST,
        ]);
    }
}
