-- sample_data.sql: test records so the system has real data to work with.
USE bus_reservation;

-- Users. admin@gmail.com and password Admin password: Admin@12345   Passenger password: Passenger@123
INSERT INTO users (full_name, email, phone, password_hash, role) VALUES
('Site Administrator', 'admin@gmail.com', '08000000001', '$2y$10$IJGAe8r1c7MmmkKy7a2Bxu/IrYLWIeF1RymE1FvpKritrFkHhsZ26', 'admin'),
('Test Passenger', 'passenger@busreservation.test', '08000000002', '$2y$10$rJqpsgr2PFA..xwDBVIXc.nlA1lcHEGDxgp8l71QZFvurlDLJLTbO', 'passenger');

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
