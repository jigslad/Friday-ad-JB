#!/bin/bash

DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

/usr/bin/php $DIR/../../../../../app/console fa:tradeit-ad:download
/usr/bin/php $DIR/../../../../../app/console fa:feed:parse --type="TradeIt" --site_id=1 image
/usr/bin/php $DIR/../../../../../app/console fa:feed:parse --type="TradeIt" --site_id=1 parse
/usr/bin/php $DIR/../../../../../app/console fa:feed:update add --type="TradeIt" --site_id=1
/usr/bin/php $DIR/../../../../../app/console fa:remove:tradeit-feed-ad delete