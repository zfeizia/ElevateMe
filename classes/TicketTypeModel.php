<?php

class TicketTypeModel extends BaseModel
{
    protected $table = 'ticket_types';

    function insert($event_id, $name, $price, $quota, $description, $early_bird_price, $early_bird_deadline)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO ticket_types
                (event_id, name, price, quota, description, early_bird_price, early_bird_deadline)
             VALUES
                (:event_id, :name, :price, :quota, :description, :early_bird_price, :early_bird_deadline)"
        );

        return $stmt->execute([
            ':event_id'            => $event_id,
            ':name'                => $name,
            ':price'               => $price,
            ':quota'               => $quota,
            ':description'         => $description,
            ':early_bird_price'    => $early_bird_price    ?: null,
            ':early_bird_deadline' => $early_bird_deadline ?: null,
        ]);
    }

    function update($id, $name, $price, $quota, $description, $early_bird_price, $early_bird_deadline)
    {
        $stmt = $this->db->prepare(
            "UPDATE ticket_types
             SET name = :name, price = :price, quota = :quota,
                 description = :description,
                 early_bird_price = :early_bird_price,
                 early_bird_deadline = :early_bird_deadline
             WHERE id = :id"
        );

        return $stmt->execute([
            ':id'                  => $id,
            ':name'                => $name,
            ':price'               => $price,
            ':quota'               => $quota,
            ':description'         => $description,
            ':early_bird_price'    => $early_bird_price    ?: null,
            ':early_bird_deadline' => $early_bird_deadline ?: null,
        ]);
    }

    function getByEvent($event_id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM ticket_types WHERE event_id = :event_id ORDER BY price ASC"
        );
        $stmt->execute([':event_id' => $event_id]);

        return $stmt->fetchAll();
    }

    function getCurrentPrice($ticket_type)
    {
        // Jika early bird tidak diset, kembalikan harga normal
        if (empty($ticket_type['early_bird_price']) || empty($ticket_type['early_bird_deadline'])) {
            return $ticket_type['price'];
        }

        $now      = new DateTime();
        $deadline = new DateTime($ticket_type['early_bird_deadline']);

        // Jika sekarang sebelum deadline, pakai harga early bird
        if ($now < $deadline) {
            return $ticket_type['early_bird_price'];
        }

        return $ticket_type['price'];
    }

    function isEarlyBirdActive($ticket_type)
    {
        if (empty($ticket_type['early_bird_price']) || empty($ticket_type['early_bird_deadline'])) {
            return false;
        }

        $now      = new DateTime();
        $deadline = new DateTime($ticket_type['early_bird_deadline']);

        return $now < $deadline;
    }

    function isAvailable($ticket_type_id)
    {
        $stmt = $this->db->prepare(
            "SELECT (quota - sold) AS sisa FROM ticket_types WHERE id = :id"
        );
        $stmt->execute([':id' => $ticket_type_id]);
        $row = $stmt->fetch();

        return $row && $row['sisa'] > 0;
    }

    function incrementSold($ticket_type_id)
    {
        $stmt = $this->db->prepare(
            "UPDATE ticket_types SET sold = sold + 1 WHERE id = :id AND sold < quota"
        );

        return $stmt->execute([':id' => $ticket_type_id]);
    }

    function decrementSold($ticket_type_id)
    {
        $stmt = $this->db->prepare(
            "UPDATE ticket_types SET sold = GREATEST(sold - 1, 0) WHERE id = :id"
        );

        return $stmt->execute([':id' => $ticket_type_id]);
    }

    function getAttendanceReport($event_id)
    {
        $stmt = $this->db->prepare(
            "SELECT
                tt.id,
                tt.name          AS tipe_tiket,
                tt.quota,
                tt.sold          AS total_terjual,
                COUNT(CASE WHEN t.status = 'checked_in' THEN 1 END) AS total_checkin,
                ROUND(
                    CASE
                        WHEN tt.sold = 0 THEN 0
                        ELSE COUNT(CASE WHEN t.status = 'checked_in' THEN 1 END) / tt.sold * 100
                    END, 1
                ) AS persen_hadir
             FROM ticket_types tt
             LEFT JOIN tickets t ON tt.id = t.ticket_type_id AND t.status != 'cancelled'
             WHERE tt.event_id = :event_id
             GROUP BY tt.id"
        );
        $stmt->execute([':event_id' => $event_id]);

        return $stmt->fetchAll();
    }

    function getRevenueBreakdown($event_id)
    {
        $stmt = $this->db->prepare(
            "SELECT
                tt.name AS tipe_tiket,
                tt.sold,
                tt.price,
                (tt.sold * tt.price) AS total_revenue
             FROM ticket_types tt
             WHERE tt.event_id = :event_id AND tt.sold > 0"
        );
        $stmt->execute([':event_id' => $event_id]);

        return $stmt->fetchAll();
    }
}
