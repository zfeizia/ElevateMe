<?php

class SpeakerModel extends BaseModel
{
    protected $table = 'speakers';

    function insert($event_id, $name, $topic, $bio, $photo, $session_time)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO speakers (event_id, name, topic, bio, photo, session_time)
             VALUES (:event_id, :name, :topic, :bio, :photo, :session_time)"
        );

        return $stmt->execute([
            ':event_id'    => $event_id,
            ':name'        => $name,
            ':topic'       => $topic,
            ':bio'         => $bio,
            ':photo'       => $photo,
            ':session_time' => $session_time,
        ]);
    }

    function update($id, $name, $topic, $bio, $photo, $session_time)
    {
        $stmt = $this->db->prepare(
            "UPDATE speakers
             SET name = :name, topic = :topic, bio = :bio,
                 photo = :photo, session_time = :session_time
             WHERE id = :id"
        );

        return $stmt->execute([
            ':id'          => $id,
            ':name'        => $name,
            ':topic'       => $topic,
            ':bio'         => $bio,
            ':photo'       => $photo,
            ':session_time' => $session_time,
        ]);
    }

    function getByEvent($event_id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM speakers WHERE event_id = :event_id ORDER BY session_time ASC"
        );
        $stmt->execute([':event_id' => $event_id]);

        return $stmt->fetchAll();
    }
 
    function isOwnedByOrganizer($speaker_id, $organizer_id)
    {
        $stmt = $this->db->prepare(
            "SELECT s.id FROM speakers s
             JOIN events e ON s.event_id = e.id
             WHERE s.id = :speaker_id AND e.organizer_id = :organizer_id"
        );
        $stmt->execute([
            ':speaker_id'   => $speaker_id,
            ':organizer_id' => $organizer_id,
        ]);

        return (bool) $stmt->fetch();
    }
}
