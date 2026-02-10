-- Reset Transactions and User Balances Script
-- WARNING: This will delete ALL transaction data and reset user balances to 0
-- Make sure you have a backup before running this!

-- Truncate all transaction tables
TRUNCATE TABLE airtime;
TRUNCATE TABLE data;
TRUNCATE TABLE cable;
TRUNCATE TABLE bill;
TRUNCATE TABLE exam;
TRUNCATE TABLE bulksms;
TRUNCATE TABLE cash;
TRUNCATE TABLE deposit;
TRUNCATE TABLE bank_transfer;
TRUNCATE TABLE data_card;
TRUNCATE TABLE recharge_card;

-- Reset all user balances to 0
UPDATE user SET bal = 0.00, refbal = 0.00;

-- Optional: Reset admin balance too (uncomment if needed)
-- UPDATE user SET bal = 0.00, refbal = 0.00 WHERE type = 'ADMIN';

SELECT 'All transactions deleted and user balances reset to 0.00' AS status;
