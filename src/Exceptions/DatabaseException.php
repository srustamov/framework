<?php namespace TT\Exceptions;

class DatabaseException extends \RuntimeException
{
    public function __construct($message ='', $query = '')
    {
        parent::__construct(" $message <br />"."[QUERY: $query]");
    }
}
