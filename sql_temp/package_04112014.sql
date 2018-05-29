SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE package;
TRUNCATE TABLE package_rule;
TRUNCATE TABLE package_upsell;
TRUNCATE TABLE package_print;

INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('1', 'YOUR CURRENT AD TYPE', 'Get FREE', 'Live in a matter of minutes...', '<ul><li>Basic online listing</li></ul>', '0', '0', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-gray-head";s:9:"btn_class";s:13:"primary-btn-3";}', 'ad', '7', null, 'Free', '1', 'For Sale');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('1', '2', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('1', '2', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('1', '2', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('1', '2', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('1', '2', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('1', '2', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('1', '2', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('1', '2', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('1', '2', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('1', '2', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('1', '2', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('1', '2', '12');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('1', '2', '13');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('2', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More views on your ad!', '<ul><li>Boost your ad to the top of the listings each week</li><li>Your ad will be labelled \'urgent\'</li></ul>', '2', '2', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '8', null, 'Non Print Package 1', '2', 'For Sale');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('2', '2', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('2', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('2', '6');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('3', 'BEST FOR QUICK RESULTS', 'Get on Top', 'Sell even quicker', '<ul><li>Boost your ad to the top of the listings</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '3', '3', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '9', null, 'Non Print Package 2', '3', 'For Sale');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('3', '2', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('3', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('3', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('4', 'MAXIMUM EXPOSURE', 'Go Premium', 'Get Maximum exposure!', '<ul><li>Your ad promoted on the Friday-Ad homepage!</li><li>Boost your ad to the top of the listings</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '7', '7', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '10', null, 'Non Print Package 3', '4', 'For Sale');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('4', '2', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('4', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('4', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('4', '37');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('5', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More views on your ad!', '<ul><li>Boost your ad to the top of the listings each week</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '3', '3', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '8', null, 'Print Area Package 1', '5', 'For Sale');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('5', '2', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('5', '2', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('5', '2', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('5', '2', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('5', '2', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('5', '2', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('5', '2', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('5', '2', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('5', '2', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('5', '2', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('5', '2', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('5', '2', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('5', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('5', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('6', 'BEST FOR QUICK RESULTS', 'Get in Print', 'Your No1 publication for buying and selling', '<ul><li>Your ad printed with a photo in your local Friday-Ad paper.</li><li>Boost your ad to the top of the listings online</li><li>Your ad will be labelled \'featured\'</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '11', null, 'Print Area Package 2', '6', 'For Sale');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('6', '2', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('6', '2', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('6', '2', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('6', '2', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('6', '2', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('6', '2', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('6', '2', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('6', '2', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('6', '2', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('6', '2', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('6', '2', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('6', '2', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('6', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('6', '6');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('6', '7');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('6', '10');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('6', '1w', '3', '3', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('6', '2w', '4.50', '4.50', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('6', '4w', '7.20', '7.20', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('7', 'MAXIMUM EXPOSURE', 'Go Premium Print', 'Get maximum print exposure!', '<ul><li>Your ad printed with a photo in three editions of the Friday-Ad paper</li><li>Your ad promoted on the Friday-Ad.co.uk homepage!</li><li>Boost your ad to the top of the listings online</li><li>Your ad will be labelled \'featured\' and appear top of the online listings</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '12', null, 'Print Area Package 3', '7', 'For Sale');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('7', '2', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('7', '2', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('7', '2', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('7', '2', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('7', '2', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('7', '2', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('7', '2', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('7', '2', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('7', '2', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('7', '2', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('7', '2', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('7', '2', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('7', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('7', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('7', '37');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('7', '8');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('7', '10');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('7', '1w', '10', '10', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('7', '2w', '15', '15', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('7', '4w', '24', '24', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('8', 'YOUR CURRENT AD TYPE', 'Get FREE', 'Live in a matter of minutes...', '<ul><li>Basic online listing</li></ul>', '0', '0', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-gray-head";s:9:"btn_class";s:13:"primary-btn-3";}', 'ad', '7', null, 'Free', '1', 'For Sale -> Home and Garden -> Furniture');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('8', '159', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('8', '159', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('8', '159', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('8', '159', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('8', '159', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('8', '159', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('8', '159', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('8', '159', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('8', '159', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('8', '159', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('8', '159', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('8', '159', '12');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('8', '159', '13');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('9', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More views on your ad!', '<ul><li>Boost your ad to the top of the listings each week</li><li>Your ad will be labelled \'urgent\'</li></ul>', '5', '5', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '8', null, 'Non Print Package 1', '2', 'For Sale -> Home and Garden -> Furniture');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('9', '159', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('9', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('9', '6');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('10', 'BEST FOR QUICK RESULTS', 'Get on Top', 'Sell even quicker', '<ul><li>Boost your ad to the top of the listings</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '7', '7', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '9', null, 'Non Print Package 2', '3', 'For Sale -> Home and Garden -> Furniture');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('10', '159', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('10', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('10', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('11', 'MAXIMUM EXPOSURE', 'Go Premium', 'Get Maximum exposure!', '<ul><li>Your ad promoted on the Friday-Ad homepage!</li><li>Boost your ad to the top of the listings</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '10', '10', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '10', null, 'Non Print Package 3', '4', 'For Sale -> Home and Garden -> Furniture');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('11', '159', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('11', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('11', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('11', '37');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('12', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More views on your ad!', '<ul><li>Boost your ad to the top of the listings each week</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '7', '7', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '8', null, 'Print Area Package 1', '5', 'For Sale -> Home and Garden -> Furniture');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('12', '159', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('12', '159', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('12', '159', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('12', '159', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('12', '159', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('12', '159', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('12', '159', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('12', '159', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('12', '159', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('12', '159', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('12', '159', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('12', '159', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('12', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('12', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('13', 'BEST FOR QUICK RESULTS', 'Get in Print', 'Your No1 publication for buying and selling', '<ul><li>Your ad printed with a photo in your local Friday-Ad paper.</li><li>Boost your ad to the top of the listings online</li><li>Your ad will be labelled \'featured\'</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '11', null, 'Print Area Package 2', '6', 'For Sale -> Home and Garden -> Furniture');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('13', '159', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('13', '159', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('13', '159', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('13', '159', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('13', '159', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('13', '159', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('13', '159', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('13', '159', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('13', '159', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('13', '159', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('13', '159', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('13', '159', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('13', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('13', '6');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('13', '7');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('13', '10');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('13', '1w', '7', '7', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('13', '2w', '10.50', '10.50', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('13', '4w', '16.80', '16.80', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('14', 'MAXIMUM EXPOSURE', 'Go Premium Print', 'Get maximum print exposure!', '<ul><li>Your ad printed with a photo in three editions of the Friday-Ad paper</li><li>Your ad promoted on the Friday-Ad.co.uk homepage!</li><li>Boost your ad to the top of the listings online</li><li>Your ad will be labelled \'featured\' and appear top of the online listings</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '12', null, 'Print Area Package 3', '7', 'For Sale -> Home and Garden -> Furniture');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('14', '159', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('14', '159', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('14', '159', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('14', '159', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('14', '159', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('14', '159', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('14', '159', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('14', '159', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('14', '159', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('14', '159', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('14', '159', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('14', '159', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('14', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('14', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('14', '37');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('14', '8');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('14', '10');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('14', '1w', '15', '15', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('14', '2w', '22.50', '22.50', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('14', '4w', '36', '36', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('15', 'YOUR CURRENT AD TYPE', 'Get FREE', 'Live in a matter of minutes...', '<ul><li>Basic online listing</li></ul>', '0', '0', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-gray-head";s:9:"btn_class";s:13:"primary-btn-3";}', 'ad', '7', null, 'Free', '1', 'For Sale -> Leisure -> Sports Equipment');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('15', '408', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('15', '408', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('15', '408', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('15', '408', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('15', '408', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('15', '408', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('15', '408', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('15', '408', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('15', '408', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('15', '408', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('15', '408', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('15', '408', '12');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('15', '408', '13');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('16', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More views on your ad!', '<ul><li>Boost your ad to the top of the listings each week</li><li>Your ad will be labelled \'urgent\'</li></ul>', '5', '5', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '8', null, 'Non Print Package 1', '2', 'For Sale -> Leisure -> Sports Equipment');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('16', '408', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('16', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('16', '6');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('17', 'BEST FOR QUICK RESULTS', 'Get on Top', 'Sell even quicker', '<ul><li>Boost your ad to the top of the listings</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '7', '7', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '9', null, 'Non Print Package 2', '3', 'For Sale -> Leisure -> Sports Equipment');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('17', '408', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('17', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('17', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('18', 'MAXIMUM EXPOSURE', 'Go Premium', 'Get Maximum exposure!', '<ul><li>Your ad promoted on the Friday-Ad homepage!</li><li>Boost your ad to the top of the listings</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '10', '10', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '10', null, 'Non Print Package 3', '4', 'For Sale -> Leisure -> Sports Equipment');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('18', '408', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('18', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('18', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('18', '37');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('19', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More views on your ad!', '<ul><li>Boost your ad to the top of the listings each week</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '7', '7', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '8', null, 'Print Area Package 1', '5', 'For Sale -> Leisure -> Sports Equipment');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('19', '408', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('19', '408', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('19', '408', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('19', '408', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('19', '408', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('19', '408', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('19', '408', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('19', '408', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('19', '408', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('19', '408', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('19', '408', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('19', '408', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('19', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('19', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('20', 'BEST FOR QUICK RESULTS', 'Get in Print', 'Your No1 publication for buying and selling', '<ul><li>Your ad printed with a photo in your local Friday-Ad paper.</li><li>Boost your ad to the top of the listings online</li><li>Your ad will be labelled \'featured\'</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '11', null, 'Print Area Package 2', '6', 'For Sale -> Leisure -> Sports Equipment');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('20', '408', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('20', '408', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('20', '408', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('20', '408', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('20', '408', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('20', '408', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('20', '408', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('20', '408', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('20', '408', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('20', '408', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('20', '408', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('20', '408', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('20', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('20', '6');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('20', '7');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('20', '10');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('20', '1w', '7', '7', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('20', '2w', '10.50', '10.50', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('20', '4w', '16.80', '16.80', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('21', 'MAXIMUM EXPOSURE', 'Go Premium Print', 'Get maximum print exposure!', '<ul><li>Your ad printed with a photo in three editions of the Friday-Ad paper</li><li>Your ad promoted on the Friday-Ad.co.uk homepage!</li><li>Boost your ad to the top of the listings online</li><li>Your ad will be labelled \'featured\' and appear top of the online listings</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '12', null, 'Print Area Package 3', '7', 'For Sale -> Leisure -> Sports Equipment');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('21', '408', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('21', '408', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('21', '408', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('21', '408', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('21', '408', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('21', '408', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('21', '408', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('21', '408', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('21', '408', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('21', '408', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('21', '408', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('21', '408', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('21', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('21', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('21', '37');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('21', '8');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('21', '10');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('21', '1w', '15', '15', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('21', '2w', '22.50', '22.50', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('21', '4w', '36', '36', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('51', 'YOUR CURRENT AD TYPE', 'Get FREE', 'Live in a matter of minutes...', '<ul><li>Basic online listing</li></ul>', '0', '0', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-gray-head";s:9:"btn_class";s:13:"primary-btn-3";}', 'ad', '7', null, 'Free', '1', 'For Sale -> Home and Garden -> Heating and Cooling');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('51', '291', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('51', '291', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('51', '291', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('51', '291', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('51', '291', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('51', '291', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('51', '291', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('51', '291', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('51', '291', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('51', '291', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('51', '291', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('51', '291', '12');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('51', '291', '13');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('52', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More views on your ad!', '<ul><li>Boost your ad to the top of the listings each week</li><li>Your ad will be labelled \'urgent\'</li></ul>', '5', '5', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '8', null, 'Non Print Package 1', '2', 'For Sale -> Home and Garden -> Heating and Cooling');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('52', '291', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('52', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('52', '6');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('53', 'BEST FOR QUICK RESULTS', 'Get on Top', 'Sell even quicker', '<ul><li>Boost your ad to the top of the listings</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '7', '7', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '9', null, 'Non Print Package 2', '3', 'For Sale -> Home and Garden -> Heating and Cooling');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('53', '291', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('53', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('53', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('54', 'MAXIMUM EXPOSURE', 'Go Premium', 'Get Maximum exposure!', '<ul><li>Your ad promoted on the Friday-Ad homepage!</li><li>Boost your ad to the top of the listings</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '10', '10', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '10', null, 'Non Print Package 3', '4', 'For Sale -> Home and Garden -> Heating and Cooling');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('54', '291', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('54', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('54', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('54', '37');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('55', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More views on your ad!', '<ul><li>Boost your ad to the top of the listings each week</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '7', '7', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '8', null, 'Print Area Package 1', '5', 'For Sale -> Home and Garden -> Heating and Cooling');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('55', '291', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('55', '291', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('55', '291', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('55', '291', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('55', '291', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('55', '291', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('55', '291', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('55', '291', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('55', '291', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('55', '291', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('55', '291', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('55', '291', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('55', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('55', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('56', 'BEST FOR QUICK RESULTS', 'Get in Print', 'Your No1 publication for buying and selling', '<ul><li>Your ad printed with a photo in your local Friday-Ad paper.</li><li>Boost your ad to the top of the listings online</li><li>Your ad will be labelled \'featured\'</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '11', null, 'Print Area Package 2', '6', 'For Sale -> Home and Garden -> Heating and Cooling');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('56', '291', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('56', '291', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('56', '291', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('56', '291', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('56', '291', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('56', '291', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('56', '291', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('56', '291', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('56', '291', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('56', '291', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('56', '291', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('56', '291', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('56', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('56', '6');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('56', '7');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('56', '10');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('56', '1w', '7', '7', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('56', '2w', '10.50', '10.50', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('56', '4w', '16.80', '16.80', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('57', 'MAXIMUM EXPOSURE', 'Go Premium Print', 'Get maximum print exposure!', '<ul><li>Your ad printed with a photo in three editions of the Friday-Ad paper</li><li>Your ad promoted on the Friday-Ad.co.uk homepage!</li><li>Boost your ad to the top of the listings online</li><li>Your ad will be labelled \'featured\' and appear top of the online listings</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '12', null, 'Print Area Package 3', '7', 'For Sale -> Home and Garden -> Heating and Cooling');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('57', '291', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('57', '291', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('57', '291', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('57', '291', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('57', '291', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('57', '291', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('57', '291', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('57', '291', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('57', '291', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('57', '291', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('57', '291', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('57', '291', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('57', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('57', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('57', '37');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('57', '8');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('57', '10');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('57', '1w', '15', '15', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('57', '2w', '22.50', '22.50', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('57', '4w', '36', '36', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('58', 'YOUR CURRENT AD TYPE', 'Get FREE', 'Live in a matter of minutes...', '<ul><li>Basic online listing</li></ul>', '0', '0', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-gray-head";s:9:"btn_class";s:13:"primary-btn-3";}', 'ad', '7', null, 'Free', '1', 'For Sale -> Home and Garden -> Health');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('58', '284', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('58', '284', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('58', '284', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('58', '284', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('58', '284', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('58', '284', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('58', '284', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('58', '284', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('58', '284', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('58', '284', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('58', '284', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('58', '284', '12');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('58', '284', '13');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('59', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More views on your ad!', '<ul><li>Boost your ad to the top of the listings each week</li><li>Your ad will be labelled \'urgent\'</li></ul>', '5', '5', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '8', null, 'Non Print Package 1', '2', 'For Sale -> Home and Garden -> Health');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('59', '284', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('59', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('59', '6');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('60', 'BEST FOR QUICK RESULTS', 'Get on Top', 'Sell even quicker', '<ul><li>Boost your ad to the top of the listings</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '7', '7', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '9', null, 'Non Print Package 2', '3', 'For Sale -> Home and Garden -> Health');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('60', '284', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('60', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('60', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('61', 'MAXIMUM EXPOSURE', 'Go Premium', 'Get Maximum exposure!', '<ul><li>Your ad promoted on the Friday-Ad homepage!</li><li>Boost your ad to the top of the listings</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '10', '10', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '10', null, 'Non Print Package 3', '4', 'For Sale -> Home and Garden -> Health');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('61', '284', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('61', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('61', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('61', '37');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('62', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More views on your ad!', '<ul><li>Boost your ad to the top of the listings each week</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '7', '7', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '8', null, 'Print Area Package 1', '5', 'For Sale -> Home and Garden -> Health');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('62', '284', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('62', '284', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('62', '284', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('62', '284', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('62', '284', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('62', '284', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('62', '284', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('62', '284', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('62', '284', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('62', '284', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('62', '284', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('62', '284', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('62', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('62', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('63', 'BEST FOR QUICK RESULTS', 'Get in Print', 'Your No1 publication for buying and selling', '<ul><li>Your ad printed with a photo in your local Friday-Ad paper.</li><li>Boost your ad to the top of the listings online</li><li>Your ad will be labelled \'featured\'</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '11', null, 'Print Area Package 2', '6', 'For Sale -> Home and Garden -> Health');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('63', '284', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('63', '284', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('63', '284', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('63', '284', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('63', '284', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('63', '284', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('63', '284', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('63', '284', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('63', '284', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('63', '284', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('63', '284', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('63', '284', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('63', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('63', '6');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('63', '7');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('63', '10');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('63', '1w', '7', '7', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('63', '2w', '10.50', '10.50', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('63', '4w', '16.80', '16.80', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('64', 'MAXIMUM EXPOSURE', 'Go Premium Print', 'Get maximum print exposure!', '<ul><li>Your ad printed with a photo in three editions of the Friday-Ad paper</li><li>Your ad promoted on the Friday-Ad.co.uk homepage!</li><li>Boost your ad to the top of the listings online</li><li>Your ad will be labelled \'featured\' and appear top of the online listings</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '12', null, 'Print Area Package 3', '7', 'For Sale -> Home and Garden -> Health');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('64', '284', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('64', '284', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('64', '284', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('64', '284', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('64', '284', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('64', '284', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('64', '284', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('64', '284', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('64', '284', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('64', '284', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('64', '284', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('64', '284', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('64', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('64', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('64', '37');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('64', '8');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('64', '10');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('64', '1w', '15', '15', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('64', '2w', '22.50', '22.50', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('64', '4w', '36', '36', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('65', 'YOUR CURRENT AD TYPE', 'Get Basic', 'Live in a matter of minutes...', '<ul><li>Basic online listing for your vehicle</li></ul>', '0', '0', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-gray-head";s:9:"btn_class";s:13:"primary-btn-3";}', 'ad', '7', '5', 'Free', '1', 'Motors');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('65', '444', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('65', '444', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('65', '444', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('65', '444', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('65', '444', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('65', '444', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('65', '444', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('65', '444', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('65', '444', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('65', '444', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('65', '444', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('65', '444', '12');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('65', '444', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('65', '2');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('66', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More eyes on your vehicle', '<ul><li>Boost your motor listing to the top of the results each week</li><li>Your ad with be labelled \'urgent\'</li></ul>', '5.99', '5.99', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '8', '5', 'Non Print Package 1', '2', 'Motors');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('66', '444', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('66', '6');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('67', 'BEST FOR QUICK RESULTS', 'Get on Top', 'More views on your vehicle', '<ul><li>Boost your motor listing to the top of the results each week</li><li>Your ad with be labelled \'featured and appear top of the listings</li><li>A accurate valuation of your vehicle to give you the best selling price</li></ul>', '7.99', '7.99', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '9', '5', 'Non Print Package 2', '3', 'Motors');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('67', '444', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('67', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('67', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('67', '22');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('68', 'MAXIMUM EXPOSURE', 'Go Premium', 'Get Maximum exposure!', '<ul><li>Your motor listing promoted on the Friday-Ad homepage!</li><li>Boost your motor listing to the top of the results each week</li><li>Your ad with be labelled \'featured and appear top of the listings above your competition</li><li>A accurate valuation of your vehicle to give you the best selling price</li></ul>', '14.99', '14.99', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '10', '5', 'Non Print Package 3', '4', 'Motors');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('68', '444', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('68', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('68', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('68', '22');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('68', '37');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('69', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More eyes on your vehicle', '<ul><li>Boost your motor listing to the top of the results each week</li><li>Your ad with be labelled \'urgent\'</li></ul>', '7.99', '7.99', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '8', '5', 'Print Area Package 1', '5', 'Motors');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('69', '444', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('69', '444', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('69', '444', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('69', '444', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('69', '444', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('69', '444', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('69', '444', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('69', '444', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('69', '444', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('69', '444', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('69', '444', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('69', '444', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('69', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('69', '6');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('70', 'BEST FOR QUICK RESULTS', 'Get in Print', 'Your No1 publication for buying and selling', '<ul><li>Your vehicle printed with a photo in your local Friday-Ad paper.</li><li>Boost your motor listing to the top of the results each week online</li><li>Your ad with be labelled \'featured\'</li><li>A accurate valuation of your vehicle to give you the best selling price</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '11', '5', 'Print Area Package 2', '6', 'Motors');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('70', '444', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('70', '444', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('70', '444', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('70', '444', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('70', '444', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('70', '444', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('70', '444', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('70', '444', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('70', '444', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('70', '444', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('70', '444', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('70', '444', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('70', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('70', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('70', '8');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('70', '22');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('70', '1w', '16.99', '16.99', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('70', '2w', '24.99', '24.99', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('70', '4w', '39.99', '39.99', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('71', 'MAXIMUM EXPOSURE', 'Go Premium Print', 'Get maximum print exposure!', '<ul><li>Your vehicle printed with a photo/logo in five editions of the Friday-Ad paper</li><li>Your vehicle promoted on the Friday-Ad.co.uk homepage!</li><li>Boost your motor listing to the top of the results each week online</li><li>Your ad with be labelled \'featured\' and appear top of the online listings</li><li>A accurate valuation of your vehicle to give you the best selling price</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '12', '5', 'Print Area Package 3', '7', 'Motors');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('71', '444', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('71', '444', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('71', '444', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('71', '444', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('71', '444', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('71', '444', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('71', '444', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('71', '444', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('71', '444', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('71', '444', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('71', '444', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('71', '444', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('71', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('71', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('71', '37');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('71', '9');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('71', '22');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('71', '1w', '29.99', '29.99', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('71', '2w', '44.99', '44.99', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('71', '4w', '69.99', '69.99', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('72', 'YOUR CURRENT AD TYPE', 'Get Basic', 'Live in a matter of minutes...', '<ul><li>Basic online listing for your vehicle</li></ul>', '5', '5', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-gray-head";s:9:"btn_class";s:13:"primary-btn-3";}', 'ad', '7', '6', 'Free', '1', 'Motors');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('72', '444', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('72', '444', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('72', '444', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('72', '444', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('72', '444', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('72', '444', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('72', '444', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('72', '444', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('72', '444', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('72', '444', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('72', '444', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('72', '444', '12');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('72', '444', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('72', '2');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('73', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More eyes on your vehicle', '<ul><li>Boost your motor listing to the top of the results each week</li><li>Your ad with be labelled \'urgent\'</li></ul>', '5.99', '5.99', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '8', '6', 'Non Print Package 1', '2', 'Motors');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('73', '444', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('73', '6');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('74', 'BEST FOR QUICK RESULTS', 'Get on Top', 'More views on your vehicle', '<ul><li>Boost your motor listing to the top of the results each week</li><li>Your ad with be labelled \'featured and appear top of the listings</li><li>A accurate valuation of your vehicle to give you the best selling price</li></ul>', '7.99', '7.99', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '9', '6', 'Non Print Package 2', '3', 'Motors');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('74', '444', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('74', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('74', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('74', '22');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('75', 'MAXIMUM EXPOSURE', 'Go Premium', 'Get Maximum exposure!', '<ul><li>Your motor listing promoted on the Friday-Ad homepage!</li><li>Boost your motor listing to the top of the results each week</li><li>Your ad with be labelled \'featured and appear top of the listings above your competition</li><li>A accurate valuation of your vehicle to give you the best selling price</li></ul>', '14.99', '14.99', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '10', '6', 'Non Print Package 3', '4', 'Motors');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('75', '444', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('75', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('75', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('75', '22');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('75', '37');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('76', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More eyes on your vehicle', '<ul><li>Boost your motor listing to the top of the results each week</li><li>Your ad with be labelled \'urgent\'</li></ul>', '7.99', '7.99', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '8', '6', 'Print Area Package 1', '5', 'Motors');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('76', '444', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('76', '444', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('76', '444', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('76', '444', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('76', '444', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('76', '444', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('76', '444', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('76', '444', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('76', '444', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('76', '444', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('76', '444', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('76', '444', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('76', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('76', '6');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('77', 'BEST FOR QUICK RESULTS', 'Get in Print', 'Your No1 publication for buying and selling', '<ul><li>Your vehicle printed with a photo in your local Friday-Ad paper.</li><li>Boost your motor listing to the top of the results each week online</li><li>Your ad with be labelled \'featured\'</li><li>A accurate valuation of your vehicle to give you the best selling price</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '11', '6', 'Print Area Package 2', '6', 'Motors');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('77', '444', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('77', '444', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('77', '444', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('77', '444', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('77', '444', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('77', '444', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('77', '444', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('77', '444', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('77', '444', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('77', '444', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('77', '444', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('77', '444', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('77', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('77', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('77', '8');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('77', '22');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('77', '1w', '16.99', '16.99', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('77', '2w', '24.99', '24.99', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('77', '4w', '39.99', '39.99', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('78', 'MAXIMUM EXPOSURE', 'Go Premium Print', 'Get maximum print exposure!', '<ul><li>Your vehicle printed with a photo/logo in five editions of the Friday-Ad paper</li><li>Your vehicle promoted on the Friday-Ad.co.uk homepage!</li><li>Boost your motor listing to the top of the results each week online</li><li>Your ad with be labelled \'featured\' and appear top of the online listings</li><li>A accurate valuation of your vehicle to give you the best selling price</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '12', '6', 'Print Area Package 3', '7', 'Motors');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('78', '444', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('78', '444', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('78', '444', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('78', '444', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('78', '444', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('78', '444', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('78', '444', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('78', '444', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('78', '444', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('78', '444', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('78', '444', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('78', '444', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('78', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('78', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('78', '37');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('78', '9');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('78', '22');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('78', '1w', '29.99', '29.99', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('78', '2w', '44.99', '44.99', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('78', '4w', '69.99', '69.99', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('79', 'YOUR CURRENT AD TYPE', 'Get FREE', 'Live in a matter of minutes...', '<ul><li>Basic online listing for your local service</li></ul>', '0', '0', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-gray-head";s:9:"btn_class";s:13:"primary-btn-3";}', 'ad', '7', null, 'Free', '1', 'Services');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('79', '585', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('79', '585', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('79', '585', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('79', '585', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('79', '585', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('79', '585', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('79', '585', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('79', '585', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('79', '585', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('79', '585', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('79', '585', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('79', '585', '12');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('79', '585', '13');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('80', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More eyes on your business', '<ul><li>Boost your business listing to the top of the results each week</li><li>Your ad with be labelled \'urgent\'</li></ul>', '7', '7', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '8', null, 'Non Print Package 1', '2', 'Services');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('80', '585', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('80', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('80', '6');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('81', 'BEST FOR QUICK RESULTS', 'Get on Top', 'More customers for your business', '<ul><li>Boost your business listing to the top of the results each week</li><li>Your ad with be labelled \'featured and appear top of the listings</li></ul>', '10', '10', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '9', null, 'Non Print Package 2', '3', 'Services');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('81', '585', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('81', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('81', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('82', 'MAXIMUM EXPOSURE', 'Go Premium', 'Get Maximum exposure!', '<ul><li>Your business promoted on the Friday-Ad homepage!</li><li>Boost your business listing to the top of the results each week</li><li>Your ad with be labelled \'featured and appear top of the listings above your competition</li></ul>', '15', '15', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '10', null, 'Non Print Package 3', '4', 'Services');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('82', '585', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('82', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('82', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('82', '37');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('83', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More views on your business!', '<ul><li>Boost your business listing to the top of the results each week</li><li>Your ad with be labelled \'urgent\'</li></ul>', '10', '10', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '8', null, 'Print Area Package 1', '5', 'Services');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('83', '585', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('83', '585', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('83', '585', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('83', '585', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('83', '585', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('83', '585', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('83', '585', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('83', '585', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('83', '585', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('83', '585', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('83', '585', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('83', '585', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('83', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('83', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('84', 'BEST FOR QUICK RESULTS', 'Get in Print', 'Your No1 publication for buying and selling', '<ul><li>Your business printed with a photo/logo in your local Friday-Ad paper.</li><li>Boost your business listing to the top of the results each week online</li><li>Your ad with be labelled \'featured\'</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '11', null, 'Print Area Package 2', '6', 'Services');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('84', '585', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('84', '585', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('84', '585', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('84', '585', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('84', '585', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('84', '585', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('84', '585', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('84', '585', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('84', '585', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('84', '585', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('84', '585', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('84', '585', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('84', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('84', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('84', '7');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('84', '1w', '15', '15', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('84', '4w', '48', '48', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('84', '12w', '120', '120', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('85', 'MAXIMUM EXPOSURE', 'Go Premium Print', 'Get maximum print exposure!', '<ul><li>Your business printed with a photo/logo in five editions of the Friday-Ad paper</li><li>Your business promoted on the Friday-Ad.co.uk homepage!</li><li>Boost your business listing to the top of the results each week online</li><li>Your ad with be labelled \'featured\' and appear top of the online listings</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '12', null, 'Print Area Package 3', '7', 'Services');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('85', '585', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('85', '585', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('85', '585', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('85', '585', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('85', '585', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('85', '585', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('85', '585', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('85', '585', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('85', '585', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('85', '585', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('85', '585', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('85', '585', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('85', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('85', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('85', '37');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('85', '9');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('85', '1w', '25', '25', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('85', '4w', '80', '80', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('85', '12w', '200', '200', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('86', 'YOUR CURRENT AD TYPE', 'Get FREE', 'Live in a matter of minutes...', '<ul><li>Basic online listing</li></ul>', '0', '0', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-gray-head";s:9:"btn_class";s:13:"primary-btn-3";}', 'ad', '7', null, 'Free', '1', 'Community');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('86', '783', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('86', '783', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('86', '783', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('86', '783', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('86', '783', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('86', '783', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('86', '783', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('86', '783', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('86', '783', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('86', '783', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('86', '783', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('86', '783', '12');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('86', '783', '13');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('87', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'Get seen first', '<ul><li>Boost your ad to the top of the listings each week</li><li>Your ad with be labelled \'urgent\'</li></ul>', '5', '5', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '8', null, 'Non Print Package 1', '2', 'Community');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('87', '783', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('87', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('87', '6');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('88', 'BEST FOR QUICK RESULTS', 'Get on Top', 'More views on your ad!', '<ul><li>Boost your ad to the top of the listings</li><li>Your ad with be labelled \'featured and appear top of the listings</li></ul>', '8', '8', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '9', null, 'Non Print Package 2', '3', 'Community');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('88', '783', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('88', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('88', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('89', 'MAXIMUM EXPOSURE', 'Go Premium', 'Get Maximum exposure!', '<ul><li>Your ad promoted on the Friday-Ad homepage!</li><li>Boost your ad to the top of the listings</li><li>Your ad with be labelled \'featured and appear top of the listings</li></ul>', '15', '15', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '10', null, 'Non Print Package 3', '4', 'Community');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('89', '783', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('89', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('89', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('89', '37');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('90', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More views on your ad!', '<ul><li>Boost your ad to the top of the listings each week</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '8', '8', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '8', null, 'Print Area Package 1', '5', 'Community');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('90', '783', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('90', '783', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('90', '783', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('90', '783', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('90', '783', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('90', '783', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('90', '783', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('90', '783', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('90', '783', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('90', '783', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('90', '783', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('90', '783', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('90', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('90', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('91', 'BEST FOR QUICK RESULTS', 'Get in Print', 'Your No1 publication for promoting local events and announcements', '<ul><li>Your ad printed with a photo in your local Friday-Ad paper.</li><li>Boost your ad to the top of the listings online</li><li>Your ad with be labelled \'featured\'</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '11', null, 'Print Area Package 2', '6', 'Community');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('91', '783', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('91', '783', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('91', '783', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('91', '783', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('91', '783', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('91', '783', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('91', '783', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('91', '783', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('91', '783', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('91', '783', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('91', '783', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('91', '783', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('91', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('91', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('91', '7');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('91', '1w', '12', '12', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('91', '4w', '36', '36', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('91', '8w', '59', '59', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('92', 'MAXIMUM EXPOSURE', 'Go Premium Print', 'Get maximum print exposure!', '<ul><li>Your ad printed with a photo in five editions of the Friday-Ad paper</li><li>Your ad promoted on the Friday-Ad.co.uk homepage!</li><li>Boost your ad to the top of the listings online</li><li>Your ad with be labelled \'featured\' and appear top of the online listings</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '12', null, 'Print Area Package 3', '7', 'Community');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('92', '783', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('92', '783', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('92', '783', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('92', '783', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('92', '783', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('92', '783', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('92', '783', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('92', '783', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('92', '783', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('92', '783', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('92', '783', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('92', '783', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('92', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('92', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('92', '37');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('92', '9');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('92', '1w', '20', '20', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('92', '4w', '60', '60', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('92', '8w', '99', '99', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('93', 'YOUR CURRENT AD TYPE', 'Get FREE', 'Live in a matter of minutes...', '<ul><li>Basic online listing for your property</li></ul>', '0', '0', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-gray-head";s:9:"btn_class";s:13:"primary-btn-3";}', 'ad', '7', null, 'Free', '1', 'Property');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('93', '678', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('93', '678', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('93', '678', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('93', '678', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('93', '678', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('93', '678', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('93', '678', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('93', '678', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('93', '678', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('93', '678', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('93', '678', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('93', '678', '12');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('93', '678', '13');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('94', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More eyes on your property', '<ul><li>Boost your property listing to the top of the results each week</li><li>Your ad with be labelled \'urgent\'</li></ul>', '7', '7', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '8', null, 'Non Print Package 1', '2', 'Property');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('94', '678', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('94', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('94', '6');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('95', 'BEST FOR QUICK RESULTS', 'Get on Top', 'More views on your property', '<ul><li>Boost your property listing to the top of the results each week</li><li>Your ad with be labelled \'featured and appear top of the listings</li></ul>', '10', '10', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '9', null, 'Non Print Package 2', '3', 'Property');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('95', '678', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('95', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('95', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('96', 'MAXIMUM EXPOSURE', 'Go Premium', 'Get Maximum exposure!', '<ul><li>Your property listing promoted on the Friday-Ad homepage!</li><li>Boost your property listing to the top of the results each week</li><li>Your ad with be labelled \'featured and appear top of the listings above your competition</li></ul>', '20', '20', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '10', null, 'Non Print Package 3', '4', 'Property');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('96', '678', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('96', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('96', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('96', '37');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('97', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More views on your property', '<ul><li>Boost your motor listing to the top of the results each week</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '10', '10', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '8', null, 'Print Area Package 1', '5', 'Property');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('97', '678', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('97', '678', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('97', '678', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('97', '678', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('97', '678', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('97', '678', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('97', '678', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('97', '678', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('97', '678', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('97', '678', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('97', '678', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('97', '678', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('97', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('97', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('98', 'BEST FOR QUICK RESULTS', 'Get in Print', 'Your No1 publication for buying and selling', '<ul><li>Your property printed with a photo in your local Friday-Ad paper.</li><li>Boost your property listing to the top of the results each week online</li><li>Your ad with be labelled \'featured\'</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '11', null, 'Print Area Package 2', '6', 'Property');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('98', '678', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('98', '678', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('98', '678', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('98', '678', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('98', '678', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('98', '678', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('98', '678', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('98', '678', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('98', '678', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('98', '678', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('98', '678', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('98', '678', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('98', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('98', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('98', '7');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('98', '10');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('98', '1w', '14', '14', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('98', '3w', '28', '28', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('98', '8w', '56', '56', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('99', 'MAXIMUM EXPOSURE', 'Go Premium Print', 'Get maximum print exposure!', '<ul><li>Your property printed with a photo/logo in three editions of the Friday-Ad paper</li><li>Your property promoted on the Friday-Ad.co.uk homepage!</li><li>Boost your property listing to the top of the results each week online</li><li>Your ad with be labelled \'featured\' and appear top of the online listings</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '12', null, 'Print Area Package 3', '7', 'Property');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('99', '678', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('99', '678', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('99', '678', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('99', '678', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('99', '678', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('99', '678', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('99', '678', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('99', '678', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('99', '678', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('99', '678', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('99', '678', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('99', '678', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('99', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('99', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('99', '37');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('99', '8');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('99', '10');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('99', '1w', '20', '20', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('99', '3w', '40', '40', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('99', '8w', '80', '80', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('100', 'YOUR CURRENT AD TYPE', 'Get FREE', 'Live in a matter of minutes...', '<ul><li>Basic online listing for your vacancy</li></ul>', '0', '0', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-gray-head";s:9:"btn_class";s:13:"primary-btn-3";}', 'ad', '7', null, 'Free', '1', 'Jobs');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('100', '500', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('100', '500', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('100', '500', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('100', '500', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('100', '500', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('100', '500', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('100', '500', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('100', '500', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('100', '500', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('100', '500', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('100', '500', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('100', '500', '12');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('100', '500', '13');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('101', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More eyes on your vacancy', '<ul><li>Boost your job listing to the top of the results each week</li><li>Your ad with be labelled \'urgent\'</li></ul>', '12', '12', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '8', null, 'Non Print Package 1', '2', 'Jobs');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('101', '500', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('101', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('101', '6');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('102', 'BEST FOR QUICK RESULTS', 'Get on Top', 'More views on your vacancy', '<ul><li>Boost your job listing to the top of the results each week</li><li>Your ad with be labelled \'featured and appear top of the listings</li></ul>', '20', '20', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '9', null, 'Non Print Package 2', '3', 'Jobs');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('102', '500', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('102', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('102', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('103', 'MAXIMUM EXPOSURE', 'Go Premium', 'Get Maximum exposure!', '<ul><li>Your job listing promoted on the Friday-Ad homepage!</li><li>Boost your job listing to the top of the results each week</li><li>Your ad with be labelled \'featured and appear top of the listings above your competition</li></ul>', '30', '30', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '10', null, 'Non Print Package 3', '4', 'Jobs');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('103', '500', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('103', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('103', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('103', '37');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('104', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More views on your vacancy', '<ul><li>Boost your job listing to the top of the results each week</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '20', '20', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '8', null, 'Print Area Package 1', '5', 'Jobs');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('104', '500', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('104', '500', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('104', '500', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('104', '500', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('104', '500', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('104', '500', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('104', '500', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('104', '500', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('104', '500', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('104', '500', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('104', '500', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('104', '500', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('104', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('104', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('105', 'BEST FOR QUICK RESULTS', 'Get in Print', 'Your No1 publication for buying and selling', '<ul><li>Your vacany printed with a photo in your local Friday-Ad paper.</li><li>Boost your job listing to the top of the results each week online</li><li>Your ad with be labelled \'featured\'</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '11', null, 'Print Area Package 2', '6', 'Jobs');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('105', '500', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('105', '500', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('105', '500', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('105', '500', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('105', '500', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('105', '500', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('105', '500', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('105', '500', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('105', '500', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('105', '500', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('105', '500', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('105', '500', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('105', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('105', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('105', '7');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('105', '10');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('105', '1w', '25', '25', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('105', '2w', '37.50', '37.50', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('105', '4w', '60', '60', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('106', 'MAXIMUM EXPOSURE', 'Go Premium Print', 'Get maximum print exposure!', '<ul><li>Your job printed with a photo/logo in five editions of the Friday-Ad paper</li><li>Your vacancy promoted on the Friday-Ad.co.uk homepage!</li><li>Boost your job listing to the top of the results each week online</li><li>Your ad with be labelled \'featured\' and appear top of the online listings</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '12', null, 'Print Area Package 3', '7', 'Jobs');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('106', '500', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('106', '500', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('106', '500', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('106', '500', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('106', '500', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('106', '500', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('106', '500', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('106', '500', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('106', '500', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('106', '500', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('106', '500', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('106', '500', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('106', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('106', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('106', '37');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('106', '9');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('106', '1w', '50', '50', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('106', '2w', '75', '75', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('106', '4w', '120', '120', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('107', 'YOUR CURRENT AD TYPE', 'Get FREE', 'Live in a matter of minutes...', '<ul><li>Basic online listing for your animal</li></ul>', '0', '0', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-gray-head";s:9:"btn_class";s:13:"primary-btn-3";}', 'ad', '7', null, 'Free', '1', 'Animals');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('107', '725', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('107', '725', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('107', '725', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('107', '725', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('107', '725', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('107', '725', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('107', '725', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('107', '725', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('107', '725', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('107', '725', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('107', '725', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('107', '725', '12');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('107', '725', '13');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('108', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More views on your ad!', '<ul><li>Boost your ad to the top of the results each week</li><li>Your ad with be labelled \'urgent\'</li></ul>', '5', '5', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '8', null, 'Non Print Package 1', '2', 'Animals');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('108', '725', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('108', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('108', '6');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('109', 'BEST FOR QUICK RESULTS', 'Get on Top', 'Sell even quicker', '<ul><li>Boost your ad to the top of the results each week</li><li>Your ad with be labelled \'featured and appear top of the listings</li></ul>', '120', '120', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '9', null, 'Non Print Package 2', '3', 'Animals');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('109', '725', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('109', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('109', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('110', 'MAXIMUM EXPOSURE', 'Go Premium', 'Get Maximum exposure!', '<ul><li>Your business promoted on the Friday-Ad homepage!</li><li>Boost your ad to the top of the results each week</li><li>Your ad with be labelled \'featured and appear top of the listings above your competition</li></ul>', '20', '20', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '10', null, 'Non Print Package 3', '4', 'Animals');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('110', '725', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('110', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('110', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('110', '37');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('111', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More views on your ad!', '<ul><li>Boost your ad to the top of the listings each week</li><li>Your ad will be labelled \'featured\' and appear top of the listings</li></ul>', '10', '10', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '8', null, 'Print Area Package 1', '5', 'Animals');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('111', '725', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('111', '725', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('111', '725', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('111', '725', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('111', '725', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('111', '725', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('111', '725', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('111', '725', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('111', '725', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('111', '725', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('111', '725', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('111', '725', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('111', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('111', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('112', 'BEST FOR QUICK RESULTS', 'Get in Print', 'Your No1 publication for buying and selling', '<ul><li>Your ad printed with a photo in your local Friday-Ad paper.</li><li>Boost your ad to the top of the listings online</li><li>Your ad with be labelled \'featured\'</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '11', null, 'Print Area Package 2', '6', 'Animals');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('112', '725', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('112', '725', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('112', '725', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('112', '725', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('112', '725', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('112', '725', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('112', '725', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('112', '725', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('112', '725', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('112', '725', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('112', '725', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('112', '725', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('112', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('112', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('112', '8');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('112', '10');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('112', '1w', '15', '15', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('112', '2w', '22.50', '22.50', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('112', '4w', '36', '36', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('113', 'MAXIMUM EXPOSURE', 'Go Premium Print', 'Get maximum print exposure!', '<ul><li>Your ad printed with a photo in five  editions of the Friday-Ad paper</li><li>Your ad promoted on the Friday-Ad.co.uk homepage!</li><li>Boost your ad to the top of the listings online</li><li>Your ad with be labelled \'featured\' and appear top of the online listings</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '12', null, 'Print Area Package 3', '7', 'Animals');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('113', '725', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('113', '725', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('113', '725', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('113', '725', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('113', '725', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('113', '725', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('113', '725', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('113', '725', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('113', '725', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('113', '725', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('113', '725', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('113', '725', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('113', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('113', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('113', '37');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('113', '9');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('113', '10');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('113', '1w', '20', '20', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('113', '2w', '30', '30', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('113', '4w', '48', '48', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('114', 'YOUR CURRENT AD TYPE', 'Get FREE', 'Live in a matter of minutes...', '<ul><li>Basic online listing for your service</li></ul>', '30', '30', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-gray-head";s:9:"btn_class";s:13:"primary-btn-3";}', 'ad', '7', null, 'Free', '1', 'Adult');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('114', '3411', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('114', '3411', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('114', '3411', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('114', '3411', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('114', '3411', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('114', '3411', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('114', '3411', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('114', '3411', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('114', '3411', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('114', '3411', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('114', '3411', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('114', '3411', '12');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('114', '3411', '13');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('115', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More eyes on your ad', '<ul><li>Boost your ad to the top of the results each week</li><li>Your ad with be labelled \'urgent\'</li></ul>', '45', '45', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '8', null, 'Non Print Package 1', '2', 'Adult');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('115', '3411', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('115', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('115', '6');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('116', 'BEST FOR QUICK RESULTS', 'Get on Top', 'More response to your ad', '<ul><li>Boost your ad listing to the top of the results each week</li><li>Your ad with be labelled \'featured and appear top of the listings above your competiton</li></ul>', '50', '50', '1437628077', 1, 'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}', 'ad', '9', null, 'Non Print Package 2', '3', 'Adult');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('116', '3411', '13');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('116', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('116', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('117', 'OUR MOST POPULAR OPTION', 'Go Urgent', 'More views on your ad!', '<ul><li>Boost your ad listing to the top of the results each week</li><li>Your ad with be labelled \'urgent\'</li></ul>', '50', '50', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '8', null, 'Print Area Package 1', '5', 'Adult');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('117', '3411', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('117', '3411', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('117', '3411', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('117', '3411', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('117', '3411', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('117', '3411', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('117', '3411', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('117', '3411', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('117', '3411', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('117', '3411', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('117', '3411', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('117', '3411', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('117', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('117', '5');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('118', 'BEST FOR QUICK RESULTS', 'Get in Print', 'Your No1 publication for buying and selling', '<ul><li>Your service printed with a photo in your local Friday-Ad paper.</li><li>Boost your ad listing to the top of the results each week online</li><li>Your ad with be labelled \'featured and appear top of the listings above your competiton</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '11', null, 'Print Area Package 2', '6', 'Adult');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('118', '3411', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('118', '3411', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('118', '3411', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('118', '3411', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('118', '3411', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('118', '3411', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('118', '3411', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('118', '3411', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('118', '3411', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('118', '3411', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('118', '3411', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('118', '3411', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('118', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('118', '7');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('118', '10');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('118', '5');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('118', '1w', '80', '80', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('118', '2w', '120', '120', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('118', '4w', '190', '190', '1437628077');


INSERT INTO package (`id`, `label`, `title`, `sub_title`, `description`, `price`, `admin_price`, `created_at`, `status`, `value`, `package_for`, `email_template_id`, `role_id`, `package_text`, `package_sr_no`, `category_name`) VALUES ('119', 'MAXIMUM EXPOSURE', 'Go Premium Print', 'Get maximum print exposure!', '<ul><li>Your ad printed with a photo/logo in three editions of the Friday-Ad paper</li><li>Boost your ad listing to the top of the results each week online</li><li>Your ad with be labelled \'featured and appear top of the listings above your competiton</li></ul>', '', '', '1437628077', 1, 'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}', 'ad', '12', null, 'Print Area Package 3', '7', 'Adult');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('119', '3411', '1');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('119', '3411', '2');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('119', '3411', '3');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('119', '3411', '4');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('119', '3411', '5');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('119', '3411', '6');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('119', '3411', '7');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('119', '3411', '8');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('119', '3411', '9');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('119', '3411', '10');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('119', '3411', '11');
INSERT INTO package_rule (`package_id`, `category_id`, `location_group_id`) VALUES ('119', '3411', '12');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('119', '11');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('119', '5');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('119', '8');
INSERT INTO package_upsell (`package_id`, `upsell_id`) VALUES ('119', '10');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('119', '1w', '100', '100', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('119', '2w', '150', '150', '1437628077');
INSERT INTO package_print (`package_id`, `duration`, `price`, `admin_price`, `created_at`) VALUES ('119', '4w', '240', '240', '1437628077');


