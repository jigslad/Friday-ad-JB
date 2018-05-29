<?php
namespace Fa\Bundle\CoreBundle\Solr;

class BootStrapSolr
{
    /**
     * Solr connection instances for various cores
     *
     * @var array
     */
    private static $instance = array();

    /**
     * Solr connection options
     *
     * @var array
     */
    private $options;

    /**
     * Initialise solr connetoin options
     *
     * @param array $options  solr connection options
     *
     * @return void
     */
    public function __construct($options = array())
    {
        $this->options = $options;
    }

    /**
    * Method which is used to prevent multiple instance with Lucen connection.
    *
    * @return Object SolrClient
    */
    public function connect()
    {
        $key = str_replace('/', '', $this->options['path']);
        if (!isset(self::$instance[$key])) {
            self::$instance[$key] = new \SolrClient($this->options);
        }

        return self::$instance[$key];
    }

    /**
    * It is used to check whether the server solr is up and running or not
    *
    * @return boolean
    */
    public function ping()
    {
        try {
            $isPing           = false;
            $solrPingResponse = @$this->connect()->ping();

            if ($solrPingResponse) {
                $isPing = true;
            }

            return $isPing;
        } catch (SolrClientException $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Get solr connection options
     *
     * @return array
     */
    public function getSolrOptions()
    {
        return $this->options;
    }
}
