ALTER TABLE booking_seats
  ADD COLUMN passenger_id BIGINT UNSIGNED NULL AFTER seat_id,
  ADD UNIQUE KEY uq_booking_seats_passenger (booking_id, passenger_id),
  ADD CONSTRAINT fk_booking_seats_passenger
    FOREIGN KEY (booking_id, passenger_id)
    REFERENCES passengers (booking_id, id)
    ON UPDATE CASCADE ON DELETE RESTRICT;
