<?php

class UserModel extends BaseModel
{
    protected $table = 'users';

    function insert($username, $email, $password, $role)
    {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare(
            "INSERT INTO users (username, password, email, role)
             VALUES (:username, :password, :email, :role)"
        );

        return $stmt->execute([
            ':username' => $username,
            ':password' => $hashed,
            ':email'    => $email,
            ':role'     => $role,
        ]);
    }

    function update($id, $username, $email, $role)
    {
        $stmt = $this->db->prepare(
            "UPDATE users
             SET username = :username, email = :email, role = :role
             WHERE id = :id"
        );

        return $stmt->execute([
            ':id'       => $id,
            ':username' => $username,
            ':email'    => $email,
            ':role'     => $role,
        ]);
    }

    function updatePassword($id, $new_password)
    {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare(
            "UPDATE users SET password = :password WHERE id = :id"
        );

        return $stmt->execute([':id' => $id, ':password' => $hashed]);
    }

    function isUsernameTaken($username, $exclude_id = null)
    {
        if ($exclude_id) {
            $stmt = $this->db->prepare(
                "SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1"
            );
            $stmt->execute([':username' => $username, ':id' => $exclude_id]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT id FROM users WHERE username = :username LIMIT 1"
            );
            $stmt->execute([':username' => $username]);
        }

        return (bool) $stmt->fetch();
    }

    function isEmailTaken($email, $exclude_id = null)
    {
        if ($exclude_id) {
            $stmt = $this->db->prepare(
                "SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1"
            );
            $stmt->execute([':email' => $email, ':id' => $exclude_id]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT id FROM users WHERE email = :email LIMIT 1"
            );
            $stmt->execute([':email' => $email]);
        }

        return (bool) $stmt->fetch();
    }

    function getAllWithStats($limit = 10, $offset = 0)
    {
        $stmt = $this->db->prepare(
            "SELECT u.*,
                    COUNT(t.id) AS jumlah_tiket
             FROM users u
             LEFT JOIN tickets t ON u.id = t.buyer_id AND t.status != 'cancelled'
             GROUP BY u.id
             ORDER BY u.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    function getByRole($role)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE role = :role ORDER BY username ASC"
        );
        $stmt->execute([':role' => $role]);

        return $stmt->fetchAll();
    }
}

class WaitlistModel extends BaseModel
{
    protected $table = 'waitlist';

    function register($ticket_type_id, $user_id)
    {
        // Cek apakah sudah terdaftar di waitlist ini
        $stmt = $this->db->prepare(
            "SELECT id FROM waitlist
             WHERE ticket_type_id = :type_id AND user_id = :user_id"
        );
        $stmt->execute([':type_id' => $ticket_type_id, ':user_id' => $user_id]);

        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Kamu sudah terdaftar di waitlist ini.'];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO waitlist (ticket_type_id, user_id) VALUES (:type_id, :user_id)"
        );
        $stmt->execute([':type_id' => $ticket_type_id, ':user_id' => $user_id]);

        return ['success' => true, 'message' => 'Berhasil masuk waitlist! Kami akan memberitahumu jika ada slot tersedia.'];
    }

    function getEligibleCount($user_id)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM waitlist WHERE user_id = :user_id AND status = 'eligible'"
        );
        $stmt->execute([':user_id' => $user_id]);

        return (int) $stmt->fetchColumn();
    }

    function getByUser($user_id)
    {
        $stmt = $this->db->prepare(
            "SELECT w.*, tt.name AS tipe_tiket, e.title AS judul_event
             FROM waitlist w
             JOIN ticket_types tt ON w.ticket_type_id = tt.id
             JOIN events e        ON tt.event_id = e.id
             WHERE w.user_id = :user_id
             ORDER BY w.registered_at DESC"
        );
        $stmt->execute([':user_id' => $user_id]);

        return $stmt->fetchAll();
    }

    function markAsRead($waitlist_id, $user_id)
    {
        $stmt = $this->db->prepare(
            "UPDATE waitlist SET status = 'expired'
             WHERE id = :id AND user_id = :user_id AND status = 'eligible'"
        );

        return $stmt->execute([':id' => $waitlist_id, ':user_id' => $user_id]);
    }

    function isOnWaitlist($ticket_type_id, $user_id)
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM waitlist
             WHERE ticket_type_id = :type_id AND user_id = :user_id AND status = 'waiting'"
        );
        $stmt->execute([':type_id' => $ticket_type_id, ':user_id' => $user_id]);

        return (bool) $stmt->fetch();
    }
}
