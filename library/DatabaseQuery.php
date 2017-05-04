<?php

class DatabaseQuery {

    /**
     * PDOStatement
     *
     * @var \PDOStatement
     */
    protected $statement;

    public function __construct(\PDOStatement $statement){
        $this->statement = $statement;
    }

    public function fetchValue(){
        return $this->statement->fetchColumn(0);
    }

    public function fetchObject($classname = NULL, $args = NULL){
        return $this->statement->fetchObject($classname, $args);
    }

    public function fetch($fetchStyle = \PDO::FETCH_ASSOC){
        return $this->statement->fetch($fetchStyle);
    }

    public function fetchAll($fetchStyle = \PDO::FETCH_ASSOC){
        return $this->statement->fetchAll($fetchStyle);
    }

    public function rowCount(){
        return $this->statement->rowCount();
    }

}