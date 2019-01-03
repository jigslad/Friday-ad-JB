#!/bin/sh

#if [ $# -ne 1 ] then
#USER="nginx"
#else
#USER=$1
#fi

php bin/console fa:update:entity generate FaEntityBundle:Entity
php bin/console fa:update:entity generate FaEntityBundle:Category
php bin/console fa:update:entity generate FaEntityBundle:Location
php bin/console fa:update:entity generate FaEntityBundle:Locality
php bin/console fa:update:entity generate FaPaymentBundle:DeliveryMethodOption
php bin/console fa:update:seo:rule:cache generate adp
php bin/console fa:update:seo:rule:cache generate aia
php bin/console fa:update:seo:rule:cache generate hp
php bin/console fa:update:seo:rule:cache generate alp
php bin/console fa:generate:category-cache-for-autosuggest
php bin/console fa:update:config:rule:cache generate 10
php bin/console fa:update:config:rule:cache generate 9
php bin/console fa:update:config:rule:cache generate 4
php bin/console fa:update:config:rule:cache generate 8
php bin/console fa:update:banner:cache generate 1
php bin/console fa:update:banner:cache generate 2
php bin/console fa:update:banner:cache generate 3
php bin/console fa:update:banner:cache generate 4