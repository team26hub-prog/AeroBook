SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(30) NULL,
  role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
  status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS airlines (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  iata_code CHAR(2) NOT NULL,
  icao_code CHAR(3) NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_airlines_iata (iata_code),
  UNIQUE KEY uq_airlines_icao (icao_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS airports (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(180) NOT NULL,
  iata_code CHAR(3) NOT NULL,
  icao_code CHAR(4) NULL,
  city VARCHAR(120) NOT NULL,
  country VARCHAR(120) NOT NULL,
  timezone VARCHAR(64) NOT NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_airports_iata (iata_code),
  UNIQUE KEY uq_airports_icao (icao_code),
  KEY idx_airports_city_country (city, country)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS flights (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  airline_id BIGINT UNSIGNED NOT NULL,
  flight_number VARCHAR(10) NOT NULL,
  departure_airport_id BIGINT UNSIGNED NOT NULL,
  arrival_airport_id BIGINT UNSIGNED NOT NULL,
  departure_at DATETIME NOT NULL,
  arrival_at DATETIME NOT NULL,
  base_fare DECIMAL(10,2) UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'PKR',
  status ENUM('scheduled', 'boarding', 'departed', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_flights_airline_number_departure (airline_id, flight_number, departure_at),
  KEY idx_flights_route_time_status (departure_airport_id, arrival_airport_id, departure_at, status),
  KEY idx_flights_airline (airline_id),
  CONSTRAINT fk_flights_airline FOREIGN KEY (airline_id) REFERENCES airlines (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_flights_departure_airport FOREIGN KEY (departure_airport_id) REFERENCES airports (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_flights_arrival_airport FOREIGN KEY (arrival_airport_id) REFERENCES airports (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT chk_flights_distinct_airports CHECK (departure_airport_id <> arrival_airport_id),
  CONSTRAINT chk_flights_valid_times CHECK (arrival_at > departure_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  flight_id BIGINT UNSIGNED NOT NULL,
  pnr VARCHAR(12) NOT NULL,
  status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'expired') NOT NULL DEFAULT 'pending',
  currency CHAR(3) NOT NULL DEFAULT 'PKR',
  total_amount DECIMAL(10,2) UNSIGNED NOT NULL,
  booked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_bookings_pnr (pnr),
  KEY idx_bookings_user_created (user_id, created_at),
  KEY idx_bookings_flight_status (flight_id, status),
  CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_bookings_flight FOREIGN KEY (flight_id) REFERENCES flights (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS passengers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id BIGINT UNSIGNED NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  date_of_birth DATE NOT NULL,
  gender ENUM('female', 'male', 'other', 'unspecified') NOT NULL DEFAULT 'unspecified',
  passport_number VARCHAR(30) NULL,
  passport_country CHAR(2) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_passengers_booking_id (booking_id, id),
  UNIQUE KEY uq_passengers_passport_country_number (passport_country, passport_number),
  KEY idx_passengers_name (last_name, first_name),
  CONSTRAINT fk_passengers_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seats (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  flight_id BIGINT UNSIGNED NOT NULL,
  seat_number VARCHAR(5) NOT NULL,
  cabin_class ENUM('economy', 'premium_economy', 'business', 'first') NOT NULL DEFAULT 'economy',
  status ENUM('available', 'blocked', 'unavailable') NOT NULL DEFAULT 'available',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_seats_flight_number (flight_id, seat_number),
  CONSTRAINT fk_seats_flight FOREIGN KEY (flight_id) REFERENCES flights (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_seats (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id BIGINT UNSIGNED NOT NULL,
  seat_id BIGINT UNSIGNED NOT NULL,
  passenger_id BIGINT UNSIGNED NULL,
  status ENUM('reserved', 'confirmed') NOT NULL DEFAULT 'reserved',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_booking_seats_seat (seat_id),
  UNIQUE KEY uq_booking_seats_booking_seat (booking_id, seat_id),
  UNIQUE KEY uq_booking_seats_passenger (booking_id, passenger_id),
  KEY idx_booking_seats_booking_status (booking_id, status),
  CONSTRAINT fk_booking_seats_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_booking_seats_seat FOREIGN KEY (seat_id) REFERENCES seats (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_booking_seats_passenger FOREIGN KEY (booking_id, passenger_id) REFERENCES passengers (booking_id, id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'PKR',
  method ENUM('bank_transfer', 'bank_deposit', 'mobile_wallet', 'card', 'cash', 'other') NOT NULL,
  status ENUM('pending', 'submitted', 'verified', 'rejected', 'refunded', 'failed') NOT NULL DEFAULT 'pending',
  sender_name VARCHAR(150) NOT NULL,
  transaction_reference VARCHAR(100) NULL,
  proof_path VARCHAR(500) NULL,
  paid_at DATETIME NULL,
  remarks TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_payments_booking_status (booking_id, status),
  UNIQUE KEY uq_payments_transaction_reference (transaction_reference),
  CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS e_tickets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id BIGINT UNSIGNED NOT NULL,
  passenger_id BIGINT UNSIGNED NOT NULL,
  ticket_number VARCHAR(20) NOT NULL,
  status ENUM('issued', 'void', 'used', 'refunded') NOT NULL DEFAULT 'issued',
  issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_e_tickets_number (ticket_number),
  UNIQUE KEY uq_e_tickets_passenger (passenger_id),
  KEY idx_e_tickets_booking_status (booking_id, status),
  CONSTRAINT fk_e_tickets_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_e_tickets_passenger_booking FOREIGN KEY (booking_id, passenger_id) REFERENCES passengers (booking_id, id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reference and flight data from seed.sql and import_additional_flights.sql.
-- The 111 overlapping October services are included once in the 115-flight set.
START TRANSACTION;

INSERT INTO airlines (name,iata_code,icao_code,status)
SELECT 'AeroBook Air','AB','ABK','active'
WHERE NOT EXISTS (SELECT 1 FROM airlines WHERE iata_code='AB');

INSERT INTO airports (name,iata_code,icao_code,city,country,timezone,status)
SELECT seed.name,seed.iata_code,seed.icao_code,seed.city,seed.country,seed.timezone,seed.status
FROM (
    SELECT 'Jinnah International Airport' AS name, 'KHI' AS iata_code, 'OPKC' AS icao_code, 'Karachi' AS city, 'Pakistan' AS country, 'Asia/Karachi' AS timezone, 'active' AS status
    UNION ALL
    SELECT 'Allama Iqbal International Airport', 'LHE', 'OPLA', 'Lahore', 'Pakistan', 'Asia/Karachi', 'active'
    UNION ALL
    SELECT 'Islamabad International Airport', 'ISB', 'OPIS', 'Islamabad', 'Pakistan', 'Asia/Karachi', 'active'
    UNION ALL
    SELECT 'Dubai International Airport', 'DXB', 'OMDB', 'Dubai', 'United Arab Emirates', 'Asia/Dubai', 'active'
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM airports existing WHERE existing.iata_code=seed.iata_code);

INSERT INTO flights (airline_id,flight_number,departure_airport_id,arrival_airport_id,departure_at,arrival_at,base_fare,currency,status)
SELECT al.id,seed.flight_number,da.id,aa.id,seed.departure_at,seed.arrival_at,seed.base_fare,seed.currency,seed.status
FROM (
    SELECT 'AB101' AS flight_number, 'KHI' AS departure_code, 'LHE' AS arrival_code, '2027-01-12 08:00:00' AS departure_at, '2027-01-12 09:55:00' AS arrival_at, '18500.00' AS base_fare, 'PKR' AS currency, 'scheduled' AS status
    UNION ALL
    SELECT 'AB102', 'LHE', 'KHI', '2027-01-12 11:00:00', '2027-01-12 12:55:00', '18500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB201', 'KHI', 'ISB', '2027-01-13 09:30:00', '2027-01-13 11:20:00', '17200.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB301', 'KHI', 'DXB', '2027-01-14 22:00:00', '2027-01-15 00:10:00', '62000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB103', 'KHI', 'LHE', '2026-10-15 08:00:00', '2026-10-15 09:55:00', '18500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB202', 'KHI', 'ISB', '2026-10-15 12:30:00', '2026-10-15 14:20:00', '17200.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB302', 'KHI', 'DXB', '2026-10-15 18:00:00', '2026-10-15 20:10:00', '62000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB104', 'KHI', 'LHE', '2026-10-16 07:00:00', '2026-10-16 08:55:00', '18500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB105', 'LHE', 'KHI', '2026-10-16 10:00:00', '2026-10-16 11:55:00', '19000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB203', 'KHI', 'ISB', '2026-10-16 08:30:00', '2026-10-16 10:20:00', '17200.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB204', 'ISB', 'KHI', '2026-10-16 11:30:00', '2026-10-16 13:20:00', '18000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB205', 'LHE', 'ISB', '2026-10-16 09:00:00', '2026-10-16 10:00:00', '12500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB206', 'ISB', 'LHE', '2026-10-16 12:00:00', '2026-10-16 13:00:00', '13000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB303', 'KHI', 'DXB', '2026-10-16 14:00:00', '2026-10-16 16:10:00', '62000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB304', 'DXB', 'KHI', '2026-10-16 17:30:00', '2026-10-16 19:40:00', '64000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB305', 'LHE', 'DXB', '2026-10-16 15:00:00', '2026-10-16 18:15:00', '68000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB306', 'DXB', 'LHE', '2026-10-16 19:30:00', '2026-10-16 22:45:00', '70000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB307', 'ISB', 'DXB', '2026-10-16 13:00:00', '2026-10-16 16:20:00', '69000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB308', 'DXB', 'ISB', '2026-10-16 18:00:00', '2026-10-16 21:20:00', '71000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB401', 'KHI', 'LHE', '2026-10-17 07:00:00', '2026-10-17 08:55:00', '18500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB402', 'LHE', 'KHI', '2026-10-17 10:00:00', '2026-10-17 11:55:00', '19000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB403', 'KHI', 'ISB', '2026-10-17 08:30:00', '2026-10-17 10:20:00', '17200.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB404', 'ISB', 'KHI', '2026-10-17 11:30:00', '2026-10-17 13:20:00', '18000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB405', 'LHE', 'ISB', '2026-10-17 09:00:00', '2026-10-17 10:00:00', '12500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB406', 'ISB', 'LHE', '2026-10-17 12:00:00', '2026-10-17 13:00:00', '13000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB407', 'KHI', 'DXB', '2026-10-17 14:00:00', '2026-10-17 16:10:00', '62000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB408', 'DXB', 'KHI', '2026-10-17 17:30:00', '2026-10-17 19:40:00', '64000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB409', 'LHE', 'DXB', '2026-10-17 15:00:00', '2026-10-17 18:15:00', '68000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB410', 'DXB', 'LHE', '2026-10-17 19:30:00', '2026-10-17 22:45:00', '70000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB411', 'ISB', 'DXB', '2026-10-17 13:00:00', '2026-10-17 16:20:00', '69000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB412', 'DXB', 'ISB', '2026-10-17 18:00:00', '2026-10-17 21:20:00', '71000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB401', 'KHI', 'LHE', '2026-10-18 07:00:00', '2026-10-18 08:55:00', '18500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB402', 'LHE', 'KHI', '2026-10-18 10:00:00', '2026-10-18 11:55:00', '19000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB403', 'KHI', 'ISB', '2026-10-18 08:30:00', '2026-10-18 10:20:00', '17200.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB404', 'ISB', 'KHI', '2026-10-18 11:30:00', '2026-10-18 13:20:00', '18000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB405', 'LHE', 'ISB', '2026-10-18 09:00:00', '2026-10-18 10:00:00', '12500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB406', 'ISB', 'LHE', '2026-10-18 12:00:00', '2026-10-18 13:00:00', '13000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB407', 'KHI', 'DXB', '2026-10-18 14:00:00', '2026-10-18 16:10:00', '62000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB408', 'DXB', 'KHI', '2026-10-18 17:30:00', '2026-10-18 19:40:00', '64000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB409', 'LHE', 'DXB', '2026-10-18 15:00:00', '2026-10-18 18:15:00', '68000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB410', 'DXB', 'LHE', '2026-10-18 19:30:00', '2026-10-18 22:45:00', '70000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB411', 'ISB', 'DXB', '2026-10-18 13:00:00', '2026-10-18 16:20:00', '69000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB412', 'DXB', 'ISB', '2026-10-18 18:00:00', '2026-10-18 21:20:00', '71000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB401', 'KHI', 'LHE', '2026-10-19 07:00:00', '2026-10-19 08:55:00', '18500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB402', 'LHE', 'KHI', '2026-10-19 10:00:00', '2026-10-19 11:55:00', '19000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB403', 'KHI', 'ISB', '2026-10-19 08:30:00', '2026-10-19 10:20:00', '17200.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB404', 'ISB', 'KHI', '2026-10-19 11:30:00', '2026-10-19 13:20:00', '18000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB405', 'LHE', 'ISB', '2026-10-19 09:00:00', '2026-10-19 10:00:00', '12500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB406', 'ISB', 'LHE', '2026-10-19 12:00:00', '2026-10-19 13:00:00', '13000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB407', 'KHI', 'DXB', '2026-10-19 14:00:00', '2026-10-19 16:10:00', '62000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB408', 'DXB', 'KHI', '2026-10-19 17:30:00', '2026-10-19 19:40:00', '64000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB409', 'LHE', 'DXB', '2026-10-19 15:00:00', '2026-10-19 18:15:00', '68000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB410', 'DXB', 'LHE', '2026-10-19 19:30:00', '2026-10-19 22:45:00', '70000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB411', 'ISB', 'DXB', '2026-10-19 13:00:00', '2026-10-19 16:20:00', '69000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB412', 'DXB', 'ISB', '2026-10-19 18:00:00', '2026-10-19 21:20:00', '71000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB401', 'KHI', 'LHE', '2026-10-20 07:00:00', '2026-10-20 08:55:00', '18500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB402', 'LHE', 'KHI', '2026-10-20 10:00:00', '2026-10-20 11:55:00', '19000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB403', 'KHI', 'ISB', '2026-10-20 08:30:00', '2026-10-20 10:20:00', '17200.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB404', 'ISB', 'KHI', '2026-10-20 11:30:00', '2026-10-20 13:20:00', '18000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB405', 'LHE', 'ISB', '2026-10-20 09:00:00', '2026-10-20 10:00:00', '12500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB406', 'ISB', 'LHE', '2026-10-20 12:00:00', '2026-10-20 13:00:00', '13000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB407', 'KHI', 'DXB', '2026-10-20 14:00:00', '2026-10-20 16:10:00', '62000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB408', 'DXB', 'KHI', '2026-10-20 17:30:00', '2026-10-20 19:40:00', '64000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB409', 'LHE', 'DXB', '2026-10-20 15:00:00', '2026-10-20 18:15:00', '68000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB410', 'DXB', 'LHE', '2026-10-20 19:30:00', '2026-10-20 22:45:00', '70000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB411', 'ISB', 'DXB', '2026-10-20 13:00:00', '2026-10-20 16:20:00', '69000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB412', 'DXB', 'ISB', '2026-10-20 18:00:00', '2026-10-20 21:20:00', '71000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB401', 'KHI', 'LHE', '2026-10-21 07:00:00', '2026-10-21 08:55:00', '18500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB402', 'LHE', 'KHI', '2026-10-21 10:00:00', '2026-10-21 11:55:00', '19000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB403', 'KHI', 'ISB', '2026-10-21 08:30:00', '2026-10-21 10:20:00', '17200.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB404', 'ISB', 'KHI', '2026-10-21 11:30:00', '2026-10-21 13:20:00', '18000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB405', 'LHE', 'ISB', '2026-10-21 09:00:00', '2026-10-21 10:00:00', '12500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB406', 'ISB', 'LHE', '2026-10-21 12:00:00', '2026-10-21 13:00:00', '13000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB407', 'KHI', 'DXB', '2026-10-21 14:00:00', '2026-10-21 16:10:00', '62000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB408', 'DXB', 'KHI', '2026-10-21 17:30:00', '2026-10-21 19:40:00', '64000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB409', 'LHE', 'DXB', '2026-10-21 15:00:00', '2026-10-21 18:15:00', '68000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB410', 'DXB', 'LHE', '2026-10-21 19:30:00', '2026-10-21 22:45:00', '70000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB411', 'ISB', 'DXB', '2026-10-21 13:00:00', '2026-10-21 16:20:00', '69000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB412', 'DXB', 'ISB', '2026-10-21 18:00:00', '2026-10-21 21:20:00', '71000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB401', 'KHI', 'LHE', '2026-10-22 07:00:00', '2026-10-22 08:55:00', '18500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB402', 'LHE', 'KHI', '2026-10-22 10:00:00', '2026-10-22 11:55:00', '19000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB403', 'KHI', 'ISB', '2026-10-22 08:30:00', '2026-10-22 10:20:00', '17200.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB404', 'ISB', 'KHI', '2026-10-22 11:30:00', '2026-10-22 13:20:00', '18000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB405', 'LHE', 'ISB', '2026-10-22 09:00:00', '2026-10-22 10:00:00', '12500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB406', 'ISB', 'LHE', '2026-10-22 12:00:00', '2026-10-22 13:00:00', '13000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB407', 'KHI', 'DXB', '2026-10-22 14:00:00', '2026-10-22 16:10:00', '62000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB408', 'DXB', 'KHI', '2026-10-22 17:30:00', '2026-10-22 19:40:00', '64000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB409', 'LHE', 'DXB', '2026-10-22 15:00:00', '2026-10-22 18:15:00', '68000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB410', 'DXB', 'LHE', '2026-10-22 19:30:00', '2026-10-22 22:45:00', '70000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB411', 'ISB', 'DXB', '2026-10-22 13:00:00', '2026-10-22 16:20:00', '69000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB412', 'DXB', 'ISB', '2026-10-22 18:00:00', '2026-10-22 21:20:00', '71000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB401', 'KHI', 'LHE', '2026-10-23 07:00:00', '2026-10-23 08:55:00', '18500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB402', 'LHE', 'KHI', '2026-10-23 10:00:00', '2026-10-23 11:55:00', '19000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB403', 'KHI', 'ISB', '2026-10-23 08:30:00', '2026-10-23 10:20:00', '17200.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB404', 'ISB', 'KHI', '2026-10-23 11:30:00', '2026-10-23 13:20:00', '18000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB405', 'LHE', 'ISB', '2026-10-23 09:00:00', '2026-10-23 10:00:00', '12500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB406', 'ISB', 'LHE', '2026-10-23 12:00:00', '2026-10-23 13:00:00', '13000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB407', 'KHI', 'DXB', '2026-10-23 14:00:00', '2026-10-23 16:10:00', '62000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB408', 'DXB', 'KHI', '2026-10-23 17:30:00', '2026-10-23 19:40:00', '64000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB409', 'LHE', 'DXB', '2026-10-23 15:00:00', '2026-10-23 18:15:00', '68000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB410', 'DXB', 'LHE', '2026-10-23 19:30:00', '2026-10-23 22:45:00', '70000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB411', 'ISB', 'DXB', '2026-10-23 13:00:00', '2026-10-23 16:20:00', '69000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB412', 'DXB', 'ISB', '2026-10-23 18:00:00', '2026-10-23 21:20:00', '71000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB401', 'KHI', 'LHE', '2026-10-24 07:00:00', '2026-10-24 08:55:00', '18500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB402', 'LHE', 'KHI', '2026-10-24 10:00:00', '2026-10-24 11:55:00', '19000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB403', 'KHI', 'ISB', '2026-10-24 08:30:00', '2026-10-24 10:20:00', '17200.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB404', 'ISB', 'KHI', '2026-10-24 11:30:00', '2026-10-24 13:20:00', '18000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB405', 'LHE', 'ISB', '2026-10-24 09:00:00', '2026-10-24 10:00:00', '12500.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB406', 'ISB', 'LHE', '2026-10-24 12:00:00', '2026-10-24 13:00:00', '13000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB407', 'KHI', 'DXB', '2026-10-24 14:00:00', '2026-10-24 16:10:00', '62000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB408', 'DXB', 'KHI', '2026-10-24 17:30:00', '2026-10-24 19:40:00', '64000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB409', 'LHE', 'DXB', '2026-10-24 15:00:00', '2026-10-24 18:15:00', '68000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB410', 'DXB', 'LHE', '2026-10-24 19:30:00', '2026-10-24 22:45:00', '70000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB411', 'ISB', 'DXB', '2026-10-24 13:00:00', '2026-10-24 16:20:00', '69000.00', 'PKR', 'scheduled'
    UNION ALL
    SELECT 'AB412', 'DXB', 'ISB', '2026-10-24 18:00:00', '2026-10-24 21:20:00', '71000.00', 'PKR', 'scheduled'
) AS seed
JOIN airlines al ON al.iata_code='AB'
JOIN airports da ON da.iata_code=seed.departure_code
JOIN airports aa ON aa.iata_code=seed.arrival_code
WHERE NOT EXISTS (SELECT 1 FROM flights existing WHERE existing.airline_id=al.id AND existing.flight_number=seed.flight_number AND existing.departure_at=seed.departure_at);

-- Only fill missing seat numbers for the 115 listed services.
-- Preserve reserved, blocked and otherwise changed seats.
INSERT INTO seats (flight_id, seat_number, cabin_class, status)
SELECT f.id, CONCAT(layout.seat_row, letters.seat_letter), layout.cabin_class, 'available'
FROM flights f
CROSS JOIN (
    SELECT 1 AS seat_row, 'business' AS cabin_class
    UNION ALL SELECT 2, 'business'
    UNION ALL SELECT 3, 'business'
    UNION ALL SELECT 10, 'economy'
    UNION ALL SELECT 11, 'economy'
    UNION ALL SELECT 12, 'economy'
    UNION ALL SELECT 13, 'economy'
    UNION ALL SELECT 14, 'economy'
    UNION ALL SELECT 15, 'economy'
    UNION ALL SELECT 16, 'economy'
    UNION ALL SELECT 17, 'economy'
) AS layout
CROSS JOIN (
    SELECT 'A' AS seat_letter
    UNION ALL SELECT 'B'
    UNION ALL SELECT 'C'
    UNION ALL SELECT 'D'
    UNION ALL SELECT 'E'
    UNION ALL SELECT 'F'
) AS letters
JOIN airlines al ON al.id = f.airline_id
WHERE al.iata_code = 'AB'
  AND (f.flight_number,f.departure_at) IN (
    ('AB101','2027-01-12 08:00:00'),
    ('AB102','2027-01-12 11:00:00'),
    ('AB201','2027-01-13 09:30:00'),
    ('AB301','2027-01-14 22:00:00'),
    ('AB103','2026-10-15 08:00:00'),
    ('AB202','2026-10-15 12:30:00'),
    ('AB302','2026-10-15 18:00:00'),
    ('AB104','2026-10-16 07:00:00'),
    ('AB105','2026-10-16 10:00:00'),
    ('AB203','2026-10-16 08:30:00'),
    ('AB204','2026-10-16 11:30:00'),
    ('AB205','2026-10-16 09:00:00'),
    ('AB206','2026-10-16 12:00:00'),
    ('AB303','2026-10-16 14:00:00'),
    ('AB304','2026-10-16 17:30:00'),
    ('AB305','2026-10-16 15:00:00'),
    ('AB306','2026-10-16 19:30:00'),
    ('AB307','2026-10-16 13:00:00'),
    ('AB308','2026-10-16 18:00:00'),
    ('AB401','2026-10-17 07:00:00'),
    ('AB402','2026-10-17 10:00:00'),
    ('AB403','2026-10-17 08:30:00'),
    ('AB404','2026-10-17 11:30:00'),
    ('AB405','2026-10-17 09:00:00'),
    ('AB406','2026-10-17 12:00:00'),
    ('AB407','2026-10-17 14:00:00'),
    ('AB408','2026-10-17 17:30:00'),
    ('AB409','2026-10-17 15:00:00'),
    ('AB410','2026-10-17 19:30:00'),
    ('AB411','2026-10-17 13:00:00'),
    ('AB412','2026-10-17 18:00:00'),
    ('AB401','2026-10-18 07:00:00'),
    ('AB402','2026-10-18 10:00:00'),
    ('AB403','2026-10-18 08:30:00'),
    ('AB404','2026-10-18 11:30:00'),
    ('AB405','2026-10-18 09:00:00'),
    ('AB406','2026-10-18 12:00:00'),
    ('AB407','2026-10-18 14:00:00'),
    ('AB408','2026-10-18 17:30:00'),
    ('AB409','2026-10-18 15:00:00'),
    ('AB410','2026-10-18 19:30:00'),
    ('AB411','2026-10-18 13:00:00'),
    ('AB412','2026-10-18 18:00:00'),
    ('AB401','2026-10-19 07:00:00'),
    ('AB402','2026-10-19 10:00:00'),
    ('AB403','2026-10-19 08:30:00'),
    ('AB404','2026-10-19 11:30:00'),
    ('AB405','2026-10-19 09:00:00'),
    ('AB406','2026-10-19 12:00:00'),
    ('AB407','2026-10-19 14:00:00'),
    ('AB408','2026-10-19 17:30:00'),
    ('AB409','2026-10-19 15:00:00'),
    ('AB410','2026-10-19 19:30:00'),
    ('AB411','2026-10-19 13:00:00'),
    ('AB412','2026-10-19 18:00:00'),
    ('AB401','2026-10-20 07:00:00'),
    ('AB402','2026-10-20 10:00:00'),
    ('AB403','2026-10-20 08:30:00'),
    ('AB404','2026-10-20 11:30:00'),
    ('AB405','2026-10-20 09:00:00'),
    ('AB406','2026-10-20 12:00:00'),
    ('AB407','2026-10-20 14:00:00'),
    ('AB408','2026-10-20 17:30:00'),
    ('AB409','2026-10-20 15:00:00'),
    ('AB410','2026-10-20 19:30:00'),
    ('AB411','2026-10-20 13:00:00'),
    ('AB412','2026-10-20 18:00:00'),
    ('AB401','2026-10-21 07:00:00'),
    ('AB402','2026-10-21 10:00:00'),
    ('AB403','2026-10-21 08:30:00'),
    ('AB404','2026-10-21 11:30:00'),
    ('AB405','2026-10-21 09:00:00'),
    ('AB406','2026-10-21 12:00:00'),
    ('AB407','2026-10-21 14:00:00'),
    ('AB408','2026-10-21 17:30:00'),
    ('AB409','2026-10-21 15:00:00'),
    ('AB410','2026-10-21 19:30:00'),
    ('AB411','2026-10-21 13:00:00'),
    ('AB412','2026-10-21 18:00:00'),
    ('AB401','2026-10-22 07:00:00'),
    ('AB402','2026-10-22 10:00:00'),
    ('AB403','2026-10-22 08:30:00'),
    ('AB404','2026-10-22 11:30:00'),
    ('AB405','2026-10-22 09:00:00'),
    ('AB406','2026-10-22 12:00:00'),
    ('AB407','2026-10-22 14:00:00'),
    ('AB408','2026-10-22 17:30:00'),
    ('AB409','2026-10-22 15:00:00'),
    ('AB410','2026-10-22 19:30:00'),
    ('AB411','2026-10-22 13:00:00'),
    ('AB412','2026-10-22 18:00:00'),
    ('AB401','2026-10-23 07:00:00'),
    ('AB402','2026-10-23 10:00:00'),
    ('AB403','2026-10-23 08:30:00'),
    ('AB404','2026-10-23 11:30:00'),
    ('AB405','2026-10-23 09:00:00'),
    ('AB406','2026-10-23 12:00:00'),
    ('AB407','2026-10-23 14:00:00'),
    ('AB408','2026-10-23 17:30:00'),
    ('AB409','2026-10-23 15:00:00'),
    ('AB410','2026-10-23 19:30:00'),
    ('AB411','2026-10-23 13:00:00'),
    ('AB412','2026-10-23 18:00:00'),
    ('AB401','2026-10-24 07:00:00'),
    ('AB402','2026-10-24 10:00:00'),
    ('AB403','2026-10-24 08:30:00'),
    ('AB404','2026-10-24 11:30:00'),
    ('AB405','2026-10-24 09:00:00'),
    ('AB406','2026-10-24 12:00:00'),
    ('AB407','2026-10-24 14:00:00'),
    ('AB408','2026-10-24 17:30:00'),
    ('AB409','2026-10-24 15:00:00'),
    ('AB410','2026-10-24 19:30:00'),
    ('AB411','2026-10-24 13:00:00'),
    ('AB412','2026-10-24 18:00:00')
  )
  AND NOT EXISTS (SELECT 1 FROM seats existing WHERE existing.flight_id=f.id AND existing.seat_number=CONCAT(layout.seat_row,letters.seat_letter))
  AND (layout.cabin_class = 'economy' OR letters.seat_letter IN ('A', 'B', 'C', 'D'));

COMMIT;

SET FOREIGN_KEY_CHECKS=1;