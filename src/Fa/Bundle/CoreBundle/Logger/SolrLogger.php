<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Logger;

// use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Psr\Log\LoggerInterface;

/**
 * This interface is used for solr data collector.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SolrLogger
{
    protected $logger;
    protected $nbCommands = 0;
    protected $commands = array();
    protected $start;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger A LoggerInterface instance
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Logs a command
     *
     * @param string      $command    Solr command
     * @param float       $duration   Duration in milliseconds
     * @param string      $connection Connection alias
     * @param bool|string $error      Error message or false if command was successful
     */
    public function logCommand($command, $duration, $connection, $error = false)
    {
        ++$this->nbCommands;

        if (null !== $this->logger) {
            $this->commands[] = array('cmd' => $command, 'executionMS' => $duration, 'conn' => $connection, 'error' => $error);
            if ($error) {
                $this->logger->err('Command "' . $command . '" failed (' . $error . ')');
            } else {
                $this->logger->info('Executing command "' . $command . '"');
            }
        }
    }

    /**
     * Returns the number of logged commands.
     *
     * @return integer
     */
    public function getNbCommands()
    {
        return $this->nbCommands;
    }

    /**
     * Returns an array of the logged commands.
     *
     * @return array
     */
    public function getCommands()
    {
        return $this->commands;
    }
}
