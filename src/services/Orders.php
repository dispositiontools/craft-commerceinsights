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
use craft\commerce\elements\Order as OrderElement;

use craft\db\Query;
use craft\helpers\Db;
use craft\db\Table as CraftTable;
use craft\commerce\db\Table;

/**
 * Orders Service
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
class Orders extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     Commerceinsights::$plugin->orders->orders($options)
     *
     * @return mixed
     */
    public function orders($options = null)
    {
        $orderQuery = OrderElement::find();

        $orders = $orderQuery
          ->limit(1000)
          ->all();


          /*
          SELECT
          orderId,
          totalPrice,
          lineItemsQty,
          maxSalePrice,
          minSalePrice,
          lineItemTotalQty
          FROM
          craft_commerce_orders as co
          LEFT JOIN
          ( SELECT cli.orderId, count(*) as lineItemsQty, max(salePrice) as maxSalePrice, min(salePrice) as minSalePrice,sum(qty) as lineItemTotalQty FROM craft_commerce_lineitems as cli group by cli.orderId ) as lineItems on lineItems.orderId = co.id
          WHERE co.isCompleted = 1

          order by totalPrice desc
order by totalPrice desc
*/


          //return $orders;

          $orderArray = [];
          $orderStatsArray = [];
        foreach($orders as $order)
        {
            $orderItem = [
              'customerId' => $order->customerId,
              'items'=>count($order->lineItems)
            ];
            $orderArray[] = $order->toArray();
        }

        $storedTotalPaidArray = array_column($orderArray, 'storedTotalPaid');

        $orderStatsArray['orderTotal_total'] = array_sum($storedTotalPaidArray);
        $orderStatsArray['orderTotal_min'] = min($storedTotalPaidArray);
        $orderStatsArray['orderTotal_max'] = max($storedTotalPaidArray);

        $orderStatsArray['orderTotal_avg'] = array_sum($storedTotalPaidArray)/count($storedTotalPaidArray);

        return $orderStatsArray;
    }
}
