-- Example seed for employees (manual password hashes)
-- This file uses placeholders for password_hash.
-- Steps:
-- 1) Generate bcrypt hash for your desired password for each username:
--    http://localhost/queue-management-system/make_hash.php?password=YOUR_PASSWORD
-- 2) Replace the placeholders below with the generated hash.

-- NOTE: table employees must exist first (run SQL_employees.sql)

-- Example: one employee per counter id=1..3
-- Replace COUNTER_ID and USERNAME as needed.

INSERT INTO employees (username, password_hash, counter_id, is_active) VALUES
  ('emp_1', 'PUT_HASH_HERE_FOR_PASSWORD_1', 1, 1),
  ('emp_2', 'PUT_HASH_HERE_FOR_PASSWORD_1', 2, 1),
  ('emp_3', 'PUT_HASH_HERE_FOR_PASSWORD_1', 3, 1)
ON DUPLICATE KEY UPDATE
  counter_id = VALUES(counter_id),
  password_hash = VALUES(password_hash),
  is_active = VALUES(is_active);

