<?php
namespace Fa\Bundle\CoreBundle\Manager;

use Jaybizzle\CrawlerDetect\CrawlerDetect;

/**
 * Fa\Bundle\CoreBundle\Manager\CacheManager
 *
 * This manager is used to set/get/remove cache.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class BotManager
{
    protected $crawler;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->crawler = new CrawlerDetect;
    }

    /**
     * This method is used to check whether the request is coming from bots or not.
     *
     * @param $container Container identifier.
     *
     * @return boolean
     */
    public function isBot($userAgent)
    {
        try {
            if ($this->crawler->isCrawler($userAgent)) {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }
}
