<?php

namespace App;

class QueryBuilder {

    private $fields = ['*'];
    private $from;
    private $where;
    private $params = [];
    private $order = [];
    private $limit;
    private $offset;

    public function from (string $table, string $alias = null): self 
    {
        $this->from = $alias == null ? $table : "$table $alias";
        return $this;
    }

    public function orderBy (string $field, string $direction = null): self 
    {
        $direction = strtoupper($direction);
        $this->order[] = in_array($direction, ["ASC", "DESC"]) ? "$field $direction" : $field;
    
        return $this;
    }

    public function limit (int $limit = 0) : self 
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset (int $offset) : self 
    {
        $this->offset = $offset;
        return $this;
    }

    public function page (int $page = null) : self 
    {
        $this->offset = $this->limit * ($page-1);
        return $this;
    }

    public function where (string $where): self 
    {
        $this->where = $where;
        return $this;
    }

    public function setParam (string $key, string $value): self 
    {
        $this->params[$key] = $value;
        return $this;
    }

    public function select (...$fields): self 
    {
        if (is_array($fields[0])) $fields = $fields[0];
        
        if ($this->fields === ['*']) $this->fields = $fields;
        else $this->fields = array_merge($this->fields, $fields);

        return $this;
    }

    public function fetch(PDO $pdo, string $field): string 
    {
        $query = $pdo->prepare($this->toSQL());
        $query->execute($this->params);
        return $query->fetch()[$field];
    }

    public function count(PDO $pdo): int 
    {
        $query = clone $this;
        return (int)$query->select('COUNT(id) count')->fetch($pdo, count);
        
        $query = $pdo->prepare($this->toSQL());
        $query->execute($this->params);
        $result = $query->fetchAll();
        return count($result);
    }

    public function toSQL () : string
    {
        $fields = implode(', ', $this->fields);
        $sql = "SELECT $fields FROM {$this->from}";

        if ($this->where) {
            $sql .= " WHERE {$this->where}";
        }

        if (!empty($this->order)) 
        {
            $sql .= " ORDER BY " . implode(', ', $this->order);
        }

        if ($this->limit > 0) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }
}
