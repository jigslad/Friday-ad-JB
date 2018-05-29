SET FOREIGN_KEY_CHECKS=0;

TRUNCATE TABLE banner_zone;
--
-- Dumping data for table `banner_zone`
--
INSERT INTO `banner_zone` (`id`, `name`, `slug`, `max_width`, `max_height`, `created_at`, `updated_at`, `is_desktop`, `is_tablet`, `is_mobile`) VALUES
(1, 'Above Header', 'above_header', NULL, NULL, 1429181948, NULL, 1, 1, 0),
(2, 'In Header', 'in_header', 728, 90, 1429181948, NULL, 1, 1, 0),
(3, 'Margin Left', 'margin_left', NULL, NULL, 1429181948, NULL, 1, 0, 0),
(4, 'Margin Right', 'margin_right', NULL, NULL, 1429181948, NULL, 1, 0, 0),
(5, 'SR Above Results', 'sr_above_results', 800, NULL, 1429181948, NULL, 1, 1, 0),
(6, 'SR In Results Top', 'sr_in_results_top', 800, NULL, 1429181948, NULL, 1, 1, 0),
(7, 'SR In Results Bottom', 'sr_in_results_bottom', 800, NULL, 1429181948, NULL, 1, 1, 0),
(8, 'SR Below Results', 'sr_below_results', 800, NULL, 1429181948, NULL, 1, 1, 0),
(9, 'Ad Details Right', 'ad_details_right', 300, 250, 1429181948, NULL, 1, 1, 0),
(10, 'Ad Details Bottom', 'ad_details_bottom', 728, 90, 1429181948, NULL, 1, 1, 0),
(11, 'SR Mobile Above Results', 'sr_mobile_above_results', 320, NULL, 1429181948, NULL, 0, 0, 1),
(12, 'SR Mobile In Results', 'sr_mobile_in_results', 320, NULL, 1429181948, NULL, 0, 0, 1),
(13, 'SR Mobile Below Results', 'sr_mobile_below_results', 300, 250, 1429181948, NULL, 0, 0, 1),
(14, 'Pixel Tracking', 'pixel_tracking', 1, 1, 1429181948, NULL, 1, 1, 1);

TRUNCATE TABLE banner_page;
--
-- Dumping data for table `banner_page`
--
INSERT INTO `banner_page` (`id`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, 'Homepage', 'homepage', 1429181946, NULL),
(2, 'Search Results', 'search_results', 1429181947, NULL),
(3, 'Ad Details', 'ad_details', 1429181947, NULL),
(4, 'All Other Pages', 'all_other_pages', 1429181947, NULL);

TRUNCATE TABLE banner_zone_banner_page;
--
-- Dumping data for table `banner_zone_banner_page`
--
INSERT INTO `banner_zone_banner_page` (`banner_zone_id`, `banner_page_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(2, 2),
(2, 3),
(3, 1),
(3, 2),
(3, 3),
(3, 4),
(4, 1),
(4, 2),
(4, 3),
(4, 4),
(5, 2),
(6, 2),
(7, 2),
(8, 2),
(9, 3),
(10, 3),
(11, 2),
(12, 2),
(13, 2),
(14, 1),
(14, 2),
(14, 3),
(14, 4);

SET FOREIGN_KEY_CHECKS=1;
