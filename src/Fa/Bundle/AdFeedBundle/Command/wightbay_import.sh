#!/bin/bash

DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

/usr/bin/php $DIR/../../../../../bin/console fa:wightbay-ad:download
/usr/bin/php $DIR/../../../../../bin/console fa:feed:parse --type="Wightbay" --site_id=2 image
/usr/bin/php $DIR/../../../../../bin/console fa:feed:parse --type="Wightbay" --site_id=2 parse
/usr/bin/php $DIR/../../../../../bin/console fa:feed:update add --type="Wightbay" --site_id=2
/usr/bin/php $DIR/../../../../../bin/console fa:remove:wightbay-feed-ad delete