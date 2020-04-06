<?php

namespace TT\Session\Drivers;

use RuntimeException;
use SessionHandlerInterface;
use TT\Database\Builder;

class SessionDatabaseHandler implements SessionHandlerInterface
{
    private $table;

    private $db;


    public function __construct(Builder $db, $config)
    {
        $this->db = $db;

        if(!isset($config['table'])) {
            throw new RuntimeException(sprintf(
                '[%s] required config [table] name',
                static::class
            ));
        }

        $this->table = $config['table'];
    }

    public function register()
    {
        session_set_save_handler(
            array($this, "open"),
            array($this, "close"),
            array($this, "read"),
            array($this, "write"),
            array($this, "destroy"),
            array($this, "gc")
        );
    }



    public function open($save_path, $name): Bool
    {
        return true;
    }



    public function read($id): String
    {
        $result = $this->db->pdo()->query("SELECT data FROM {$this->table} WHERE session_id='{$id}'AND expires > " . time() . "");

        if ($result->rowCount() > 0) {
            return $result->fetch()->data;
        }
        return "";
    }



    public function write($id, $data): Bool
    {
        $time    = time() + (int) ini_get('session.gc_maxlifetime');

        $result  = $this->db->pdo()->query("REPLACE INTO {$this->table} SET session_id ='{$id}',expires = {$time},data ='{$data}'");

        return $result ? true : false;
    }


    public function close(): Bool
    {
        return $this->gc((int) ini_get('session.gc_maxlifetime'));
    }



    public function destroy($id): Bool
    {
        try {
            $this->db->pdo()->query("DELETE FROM {$this->table} WHERE session_id = '{$id}'");
        } catch (\PDOException $e) {
        }

        return  true;
    }



    public function gc($maxlifetime): Bool
    {
        $this->db->pdo()->query("DELETE FROM {$this->table} WHERE expires < " . (time() + $maxlifetime));

        return true;
    }
}
