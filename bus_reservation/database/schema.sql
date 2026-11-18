-- schema.sql: creates the database and all tables.
-- WARNING: running this deletes existing tables and data.

CREATE DATABASE IF NOT EXISTS bus_reservation
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE bus_reservation;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS contact_messages, payments, bookings, trips, routes, seats, buses, locations, settings, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL,
  phone         VARCHAR(20)  NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('passenger','admin') NOT NULL DEFAULT 'passenger',
  status        ENUM('active','suspended') NOT NULL DEFAULT 'active',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE locations (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name      VARCHAR(100) NOT NULL,
  state     VARCHAR(100) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_locations_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE buses (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  bus_number VARCHAR(20)  NOT NULL,
  model      VARCHAR(100) NULL,
  bus_type   ENUM('standard','luxury','executive') NOT NULL DEFAULT 'standard',
  capacity   SMALLINT UNSIGNED NOT NULL,
  status     ENUM('active','maintenance','retired') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_buses_number (bus_number),
  CONSTRAINT chk_bus_capacity CHECK (capacity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE seats (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  bus_id      INT UNSIGNED NOT NULL,
  seat_number VARCHAR(10)  NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_seat_per_bus (bus_id, seat_number),
  CONSTRAINT fk_seats_bus FOREIGN KEY (bus_id) REFERENCES buses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE routes (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  origin_id        INT UNSIGNED NOT NULL,
  destination_id   INT UNSIGNED NOT NULL,
  distance_km      SMALLINT UNSIGNED NULL,
  duration_minutes SMALLINT UNSIGNED NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_route_pair (origin_id, destination_id),
  CONSTRAINT fk_routes_origin FOREIGN KEY (origin_id) REFERENCES locations (id) ON DELETE RESTRICT,
  CONSTRAINT fk_routes_destination FOREIGN KEY (destination_id) REFERENCES locations (id) ON DELETE RESTRICT,
  CONSTRAINT chk_route_different CHECK (origin_id <> destination_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE trips (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  route_id       INT UNSIGNED NOT NULL,
  bus_id         INT UNSIGNED NOT NULL,
  departure_time DATETIME NOT NULL,
  arrival_time   DATETIME NOT NULL,
  price          DECIMAL(10,2) NOT NULL,
  status         ENUM('scheduled','departed','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_bus_departure (bus_id, departure_time),
  KEY idx_trips_search (route_id, departure_time),
  CONSTRAINT fk_trips_route FOREIGN KEY (route_id) REFERENCES routes (id) ON DELETE RESTRICT,
  CONSTRAINT fk_trips_bus FOREIGN KEY (bus_id) REFERENCES buses (id) ON DELETE RESTRICT,
  CONSTRAINT chk_trip_price CHECK (price >= 0),
  CONSTRAINT chk_trip_times CHECK (arrival_time > departure_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bookings (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_reference VARCHAR(20)  NOT NULL,
  user_id           INT UNSIGNED NOT NULL,
  trip_id           INT UNSIGNED NOT NULL,
  seat_id           INT UNSIGNED NOT NULL,
  passenger_name    VARCHAR(100) NOT NULL,
  passenger_phone   VARCHAR(20)  NOT NULL,
  amount            DECIMAL(10,2) NOT NULL,
  status            ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  -- Filled by MySQL: 1 for active bookings, NULL for cancelled ones.
  seat_lock         TINYINT GENERATED ALWAYS AS (IF(status = 'cancelled', NULL, 1)) STORED,
  booked_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cancelled_at      DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_booking_reference (booking_reference),
  UNIQUE KEY uq_trip_seat_active (trip_id, seat_id, seat_lock),
  KEY idx_bookings_user (user_id),
  CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT fk_bookings_trip FOREIGN KEY (trip_id) REFERENCES trips (id) ON DELETE RESTRICT,
  CONSTRAINT fk_bookings_seat FOREIGN KEY (seat_id) REFERENCES seats (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id      INT UNSIGNED NOT NULL,
  amount          DECIMAL(10,2) NOT NULL,
  method          ENUM('simulated','card','bank_transfer','mobile_money') NOT NULL DEFAULT 'simulated',
  status          ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  transaction_ref VARCHAR(50) NOT NULL,
  paid_at         DATETIME NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payment_booking (booking_id),
  UNIQUE KEY uq_payment_ref (transaction_ref),
  CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
  setting_key   VARCHAR(50)  NOT NULL,
  setting_value VARCHAR(255) NOT NULL,
  description   VARCHAR(255) NULL,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_messages (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL,
  subject    VARCHAR(150) NOT NULL,
  message    TEXT NOT NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- sample_data.sql: test records so the system has real data to work with.
USE bus_reservation;

-- Users. Admin password: Admin@12345   Passenger password: Passenger@123
INSERT INTO users (full_name, email, phone, password_hash, role) VALUES
('Site Administrator', 'admin@gmail.com', '08000000001', '$2y$10$IJGAe8r1c7MmmkKy7a2Bxu/IrYLWIeF1RymE1FvpKritrFkHhsZ26', 'admin'),
('Test Passenger', 'passenger@gmail.com', '08000000002', '$2y$10$rJqpsgr2PFA..xwDBVIXc.nlA1lcHEGDxgp8l71QZFvurlDLJLTbO', 'passenger');

INSERT INTO settings (setting_key, setting_value, description) VALUES
('cancellation_hours', '24', 'Passengers may cancel only if departure is at least this many hours away');

INSERT INTO locations (name, state) VALUES
('Abuja', 'FCT'), ('Lagos', 'Lagos'), ('Kano', 'Kano'),
('Port Harcourt', 'Rivers'), ('Kaduna', 'Kaduna'), ('Enugu', 'Enugu');

INSERT INTO buses (bus_number, model, bus_type, capacity) VALUES
('ABC-101-XY', 'Toyota Coaster', 'standard', 40),
('LAG-202-ZA', 'Marcopolo Paradiso', 'luxury', 32),
('KAN-303-BC', 'Yutong ZK6122', 'executive', 40);

INSERT INTO seats (bus_id, seat_number)
SELECT 1, CONCAT(r.n, c.l)
FROM (SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
      UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10) r
CROSS JOIN (SELECT 'A' AS l UNION SELECT 'B' UNION SELECT 'C' UNION SELECT 'D') c
WHERE r.n <= 10;

INSERT INTO seats (bus_id, seat_number)
SELECT 2, CONCAT(r.n, c.l)
FROM (SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
      UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10) r
CROSS JOIN (SELECT 'A' AS l UNION SELECT 'B' UNION SELECT 'C' UNION SELECT 'D') c
WHERE r.n <= 8;

INSERT INTO seats (bus_id, seat_number)
SELECT 3, CONCAT(r.n, c.l)
FROM (SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
      UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10) r
CROSS JOIN (SELECT 'A' AS l UNION SELECT 'B' UNION SELECT 'C' UNION SELECT 'D') c
WHERE r.n <= 10;

INSERT INTO routes (origin_id, destination_id, distance_km, duration_minutes) VALUES
(1, 2, 760, 600), (2, 1, 760, 600), (1, 3, 350, 300),
(3, 1, 350, 300), (1, 4, 600, 480), (2, 6, 540, 420);

INSERT INTO trips (route_id, bus_id, departure_time, arrival_time, price) VALUES
(1, 1, TIMESTAMP(CURDATE() + INTERVAL 1 DAY, '07:00:00'), TIMESTAMP(CURDATE() + INTERVAL 1 DAY, '17:00:00'), 15000.00),
(1, 2, TIMESTAMP(CURDATE() + INTERVAL 1 DAY, '20:00:00'), TIMESTAMP(CURDATE() + INTERVAL 2 DAY, '06:00:00'), 17500.00),
(2, 3, TIMESTAMP(CURDATE() + INTERVAL 1 DAY, '08:00:00'), TIMESTAMP(CURDATE() + INTERVAL 1 DAY, '18:00:00'), 15000.00),
(3, 1, TIMESTAMP(CURDATE() + INTERVAL 2 DAY, '06:30:00'), TIMESTAMP(CURDATE() + INTERVAL 2 DAY, '11:30:00'),  9000.00),
(4, 2, TIMESTAMP(CURDATE() + INTERVAL 2 DAY, '14:00:00'), TIMESTAMP(CURDATE() + INTERVAL 2 DAY, '19:00:00'),  9000.00),
(5, 3, TIMESTAMP(CURDATE() + INTERVAL 3 DAY, '07:00:00'), TIMESTAMP(CURDATE() + INTERVAL 3 DAY, '15:00:00'), 14000.00),
(1, 1, TIMESTAMP(CURDATE() + INTERVAL 3 DAY, '07:00:00'), TIMESTAMP(CURDATE() + INTERVAL 3 DAY, '17:00:00'), 15000.00);

