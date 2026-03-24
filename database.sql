-- ============================================================
-- ElevateMe - Sistem Manajemen Event & Tiket
-- Database: elevate_me
-- Dibuat untuk Tugas CPMK-01 Pemrograman Web II
-- ============================================================

CREATE DATABASE IF NOT EXISTS elevate_me
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE elevate_me;

-- ------------------------------------------------------------
-- Tabel: users
-- Menyimpan data akun dengan 3 role: admin, organizer, attendee
-- ------------------------------------------------------------
CREATE TABLE users (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    role        ENUM('admin', 'organizer', 'attendee') NOT NULL DEFAULT 'attendee',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel: events
-- Menyimpan data event yang dibuat oleh organizer
-- ------------------------------------------------------------
CREATE TABLE events (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    organizer_id  INT NOT NULL,
    title         VARCHAR(200) NOT NULL,
    description   TEXT,
    location      VARCHAR(200),
    start_date    DATETIME,
    end_date      DATETIME,
    banner        VARCHAR(255) DEFAULT NULL,
    status        ENUM('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel: ticket_types
-- Tipe tiket per event (misal: Regular, VIP, Early Bird)
-- Kolom early_bird_price & early_bird_deadline untuk Fitur 3
-- ------------------------------------------------------------
CREATE TABLE ticket_types (
    id                    INT PRIMARY KEY AUTO_INCREMENT,
    event_id              INT NOT NULL,
    name                  VARCHAR(100) NOT NULL,
    price                 DECIMAL(10,2) NOT NULL,
    quota                 INT NOT NULL,
    sold                  INT NOT NULL DEFAULT 0,
    description           TEXT,
    early_bird_price      DECIMAL(10,2) DEFAULT NULL,
    early_bird_deadline   DATETIME DEFAULT NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel: tickets
-- Tiket yang sudah dibeli oleh attendee
-- ticket_code format: EVT-XXXX-NNNNNN (Fitur 1)
-- ------------------------------------------------------------
CREATE TABLE tickets (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    ticket_type_id  INT NOT NULL,
    buyer_id        INT NOT NULL,
    ticket_code     VARCHAR(20) NOT NULL UNIQUE,
    purchase_date   DATETIME DEFAULT CURRENT_TIMESTAMP,
    status          ENUM('valid', 'checked_in', 'cancelled') DEFAULT 'valid',
    checked_in_at   DATETIME DEFAULT NULL,
    FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel: speakers
-- Data pembicara/narasumber per event
-- ------------------------------------------------------------
CREATE TABLE speakers (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    event_id      INT NOT NULL,
    name          VARCHAR(100) NOT NULL,
    topic         VARCHAR(200),
    bio           TEXT,
    photo         VARCHAR(255) DEFAULT NULL,
    session_time  DATETIME,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel: waitlist
-- Untuk fitur bonus: antrian jika kuota habis
-- ------------------------------------------------------------
CREATE TABLE waitlist (
    id               INT PRIMARY KEY AUTO_INCREMENT,
    ticket_type_id   INT NOT NULL,
    user_id          INT NOT NULL,
    registered_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status           ENUM('waiting', 'eligible', 'expired') DEFAULT 'waiting',
    UNIQUE KEY unique_waitlist (ticket_type_id, user_id),
    FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- DATA DUMMY (minimal 5 baris per tabel)
-- Password semua akun: Password123!
-- Hash: password_hash('Password123!', PASSWORD_DEFAULT)
-- ============================================================

INSERT INTO users (username, password, email, role) VALUES
('superadmin',  '$2y$12$eKLyBn9XTbfWMhsv6sJGxeXJZvSPcpjCJLVaZg1Cs2cB1KCn2kfxW', 'admin@elevateme.id',        'admin'),
('budi_org',    '$2y$12$eKLyBn9XTbfWMhsv6sJGxeXJZvSPcpjCJLVaZg1Cs2cB1KCn2kfxW', 'budi@elevateme.id',         'organizer'),
('sari_org',    '$2y$12$eKLyBn9XTbfWMhsv6sJGxeXJZvSPcpjCJLVaZg1Cs2cB1KCn2kfxW', 'sari@elevateme.id',         'organizer'),
('andi_user',   '$2y$12$eKLyBn9XTbfWMhsv6sJGxeXJZvSPcpjCJLVaZg1Cs2cB1KCn2kfxW', 'andi@gmail.com',            'attendee'),
('dewi_user',   '$2y$12$eKLyBn9XTbfWMhsv6sJGxeXJZvSPcpjCJLVaZg1Cs2cB1KCn2kfxW', 'dewi@gmail.com',            'attendee'),
('riko_user',   '$2y$12$eKLyBn9XTbfWMhsv6sJGxeXJZvSPcpjCJLVaZg1Cs2cB1KCn2kfxW', 'riko@gmail.com',            'attendee'),
('maya_user',   '$2y$12$eKLyBn9XTbfWMhsv6sJGxeXJZvSPcpjCJLVaZg1Cs2cB1KCn2kfxW', 'maya@gmail.com',            'attendee');

INSERT INTO events (organizer_id, title, description, location, start_date, end_date, status) VALUES
(2, 'Bootcamp UI/UX Design 2025',        'Workshop intensif 2 hari untuk mempelajari dasar hingga mahir UI/UX Design menggunakan Figma dan prinsip Human-Centered Design.',   'Gedung Inovasi Lt.3, Jakarta Selatan',  '2025-09-10 08:00:00', '2025-09-11 17:00:00', 'published'),
(2, 'Seminar Growth Mindset',             'Seminar setengah hari bersama pakar psikologi untuk membangun pola pikir berkembang dan resiliensi diri.',                           'Aula Utama Universitas Negeri Jakarta', '2025-08-20 09:00:00', '2025-08-20 13:00:00', 'published'),
(3, 'Workshop Public Speaking Mastery',  'Latihan intensif teknik berbicara di depan publik, storytelling, dan manajemen panggung.',                                           'Studio Kreatif Bandung',                '2025-10-05 09:00:00', '2025-10-05 16:00:00', 'published'),
(3, 'Bootcamp Python for Data Science',  'Bootcamp 3 hari dari Python dasar hingga analisis data nyata dengan Pandas dan Matplotlib.',                                         'Tech Hub Surabaya',                     '2025-11-01 08:00:00', '2025-11-03 17:00:00', 'published'),
(2, 'Seminar Financial Freedom Muda',    'Pelajari strategi investasi, manajemen utang, dan perencanaan keuangan untuk generasi muda.',                                       'Hotel Grand Mercure, Yogyakarta',        '2025-07-15 13:00:00', '2025-07-15 18:00:00', 'completed');

INSERT INTO ticket_types (event_id, name, price, quota, sold, description, early_bird_price, early_bird_deadline) VALUES
(1, 'Regular',       350000, 50, 12, 'Akses 2 hari penuh + materi digital',           300000, '2025-08-01 23:59:59'),
(1, 'VIP',           750000, 20,  5, 'Akses 2 hari + makan siang + mentoring 1-on-1', 650000, '2025-08-01 23:59:59'),
(2, 'General',       150000, 200, 87, 'Akses seminar + e-sertifikat',                  120000, '2025-08-01 23:59:59'),
(3, 'Peserta',       250000, 30, 10, 'Akses workshop + modul latihan',                NULL,   NULL),
(4, 'Reguler',       500000, 40,  8, 'Bootcamp 3 hari + akses materi seumur hidup',   420000, '2025-10-01 23:59:59'),
(4, 'Premium',       900000, 15,  3, 'Semua benefit Reguler + laptop loaner',         800000, '2025-10-01 23:59:59'),
(5, 'Umum',          75000,  300, 251, 'Akses seminar + e-book gratis',               NULL,   NULL);

INSERT INTO tickets (ticket_type_id, buyer_id, ticket_code, status, checked_in_at) VALUES
(1, 4, 'EVT-UIUX-A1B2C3', 'checked_in', '2025-09-10 08:15:00'),
(1, 5, 'EVT-UIUX-D4E5F6', 'valid',       NULL),
(2, 6, 'EVT-UIUX-G7H8I9', 'valid',       NULL),
(3, 4, 'EVT-GROW-J1K2L3', 'checked_in', '2025-08-20 09:05:00'),
(3, 7, 'EVT-GROW-M4N5O6', 'checked_in', '2025-08-20 09:22:00'),
(5, 5, 'EVT-PYTH-P7Q8R9', 'valid',       NULL),
(7, 4, 'EVT-FINA-S1T2U3', 'checked_in', '2025-07-15 13:10:00');

INSERT INTO speakers (event_id, name, topic, bio, session_time) VALUES
(1, 'Arya Nugraha',     'Fundamentals of UI Design',     'Senior UI/UX Designer dengan 8 tahun pengalaman di perusahaan fintech terkemuka.',    '2025-09-10 09:00:00'),
(1, 'Rina Kusuma',      'User Research & Prototyping',   'UX Researcher dan penulis buku "Desain yang Berempati".',                              '2025-09-11 09:00:00'),
(2, 'Prof. Hartanto',   'Membangun Growth Mindset',      'Psikolog klinis dan dosen Universitas Indonesia, spesialis psikologi positif.',        '2025-08-20 09:30:00'),
(3, 'Dian Permata',     'The Art of Storytelling',       'MC nasional dan pelatih public speaking bersertifikat internasional.',                 '2025-10-05 09:30:00'),
(4, 'Kevin Santoso',    'Python & Data Manipulation',    'Data Scientist di perusahaan e-commerce top Indonesia, kontributor open source.',      '2025-11-01 09:00:00'),
(4, 'Lita Wulandari',   'Visualisasi Data dengan Python','Analis data senior dengan spesialisasi machine learning dan business intelligence.',    '2025-11-02 09:00:00'),
(5, 'Fikri Ramadhan',   'Investasi Saham untuk Pemula',  'Financial planner bersertifikat CFP dengan lebih dari 10.000 klien tersertifikasi.',   '2025-07-15 14:00:00');
