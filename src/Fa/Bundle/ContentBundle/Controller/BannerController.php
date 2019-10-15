<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Controller;

use Fa\Bundle\CoreBundle\Manager\AmazonS3ImageManager;
use GuzzleHttp\Client;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Fa\Bundle\ContentBundle\Repository\BannerRepository;
use Fa\Bundle\ContentBundle\Repository\BannerPageRepository;
use Fa\Bundle\ContentBundle\Repository\BannerZoneRepository;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\VarDumper\VarDumper;

/**
 * This controller is used for banner management.
 *
 * @author    Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version   1.0
 */
class BannerController extends CoreController
{
    /**
     * This action is used to send one click enquiry.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function getBannerByZoneAjaxAction($zoneId, Request $request)
    {
        $bannerCode = '';
        if (!empty($zoneId) && $zoneId > 0) {
            $bannersArray = $this->getRepository("FaContentBundle:Banner")->getBannersArrayByPage('ad_detail_page', $this->container);
            $bannerCode = $this->renderView('FaContentBundle:Banner:show.html.twig', array('zone_id' => $zoneId, 'bannersArray' => $bannersArray));
        }

        return new JsonResponse(array('bannerCode' => $bannerCode));
    }

    public function testS3ImageUploaderAction(Request $request)
    {
        $objAS3IM = AmazonS3ImageManager::getInstance($this->container);
        $uploadedImageUrl = "";
        $thumbnailUrls = [];
        $formObjS3ImageUpload = $this->createFormBuilder()
            ->add(
                'tests3name',
                TextType::class,
                ['attr' => ['class' => 'form-control'],
                 'constraints' => [
                     new Constraints\NotBlank(['message' => 'Please enter a text value.',]),
                 ],
                ]
            )
            ->add(
                'tests3image',
                FileType::class,
                ['attr' => ['class' => 'form-control-file'],
                 'constraints' => [
                     new Constraints\NotBlank(['message' => 'Please submit an image',]),
                     new Constraints\Image(['mimeTypes' => 'image/*', 'detectCorrupted' => true,]),
                 ],
                ]
            )
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Upload', 'attr' => ['class' => 'btn btn-info']]
            );
        $formObjS3ImageUpload->setAttribute('required', false);
        $formS3ImageUpload = $formObjS3ImageUpload->getForm();

        if ($request->getMethod() == "POST") {
            $reqData = $request->request->all();
            $reqDataFiles = $request->files->all();
            if (!isset($reqDataFiles['form']['tests3image']) || empty($reqDataFiles['form']['tests3image'])) {
                $this->addFlash('error', 'testing flash');
            }
            /** @var UploadedFile $testS3Image */
            // if ($formS3ImageUpload->isValid()) {
            // VarDumper::dump("if");
            $testS3Image = $reqDataFiles['form']['tests3image'];
            $imgRealpath = $testS3Image->getRealpath();
            $uploadedImageUrl = $objAS3IM->uploadImageToS3($imgRealpath, "uploads/image/testpath_" . time() . "." . $testS3Image->getClientOriginalExtension());
            VarDumper::dump($uploadedImageUrl);
            die;
            // } else {
            VarDumper::dump("else");
            $errors = $formS3ImageUpload->getErrors(true);
            VarDumper::dump($errors);
            die;
            // }
            VarDumper::dump("post end");
            die;
        }
        // $res = $objAS3IM->checkImageExistOnAws('testpath.jpg');
        // VarDumper::dump($res);
        // die;

        VarDumper::dump("end");
        if (!empty($uploadedImageUrl)) {
            $thumbnailUrls = $this->getThumbnailUrls($uploadedImageUrl);
        }

        return $this->render('FaContentBundle:Banner:testS3ImageUploader.html.twig', array(
            'formS3ImageUpload' => $formS3ImageUpload->createView(),
            'thumbnailUrls' => $thumbnailUrls,
            'uploadedImageUrl' => $uploadedImageUrl,
        ));
    }

    private function getThumbnailUrls($imageUrl)
    {
        $thumbnailUrls = [];
        $thumbnailUrls[] = $imageUrl;
        return $thumbnailUrls;
    }

    public function testGuzzleGetReqAction(Request $request)
    {
        // testing amazon lambda edit image using guzzle request. Working as expected.
        $imageRelpath = "uploads/image/17270501_17270600/adssadsadsa-17270593-3.jpg";
        $x = 92;
        $y = 14;
        $scale = 0.2083;
        $angle = 0;
        $successMsg = $error = "";
        $amazonAPIs = $this->container->getParameter("amazon.lambda.api");
        if (isset($amazonAPIs['s3UploadImagePython']) && isset($amazonAPIs['s3UploadImagePython']['url'])) {
            $amazonAPIUrl = $amazonAPIs['s3UploadImagePython']['url'];
            $amazonAPIUrl = "https://6eb1nr8na2.execute-api.eu-west-2.amazonaws.com/default/s3UploadImagePython";
            $apiKey = "9l0BEbIe2F8BXN8zAnrbG2yynzoYga9q49rZQG7m";
            try {
                $clientReqAPI = new Client();
                $resAPI = $clientReqAPI->request("GET", $amazonAPIUrl, [
                    'query' => [
                        'key' => $imageRelpath,
                        'x' => $x,
                        'y' => $y,
                        'scale' => $scale,
                        'angle' => $angle,
                    ],
                    'headers' => [
                        'x-api-key' => $apiKey,
                    ],
                ]);
                if ($resAPI->getStatusCode() == 200) {
                    $resJsonBody = $resAPI->getBody()->getContents();
                    $resArr = json_decode($resJsonBody, true);
                    print_r($resArr);
                    if (isset($resArr['error']) && $resArr['error'] == 0) {
                        $successMsg = $this->get('translator')->trans('Photo has been edited successfully.');
                    } else {
                        $error = $this->get('translator')->trans('Problem in croping photo.');
                    }
                } else {
                    $error = $this->get('translator')->trans('Problem in croping photo.');
                }
            } catch (\Exception $e) {
                VarDumper::dump($e->getMessage());
            }
        } else {
            $error = $this->get('translator')->trans('Image not cropped due to Lambda unavailability.');
        }
        print_r($successMsg);
        print_r($error);
        die;

        $client = new Client();
        $res = $client->request("GET", "https://www.google.com/search", [
            'query' => ['q' => "parrot"],
            // 'headers' => [CURLOPT_RETURNTRANSFER => true],
        ]);
        echo "<pre>";
        print_r($res->getStatusCode());
        // echo "<br>";
        // print_r($res->getHeaders());
        echo "<br>";
        print_r($res->getBody()->getContents());
        die;
    }
}
