<?php

class EventModel extends BaseModel
{
    protected $table = 'events';

    function insert($organizer_id, $title, $description, $location, $start_date, $end_date, $banner, $status, $is_online = 0, $category = 'Seminar')
    {
        $stmt = $this->db->prepare(
            "INSERT INTO events (organizer_id, title, description, location, is_online, category, start_date, end_date, banner, status)
             VALUES (:organizer_id, :title, :description, :location, :is_online, :category, :start_date, :end_date, :banner, :status)"
        );
        return $stmt->execute([
            ':organizer_id' => $organizer_id,
            ':title'        => $title,
            ':description'  => $description,
            ':location'     => $location,
            ':is_online'    => $is_online,
            ':category'     => $category,
            ':start_date'   => $start_date,
            ':end_date'     => $end_date,
            ':banner'       => $banner,
            ':status'       => $status,
        ]);
    }

    function update($id, $title, $description, $location, $start_date, $end_date, $banner, $status, $is_online = 0, $category = 'Seminar')
    {
        $stmt = $this->db->prepare(
            "UPDATE events
             SET title=:title, description=:description, location=:location,
                 is_online=:is_online, category=:category,
                 start_date=:start_date, end_date=:end_date,
                 banner=:banner, status=:status
             WHERE id=:id"
        );
        return $stmt->execute([
            ':id'          => $id,
            ':title'       => $title,
            ':description' => $description,
            ':location'    => $location,
            ':is_online'   => $is_online,
            ':category'    => $category,
            ':start_date'  => $start_date,
            ':end_date'    => $end_date,
            ':banner'      => $banner,
            ':status'      => $status,
        ]);
    }

    function updateBanner($id, $banner)
    {
        $stmt = $this->db->prepare("UPDATE events SET banner=:banner WHERE id=:id");
        return $stmt->execute([':id' => $id, ':banner' => $banner]);
    }

    function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM events WHERE id=:id");
        return $stmt->execute([':id' => $id]);
    }

    function getAllWithOrganizer($limit = 10, $offset = 0)
    {
        $stmt = $this->db->prepare(
            "SELECT e.*, u.username AS organizer_name
             FROM events e JOIN users u ON e.organizer_id = u.id
             ORDER BY e.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    function getByOrganizer($organizer_id, $limit = 10, $offset = 0)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM events WHERE organizer_id=:organizer_id
             ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':organizer_id', $organizer_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit',        $limit,        PDO::PARAM_INT);
        $stmt->bindValue(':offset',       $offset,       PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    function countByOrganizer($organizer_id)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM events WHERE organizer_id=:organizer_id");
        $stmt->execute([':organizer_id' => $organizer_id]);
        return (int) $stmt->fetchColumn();
    }

    function getPublished($limit = 10, $offset = 0)
    {
        $stmt = $this->db->prepare(
            "SELECT e.*, u.username AS organizer_name
             FROM events e JOIN users u ON e.organizer_id = u.id
             WHERE e.status='published'
             ORDER BY e.start_date ASC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    function countPublished()
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM events WHERE status='published'");
        return (int) $stmt->fetchColumn();
    }

    function getByIdWithOrganizer($id)
    {
        $stmt = $this->db->prepare(
            "SELECT e.*, u.username AS organizer_name, u.email AS organizer_email
             FROM events e JOIN users u ON e.organizer_id = u.id
             WHERE e.id=:id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    function isOwnedBy($event_id, $organizer_id)
    {
        $stmt = $this->db->prepare("SELECT id FROM events WHERE id=:event_id AND organizer_id=:organizer_id");
        $stmt->execute([':event_id' => $event_id, ':organizer_id' => $organizer_id]);
        return (bool) $stmt->fetch();
    }

    function updateStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE events SET status=:status WHERE id=:id");
        return $stmt->execute([':id' => $id, ':status' => $status]);
    }

    function getTopEvents($limit = 3)
    {
        $stmt = $this->db->prepare(
            "SELECT e.*, u.username AS organizer_name,
                    COALESCE(SUM(tt.sold), 0) AS total_terjual
             FROM events e
             JOIN users u ON e.organizer_id = u.id
             LEFT JOIN ticket_types tt ON tt.event_id = e.id
             WHERE e.status = 'published'
             GROUP BY e.id
             ORDER BY total_terjual DESC, e.created_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    function getRelated($event_id, $category, $limit = 3)
    {
        $stmt = $this->db->prepare(
            "SELECT e.*, u.username AS organizer_name
             FROM events e JOIN users u ON e.organizer_id = u.id
             WHERE e.status = 'published' AND e.id != :event_id
             ORDER BY (e.category = :category) DESC, RAND()
             LIMIT :limit"
        );
        $stmt->bindValue(':event_id', $event_id, PDO::PARAM_INT);
        $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        $stmt->bindValue(':limit',    $limit,    PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    function getCategories()
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT category FROM events WHERE status='published' AND category != '' ORDER BY category ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    function getLocations()
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT location FROM events WHERE status='published' AND is_online=0 AND location != '' ORDER BY location ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    function filter($params = [], $limit = 12, $offset = 0)
    {
        $where  = ["e.status = 'published'"];
        $binds  = [];

        if (isset($params['is_online']) && $params['is_online'] !== '') {
            $where[]              = 'e.is_online = :is_online';
            $binds[':is_online']  = (int) $params['is_online'];
        }
        if (!empty($params['location'])) {
            $where[]              = 'e.location = :location';
            $binds[':location']   = $params['location'];
        }
        if (!empty($params['category'])) {
            $where[]              = 'e.category = :category';
            $binds[':category']   = $params['category'];
        }
        if (isset($params['gratis']) && $params['gratis'] === '1') {
            // Event gratis = tidak ada tipe tiket berbayar
            $where[] = "(SELECT MIN(tt.price) FROM ticket_types tt WHERE tt.event_id = e.id) = 0
                        OR (SELECT COUNT(*) FROM ticket_types tt WHERE tt.event_id = e.id) = 0";
        }
        if (!empty($params['q'])) {
            $where[]      = "(e.title LIKE :q OR e.description LIKE :q2)";
            $like         = '%' . $params['q'] . '%';
            $binds[':q']  = $like;
            $binds[':q2'] = $like;
        }

        $sql = "SELECT e.*, u.username AS organizer_name
                FROM events e JOIN users u ON e.organizer_id = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY e.start_date ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($binds as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    function countFilter($params = [])
    {
        $where = ["e.status = 'published'"];
        $binds = [];

        if (isset($params['is_online']) && $params['is_online'] !== '') {
            $where[]             = 'e.is_online = :is_online';
            $binds[':is_online'] = (int) $params['is_online'];
        }
        if (!empty($params['location'])) {
            $where[]             = 'e.location = :location';
            $binds[':location']  = $params['location'];
        }
        if (!empty($params['category'])) {
            $where[]             = 'e.category = :category';
            $binds[':category']  = $params['category'];
        }
        if (!empty($params['q'])) {
            $where[]      = "(e.title LIKE :q OR e.description LIKE :q2)";
            $like         = '%' . $params['q'] . '%';
            $binds[':q']  = $like;
            $binds[':q2'] = $like;
        }

        $sql  = "SELECT COUNT(*) FROM events e WHERE " . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);
        foreach ($binds as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    function search($keyword, $limit = 10, $offset = 0)
    {
        $like = '%' . $keyword . '%';
        $stmt = $this->db->prepare(
            "SELECT e.*, u.username AS organizer_name
             FROM events e JOIN users u ON e.organizer_id = u.id
             WHERE e.status='published' AND (e.title LIKE :keyword OR e.location LIKE :keyword2)
             ORDER BY e.start_date ASC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':keyword',  $like,   PDO::PARAM_STR);
        $stmt->bindValue(':keyword2', $like,   PDO::PARAM_STR);
        $stmt->bindValue(':limit',    $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset',   $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
