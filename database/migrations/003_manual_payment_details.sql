ALTER TABLE payments
  MODIFY COLUMN method ENUM('bank_transfer', 'bank_deposit', 'mobile_wallet', 'card', 'cash', 'other') NOT NULL,
  ADD COLUMN sender_name VARCHAR(150) NOT NULL DEFAULT '' AFTER status,
  ADD COLUMN remarks TEXT NULL AFTER paid_at;
