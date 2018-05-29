#!/bin/bash
date

/usr/bin/php /web/friday-ad.co.uk/app/console fa:generate:sitemap

time sudo -u nginx rsync -avzhe ssh --exclude=data --exclude=web/bundles --exclude=web/uploads --exclude=app/cache --exclude=app/logs --exclude=app/cache_ --exclude=data_ --exclude=web/uploads_ --exclude=web/bundles_ --exclude=app/cache__ --exclude=.ssh --exclude=.git --exclude=app/cache_local --delete /web/friday-ad.co.uk/web/sitemap.xml nginx@www2:/web/friday-ad.co.uk/web/sitemap.xml
time sudo -u nginx rsync -avzhe ssh --exclude=data --exclude=web/bundles --exclude=web/uploads --exclude=app/cache --exclude=app/logs --exclude=app/cache_ --exclude=data_ --exclude=web/uploads_ --exclude=web/bundles_ --exclude=app/cache__ --exclude=.ssh --exclude=.git --exclude=app/cache_local --delete /web/friday-ad.co.uk/web/sitemap.xml nginx@www3:/web/friday-ad.co.uk/web/sitemap.xml
time sudo -u nginx rsync -avzhe ssh --exclude=data --exclude=web/bundles --exclude=web/uploads --exclude=app/cache --exclude=app/logs --exclude=app/cache_ --exclude=data_ --exclude=web/uploads_ --exclude=web/bundles_ --exclude=app/cache__ --exclude=.ssh --exclude=.git --exclude=app/cache_local --delete /web/friday-ad.co.uk/web/sitemap.xml nginx@www4:/web/friday-ad.co.uk/web/sitemap.xml
time sudo -u nginx rsync -avzhe ssh --exclude=data --exclude=web/bundles --exclude=web/uploads --exclude=app/cache --exclude=app/logs --exclude=app/cache_ --exclude=data_ --exclude=web/uploads_ --exclude=web/bundles_ --exclude=app/cache__ --exclude=.ssh --exclude=.git --exclude=app/cache_local --delete /web/friday-ad.co.uk/web/sitemap.xml nginx@www5:/web/friday-ad.co.uk/web/sitemap.xml
time sudo -u nginx rsync -avzhe ssh --exclude=data --exclude=web/bundles --exclude=web/uploads --exclude=app/cache --exclude=app/logs --exclude=app/cache_ --exclude=data_ --exclude=web/uploads_ --exclude=web/bundles_ --exclude=app/cache__ --exclude=.ssh --exclude=.git --exclude=app/cache_local --delete /web/friday-ad.co.uk/web/sitemap.xml nginx@www6:/web/friday-ad.co.uk/web/sitemap.xml
time sudo -u nginx rsync -avzhe ssh --exclude=data --exclude=web/bundles --exclude=web/uploads --exclude=app/cache --exclude=app/logs --exclude=app/cache_ --exclude=data_ --exclude=web/uploads_ --exclude=web/bundles_ --exclude=app/cache__ --exclude=.ssh --exclude=.git --exclude=app/cache_local --delete /web/friday-ad.co.uk/web/sitemap.xml nginx@lb1:/web/friday-ad.co.uk/web/sitemap.xml
time sudo -u nginx rsync -avzhe ssh --exclude=data --exclude=web/bundles --exclude=web/uploads --exclude=app/cache --exclude=app/logs --exclude=app/cache_ --exclude=data_ --exclude=web/uploads_ --exclude=web/bundles_ --exclude=app/cache__ --exclude=.ssh --exclude=.git --exclude=app/cache_local --delete /web/friday-ad.co.uk/web/sitemap.xml nginx@lb2:/web/friday-ad.co.uk/web/sitemap.xml
time sudo -u nginx rsync -avzhe ssh --exclude=data --exclude=web/bundles --exclude=web/uploads --exclude=app/cache --exclude=app/logs --exclude=app/cache_ --exclude=data_ --exclude=web/uploads_ --exclude=web/bundles_ --exclude=app/cache__ --exclude=.ssh --exclude=.git --exclude=app/cache_local --delete /web/friday-ad.co.uk/web/sitemap.xml nginx@script:/web/friday-ad.co.uk/web/sitemap.xml
date



