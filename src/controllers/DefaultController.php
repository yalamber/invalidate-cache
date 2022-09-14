<?php

/**
 * Invalidate AWS Cache plugin for Craft CMS 3.x
 *
 * Plugin for invalidating AWS cache via AWS SDK
 *
 * @link      https://www.traffic.com.au/
 * @copyright Copyright (c) 2022 Traffic
 */

namespace traffic\invalidateawscache\controllers;

use traffic\invalidateawscache\InvalidateAwsCache;

use Craft;
use craft\web\Controller;
use craft\web\View;
use craft\helpers\App;

use Aws\CloudFront\CloudFrontClient;
use Aws\Exception\AwsException;

class DefaultController extends Controller
{
    protected $allowAnonymous = ['index', 'do-something'];

    public function actionInvalidate()
    {
        try {
            $cloudFrontClient = new CloudFrontClient([
                'version' => App::env('AWS_CLOUDFRONT_VERSION'),
                'region' => App::env('AWS_CLOUDFRONT_REGION'),
                'credentials' => [
                    'key' => App::env('AWS_CLOUDFRONT_KEY_ID'),
                    'secret' => App::env('AWS_CLOUDFRONT_SECRET'),
                ]
            ]);

            $result = $cloudFrontClient->createInvalidation([
                'DistributionId' => App::env('AWS_CLOUDFRONT_DISTRIBUTION_ID'),
                'InvalidationBatch' => [
                    'CallerReference' => uniqid(),
                    'Paths' => [
                        'Items' => ['/*'],
                        'Quantity' => 1,
                    ],
                ]
            ]);

            $message = '';

            if (isset($result['Location'])) {
                $message = 'The invalidation location is: ' .
                    $result['Location'];
            }

            $message .= ' and the effective URI is ' .
                $result['@metadata']['effectiveUri'] . '.';

            return $message;
        } catch (AwsException $e) {
            return 'Error: ' . "$e->getAwsErrorMessage()";
        }
    }

    public function actionIndex()
    {
        $this->actionInvalidate();

        return $this->renderTemplate(
            'invalidate-aws-cache/index.twig',
            array('message' => $this->actionInvalidate()),
            View::TEMPLATE_MODE_CP
        );
    }
}
