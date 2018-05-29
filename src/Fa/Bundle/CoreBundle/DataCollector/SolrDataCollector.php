<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\DataCollector;

use Fa\Bundle\CoreBundle\Logger\SolrLogger;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This interface is used for solr data collector.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SolrDataCollector extends DataCollector
{
    /**
     * @var SolrLogger
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param RedisLogger $logger
     */
    public function __construct(SolrLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = array(
            'commands' => null !== $this->logger ? $this->logger->getCommands() : array(),
        );
    }

    /**
     * Returns an array of collected commands.
     *
     * @return array
     */
    public function getCommands()
    {
        return $this->data['commands'];
    }

    /**
     * Returns the number of collected commands.
     *
     * @return integer
     */
    public function getCommandCount()
    {
        return count($this->data['commands']);
    }

    /**
     * Returns the execution time of all collected commands in seconds.
     *
     * @return float
     */
    public function getTime()
    {
        $time = 0;
        foreach ($this->data['commands'] as $command) {
            $time += $command['executionMS'];
        }

        return $time;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'solr';
    }
}
