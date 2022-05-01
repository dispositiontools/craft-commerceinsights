<?php
/**
 * Commerceinsights plugin for Craft CMS 3.x
 *
 * Get the low down on how your Commerce is doing
 *
 * @link      http://www.disposition.tools/
 * @copyright Copyright (c) 2021 Disposition Tools
 */

namespace dispositiontools\commerceinsights\controllers;

use dispositiontools\commerceinsights\Commerceinsights;

use Craft;
use craft\web\Controller;
use craft\helpers\UrlHelper;
use craft\helpers\DateTimeHelper;
/**
 * Cp Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Disposition Tools
 * @package   Commerceinsights
 * @since     1.0.0
 */
class CpController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = false;

    // Public Methods
    // =========================================================================



    /**
     * Handle a request going to our plugin's actionCustomersSummary URL,
     * e.g.: actions/commerceinsights/cp/customers-summary
     *
     * @return mixed
     */
    public function actionCustomersSummary()
    {

        $cutomers = Commerceinsights::$plugin->customers->getCustomers(null);
        return $this->renderTemplate(
          'commerceinsights/_cp/customers_summary',
          [
              'customers'            => $cutomers['customers'],

          ]
        );
    }

    /**
     * Handle a request going to our plugin's actionCustomers URL,
     * e.g.: actions/commerceinsights/cp/customers
     *
     * @return mixed
     */
    public function actionCustomers()
    {

        $cutomers = Commerceinsights::$plugin->customers->getCustomers(null);
        return $this->renderTemplate(
          'commerceinsights/_cp/customers_summary',
          [
              'customers'            => $cutomers['customers'],

          ]
        );
    }


    /**
     * Handle a request going to our plugin's actionCustomersRfm URL,
     * e.g.: actions/commerceinsights/cp/customers-rfm
     *
     * @return mixed
     */
    public function actionCustomersRfm()
    {

        $frequency  = Craft::$app->request->getQueryParam('frequency', false);
        $monetary = Craft::$app->request->getQueryParam('monetary', false);
        $recency = Craft::$app->request->getQueryParam('recency', false);

        $options = [
          'frequency' => $frequency,
          'monetary' => $monetary,
          'recency' => $recency,

        ];

        $cutomers = Commerceinsights::$plugin->customers->getCustomersRfm($options);
        return $this->renderTemplate(
          'commerceinsights/_cp/customers_rfm',
          [
              'customers'            => $cutomers['customers'],

          ]
        );
    }



    /**
     * Handle a request going to our plugin's actionTransactions URL,
     * e.g.: actions/commerceinsights/cp/transactions
     *
     * @return mixed
     */
    public function actionTransactions()
    {

        $startDate = Craft::$app->request->getQueryParam('startDate', false);
        $endDate = Craft::$app->request->getQueryParam('endDate',false);
        $transactionStatus = Craft::$app->request->getQueryParam('status',false);

        if(! $startDate)
        {
          $startDate = date("Y-m-")."01";
        }

        if(! $endDate)
        {
          $endDate = date("Y-m-d");
        }
        $startDateObject = DateTimeHelper::toDateTime($startDate);
        $endDateObject = DateTimeHelper::toDateTime($endDate);


        $options =[];
        if($startDate)
        {
            $options['afterDate'] = $startDate;
        }
        if($endDate)
        {
          $options['beforeDate'] = $endDate;
        }
        if($transactionStatus)
        {
          $options['transactionStatus']  = $transactionStatus;
        }

        $transactionDetails  = Commerceinsights::$plugin->transactions->getTransactions($options);

        return $this->renderTemplate(
          'commerceinsights/_cp/transactions',
          [
              'startDate'      => $startDateObject,
              'endDate'        => $endDateObject,
              'transactions'      => $transactionDetails['transactions'],
              'stats'         => $transactionDetails['stats']
          ]
        );
    }

    /**
     * Handle a request going to our plugin's actionProducts URL,
     * e.g.: actions/commerceinsights/cp/products
     *
     * @return mixed
     */
    public function actionProducts()
    {
        $startDate = date("Y-m-")."01";
        $endDate = date("Y-m-d");
        if( ! $startDate)
        {
          $startDate = date("Y-m-")."01";
        }

        if( ! $endDate)
        {
          $endDate = date("Y-m-d");
        }
        $startDateObject = DateTimeHelper::toDateTime($startDate);
        $endDateObject = DateTimeHelper::toDateTime($endDate);

        return $this->renderTemplate(
          'commerceinsights/_cp/products',
          [
            'startDate'      => $startDateObject,
            'endDate'        => $endDateObject,
          ]
        );
    }

    /**
     * Handle a request going to our plugin's actionProducttypes URL,
     * e.g.: actions/commerceinsights/cp/producttypes
     *
     * @return mixed
     */
    public function actionProducttypes()
    {

        return $this->renderTemplate(
          'commerceinsights/_cp/product_types'
        );
    }


    /**
     * Handle a request going to our plugin's actionProducttype URL,
     * e.g.: actions/commerceinsights/cp/producttype
     *
     * @return mixed
     */
    public function actionProducttype($productTypeSlug)
    {
        $productTypeDetails = Commerceinsights::$plugin->products->getProductsByProductType($productTypeSlug);

        return $this->renderTemplate(
          'commerceinsights/_cp/product_type',
          [
              'products'            => $productTypeDetails['products'],
              'productType'         => $productTypeDetails['productType']


          ]
        );
    }

    /**
     * Handle a request going to our plugin's actionProduct URL,
     * e.g.: actions/commerceinsights/cp/product
     *
     * @return mixed
     */
    public function actionProduct($productId)
    {
        $productDetails = Commerceinsights::$plugin->products->getPurchasesByProduct($productId);

        return $this->renderTemplate(
          'commerceinsights/_cp/product',
          [
              'productId'      => $productId,
              'purchases'      => $productDetails['purchases'],
              'totals'         => $productDetails['totals']
          ]
        );
    }

    /**
     * Handle a request going to our plugin's actionGotoPage URL,
     * e.g.: actions/commerceinsights/cp/goto-page
     *
     * @return mixed
     */
    public function actionGotoPage()
    {
      $startDate      = Craft::$app->request->post('startDate');
      $endDate        = Craft::$app->request->post('endDate');
      $pageUrl        = Craft::$app->request->post('pageUrl');

      $dates = [
        'startDate'=> $startDate['date'],
        'endDate'=>$endDate['date']
      ];
      $pageUrl = "commerceinsights/products/purchases";
      UrlHelper::cpUrl($pageUrl);

      $redirectUrl = $pageUrl."?startDate=".$startDate['date'];

      return $this->redirectToPostedUrl($dates);
      return $this->redirect($redirectUrl);
    }


    /**
     * Handle a request going to our plugin's actionProductsPurchasesByOrderDates URL,
     * e.g.: actions/commerceinsights/cp/products-purchases-by-order-dates
     *
     * @return mixed
     */
    public function actionProductsPurchasesByOrderDates()
    {

        $startDate = Craft::$app->request->getQueryParam('startDate', false);
        $endDate = Craft::$app->request->getQueryParam('endDate',false);

        if(! $startDate)
        {
          $startDate = date("Y-m-")."01";
        }

        if(! $endDate)
        {
          $endDate = date("Y-m-d");
        }
        $startDateObject = DateTimeHelper::toDateTime($startDate);
        $endDateObject = DateTimeHelper::toDateTime($endDate);

        $productsDetails = Commerceinsights::$plugin->products->getPurchasesByOrderDates($startDate,$endDate);

        return $this->renderTemplate(
          'commerceinsights/_cp/products_purchases_order_date',
          [
              'startDate'      => $startDateObject,
              'endDate'        => $endDateObject,
              'purchases'      => $productsDetails['purchases'],
              'totals'         => $productsDetails['totals']
          ]
        );
    }



    /**
     * Handle a request going to our plugin's actionProductsBestSelling URL,
     * e.g.: actions/commerceinsights/cp/products-best-selling
     *
     * @return mixed
     */
    public function actionProductsBestSelling()
    {

        $startDate = Craft::$app->request->getQueryParam('startDate', false);
        $endDate = Craft::$app->request->getQueryParam('endDate',false);
        $siteId = Craft::$app->request->getQueryParam('siteId',false);

        if(! $startDate)
        {
          $startDate = date("Y-m-")."01";
        }

        if(! $endDate)
        {
          $endDate = date("Y-m-d");
        }
        $startDateObject = DateTimeHelper::toDateTime($startDate);
        $endDateObject = DateTimeHelper::toDateTime($endDate);

        $productsDetails = Commerceinsights::$plugin->products->getBestSellingProducts($startDate,$endDate);

        return $this->renderTemplate(
          'commerceinsights/_cp/products_best_selling',
          [
              'startDate'      => $startDateObject,
              'endDate'        => $endDateObject,
              'products'      => $productsDetails['products'],
              'variants'      => $productsDetails['variants'],
              'totals'         => $productsDetails['totals']
          ]
        );
    }




}
