sudo -u nginx php app/console doctrine:database:drop --force
sudo -u nginx php app/console doctrine:database:create

sudo -u nginx php app/console doctrine:schema:update --force
sudo -u nginx php app/console doctrine:fixtures:load
sudo -u nginx php app/console assets:install web --symlink
sudo -u nginx php app/console assetic:dump
sudo -u nginx php app/console cache:clear --env=dev
sudo -u nginx php app/console cache:clear --env=prod

sudo -u nginx php app/console lexik:translations:import FaUserBundle
sudo -u nginx php app/console lexik:translations:import FaPromotionBundle
sudo -u nginx php app/console lexik:translations:import FaPaymentBundle
sudo -u nginx php app/console lexik:translations:import FaContentBundle
sudo -u nginx php app/console lexik:translations:import FaEntityBundle
sudo -u nginx php app/console lexik:translations:import FaAdBundle

cd migration/
sudo -u nginx php import.php import:category
sudo -u nginx php import.php import:location
sudo -u nginx php import.php import:paa-fields
sudo -u nginx php import.php import:ad
sudo -u nginx php import.php import:location-groups
sudo -u nginx php import.php import:category-dimension