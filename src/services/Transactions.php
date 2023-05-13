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

use craft\commerce\records\Transaction as TransactionRecord;

use craft\commerce\Plugin as Commerce;

use craft\db\Query;
use craft\helpers\Db;
use craft\db\Table as CraftTable;
use craft\commerce\db\Table;

/**
 * Transactions Service
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
class Transactions extends Component
{
    // Public Methods
    // =========================================================================



    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     Commerceinsights::$plugin->transactions->getTransactions($options);
     *
     * @return mixed
     */
    public function getTransactions($options)
    {
        $result = 'something';
        // Check our Plugin's settings for `someAttribute`
        if (Commerceinsights::$plugin->getSettings()->someAttribute) {
        }



        $transactionsQuery = (new Query())
            ->select([
              'ct.id as transactionId',
              'ct.gatewayId as gatewayId',
              'cg.name as gatewayName',
              'ct.status as transactionStatus',
              'ct.paymentAmount as transactionAmount',
              'ct.type as transactionType',
              'ct.dateCreated as transactionDate',
              'u.firstName as userFirstName',
              'u.lastName as userLastName',
              'u.id as userId',
              'co.email as email',
              'co.number as orderNumber',
              'co.reference as orderReference',
              'co.id as orderId',
              'co.datePaid',
              'co.currency',
              'co.paymentCurrency',
              'cos.name',
              'cos.handle',
              'cos.color',
              'cosa.businessName as shippingBusinessName',
              'cosa.firstName as shippingFirstName',
              'cosa.lastName as shippingLastName',
              'cosa.address1 as shippingAddress1',
              'cosa.address2 as shippingAddress2',
              'cosa.address3 as shippingAddress3',
              'cosa.city as shippingCity',
              'cosa.zipCode as shippingPostcode',

              'coba.businessName as billingBusinessName',
              'coba.firstName as billingFirstName',
              'coba.lastName as billingLastName',
              'coba.address1 as billingAddress1',
              'coba.address2 as billingAddress2',
              'coba.address3 as billingAddress3',
              'coba.city as billingCity',
              'coba.zipCode as billingPostcode',
              ]
            )
            ->from(['ct' => Table::TRANSACTIONS])
            ->leftJoin(['cg' => Table::GATEWAYS],'cg.id = ct.gatewayId')
            ->leftJoin(['co' => Table::ORDERS],'co.id = ct.orderId')
            ->leftJoin(['cc' => Table::CUSTOMERS],'cc.id = co.customerId')
            ->leftJoin(['u' => CraftTable::USERS],'u.id = cc.userId')
            ->leftJoin(['cos' => Table::ORDERSTATUSES],'cos.id = co.orderStatusId')
            ->leftJoin(['cosa' => Table::ADDRESSES],'cosa.id = co.shippingAddressId')
            ->leftJoin(['coba' => Table::ADDRESSES],'coba.id = co.billingAddressId');


            if( array_key_exists('transactionStatus' , $options) )
            {
                $transactionsQuery->where("ct.status = :transactionStatus", [ ':transactionStatus' => $options['transactionStatus'] ]);
            }
            else
            {
              // code...
              $transactionsQuery->where("ct.status='success'");
            }


            if( array_key_exists('afterDate' , $options) )
            {
                $transactionsQuery->andWhere("DATE(ct.dateCreated) >= :afterDate", [ ':afterDate' => $options['afterDate'] ]);
            }

            if( array_key_exists('beforeDate' , $options) )
            {
                $transactionsQuery->andWhere("DATE(ct.dateCreated) <= :beforeDate", [ ':beforeDate' => $options['beforeDate'] ]);
            }

            $transactions = $transactionsQuery->all();


            $stats = [
              'totalPaid' => 0,
              'totalRefunded' => 0,
              'total' => 0,
              'averagePaid' =>0,
              'averageRefunded' =>0,
              'numberOfTransactions' => 0,
              'numberOfPaidTransactions' => 0,
              'numberOfRefunds' => 0,
              'firstTransactionDate' => null,
              'lastTransactionDate' => null,

            ];

            // calculate the stats
            $orderIdsArray = [];
            foreach ($transactions as $transaction)
            {
                if($stats['firstTransactionDate'] == null or strtotime($transaction['transactionDate']) < strtotime($stats['firstTransactionDate']) )
                {
                    $stats['firstTransactionDate'] = $transaction['transactionDate'];
                }


                if($stats['lastTransactionDate'] == null or strtotime($transaction['transactionDate']) > strtotime($stats['lastTransactionDate']) )
                {
                    $stats['lastTransactionDate'] = $transaction['transactionDate'];
                }

                $stats['numberOfTransactions'] = $stats['numberOfTransactions'] + 1;

                if($transaction['transactionType'] == "purchase"  )
                {
                  $stats['numberOfPaidTransactions'] = $stats['numberOfPaidTransactions'] + 1;
                  $stats['totalPaid'] = $stats['totalPaid'] + $transaction['transactionAmount'];
                  $stats['total'] = $stats['total'] + $transaction['transactionAmount'];
                }
                if($transaction['transactionType'] == "refund"   )
                {
                  $stats['numberOfRefunds'] = $stats['numberOfRefunds'] + 1;
                  $stats['totalRefunded'] = $stats['totalRefunded'] + ( $transaction['transactionAmount']*-1);
                  $stats['total'] = $stats['total'] + ( $transaction['transactionAmount']*-1);
                }

                $orderIdsArray[] =  $transaction['orderId'];
            }



            if( $stats['numberOfPaidTransactions'] > 0 )
            {
                $stats['averagePaid'] = $stats['totalPaid'] / $stats['numberOfPaidTransactions'];
            }


            if( $stats['numberOfRefunds'] > 0 )
            {
                $stats['averageRefunded'] = $stats['totalRefunded'] / $stats['numberOfRefunds'];
            }

        return [
          'transactions' => $transactions,
          'stats'=> $stats
        ];
    }
}
