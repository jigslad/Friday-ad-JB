#!/bin/bash
modified_since=''
while getopts ":m:t:" opt; do
  case $opt in
    m)
      modified_since="--modified_since=${OPTARG}"
      ;;
    t)
      type="--type=${OPTARG}"
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      ;;
  esac
done
DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
/usr/bin/php -d memory_limit="512M" $DIR/../../../../../bin/console fa:feed:download add $type --site_id=10 $modified_since
/usr/bin/php -d memory_limit="512M" $DIR/../../../../../bin/console fa:feed:parse $type --site_id=10 parse
/usr/bin/php -d memory_limit="512M" $DIR/../../../../../bin/console fa:feed:update add $type
/usr/bin/php -d memory_limit="512M" $DIR/../../../../../bin/console fa:feed:update add $type --force=remap --status="R"
