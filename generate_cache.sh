#!/bin/sh

#if [ $# -ne 1 ] then
#USER="nginx"
#else
#USER=$1
#fi

php app/console fa:update:entity generate FaEntityBundle:Entity
php app/console fa:update:entity generate FaEntityBundle:Category
php app/console fa:update:entity generate FaEntityBundle:Location
php app/console fa:update:entity generate FaEntityBundle:Locality
php app/console fa:update:entity generate FaPaymentBundle:DeliveryMethodOption
php app/console fa:update:seo:rule:cache generate adp
php app/console fa:update:seo:rule:cache generate aia
php app/console fa:update:seo:rule:cache generate hp
php app/console fa:update:seo:rule:cache generate alp
php app/console fa:generate:category-cache-for-autosuggest
php app/console fa:update:config:rule:cache generate 10
php app/console fa:update:config:rule:cache generate 9
php app/console fa:update:config:rule:cache generate 4
php app/console fa:update:config:rule:cache generate 8
php app/console fa:update:banner:cache generate 1
php app/console fa:update:banner:cache generate 2
php app/console fa:update:banner:cache generate 3
php app/console fa:update:banner:cache generate 4