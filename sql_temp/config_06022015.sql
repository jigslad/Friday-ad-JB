SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE config_rule;
TRUNCATE TABLE config;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO `config` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'PayPal Commission', 1424090522, NULL),
(2, 'Product Insertion Fee', 1424090522, NULL),
(3, 'Ad Expiration Days', 1424090522, NULL),
(4, 'Listing Top Ad Slots', 1424090522, NULL),
(5, 'Period(in days) before checking views(Move expired to archive)', 1424090522, NULL),
(6, 'Preceding period(in days) to check views(Move expired to archive)', 1424090522, NULL);