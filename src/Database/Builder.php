<?php

namespace TT\Database;

/**
 * @package    TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @subpackage    Library
 * @category    Builder
 */


use Closure;
use PDO;
use PDOException;
use PDOStatement;
use TT\Database\Orm\Model;
use TT\Exceptions\DatabaseException;

class Builder extends Connection
{
    use Traits\InsertDeleteUpdate;
    use Traits\Join;
    use Traits\Where;
    use Traits\Calculation;

    private $table;

    private $Builder;

    private $select = [];

    private $where = [];

    private $limit = [];

    private $orderBy = [];

    private $groupBy = [];

    private $join = [];

    private $bindValues = [];

    private $model;



    /**
     * @param Model $model
     * @return Builder
     */
    public function setModel(Model $model): Builder
    {
        $this->model = $model;

        return $this;
    }


    /**
     * @param string $sql
     * @param array $data
     * @return object|null
     */
    public function raw(string $sql, array $data = [])
    {
        if (empty($data)) {
            $stmt = $this->pdo->query($sql);
        } else {
            $stmt = $this->pdo->prepare($sql);

            $stmt->execute($data);
        }
        return ($stmt->rowCount() > 0) ? $stmt : null;
    }


    /**
     * @return object|null
     * @throws DatabaseException
     */

    public function first()
    {
        return $this->get(true);
    }

    /**
     * @param bool $first
     * @param null $fetch_style
     * @return object|array|null
     */
    public function get($first = false, $fetch_style = null)
    {
        $query = $this->getQueryString() . ((empty($this->limit) && $first) ? ' LIMIT 1' : '');

        $queryString = $this->normalizeQueryString($query);

        try {
            $statement = $this->pdo->prepare($query);
            $this->bindValues($statement);
            $statement->execute();
            $model = $this->model;
            $this->reset();
            if ($statement->rowCount() > 0) {
                if ($model) {
                    return $this->createModelCollectionData($statement, $model, $first);
                }
                return $first ? $statement->fetch($fetch_style) : $statement->fetchAll($fetch_style);
            }
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $queryString);
        }
    }

    /**
     * @param PDOStatement $statement
     * @param Model $model
     * @param bool $first
     * @return mixed
     */
    protected function createModelCollectionData(PDOStatement $statement, Model $model, $first = false)
    {
        if ($first) {
            $statement->setFetchMode(PDO::FETCH_INTO, $model);
            $result = $statement->fetch();
        } else {
            $statement->setFetchMode(PDO::FETCH_CLASS, get_class($model));
            $result = $statement->fetchAll();
        }

        if (!empty($model->eager)) {
            foreach ($model->eager as $method) {
                $result = $model->$method()->getResult($result, $method);
            }
        }

        return $result;
    }


    /**
     * @return string
     */
    public function getQueryString(): string
    {
        if (empty($this->select)) {
            $this->select[] = '*';
        }

        $query = 'SELECT ' . implode(',', $this->select) . ' FROM ' . $this->table . ' ';

        $query .= implode(
            ' ',
            array_merge(
                $this->join,
                $this->where,
                $this->orderBy,
                $this->groupBy,
                $this->limit
            )
        );

        return $query;
    }

    /**
     * @param $query
     * @return mixed
     */
    private function normalizeQueryString($query)
    {
        foreach ($this->bindValues as $value) {
            $position = strpos($query, '?');
            if ($position !== false) {
                $query = substr_replace($query, $value, $position, 1);
            }
        }
        return $query;
    }

    /**
     * @param PDOStatement $statement
     */
    public function bindValues(PDOStatement $statement): void
    {
        foreach ($this->bindValues as $key => $value) {
            $statement->bindValue(
                $key + 1,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }


    /**
     * @param Closure $callback
     * @return Builder
     */
    public function transaction(Closure $callback = null): Builder
    {
        $this->pdo->beginTransaction();

        if ($callback) {
            $callback($this);
        }

        return $this;
    }


    /**
     * @param String $table
     * @return $this
     */
    public function table(String $table): Builder
    {
        $this->table = $this->config[$this->group]['prefix'] . $table;

        return $this;
    }

    /**
     * @param String $Builder
     * @return $this
     */
    public function Builder(String $Builder): Builder
    {
        $this->Builder = $Builder;
        return $this;
    }

    /**
     * @param bool $first
     * @return array|object|null
     */
    public function toArray($first = false)
    {
        return $this->get($first, PDO::FETCH_ASSOC);
    }

    /**
     * @param bool $first
     * @return false|string|null
     */
    public function toJson($first = false)
    {
        if (($result = $this->toArray($first))) {
            return json_encode($result);
        }
        return null;
    }

    /**
     * @param $select
     * @return $this
     */
    public function select($select): Builder
    {
        if (is_array($select)) {
            $select = implode(',', $select);
        }

        $this->select = [$select];

        return $this;
    }

    /**
     * @param $limit
     * @param int $offset
     * @return Builder
     */
    public function limit($limit, $offset = 0): Builder
    {
        $this->limit[] = ' LIMIT ' . $offset . ',' . $limit;

        return $this;
    }

    /**
     * @param $column
     * @param string $sort
     * @return $this
     */
    public function orderBy($column, $sort = 'ASC'): Builder
    {
        $this->orderBy[] = ' ORDER BY ' . $column . ' ' . strtoupper($sort);

        return $this;
    }

    /**
     * @return Builder
     */
    public function orderByRand(): Builder
    {
        $this->orderBy[] = ' ORDER BY RAND() ';

        return $this;
    }

    /**
     * @param $column
     * @return $this
     */
    public function groupBy($column): Builder
    {
        $this->groupBy[] = ' GROUP BY ' . $column;
        return $this;
    }


    /**
     * @param $data
     * @return string
     */
    private function normalizeCrud($data): string
    {
        return implode(',', array_map(static function ($item) {
            return $item . '=?';
        }, array_keys($data)));
    }


    /**
     * @return array|bool
     */
    public function showTables()
    {
        $result = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($item) {
            return array_values($item)[0];
        }, $result);
    }


    /**
     * Truncate table
     * @return bool
     * @throws DatabaseException
     */
    public function truncate(): ?bool
    {
        $queryString = "TRUNCATE TABLE IF EXISTS {$this->table} ";

        try {
            return ($this->pdo->exec($queryString) === false);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $queryString);
        }
    }

    /**
     * @return null|object|array
     * @throws DatabaseException
     */
    public function tables()
    {
        $Builder = $this->Builder ?: $this->config[$this->group]['dbname'];

        $queryString = "SHOW TABLES FROM {$Builder}";

        try {
            $result = $this->pdo->query($queryString);

            if ($result->rowCount() > 0) {
                return $result->fetchAll();
            }
            return null;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $queryString);
        }
    }

    /**
     * @return array|bool
     * @throws DatabaseException
     */
    public function columns()
    {
        $queryString = "SHOW COLUMNS FROM {$this->table}";

        try {
            $result = $this->pdo->query($queryString);

            if ($result->rowCount() > 0) {
                return $result->fetchAll();
            }
            return null;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $queryString);
        }
    }


    /**
     * @return mixed
     */
    public function lastId()
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo->lastInsertId();
        }
        return null;
    }


    /**
     * @return $this
     */
    public function reset(): Builder
    {
        $this->bindValues = [];
        $this->select = [];
        $this->where = [];
        $this->limit = [];
        $this->orderBy = [];
        $this->groupBy = [];
        $this->join = [];
        $this->table = null;
        $this->Builder = null;
        $this->model = null;

        return $this;
    }

    /**
     * @param $method
     * @param $args
     * @return Builder
     */
    public function __call($method, $args)
    {
        if (stripos($method, 'where') === 0) {
            $column = substr($method, 5);
            return $this->where($column, $args[0] ?? false);
        }

        if (stripos($method, 'orderBy') === 0) {
            $order = substr($method, 7);
            return $this->orderBy($args[0] ?? false, strtoupper($order));
        }

        return $this->pdo->$method(...$args);
    }


    public function __clone()
    {
        $this->reset();
    }

    public function __destruct()
    {
        $this->connections = [];

        $this->pdo = null;
    }
}
