<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryDimensionRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as FaEntityRepo;
use Gedmo\Sluggable\Util\Urlizer as Urlizer;

/**
 * This command is used to generate cache for categories used in auto suggest.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class GenerateEntityUrlKeysCommand extends ContainerAwareCommand
{

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:generate:entity-url-keys')
        ->setDescription("Generate url keys used in dimension matching")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('category_id', null, InputOption::VALUE_OPTIONAL, 'Category id', null)
        ->setHelp(
            <<<EOF

Actions:
- Generate url keys

Command:
 - php app/console fa:generate:entity-url-keys
 - php app/console fa:generate:entity-url-keys --category_id=XXX

EOF
        );
    }

    /**
     * Execute
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();

        $categoryId = $input->getOption('category_id', null);
        $entities   = $this->getEntityData($categoryId);

        $output->writeln('Total entities will be updated : '.count($entities), true);

        $i = 0;
        foreach ($entities as $entity) {
            echo '.';
            if ($entity->getCategoryDimension()->getCategory()) {
                $keys = $this->getParentPath($entity->getCategoryDimension()->getCategory(), $entity->getCategoryDimension()->getName());
                $entity->setUrlKeys($keys);
            }

            if ($entity->getCategoryDimension()->getId() == 165) {
                $entity->setSlug(Urlizer::urlize($entity->getName().' '.'Bedroom'));
            } else {
                $entity->setSlug(Urlizer::urlize($entity->getName()));
            }


            $this->getContainer()->get('doctrine')->getManager()->persist($entity);
            $this->em->getRepository('FaEntityBundle:Entity')->getSlugById($entity->getSlug(), $this->getContainer());

            if ($i % 100 == 0) {
                echo '+';
                $this->getContainer()->get('doctrine')->getManager()->flush();
            }

            $i++;
        }

        $this->getContainer()->get('doctrine')->getManager()->flush();
    }

    /**
     * Get entity data.
     *
     * @param integer $categoryId Category id.
     *
     */
    public function getEntityData($categoryId = null)
    {
        $qb = $this->em->getRepository('FaEntityBundle:Entity')->createQueryBuilder(FaEntityRepo::ALIAS)
                       ->leftJoin(FaEntityRepo::ALIAS.'.category_dimension', CategoryDimensionRepository::ALIAS)
                       ->andWhere(CategoryDimensionRepository::ALIAS.'.is_searchable= 1')
                       ->leftJoin(CategoryDimensionRepository::ALIAS.'.category', CategoryRepository::ALIAS);

        if ($categoryId) {
            $categoryPath = $this->getContainer()->get('doctrine')->getManager()->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $this->getContainer());
            $categorieIds = array_keys($categoryPath);

            $qb->andWhere(CategoryRepository::ALIAS.'.id IN (:category_id)')
               ->setParameter('category_id', $categorieIds);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get parent path.
     *
     * @param string $category
     *
     * @return string
     */
    public function getParentPath($category, $dimension = null)
    {
        $flag       = true;
        $urlKyes    = array();
        $skip       = array();

        if ($category->getId() == 8 && $dimension == 'Brand') {
            $skip = array(12);
        }

        $childrens = $category->getChildren();

        foreach ($childrens as $child) {
            if (!in_array($child->getId(), $skip)) {
                $urlKyes[] = $child->getFullSlug();
            }

            foreach ($child->getChildren() as $child2) {
                if (!in_array($child2->getId(), $skip)) {
                    $urlKyes[] = $child2->getFullSlug();
                }

                foreach ($child2->getChildren() as $child3) {
                    if (!in_array($child3->getId(), $skip)) {
                        $urlKyes[] = $child3->getFullSlug();
                    }

                    foreach ($child3->getChildren() as $child4) {
                        if (!in_array($child4->getId(), $skip)) {
                            $urlKyes[] = $child4->getFullSlug();
                        }

                        foreach ($child4->getChildren() as $child5) {
                            if (!in_array($child5->getId(), $skip)) {
                                $urlKyes[] = $child5->getFullSlug();
                            }

                            foreach ($child5->getChildren() as $child6) {
                                if (!in_array($child6->getId(), $skip)) {
                                    $urlKyes[] = $child6->getFullSlug();
                                }

                                foreach ($child5->getChildren() as $child6) {
                                    if (!in_array($child6->getId(), $skip)) {
                                        $urlKyes[] = $child6->getFullSlug();
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($category->getLvl() >= 1) {
            $cat = $category;

            while ($flag) {
                $urlKyes[] = $cat->getFullSlug();

                if (is_object($cat) && $cat->getLvl() == 1) {
                    $flag = false;
                } else {
                    $cat = $cat->getParent();
                }
            }
        }

        return implode("||", $urlKyes);
    }
}
