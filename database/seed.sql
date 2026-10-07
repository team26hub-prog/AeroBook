INSERT INTO airlines (name, iata_code, icao_code, status) VALUES
('AeroBook Air', 'AB', 'ABK', 'active');

INSERT INTO airports (name, iata_code, icao_code, city, country, timezone, status) VALUES
('Jinnah International Airport', 'KHI', 'OPKC', 'Karachi', 'Pakistan', 'Asia/Karachi', 'active'),
('Allama Iqbal International Airport', 'LHE', 'OPLA', 'Lahore', 'Pakistan', 'Asia/Karachi', 'active'),
('Islamabad International Airport', 'ISB', 'OPIS', 'Islamabad', 'Pakistan', 'Asia/Karachi', 'active'),
('Dubai International Airport', 'DXB', 'OMDB', 'Dubai', 'United Arab Emirates', 'Asia/Dubai', 'active');

INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, departure_at, arrival_at, base_fare, currency, status) VALUES
(1, 'AB101', 1, 2, '2027-01-12 08:00:00', '2027-01-12 09:55:00', 18500.00, 'PKR', 'scheduled'),
(1, 'AB102', 2, 1, '2027-01-12 11:00:00', '2027-01-12 12:55:00', 18500.00, 'PKR', 'scheduled'),
(1, 'AB201', 1, 3, '2027-01-13 09:30:00', '2027-01-13 11:20:00', 17200.00, 'PKR', 'scheduled'),
(1, 'AB301', 1, 4, '2027-01-14 22:00:00', '2027-01-15 00:10:00', 62000.00, 'PKR', 'scheduled');

INSERT INTO seats (flight_id, seat_number, cabin_class, status) VALUES
(1, '1A', 'business', 'available'), (1, '1B', 'business', 'available'), (1, '2A', 'business', 'available'),
(1, '10A', 'economy', 'available'), (1, '10B', 'economy', 'available'), (1, '10C', 'economy', 'available'), (1, '11A', 'economy', 'available'), (1, '11B', 'economy', 'available'),
(2, '1A', 'business', 'available'), (2, '1B', 'business', 'available'), (2, '10A', 'economy', 'available'), (2, '10B', 'economy', 'available'), (2, '10C', 'economy', 'available'),
(3, '1A', 'business', 'available'), (3, '1B', 'business', 'available'), (3, '10A', 'economy', 'available'), (3, '10B', 'economy', 'available'), (3, '10C', 'economy', 'available'),
(4, '1A', 'business', 'available'), (4, '1B', 'business', 'available'), (4, '10A', 'economy', 'available'), (4, '10B', 'economy', 'available'), (4, '10C', 'economy', 'available');
