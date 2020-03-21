<?php namespace TT\Libraries\Database\Traits;

/**
 * @package    TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @subpackage    Library
 * @category    Database
 */
trait Join
{
    /**
     * @param String $table
     * @param $opt
     * @return Join
     */
    public function leftJoin(String $table, $opt): Join
    {
        return $this->join($table, $opt, 'LEFT');
    }

    /**
     * @param String $table
     * @param $opt
     * @return Join
     */
    public function innerJoin(String $table, $opt): Join
    {
        return $this->join($table, $opt);
    }

    /**
     * @param String $table
     * @param $opt
     * @param string $join
     * @return $this
     */
    public function join(String $table, $opt, $join = 'INNER'): self
    {
        $this->join[] = strtoupper($join) . ' JOIN ' . $this->config[$this->group]['prefix'] . $table . ' ON ' . $opt . ' ';
        return $this;
    }

    /**
     * @param String $table
     * @param $opt
     * @return Join
     */
    public function rightJoin(String $table, $opt): Join
    {
        return $this->join($table, $opt, 'RIGHT');
    }

    /**
     * @param String $table
     * @param $opt
     * @return Join
     */
    public function fullJoin(String $table, $opt): Join
    {
        return $this->join($table, $opt, 'FULL');
    }
}
