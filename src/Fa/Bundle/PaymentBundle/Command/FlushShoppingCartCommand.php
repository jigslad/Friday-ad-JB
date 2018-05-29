<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PaymentBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PaymentBundle\Repository\CartRepository;

/**
 * This command is used to remove unwanted data from shopping cart.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class FlushShoppingCartCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:flush:shopping-cart')
        ->setDescription("Check and removed inactive and older cart")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('cartCode', null, InputOption::VALUE_OPTIONAL, 'Cart code', null)
        ->addOption('olderThan', null, InputOption::VALUE_OPTIONAL, 'remove shopping cart data older then x day/week/month', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at regular intervals.

Actions:
- Check and removed inactive and older cart

Command:
 - php app/console fa:flush:shopping-cart
 - php app/console fa:flush:shopping-cart --cartCode="xxxx"
 - php app/console fa:flush:shopping-cart --cartCode="xxxx" --olderThan='1w'

EOF
        );
    }

    /**
     * Execute.
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $searchParam = array();

        //get options passed in command
        $cartCodes  = $input->getOption('cartCode');
        $olderThan  = $input->getOption('olderThan');
        $offset     = $input->getOption('offset');

        if (!$olderThan) {
            $olderThan = '1w';
        }

        if ($cartCodes) {
            $cartCodes = explode(',', $cartCodes);
            $cartCodes = array_map('trim', $cartCodes);
            $searchParam['cart']['cart_code'] = $cartCodes;
        } else {
            // set updated at
            $time = CommonManager::getTimeFromDuration($olderThan, null, '-');
            $searchParam['cart']['updated_at_from_to'] =  '|'.$time;
        }

        if (isset($offset)) {
            $this->flushShoppingCartWithOffset($searchParam, $input, $output);
        } else {
            $this->flushShoppingCart($searchParam, $input, $output);
        }
    }

    /**
     * Flush shopping cart data with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function flushShoppingCartWithOffset($searchParam, $input, $output)
    {
        $removedCartCode = array();
        $qb              = $this->getCartQueryBuilder($searchParam);
        $step            = 1000;
        $offset          = 0;

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $carts = $qb->getQuery()->getResult();

        foreach ($carts as $cart) {
            $removedCartCode[] = $cart->getCartCode();
            $this->getContainer()->get('doctrine')->getManager()->remove($cart);
        }

        $this->getContainer()->get('doctrine')->getManager()->flush();
        $this->getContainer()->get('doctrine')->getManager()->clear();

        $removedCartCode = implode(", ", $removedCartCode);

        $output->writeln('Removed Cart: '.$removedCartCode, true);

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Flush shopping cart data with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function flushShoppingCart($searchParam, $input, $output)
    {
        $count     = $this->getCartCount($searchParam);
        $step      = 1000;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        for ($i = 0; $i <= $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $step);
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value) {
                    $commandOptions .= ' --'.$option.'="'.$value.'"';
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:flush:shopping-cart '.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    /**
     * Get query builder for shopping cart.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getCartQueryBuilder($searchParam)
    {
        $entityManager         = $this->getContainer()->get('doctrine')->getManager();
        $adModerateRepository  = $entityManager->getRepository('FaPaymentBundle:Cart');

        $data                  = array();
        $data['query_filters'] = $searchParam;
        $data['query_sorter']  = array('cart' => array ('created_at' => 'asc'));

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($adModerateRepository, $data);

        $qb = $searchManager->getQueryBuilder();

        if (!isset($searchParam['cart']['cart_code'])) {
            $qb->orWhere(CartRepository::ALIAS.'.status = 0');
        }

        return $qb;
    }

    /**
     * Get count for cart.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getCartCount($searchParam)
    {
        $qb = $this->getCartQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
