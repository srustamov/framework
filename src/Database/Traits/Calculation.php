<?php

namespace TT\Database\Traits;

/**
 * @package    TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @subpackage    Library
 * @category    Database
 */


trait Calculation
{
    /**
     * @param string $column
     * @return int|null
     */
    public function count(string $column = null): ?int
    {
        $column = $column ?? implode('', $this->select);

        $this->select = array("COUNT({$column}) as count");

        if ($result = $this->get(true)) {
            return (int) $result->count;
        }

        return null;
    }

    /**
     * @param string $column
     * @return int|null
     */
    public function min(string $column): ?int
    {
        $as_name = 'min_' . $column;
        $this->select = array("MIN({$column}) as {$as_name}");

        if ($result = $this->get(true)) {
            return (int) $result->$as_name;
        }
        return null;
    }

    /**
     * @param string $column
     * @return int|null
     */
    public function max(string $column): ?int
    {
        $as_name = 'min_' . $column;

        $this->select = array("MAX({$column}) as {$as_name}");

        if ($result = $this->get(true)) {
            return (int) $result->$as_name;
        }
        return null;
    }

    /**
     * @param string $column
     * @return mixed
     */
    public function avg(string $column = null)
    {
        $column = $column ?? implode('', $this->select);

        $this->select = array("AVG({$column}) as avg");

        if ($result = $this->get(true)) {
            return $result->avg;
        }
        return null;
    }

    /**
     * @param string|null $column
     * @return mixed
     */
    public function sum(string $column = null)
    {
        $column = $column ?? implode('', $this->select);

        $this->select = array("SUM({$column}) as sum");

        if ($result = $this->get(true)) {
            return $result->sum;
        }
        return null;
    }
}
