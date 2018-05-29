ALTER TABLE `category` ADD `no_index` TINYINT(1) NULL DEFAULT '0' , ADD `no_follow` TINYINT(1) NULL DEFAULT '0' ;
ALTER TABLE `static_page` ADD `no_index` TINYINT(1) NULL DEFAULT '0' , ADD `no_follow` TINYINT(1) NULL DEFAULT '0' ;
ALTER TABLE `landing_page` ADD `no_index` TINYINT(1) NULL DEFAULT '0' , ADD `no_follow` TINYINT(1) NULL DEFAULT '0' ;