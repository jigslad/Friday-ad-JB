#!/bin/bash
DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
/usr/bin/php -d memory_limit="512M" $DIR/../../../../../bin/console fa:export-trovit-feed --category="Vehicles"
/usr/bin/php -d memory_limit="512M" $DIR/../../../../../bin/console fa:export-trovit-feed --category="Jobs"
/usr/bin/php -d memory_limit="512M" $DIR/../../../../../bin/console fa:export-trovit-feed --category="Products"
/usr/bin/php -d memory_limit="512M" $DIR/../../../../../bin/console fa:export-trovit-feed --category="Property"
