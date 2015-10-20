<?php
namespace base;

class DbManager
{
    public $db;
    public function __construct()
    {
        $this->db = new \PDO('sqlite:' .__DIR__.'/../data/db.sqlite3');
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
}