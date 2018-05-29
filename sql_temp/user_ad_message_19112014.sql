SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE message;

INSERT INTO `message` (`id`, `sender_id`, `receiver_id`, `ad_id`, `parent_id`, `subject`, `text_message`, `html_message`, `sender_email`, `sender_first_name`, `sender_last_name`, `receiver_email`, `receiver_first_name`, `receiver_last_name`, `is_read`, `created_at`, `lft`, `rgt`, `root`, `lvl`) VALUES
(1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1416395308, 1, 14, 1, 0),
(2, -3250331, -1766586, 2640860, 1, NULL, 'Hello david, I''m looking for a bike for my son. He loves Star Wars and this looks ideal. Can I ask a few questions please? How old is it? Where has it been kept. Do you think it would be an ok size for a 7 year old? Thanks, Clarkq', NULL, NULL, 'Mr. A', 'Clarkq', NULL, 'david', 'bruton', 1, 1416395308, 2, 7, 1, 1),
(3, -1766586, -3250331, 2640860, 2, NULL, 'Hi Clarkq,\n\nNo problem asking questions.\n\nThe bike is 3 years old, it hasn''t been used that much and has been kept in the hall. There''s no rust, only a bit of wear and tear from use. But it''s in very good nick.\n\ndavid.', NULL, NULL, 'david', 'bruton', NULL, 'Mr. A', 'Clarkq', 1, 1416395308, 3, 6, 1, 2),
(4, -3250331, -1766586, 2640860, 3, NULL, 'Hello david, would be nice if my son to try it out a bit, when would you be available? I''m really interested, thanks!', NULL, NULL, 'Mr. A', 'Clarkq', NULL, 'david', 'bruton', 0, 1416395308, 4, 5, 1, 3),
(5, -3285411, -1766586, 2640860, 1, NULL, 'Hi david, I am looking to buy this cycle.', NULL, NULL, 'Joy', 'Wu', NULL, 'david', 'bruton', 1, 1416395308, 8, 9, 1, 1),
(6, -3250331, -1766586, 257382, 1, NULL, 'Hello david, can i see this?  Thanks, Clarkq', NULL, NULL, 'Mr. A', 'Clarkq', NULL, 'david', 'bruton', 1, 1416395308, 10, 13, 1, 1),
(7, -1766586, -3250331, 257382, 6, NULL, 'Hi Clarkq,\n  Yes sure    .\n        \ndavid.', NULL, NULL, 'david', 'bruton', NULL, 'Mr. A', 'Clarkq', 1, 1416395308, 11, 12, 1, 2);
