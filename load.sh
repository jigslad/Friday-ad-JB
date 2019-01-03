sudo -u apache php bin/console doctrine:database:drop --force
sudo -u apache php bin/console doctrine:database:create
sudo -u apache php bin/console doctrine:schema:update --force
sudo -u apache php bin/console doctrine:fixtures:load
sudo -u apache php bin/console assets:install web --symlink
sudo -u apache php bin/console assetic:dump

sudo -u apache php bin/console lexik:translations:import FaUserBundle
sudo -u apache php bin/console lexik:translations:import FaPromotionBundle
sudo -u apache php bin/console lexik:translations:import FaPaymentBundle
sudo -u apache php bin/console lexik:translations:import FaContentBundle
sudo -u apache php bin/console lexik:translations:import FaEntityBundle
sudo -u apache php bin/console lexik:translations:import FaAdBundle

sudo -u apache php bin/console cache:clear --env=dev
sudo -u apache php bin/console cache:clear --env=prod

cd migration/
php import.php import:category
php import.php import:location
php import.php import:paa-fields
php import.php import:location-groups
php import.php import:category-dimension
php import.php import:upsell
php import.php import:user
php import.php import:ad
php import.php import:user-review
