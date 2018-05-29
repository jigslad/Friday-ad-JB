SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE user_review;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO `user_review` (`id`, `ad_id`, `user_id`, `reviewer_id`, `parent_id`, `title`, `message`, `rating`, `report`, `lft`, `rgt`, `root`, `lvl`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 6, 1, 0, NULL, 1432189599, NULL);