<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Fa\Bundle\EntityBundle\Entity\Category;

/**
 * This controller is used for
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class LoadCategoryData extends AbstractFixture implements OrderedFixtureInterface
{

    /**
     * Load fixture.
     *
     * @param ObjectManager $em object.
     */
    public function load(ObjectManager $em)
    {
        return false; // we will not use this now importing data

        $row = 1;
        $categoryObjArr = array();
        $batchSize = 100;
        if (($handle = fopen(__DIR__."/categories.csv", "r")) !== false) {
            while (($data = fgetcsv($handle, 8098, ';')) !== false) {
                $num = count($data);
                for ($c=0; $c < $num; $c++) {
                    //Create a string of category name upto last child- $categoryNameStr.
                    //md5($categoryNameStr) this string will be stored in array as key which holds the category object name through which we insert category.
                    //This array will be used in finding parent category object and deciding whether to make category entry or not
                    $categoryNameStr = '';
                    for ($i=0; $i<=$c; $i++) {
                        $categoryNameStr .= $data[$i];
                    }

                    //If current category is not empty and md5($categoryNameStr) key is not exists in $categoryObjArr
                    //then make category entry in db and store the $categoryObj in $categoryObjArr[md5($categoryNameStr)]
                    $data[$c] = trim($data[$c]);
                    if ($data[$c] != "" && !array_key_exists(md5($categoryNameStr), $categoryObjArr)) {
                        $categoryObj  = 'cell_'.$row.($c+1);

                        if (!isset($$categoryObj)) {
                            $$categoryObj = new Category();
                            $$categoryObj->setName(ucwords(strtolower($data[$c])));

                            $slug = str_replace(' and ', ' ', $data[$c]);
                            $$categoryObj->setSlug($slug);
                            $$categoryObj->setStatus(1);
                            if ($row != 1) {
                                //Create a string of category name upto parent category of current category.
                                //Convert it to md5 and find the parent object from the $categoryObjArr using this md5 string as key.
                                $parentCategoryNameStr = '';
                                for ($j=0; $j<$c; $j++) {
                                    $parentCategoryNameStr .= $data[$j];
                                }

                                echo $data[$c];
                                $$categoryObj->setParent($$categoryObjArr[md5($parentCategoryNameStr)]);
                            }

                            $em->persist($$categoryObj);
                            if (($row % $batchSize) == 0) {
                                $em->flush();
                                //$em->clear();
                            }


                            //$em->clear();

                            $categoryObjArr[md5($categoryNameStr)] = $categoryObj;
                        }
                    }
                }
                $row++;
            }

            $em->flush();
            fclose($handle);
        }
    }

    /**
     * Get order of fixture.
     *
     * @return integer
     */
    public function getOrder()
    {
        return 1; // the order in which fixtures will be loaded
    }
}
