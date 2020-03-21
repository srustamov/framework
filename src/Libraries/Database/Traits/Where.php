<?php namespace TT\Libraries\Database\Traits;

/**
 * @package    TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @subpackage    Library
 * @category    Database
 */

use InvalidArgumentException;

trait Where
{

    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>','is not null',
        'rlike', 'not rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to','is null',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    protected $defaultWhereOperator = '=';

    /**
     * @param $operator
     * @param $value
     * @return array
     */
    protected function getOperatorAndValue($operator, $value): array
    {
        if ($operator && !$value) {
            $value = $operator;
            $operator = $this->defaultWhereOperator;
        }
        if (!$operator && $value) {
            $operator = $this->defaultWhereOperator;
        }
        if (
            (!$operator && !$value) ||
            !in_array(strtolower(trim($operator)), $this->operators, true)
        ) {
            throw new InvalidArgumentException('Illegal operator and value combination.');
        }

        return [$operator, $value];
    }

    /**
     * @param $column
     * @param null $operator
     * @param null $value
     * @param array $logic
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $logic = ['WHERE', 'AND']): self
    {

        if (is_array($column)) {
            foreach ($column as $columnName => $columnValue) {
                $this->where($columnName, $operator, $columnValue, $logic);
            }
            return $this;
        }

        [$operator, $value] = $this->getOperatorAndValue($operator, $value);

        $where = empty($this->where) ? $logic[0] : $logic[1];

        $this->where[] = ' ' . $where . ' ' . $column .' '. $operator . ' ? ';

        $this->bindValues[] = $value;

        return $this;

    }


    /**
     * @param $column
     * @param null $operator
     * @param bool $value
     * @return Where
     */
    public function orWhere($column, $operator = null, $value = null): self
    {
        return $this->where($column, $operator, $value, ['WHERE', 'OR']);
    }


    /**
     * @param $column
     * @param null $operator
     * @param bool $value
     * @return Where
     */
    public function notWhere($column, $operator = null, $value = null): self
    {
        return $this->where($column, $operator, $value, ['WHERE NOT', 'AND NOT']);
    }

    /**
     * @param $column
     * @param null $operator
     * @param null $value
     * @return Where
     */
    public function orNotWhere($column, $operator = null, $value = null): self
    {
        return $this->where($column, $operator, $value, ['WHERE NOT', 'OR NOT']);
    }

    /**
     * @param $column
     * @param $in
     * @return Where
     */
    public function whereNotIn($column, $in): self
    {
        return $this->whereIn($column, $in, 'NOT');
    }

    /**
     * @param $column
     * @param $in
     * @param string $logic
     * @return $this
     */
    public function whereIn($column, $in, $logic = ''): self
    {
        $in = is_array($in) ? $in : explode(',', $in);

        $this->where[] = (empty($this->where) ? 'WHERE ' : ' AND ') . $column . " {$logic} IN(" . rtrim(str_repeat('?,', count($in)), ',') . ")";

        $this->bindValues = array_merge($this->bindValues, $in);

        return $this;
    }

    /**
     * @param $column
     * @return Where
     */
    public function orWhereNull($column): self
    {
        return $this->whereNull($column, 'OR');
    }

    /**
     * @param $column
     * @param string $logic
     * @return $this
     */
    public function whereNull($column, $logic = 'AND'): self
    {
        $this->where[] = (!empty($this->where) ? $logic : 'WHERE') . " {$column} IS NULL ";
        return $this;
    }

    /**
     * @param $column
     * @return Where
     */
    public function orWhereNotNull($column): self
    {
        return $this->WhereNotNull($column, 'OR');
    }

    /**
     * @param $column
     * @param string $logic
     * @return $this
     */
    public function whereNotNull($column, $logic = 'AND'): self
    {
        $this->where[] = (!empty($this->where) ? $logic : 'WHERE') . " {$column} IS NOT NULL ";
        return $this;
    }

    /**
     * @param $where
     * @param $start
     * @param $stop
     * @param string $mark
     * @return $this
     */
    public function between($where, $start, $stop, $mark = 'AND'): self
    {
        $this->where[] = empty($this->where) ? 'WHERE ' : 'AND ' . $where . " BETWEEN ? {$mark} ? ";

        $this->bindValues = array_merge($this->bindValues, [$start, $stop]);

        return $this;
    }


    /**
     * @param $column
     * @param $value
     * @return Where
     */
    public function notLike($column, $value): self
    {
        return $this->like($column, $value, 'NOT');
    }


    /**
     * @param $column
     * @param $value
     * @return Where
     */
    public function orNotLike($column, $value): self
    {
        return $this->like($column,$value,'OR NOT');
    }

    /**
     * @param $column
     * @param $value
     * @param string $logic
     * @return $this
     */
    public function like($column, $value, $logic = ''): self
    {
        return $this->where($column,$logic.' LIKE',$value);
    }
}