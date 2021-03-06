<?php
/**
 * File: HotspotController.php
 * Date: 12/03/14
 * Created by Jérémy BOUNY & Arnaud CHALIEZ.
 * Project: hotspotmap
 */

namespace HotspotMap\Controller;

use HotspotMap\CoreDomain\Entity\Hotspot;
use HotspotMap\CoreDomain\ValueObject\Address;
use HotspotMap\CoreDomain\ValueObject\PlaceIdentity;
use HotspotMap\CoreDomain\ValueObject\Price;
use HotspotMap\CoreDomain\ValueObject\Schedule;
use HotspotMap\CoreDomain\ValueObject\SocialInformation;
use HotspotMap\CoreDomainBundle\Specification\ValueSpecification;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HotspotController
{
    protected $hotspotRepository;

    public function __construct($repository)
    {
        $this->hotspotRepository = $repository;
    }

    public function hotspotsAction(Request $request, Application $app)
    {
        return $app['helper.response']->handle($this->hotspotRepository->findAll(), 'Hotspot/hotspots.html');
    }

    public function valideHotspotsAction(Request $request, Application $app)
    {
        $hotspots = $this->hotspotRepository->findSatisfying(new ValueSpecification('getStatus', Status::Validate));

        if (null !== $hotspots) {
            return $app['helper.response']->handle($hotspots, 'Hotspot/hotspots.html');
        }
        else {
            return $app['helper.response']->handle('no valid hotspots found', 'error.html', 404);
        }
    }

    public function waitingHotspotsAction(Request $request, Application $app)
    {
        $hotspots = $this->hotspotRepository->findSatisfying(new ValueSpecification('getStatus', Status::Waiting));

        if (null !== $hotspots) {
            return $app['helper.response']->handle($hotspots, 'Hotspot/hotspots.html');
        }
        else {
            return $app['helper.response']->handle('no valid hotspots found', 'error.html', 404);
        }
    }

    public function showAction(Request $request, Application $app, $hotspotId)
    {
        $hotspot = $this->hotspotRepository->find($hotspotId);
        if (null !== $hotspot) {
            return $app['helper.response']->handle($hotspot, 'Hotspot/hotspot.html');
        }
        else {
            return $app['helper.response']->handle('hotspot not found', 'error.html', 404);
        }
    }

    public function findByNameAction(Request $request, Application $app, $hotspotName)
    {
        $hotspots = $this->hotspotRepository->findSatisfying(new ValueSpecification('getName', $hotspotName));

        if (null !== $hotspots) {
            return $app['helper.response']->handle($hotspots, 'Hotspot/hotspots.html');
        }
        else {
            return $app['helper.response']->handle('hotspot not found', 'error.html', 404);
        }
    }

    public function addAction(Request $request, Application $app)
    {
        $hotspot = $this->createHotspotFromRequest($request, $app);

        if ($hotspot) {
            if ($this->hotspotRepository->add($hotspot)) {
                return $app['helper.response']->handle($hotspot, 'Hotspot/hotspot.html', 201);
            }

            return $app['helper.response']->handle('error adding hotspot', 'error.html', 400);
        }

        return $app['helper.response']->handle('error creating hotspot', 'error.html', 400);
    }

    public function updateAction(Request $request, Application $app)
    {
        //todo
    }

    public function deleteAction($id)
    {
        $hotspot = $this->hotspotRepository->findSatisfying(new ValueSpecification('getId', $id));
        return new Response($this->hotspotRepository->remove($hotspot));
    }

    protected function createHotspotFromRequest(Request $request, Application $app)
    {
        $hotspot = null;
        {
            $price = 0;
            $equipments = $facebook = $twitter = $description = $thumbnail = null;

            extract($request->request->all());

            if ( isset($name) && isset($street) && isset($city) && isset($postalCode) && isset($country))
            {

                // Create data transfert objects
                $addressDTO = new \HotspotMap\CoreDomain\DTO\Address($street, $city, $postalCode, $country);
                $priceDTO = new \HotspotMap\CoreDomain\DTO\Price($price);

                // Validate them
                if ($app['validator']->validate($addressDTO) &&
                    $app['validator']->validate($priceDTO)) {
                    $hotspot = new Hotspot(
                        new PlaceIdentity($name, $description, $thumbnail),
                        $addressDTO->toValueObject(),
                        $priceDTO->toValueObject(),
                        new Schedule(),
                        array($equipments),
                        new SocialInformation($facebook, $twitter)
                    );
                }
            }
        }
        return $hotspot;
    }
} 