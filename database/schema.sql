CREATE TABLE users (
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

CREATE TABLE airlines (
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

CREATE TABLE airports (
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

CREATE TABLE flights (
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

CREATE TABLE bookings (
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

CREATE TABLE passengers (
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

CREATE TABLE seats (
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

CREATE TABLE booking_seats (
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

CREATE TABLE payments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'PKR',
  method ENUM('bank_transfer', 'card', 'cash', 'other') NOT NULL,
  status ENUM('pending', 'submitted', 'verified', 'rejected', 'refunded', 'failed') NOT NULL DEFAULT 'pending',
  transaction_reference VARCHAR(100) NULL,
  proof_path VARCHAR(500) NULL,
  paid_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_payments_booking_status (booking_id, status),
  UNIQUE KEY uq_payments_transaction_reference (transaction_reference),
  CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE e_tickets (
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
