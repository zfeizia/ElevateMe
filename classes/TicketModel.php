<?php

class TicketModel extends BaseModel
{
    protected $table = 'tickets';

    function generateTicketCode($event_title)
    {
        // Ambil 4 huruf pertama dari judul event, hilangkan spasi, uppercase
        $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $event_title));
        $prefix = substr($prefix, 0, 4);
        $prefix = str_pad($prefix, 4, 'X'); // Padding jika judul < 4 karakter

        // Loop sampai dapat kode yang benar-benar unik di database
        do {
            $chars      = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $random_part = '';

            for ($i = 0; $i < 6; $i++) {
                $random_part .= $chars[random_int(0, strlen($chars) - 1)];
            }

            $kode = 'EVT-' . $prefix . '-' . $random_part;

            // Cek apakah kode ini sudah ada di database
            $stmt = $this->db->prepare(
                "SELECT id FROM tickets WHERE ticket_code = :kode LIMIT 1"
            );
            $stmt->execute([':kode' => $kode]);

        } while ($stmt->fetch()); // Ulangi jika kode sudah ada (sangat jarang terjadi)

        return $kode;
    }

    function purchaseTicket($ticket_type_id, $buyer_id, $event_title)
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "SELECT * FROM ticket_types WHERE id = :id FOR UPDATE"
            );
            $stmt->execute([':id' => $ticket_type_id]);
            $tipe = $stmt->fetch();

            if (!$tipe) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Tipe tiket tidak ditemukan.'];
            }

            if ($tipe['sold'] >= $tipe['quota']) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Maaf, tiket sudah habis.', 'quota_full' => true];
            }

            // Generate kode tiket unik
            $ticket_code = $this->generateTicketCode($event_title);

            // Insert tiket baru
            $stmt = $this->db->prepare(
                "INSERT INTO tickets (ticket_type_id, buyer_id, ticket_code)
                 VALUES (:ticket_type_id, :buyer_id, :ticket_code)"
            );
            $stmt->execute([
                ':ticket_type_id' => $ticket_type_id,
                ':buyer_id'       => $buyer_id,
                ':ticket_code'    => $ticket_code,
            ]);

            // Update sold count di ticket_types
            $stmt = $this->db->prepare(
                "UPDATE ticket_types SET sold = sold + 1 WHERE id = :id"
            );
            $stmt->execute([':id' => $ticket_type_id]);

            $this->db->commit();

            return ['success' => true, 'ticket_code' => $ticket_code, 'message' => 'Tiket berhasil dibeli!'];

        } catch (PDOException $e) {
            // Ada error — batalkan semua perubahan
            $this->db->rollBack();

            return ['success' => false, 'message' => 'Transaksi gagal: ' . $e->getMessage()];
        }
    }

    function checkIn($ticket_code)
    {
        // Ambil tiket beserta detail event dan buyer
        $stmt = $this->db->prepare(
            "SELECT
                t.*,
                tt.name        AS tipe_tiket,
                tt.price,
                e.title        AS judul_event,
                e.start_date,
                e.location,
                u.username     AS nama_pembeli,
                u.email        AS email_pembeli
             FROM tickets t
             JOIN ticket_types tt ON t.ticket_type_id = tt.id
             JOIN events e        ON tt.event_id = e.id
             JOIN users u         ON t.buyer_id = u.id
             WHERE t.ticket_code = :ticket_code"
        );
        $stmt->execute([':ticket_code' => $ticket_code]);
        $tiket = $stmt->fetch();

        if (!$tiket) {
            return ['success' => false, 'message' => 'Kode tiket tidak ditemukan.'];
        }

        if ($tiket['status'] === 'cancelled') {
            return ['success' => false, 'message' => 'Tiket ini sudah dibatalkan.', 'tiket' => $tiket];
        }

        if ($tiket['status'] === 'checked_in') {
            return [
                'success' => false,
                'message' => 'Tiket ini sudah di-check-in pada ' . $tiket['checked_in_at'],
                'tiket'   => $tiket,
            ];
        }

        // Update status tiket jadi checked_in + catat waktu check-in
        $stmt = $this->db->prepare(
            "UPDATE tickets
             SET status = 'checked_in', checked_in_at = NOW()
             WHERE ticket_code = :ticket_code"
        );
        $stmt->execute([':ticket_code' => $ticket_code]);

        // Ambil ulang data tiket yang sudah diupdate
        $stmt = $this->db->prepare(
            "SELECT t.*, tt.name AS tipe_tiket, e.title AS judul_event, u.username AS nama_pembeli
             FROM tickets t
             JOIN ticket_types tt ON t.ticket_type_id = tt.id
             JOIN events e        ON tt.event_id = e.id
             JOIN users u         ON t.buyer_id = u.id
             WHERE t.ticket_code = :ticket_code"
        );
        $stmt->execute([':ticket_code' => $ticket_code]);

        return [
            'success' => true,
            'message' => 'Check-in berhasil! Selamat datang.',
            'tiket'   => $stmt->fetch(),
        ];
    }

    function getByBuyer($buyer_id)
    {
        $stmt = $this->db->prepare(
            "SELECT
                t.*,
                tt.name        AS tipe_tiket,
                tt.price,
                e.title        AS judul_event,
                e.start_date,
                e.end_date,
                e.location,
                e.status       AS status_event
             FROM tickets t
             JOIN ticket_types tt ON t.ticket_type_id = tt.id
             JOIN events e        ON tt.event_id = e.id
             WHERE t.buyer_id = :buyer_id
             ORDER BY t.purchase_date DESC"
        );
        $stmt->execute([':buyer_id' => $buyer_id]);

        return $stmt->fetchAll();
    }

    function getByTicketCode($ticket_code)
    {
        $stmt = $this->db->prepare(
            "SELECT
                t.*,
                tt.name        AS tipe_tiket,
                tt.price,
                e.id           AS event_id,
                e.title        AS judul_event,
                e.start_date,
                e.end_date,
                e.location,
                u.username     AS nama_pembeli,
                u.email        AS email_pembeli
             FROM tickets t
             JOIN ticket_types tt ON t.ticket_type_id = tt.id
             JOIN events e        ON tt.event_id = e.id
             JOIN users u         ON t.buyer_id = u.id
             WHERE t.ticket_code = :ticket_code"
        );
        $stmt->execute([':ticket_code' => $ticket_code]);

        return $stmt->fetch();
    }

    function generateAsciiQR($ticket_code)
    {
        // Buat hash dari kode tiket sebagai seed
        $hash    = md5($ticket_code);
        $chars   = str_split($hash);
        $size    = 10;

        $html  = '<table class="qr-table">';
        $html .= '<tr><td colspan="' . ($size + 2) . '" class="qr-border-h"></td></tr>';

        for ($i = 0; $i < $size; $i++) {
            $html .= '<tr>';
            $html .= '<td class="qr-border-v"></td>'; 

            for ($j = 0; $j < $size; $j++) {
                // Ambil karakter hex, konversi ke int, cek genap/ganjil untuk warna
                $hex_val = hexdec($chars[($i * $size + $j) % 32]);
                $is_dark = ($hex_val % 2 === 0);

                // Pojok selalu gelap (seperti finder pattern pada QR asli)
                $is_corner = (
                    ($i < 2 && $j < 2) ||
                    ($i < 2 && $j >= $size - 2) ||
                    ($i >= $size - 2 && $j < 2)
                );

                $css_class = ($is_dark || $is_corner) ? 'qr-dark' : 'qr-light';
                $html .= '<td class="' . $css_class . '"></td>';
            }

            $html .= '<td class="qr-border-v"></td>'; // Border kanan
            $html .= '</tr>';
        }

        $html .= '<tr><td colspan="' . ($size + 2) . '" class="qr-border-h"></td></tr>';
        $html .= '</table>';

        return $html;
    }

    function cancelTicket($ticket_id, $buyer_id)
    {
        try {
            $this->db->beginTransaction();

            // Ambil tiket dulu untuk validasi
            $stmt = $this->db->prepare(
                "SELECT * FROM tickets WHERE id = :id AND buyer_id = :buyer_id FOR UPDATE"
            );
            $stmt->execute([':id' => $ticket_id, ':buyer_id' => $buyer_id]);
            $tiket = $stmt->fetch();

            if (!$tiket || $tiket['status'] !== 'valid') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Tiket tidak bisa dibatalkan.'];
            }

            // Update status tiket jadi cancelled
            $stmt = $this->db->prepare(
                "UPDATE tickets SET status = 'cancelled' WHERE id = :id"
            );
            $stmt->execute([':id' => $ticket_id]);

            // Kembalikan stok ke ticket_types
            $stmt = $this->db->prepare(
                "UPDATE ticket_types SET sold = GREATEST(sold - 1, 0) WHERE id = :id"
            );
            $stmt->execute([':id' => $tiket['ticket_type_id']]);

            // Cek apakah ada di waitlist yang bisa dinotifikasi
            $stmt = $this->db->prepare(
                "UPDATE waitlist
                 SET status = 'eligible'
                 WHERE ticket_type_id = :type_id AND status = 'waiting'
                 ORDER BY registered_at ASC
                 LIMIT 1"
            );
            $stmt->execute([':type_id' => $tiket['ticket_type_id']]);

            $this->db->commit();

            return ['success' => true, 'message' => 'Tiket berhasil dibatalkan.'];

        } catch (PDOException $e) {
            $this->db->rollBack();

            return ['success' => false, 'message' => 'Gagal membatalkan tiket: ' . $e->getMessage()];
        }
    }

    function getByEvent($event_id, $limit = 10, $offset = 0)
    {
        $stmt = $this->db->prepare(
            "SELECT
                t.*,
                tt.name    AS tipe_tiket,
                u.username AS nama_pembeli,
                u.email    AS email_pembeli
             FROM tickets t
             JOIN ticket_types tt ON t.ticket_type_id = tt.id
             JOIN events e        ON tt.event_id = e.id
             JOIN users u         ON t.buyer_id = u.id
             WHERE e.id = :event_id
             ORDER BY t.purchase_date DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':event_id', $event_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit',    $limit,    PDO::PARAM_INT);
        $stmt->bindValue(':offset',   $offset,   PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
