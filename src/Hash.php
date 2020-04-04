<?php

namespace TT;

/**
 * @package    TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @subpackage    Library
 * @category    Hash
 */


use RuntimeException;

class Hash
{
    protected $round = 10;


    /**
     * @param $value
     * @param array $option
     * @return String
     */
    public function make($value, $option = []): String
    {
        $hash = password_hash($value, PASSWORD_BCRYPT, ['cost' => $this->cons($option)]);

        if ($hash === false) {
            throw new RuntimeException('Bcrypt hashing not supported.');
        }

        return $hash;
    }


    /**
     * @param $value
     * @param $hash
     * @return bool
     */
    public function check($value, $hash): Bool
    {
        if ($hash === '') {
            return false;
        }
        return password_verify($value, $hash);
    }


    /**
     * @param $round
     * @return Hash
     */
    public function round($round): Hash
    {
        $this->round = $round;
        return $this;
    }


    /**
     * @param $option
     * @return Int
     */
    protected function cons($option): Int
    {
        return $option['round'] ?? $this->round;
    }
}
