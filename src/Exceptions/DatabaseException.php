<?php namespace TT\Exceptions;

class DatabaseException extends \RuntimeException
{
    public function __construct($message ='', $query = null)
    {
        if($query) {
            $message .= !CONSOLE
                        ? '\n SQL : <b style="color: #97310e">'.$query.'</b>'
                        : '<br/> SQL :[ $query ]';
        }
        parent::__construct($message);
    }
}
