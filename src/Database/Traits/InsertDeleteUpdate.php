<?php

namespace TT\Database\Traits;

/**
 * @package    TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @subpackage    Library
 * @category    Database
 */

use TT\Arr;
use TT\Exceptions\DatabaseException;

trait InsertDeleteUpdate
{

    /**
     * @param $insert
     * @param array $data
     * @param bool $getId
     * @return mixed
     */
    public function insert($insert, array $data = [], Bool $getId = false)
    {
        if (is_string($insert)) {
            $query = $insert;
            $this->bindValues = $data;
        } else if (is_array($insert) && Arr::isAssoc($insert)) {
            $query = "INSERT INTO {$this->table} SET " . $this->normalizeCrud($insert);
            $this->bindValues = array_values($insert);
        } else {
            throw new DatabaseException('Insert method variables not correct');
        }

        $queryString = $this->normalizeQueryString($query);

        try {
            $statement = $this->pdo->prepare($query);

            $this->bindValues($statement);

            $statement->execute();

            $this->reset();

            if($statement->rowCount() > 0) {
                return $getId ? $this->pdo->lastInsertId() : true;
            }

            return false;
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $queryString);
        }
    }


    /**
     * @param $insert
     * @param array $data
     * @return mixed
     */
    public function insertGetId($insert, array $data = [])
    {
        return $this->insert($insert, $data, true);
    }


    /**
     * @param $update
     * @param array $data
     * @return bool|null
     */
    public function update($update, array $data = []): ?bool
    {
        if (is_string($update)) {
            $query = $update;

            $this->bindValues = $data;
        } else if (is_array($update) && Arr::isAssoc($update)) {
            $query = "UPDATE {$this->table} SET " . $this->normalizeCrud($update);
            $query .= ' ' . preg_replace("/^SELECT.*FROM {$this->table}/", '', $this->getQueryString(), 1);

            $this->bindValues = array_merge(array_values($update), $this->bindValues);
        } else {
            throw new DatabaseException('Update method variables not correct');
        }

        $queryString = $this->normalizeQueryString($query);

        try {
            $statement = $this->pdo->prepare($query);

            $this->bindValues($statement);

            $statement->execute();

            $this->reset();

            return $statement->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $queryString);
        }
    }

    /**
     * @param null $delete
     * @param array $data
     * @return bool|null
     */
    public function delete($delete = null, array $data = []): ?bool
    {
        if (is_string($delete)) {
            $query = $delete;

            $this->bindValues = $data;
        } else if (is_array($delete)) {
            if (Arr::isAssoc($delete)) {
                $this->where($delete);
            }
        } else {
            $query = "DELETE FROM {$this->table} " .
                preg_replace(
                    "/SELECT.*FROM {$this->table}/",
                    '',
                    $this->getQueryString(),
                    1
                );
        }

        $queryString = $this->normalizeQueryString($query);

        try {
            $statement = $this->pdo->prepare($query);

            $this->bindValues($statement);

            $statement->execute();

            $this->reset();

            return $statement->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $queryString);
        }
    }
}
