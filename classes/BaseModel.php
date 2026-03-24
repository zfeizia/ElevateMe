<?php

abstract class BaseModel
{
    protected $db;

    protected $table = '';

    function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    function getAll($limit = 10, $offset = 0)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    function getById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    function delete($id)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE id = :id"
        );

        return $stmt->execute([':id' => $id]);
    }

    function countAll()
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");

        return (int) $stmt->fetchColumn();
    }

    function getLastInsertId()
    {
        return $this->db->lastInsertId();
    }
}
