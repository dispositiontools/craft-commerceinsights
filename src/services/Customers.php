<?php
/**
 * Commerceinsights plugin for Craft CMS 3.x
 *
 * Get the low down on how your Commerce is doing
 *
 * @link      http://www.disposition.tools/
 * @copyright Copyright (c) 2021 Disposition Tools
 */

namespace dispositiontools\commerceinsights\services;

use dispositiontools\commerceinsights\Commerceinsights;

use Craft;
use craft\base\Component;

use craft\commerce\Plugin as Commerce;

use craft\db\Query;
use craft\helpers\Db;
use craft\db\Table as CraftTable;
use craft\commerce\db\Table;
use craft\helpers\DateTimeHelper;

/**
 * Customers Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Disposition Tools
 * @package   Commerceinsights
 * @since     1.0.0
 */
class Customers extends Component
{
    // Public Methods
    // =========================================================================



    /*


    SELECT
        co.customerId,
       group_concat(co.id),
      	count(co.id) as numberOfOrders,
      	SUM(totalPrice) as ordersTotalPrice,
      	( SUM(totalPrice) / count(co.id) ) as avgOrderTotalPrice,
      	MIN(totalPrice) as minOrderTotalPrice,
      	MAX(totalPrice) as maxOrderTotalPrice,

      	MIN(dateOrdered) as firstOrderDate,
      	MAX(dateOrdered) as lastOrderDate,
      	DATEDIFF( MAX(dateOrdered), MIN(dateOrdered) ) as daysBetweenFirstandLastOrder,

      	DATEDIFF( CURDATE(),  MAX(dateOrdered) ) as daysSinceLastOrder,

      	u.id as orderUserId,
      	u.firstName,
      	u.lastName,
      	u.email
       FROM
        craft_commerce_orders as co

        LEFT JOIN craft_commerce_customers as cc on cc.id = co.customerId
        LEFT JOIN craft_users as u on u.id = cc.userId

     WHERE co.isCompleted = 1
     group by co.customerId
    order by ordersTotalPrice desc

    */









    /**
     * This function gets the product requested
     *
     * From any other plugin file, call it like this:
     *
     *     Commerceinsights::$plugin->customers->getCustomers($productId);
     *
     * @return mixed
     */
    public function getCustomers($productId = null)
    {



        $query = (new Query())
            ->select([
              'co.customerId',
              'count(co.id) as numberOfOrders',
              'SUM(totalPrice) as ordersTotalPrice',
              '( SUM(totalPrice) / count(co.id) ) as avgOrderTotalPrice',
              'MIN(totalPrice) as minOrderTotalPrice',
              'MAX(totalPrice) as maxOrderTotalPrice',
              'MIN(dateOrdered) as firstOrderDate',
              'MAX(dateOrdered) as lastOrderDate',
              'DATEDIFF( MAX(dateOrdered), MIN(dateOrdered) ) as daysBetweenFirstandLastOrder',
              'DATEDIFF( CURDATE(),  MAX(dateOrdered) ) as daysSinceLastOrder',
              'u.id as orderUserId',
              'u.firstName as userFirstName',
              'u.lastName as userLastName',
              'u.email as userEmail',
              'co.email as orderEmail'
          ]
            )
            ->from(['co' => Table::ORDERS])
            ->leftJoin(['cc' => Table::CUSTOMERS],'cc.id = co.customerId')
            ->leftJoin(['u' => CraftTable::USERS],'u.id = cc.userId')

            ->where('co.isCompleted = 1')
            ->groupBy(['co.customerId'])
            ->orderBy([
              'ordersTotalPrice' => SORT_DESC,
            ]);

            /*
                    AND co.datePaid > '2020-12-01'  and co.datePaid < '2021-01-01'
            */

            $customers = $query->all();
            $ordersTotalPriceArray = [];
            $numberOfOrdersArray=[];
            $lastOrderDateArray=[];
            $daysBetweenFirstandLastOrderArray = [];
            foreach($customers as $index => $customer)
            {
              $ordersTotalPriceArray[] = $customer['ordersTotalPrice'];
              $numberOfOrdersArray[] = $customer['numberOfOrders'];
              $lastOrderDateArray[] = $customer['lastOrderDate'];
              $daysBetweenFirstandLastOrderArray[] = $customer['daysBetweenFirstandLastOrder'];
            }


            //return $ordersTotalPriceArray;


        return [
          'customers' => $customers,
          'totals' => [
            'ordersTotalPrice' => [
              'sum' => array_sum($ordersTotalPriceArray),
              'avg' => array_sum($ordersTotalPriceArray)/count($ordersTotalPriceArray),
              'max' => max($ordersTotalPriceArray),
              'min' => min($ordersTotalPriceArray),
            ],
            'numberOfOrders' => [
              'sum' => array_sum($numberOfOrdersArray),
              'avg' => array_sum($numberOfOrdersArray)/count($numberOfOrdersArray),
              'max' => max($numberOfOrdersArray),
              'min' => min($numberOfOrdersArray),
            ],
            'lastOrderDate' => [
              'max' => max($lastOrderDateArray),
              'min' => min($lastOrderDateArray),
            ],
            'customerLength' => [
              'max' => max($daysBetweenFirstandLastOrderArray),
              'min' => min($daysBetweenFirstandLastOrderArray),
            ]
          ]
          //'totals' => $purchasesTotals

        ];




    }




    /**
     * This function gets the product requested
     *
     * From any other plugin file, call it like this:
     *
     *     Commerceinsights::$plugin->customers->getCustomersRfm();
     *
     * @return mixed
     */
    public function getCustomersRfm($options)
    {


      ray($options);
        $customerData = $this->getCustomers();

        $rfmData = [];



          $ordersTotalPriceMax = $customerData['totals']['ordersTotalPrice']['max'];
          $ordersTotalPriceMin = $customerData['totals']['ordersTotalPrice']['min'];

          $numberOfOrdersMax = $customerData['totals']['numberOfOrders']['max'];
          $numberOfOrdersMin = $customerData['totals']['numberOfOrders']['min'];

          $now = date("Y-m-d H:i:s");
          $recencyMax = $this->timeDifferenceInDays($customerData['totals']['lastOrderDate']['max'], $now);
          $recencyMin = $this->timeDifferenceInDays($customerData['totals']['lastOrderDate']['min'], $now);

          $customerLengthMax = $customerData['totals']['customerLength']['max'];
          $customerLengthMin = $customerData['totals']['customerLength']['min'];



        foreach($customerData['customers'] as $index => $customer)
        {

            $filter = true;
            $ordersTotalPrice = $customer['ordersTotalPrice'];
            $numberOfOrders = $customer['numberOfOrders'];
            $lastOrderDate = $customer['lastOrderDate'];
            $daysBetweenFirstandLastOrder = $customer['daysBetweenFirstandLastOrder'];

            $recency = $this->normalizeValue($this->timeDifferenceInDays($lastOrderDate, $now), $recencyMin, $recencyMax);
            $frequency = $this->normalizeValue($numberOfOrders, $numberOfOrdersMin, $numberOfOrdersMax);
            $monitary = $this->normalizeValue($ordersTotalPrice, $ordersTotalPriceMin, $ordersTotalPriceMax);
            $length = $this->normalizeValue($daysBetweenFirstandLastOrder, $customerLengthMin, $customerLengthMax);

            $customer = [
              'customerId' => $customer['customerId'],
              'name' => $customer['userFirstName']." ".$customer['userLastName'],
              'orderUserId' => $customer['orderUserId'],
              'rfmScore' => $recency+$frequency+$monitary,
              'rfmlScore' =>$recency+$frequency+$monitary+$length,
              'rfm' => $recency." ".$frequency." ".$monitary,
              'rfml' => $recency." ".$frequency." ".$monitary." ".$length,
              'recency' => $recency,
              'frequency' => $frequency,
              'monitary' => $monitary,
              'length' => $length,
              'ordersTotalPrice' => $ordersTotalPrice,
              'numberOfOrders' => $numberOfOrders,
              'lastOrderDate' => $lastOrderDate,
              'daysBetweenFirstandLastOrder' => $daysBetweenFirstandLastOrder
            ];

            if( array_key_exists('monetary',$options) && $options['monetary'] != "*" && $options['monetary'] != false)
            {
                if( $monitary != $options['monetary'])
                {
                  $filter = false;
                }
            }

            if( array_key_exists('frequency',$options) && $options['frequency'] != "*" && $options['frequency'] != false)
            {
                if( $frequency != $options['frequency'])
                {
                  $filter = false;
                }
            }

            if( array_key_exists('recency',$options) && $options['recency'] != "*" && $options['recency'] != false)
            {

                if( $recency != $options['recency'])
                {
                  $filter = false;
                }
            }


            if ($filter)
            {
              $rfmData[] = $customer;
            }

        }

        return ['customers'=>$rfmData];


    }


    private function  timeDifferenceInDays($startDateStr, $endDateStr)
    {


        $startDate =  DateTimeHelper::toDateTime($startDateStr,true);
        $endDate = DateTimeHelper::toDateTime($endDateStr,true);

        $diff = $endDate->diff($startDate)->format("%a");

        if (!$diff)
        {
          return 0;
        }

        return $diff;
    }


    private function calculateRfm_score($customer)
    {
        $gamification_data = $customer['gamification_data'];
        $r = $this->normalizeValue(round($gamification_data['R'], 2), round($gamification_data['MIN_R'], 2), round($gamification_data['MAX_R'], 2));
        $f = $this->normalizeValue(round($gamification_data['F'], 2), round($gamification_data['MIN_F'], 2), round($gamification_data['MAX_F'], 2));
        $m = $this->normalizeValue(round($gamification_data['TM'], 2), round($gamification_data['MIN_TM'], 2), round($gamification_data['MAX_TM'], 2));
        $rfm = "{$r} {$f} ${m}";
        return $rfm;
    }




    private function normalizeValue($inputValue, $minValue, $maxValue)
    {
        $normalizedValue = 0;
        $minRange = 1;
        $maxRange = 5;

        $normalized = (($maxRange-$minRange)*(($inputValue-$minValue)/($maxValue-$minValue))) + $minRange;
        $normalizedValue = (int)round($normalized, 2);

        return $normalizedValue;
    }







}
