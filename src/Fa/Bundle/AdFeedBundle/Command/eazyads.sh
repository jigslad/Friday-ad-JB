#!/bin/bash
DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

/usr/bin/php -d memory_limit="512M" $DIR/../../../../../bin/console fa:export-ezy-ads-feed --category="Cars"  --last_days=7
/usr/bin/php -d memory_limit="512M" $DIR/../../../../../bin/console fa:export-ezy-ads-feed --category="OnlyCars"  --last_days=7
/usr/bin/php -d memory_limit="512M" $DIR/../../../../../bin/console fa:export-ezy-ads-feed --category="Forsale"  --last_days=7
/usr/bin/php -d memory_limit="512M" $DIR/../../../../../bin/console fa:export-ezy-ads-feed --category="Gardening"  --last_days=7
/usr/bin/php -d memory_limit="512M" $DIR/../../../../../bin/console fa:export-ezy-ads-feed --category="Pets"  --last_days=7
/usr/bin/php -d memory_limit="512M" $DIR/../../../../../bin/console fa:export-ezy-ads-feed --category="Property"  --last_days=7
