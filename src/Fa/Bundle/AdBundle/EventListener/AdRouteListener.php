<?php


/**
 * This file is part of the AdBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Fa\Bundle\AdBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as FaEntityRepo;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This event listener is used for decide location based route
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdRouteListener
{
    public $dimensionOrder = array();

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct($router, ContainerInterface $container)
    {
        $this->container = $container;
        $this->router = $router;
    }

    /**
     * match urls
     *
     * @param GetResponseEvent $event
     *
     * @return void|boolean
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        $request = $event->getRequest();
        $currentRoute = $event->getRequest()->get('_route');

        //ad_left_search_result
        if ($currentRoute == 'ad_search_result') {
            $topSearchParams = $request->get('fa_top_search');
            $routeManager = $this->container->get('fa_ad.manager.ad_routing');
            $url = $routeManager->getListingUrl($topSearchParams, $request->get('page'), true);
            $event->setResponse(new RedirectResponse($url));
        }
        elseif ($currentRoute == 'ad_left_search_result' || $currentRoute == 'shop_user_ad_left_search_result') {
            $routeManager = $this->container->get('fa_ad.manager.ad_routing');
            $url = $routeManager->getListingUrl($request->get('fa_left_search'), $request->get('page'), true);
            $event->setResponse(new RedirectResponse($url));
        }
        elseif ($currentRoute == 'ad_left_search_dimension_result') {
            $routeManager = $this->container->get('fa_ad.manager.ad_routing');
            $url = $routeManager->getListingUrl($request->get('fa_left_search_dimension'), $request->get('page'), true);
            $event->setResponse(new RedirectResponse($url));
        }
        elseif ($currentRoute == 'ad_landing_page_search_result') {
            $routeManager = $this->container->get('fa_ad.manager.ad_routing');
            if ($request->get('fa_landing_page_property_search') && count($request->get('fa_landing_page_property_search'))) {
                $url = $routeManager->getListingUrl($request->get('fa_landing_page_property_search'), $request->get('page'));
                $event->setResponse(new RedirectResponse($url));
            }
            elseif ($request->get('fa_landing_page_jobs_search') && count($request->get('fa_landing_page_jobs_search'))) {
                $url = $routeManager->getListingUrl($request->get('fa_landing_page_jobs_search'), $request->get('page'));
                $event->setResponse(new RedirectResponse($url));
            }
            elseif ($request->get('fa_landing_page_adult_search') && count($request->get('fa_landing_page_adult_search'))) {
                $adultSearchParams = $request->get('fa_landing_page_adult_search');
                if(!is_array($adultSearchParams)){
                    $adultSearchParams = array_map('trim', $adultSearchParams);
                }
                $adultSearchParams = array_filter($adultSearchParams);
                $url = $routeManager->getListingUrl($adultSearchParams, $request->get('page'));
                $event->setResponse(new RedirectResponse($url));
            }
            elseif ($request->get('fa_landing_page_car_search') && count($request->get('fa_landing_page_car_search'))) {
                $carSearchParams = $request->get('fa_landing_page_car_search');
                if (isset($carSearchParams['item_motors__body_type_id'])) {
                    if ($carSearchParams['item_motors__body_type_id'] == EntityRepository::THREE_DOOR_HATCHBACK_ID) {
                        $carSearchParams['item_motors__body_type_id'] = array(EntityRepository::THREE_DOOR_HATCHBACK_ID, EntityRepository::FIVE_DOOR_HATCHBACK_ID);
                    } elseif ($carSearchParams['item_motors__body_type_id'] == EntityRepository::TWO_DOOR_SALOON_ID) {
                        $carSearchParams['item_motors__body_type_id'] = array(EntityRepository::TWO_DOOR_SALOON_ID, EntityRepository::FOUR_DOOR_SALOON_ID);
                    }
                }
                $url = $routeManager->getListingUrl($carSearchParams, $request->get('page'));
                $event->setResponse(new RedirectResponse($url));
            }
            elseif ($request->get('fa_adult_home_page_search') && count($request->get('fa_adult_home_page_search'))) {
                $adultSearchParams = $request->get('fa_adult_home_page_search');
                if(!is_array($adultSearchParams)){
                    $adultSearchParams = array_map('trim', $adultSearchParams);
                }
                $adultSearchParams = array_filter($adultSearchParams);
                $url = $routeManager->getListingUrl($adultSearchParams, $request->get('page'));
                $event->setResponse(new RedirectResponse($url));
            }
        }
    }

    /**
     * Checck for landing page redirect on top search basic only.
     *
     * @param GetResponseEvent $event
     *
     */
    private function checkAndGetLandingPageUrl(GetResponseEvent $event)
    {
        $request               = $event->getRequest();
        $routeManager          = $this->container->get('fa_ad.manager.ad_routing');
        $landingPageCategories = array(CategoryRepository::FOR_SALE_ID, CategoryRepository::MOTORS_ID, CategoryRepository::PROPERTY_ID, CategoryRepository::ANIMALS_ID);
        $topSearchParams       = $request->get('fa_top_search');
        $landingPageParams     = array();

        if (isset($topSearchParams['item__category_id']) && in_array($topSearchParams['item__category_id'], $landingPageCategories)) {
            $landingPageParams['item__category_id'] = $topSearchParams['item__category_id'];

            if (isset($topSearchParams['item__location']) && $topSearchParams['item__location']) {
                $landingPageParams['item__location'] = $topSearchParams['item__location'];
            }

            if (isset($topSearchParams['item__location_autocomplete']) && $topSearchParams['item__location_autocomplete']) {
                $landingPageParams['item__location_autocomplete'] = $topSearchParams['item__location_autocomplete'];
            }

            if (isset($topSearchParams['item__distance']) && $topSearchParams['item__distance']) {
                $landingPageParams['item__distance'] = $topSearchParams['item__distance'];
            } else {
                $getDefaultRadius = $routeManager->getDefaultRadiusBySearchParams($topSearchParams, $this->container);
                $landingPageParams['item__distance'] = ($getDefaultRadius)?$getDefaultRadius:CategoryRepository::MAX_DISTANCE;
            }

            // Compare array for basic search with category and location only, if both same then redirect to landing page.
            $topSearchParams = array_filter($topSearchParams);
            $finalParams     = array_diff($topSearchParams, $landingPageParams);

            if (empty($finalParams)) {
                return $routeManager->getCategoryLandingPageUrl($landingPageParams);
            }
        }

        return null;
    }
}
