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

/**
 * Reports Controller
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
class ReportsController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['index', 'do-something'];

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/commerceinsights/reports
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $result = 'Welcome to the ReportsController actionIndex() method';

        return $result;
    }




    /**
     * Handle a request going to our plugin's actionDoSomething URL,
     * e.g.: actions/commerceinsights/reports/download-csv
     *
     * @return mixed
     */
    public function actionDownloadCsv()
    {


        $reportType    = Craft::$app->request->post('reportType');
        $startDate    = Craft::$app->request->post('startDate', false);
        $endDate    = Craft::$app->request->post('endDate', false);
        $filenameDates ="";
        if($startDate && $endDate)
        {
          $filenameDates ="_between_".$startDate."_and_".$endDate;
        }elseif($startDate)
        {
          $filenameDates ="_starting_".$startDate;
        }elseif($endDate)
        {
          $filenameDates ="_ending_".$endDate;
        }
        switch($reportType){
          case "product":
              $productId       = Craft::$app->request->post('productId');
              $reportDetails  = Commerceinsights::$plugin->products->getPurchasesByProduct($productId);
              $csvData        = $this->makeCsv( $reportDetails['purchases'] );
              $filenameReportType       = "ProductPurchases";

            break;

          case "productsPurchasesByOrderDates":
              $reportDetails  = Commerceinsights::$plugin->products->getPurchasesByOrderDates($startDate,$endDate);
              $csvData        = $this->makeCsv( $reportDetails['purchases'] );
              $filenameReportType       = "ProductPurchasesByOrderDates";
              break;
          case "customer-summary":
              $reportDetails  = Commerceinsights::$plugin->customers->getCustomers();
              $csvData        = $this->makeCsv( $reportDetails['customers'] );
              $filenameReportType       = "CustomerSummaries";
              break;
          case "transactions":

              $options =[];
              if($startDate)
              {
                  $options['afterDate'] = $startDate;
              }
              if($endDate)
              {
                $options['beforeDate'] = $endDate;
              }
              //$options['transactionStatus']  = "";


              $reportDetails  = Commerceinsights::$plugin->transactions->getTransactions($options);
              $csvData        = $this->makeCsv( $reportDetails['transactions'] );
              $filenameReportType       = "Transactions";
              break;




          default:

            echo "nope";

        }

          $filename = $filenameReportType.$filenameDates."_downloaded_on_".date("Y-m-d_h-i").".csv";




        $options['mimeType'] = "application/csv";
        return Craft::$app->response->sendContentAsFile($csvData, $filename,$options);


    }



    public function makeCsv($rows)
    {

         ob_start();
         $out = fopen('php://output', 'w');

         $has_header = false;

         foreach ( $rows as $item)
         {
            if (!$has_header)
            {
                 fputcsv( $out, array_keys($item) );
                 $has_header = true;
             }
             fputcsv($out,  $item);
         }
         // End CSV output
         fclose($out);
         $out = ob_get_clean();
         $out = str_replace("\n", "\r\n", $out);
         //echo $out;

         return $out;
     }
}
