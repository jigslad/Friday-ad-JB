<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\lib\Migration;

use Fa\Bundle\AdBundle\Entity\AdJobs;

/**
 * Fa\Bundle\AdBundle\lib\Migration
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class Jobs
{

    private $meta_text;

    private $ad_id;

    private $data = array();


    public function __construct($metaText, $ad_id, $em)
    {
        $this->meta_text = $metaText;
        $this->ad_id     = $ad_id;
        $this->em = $em;
        $this->init();

    }

    public function init()
    {
        $string     = null;
        $this->data = array();

        if ($this->meta_text != "") {
            try {
                $string = simplexml_load_string($this->meta_text);
                libxml_use_internal_errors(true);
            } catch (\Exception $e) {
                return 0;
            }

            if (isset($string->JobsTransMeta->contractType)) {
                $this->data['contract_type_id'] =  $this->getContractTypeId((string) $string->JobsTransMeta->contractType);
            }
        }
    }

    private function getContractTypeId($string)
    {
        $string = trim($string);
        $cType  = array();

        $cType['Part-time']      = 2444;
        $cType['Full-time']      = 2445;
        $cType['Evenings']       = 2446;
        $cType['Evening']        = 2446;
        $cType['Weekend']        = 2447;
        $cType['Variable Hours'] = 2448;
        $cType['Temporary']      = 2449;
        $cType['Freelance']      = 2450;
        $cType['Home Working']   = 2451;
        $cType['Wanted']         = 2452;

        if (isset($cType[$string])) {
            return $cType[$string];
        }
    }

    public function update()
    {
        if (count($this->data) > 0) {
            $jobsRepository = $this->em->getRepository('FaAdBundle:AdJobs')->findOneBy(array('ad' => $this->ad_id));

            if (!$jobsRepository) {
                $jobsRepository = new AdJobs();
                $jobsRepository->setAd($this->em->getReference('FaAdBundle:Ad', $this->ad_id));
            }

            $jobsRepository->setContractTypeId($this->data['contract_type_id']);
            $this->em->persist($jobsRepository);
            echo "Dimension updated for ".$jobsRepository->getAd()->getId()."\n";

        }
    }
}
