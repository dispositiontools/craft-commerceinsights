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


use craft\commerce\elements\Product as ProductElement;
use craft\commerce\elements\Order as OrderElement;
use craft\commerce\services\ProductTypes;
use craft\commerce\Plugin as Commerce;

use craft\db\Query;
use craft\helpers\Db;
use craft\db\Table as CraftTable;
use craft\commerce\db\Table;


/**
 * Products Service
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
class Products extends Component
{
    // Public Methods
    // =========================================================================



    /**
     * This function gets the products by product type requested
     *
     * From any other plugin file, call it like this:
     *
     *     Commerceinsights::$plugin->products->getProductsByProductType($productType);
     *
     * @return mixed
     */
    public function getProductsByProductType($productType)
    {
        $result = 'something';

        if(is_int($productType))
        {
          // get product type by id
            $productType = Commerce::getInstance()->ProductTypes->getProductTypeById($productType);
        }
        elseif (is_string($productType))
        {
          // get product type by handle
            $productType = Commerce::getInstance()->ProductTypes->getProductTypeByHandle($productType);
        }
        else
        {
          return false;
        }

        if (!$productType)
        {
          return false;
        }

        $productQuery = ProductElement::find();
        $products = $productQuery->typeId($productType->id)->siteId('*')->all();

        if ( ! $products )
        {
          return false;
        }

        return [
          'products' => $products,
          'productType' => $productType
        ];



        return $result;
    }





/**
 * This function gets the product requested
 *
 * From any other plugin file, call it like this:
 *
 *     Commerceinsights::$plugin->products->getPurchasesByOrderDates($startDate,$endDate);
 *
 * @return mixed
 */
 public function getBestSellingProducts($startDate=false,$endDate=false)
 {

     $orderQuery = OrderElement::find()->isCompleted(true);
     if($startDate && $endDate)
     {
         $orderQuery->dateOrdered(['and', ">= {$startDate}", "< {$endDate}"]);
     }
     elseif($startDate){
           $orderQuery->dateOrdered(">= {$startDate}");
     }elseif($endDate)
     {
       $orderQuery->dateOrdered("< {$endDate}");
     }

     //$orderQuery->limit(10);

     $orders = $orderQuery->all();


     $products = [];

     $variants = [];
     $ordersCount = count($orders);
     $customers = [];
     $qtyTotal = 0;
     $totalTotal = 0;
     $subtotalTotal = 0;
     $productTypes = [];

     foreach($orders AS $order)
     {
         $customerId = $order->customerId;

         if(!array_key_exists($customerId , $customers))
         {
           $customers[ $customerId ] = 1;
         }
         else
         {
           // code...
           $customers[ $customerId ] = $customers[ $customerId ] + 1;
         }
         $lineItems = $order->getLineItems();

         foreach($lineItems as $lineItem)
         {
             //$variant = $lineItem->getPurchasable();
             //$product = $variant->getProduct();
             $snapshotArray =  $lineItem->snapshot;


             if(!array_key_exists($snapshotArray['product']['typeId'], $productTypes))
             {
                 $productType = Commerce::getInstance()->ProductTypes->getProductTypeById($snapshotArray['product']['typeId']);
                 if($productType)
                 {
                     $productTypes[ $snapshotArray['product']['typeId'] ] = $productType->name;
                     unset( $productType );
                 }
                 else
                 {
                     $productTypes[ $snapshotArray['product']['typeId'] ] = $snapshotArray['product']['typeId'];
                 }


             }



             $qtyTotal   = $qtyTotal + $lineItem->qty;
             $totalTotal = $totalTotal + $lineItem->total;
             $subtotalTotal = $subtotalTotal + $lineItem->subtotal;

             if(!array_key_exists( $snapshotArray['product']['id'], $products))
             {

                 $productData = [

                   'productId'     => $snapshotArray['product']['id'],
                   'productTypeName' => $productTypes[ $snapshotArray['product']['typeId'] ],
                   'productTitle'  => $snapshotArray['product']['title'],
                   'qty'  => $lineItem->qty,
                   'subtotal'  => $lineItem->subtotal,
                   'total'  => $lineItem->total,


                 ];

                 $products[ $snapshotArray['product']['id'] ] = $productData;
             }
             else
             {
                $products[ $snapshotArray['product']['id'] ]['subtotal'] = $products[ $snapshotArray['product']['id'] ]['subtotal'] + $lineItem->subtotal;
                $products[ $snapshotArray['product']['id'] ]['total'] = $products[ $snapshotArray['product']['id'] ]['total'] + $lineItem->total;
                $products[ $snapshotArray['product']['id'] ]['qty'] = $products[ $snapshotArray['product']['id'] ]['qty'] + $lineItem->qty;

             }

             if(!array_key_exists($snapshotArray['id'], $variants))
             {

                 $variantData = [
                   'productId'     => $snapshotArray['product']['id'],
                   'productTypeName' => $productTypes[ $snapshotArray['product']['typeId'] ],
                   'productTitle'  => $snapshotArray['product']['title'],
                   'variantId'     => $snapshotArray['id'],
                   'variantTitle'  => $snapshotArray['title'],
                   'qty'  => $lineItem->qty,
                   'subtotal'  => $lineItem->subtotal,
                   'total'  => $lineItem->total,


                 ];

                 $variants[ $snapshotArray['id'] ] = $variantData;
             }
             else
             {
                $variants[ $snapshotArray['id'] ]['subtotal'] = $variants[ $snapshotArray['id'] ]['subtotal'] + $lineItem->subtotal;
                $variants[ $snapshotArray['id'] ]['total'] = $variants[ $snapshotArray['id'] ]['total'] + $lineItem->total;
                $variants[ $snapshotArray['id'] ]['qty'] = $variants[ $snapshotArray['id'] ]['qty'] + $lineItem->qty;

             }


             unset($variant);
             unset($product);
             unset($productData);
             unset($variantData);
             unset($snapshotArray);
         } // end foreach lineitem

     }// end foreach order

       $productsCount = count($products);
       $variantsCount = count($variants);
       $customersCount = count($customers);

       usort($products, function($a, $b) {
           return $b['total'] <=> $a['total'];
       });
       usort($variants, function($a, $b) {
           return $b['total'] <=> $a['total'];
       });

     $returnObject =  [
       'products' => $products,
       'variants' => $variants,
       'totals' => [
         'products' => $productsCount,
         'variants' => $variantsCount,
         'customers' => $customersCount,
         'orders' => $ordersCount,
         'qty' => $qtyTotal,
         'total' => $totalTotal,
         'subtotal' => $subtotalTotal
       ]

     ];

     //print_r($returnObject);

     return $returnObject;





 } // close get best selling products


















    /**
     * This function gets the product requested
     *
     * From any other plugin file, call it like this:
     *
     *     Commerceinsights::$plugin->products->getPurchasesByOrderDates($startDate,$endDate);
     *
     * @return mixed
     */
    public function getPurchasesByOrderDates($startDate=false,$endDate=false)
    {


        //print_r($purchasableIds);
/*

SELECT cv.sku,
cv.id as variantId,
cp.id as productId,
cvc.title as variantTitle,
cpc.title as productTitle
FROM craft_commerce_variants as cv
LEFT JOIN craft_commerce_products as cp on cp.id = cv.productId
LEFT JOIN craft_content as cvc on cvc.elementId = cv.id
LEFT JOIN craft_content as cpc on cpc.elementId = cp.id
GROUP BY cv.id

*/

        $productVariantsQuery = (new Query())
          ->select([
            'cv.id AS variantDetailsId',
            'cp.id AS variantProductId',
            'cvc.title AS variantTitle',
            'cpc.title AS productTitle',
            'cpt.id as productTypeId',
            'cpt.name as productTypeName',

          ])
          ->from(['cv' => Table::VARIANTS])
          ->leftJoin(['cp' => Table::PRODUCTS],'cp.id = cv.productId')
          ->leftJoin(['cpt' => Table::PRODUCTTYPES],'cpt.id = cp.typeId')
          ->leftJoin(['cvc' => CraftTable::CONTENT],'cvc.elementId = cv.id')
          ->leftJoin(['cpc' => CraftTable::CONTENT],'cpc.elementId = cp.id')
          ->groupBy('cv.id');


        $query = (new Query())
            ->select([
              'p.productTypeId',
              'p.productTypeName',
              'p.productTitle',
              'p.variantTitle',
              'co.id as orderId',
              'co.email as orderEmail',
              'co.orderSiteId as orderSiteId',
              'co.customerId as customerId',
              'cc.userId as userId',
              'cu.email as userEmail',
              'cu.firstname as userFirstName',
              'cu.lastName as userLastName',
              'co.gatewayId as orderGatewayId',
              'co.number as orderNumber',
              'co.reference as orderReference',
              'co.datePaid as orderDatePaid',
              'co.totalPaid as orderTotalPaid',
              'co.currency as orderCurrency',
              'co.paymentCurrency as orderPaymentCurrency',
              'cos.name as orderStatusName',
              'cos.handle as orderStatusHandle',
              'cos.color as orderStatusColor',
              'clis.name as lineItemStatusName',
              'clis.handle as lineItemStatusHandle',
              'clis.color as lineItemStatusColor',
              'cli.description as lineItemStatusDescription',
              'cli.options as lineItemOptions',
              'cli.qty as lineItemQty',
              'cli.total as lineItemTotal',
              'cli.subtotal as lineItemSubtotal',
              'cli.price as lineItemPrice',
              'cli.salePrice as lineItemSalePrice',
              'cli.saleAmount as lineItemSaleAmount',
              'cli.note as lineItemNote',
              'cs.name as siteName',
              'cs.handle as siteHandle',

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
            ->from(['cli' => Table::LINEITEMS])
            ->leftJoin(['co' => Table::ORDERS],'co.id = cli.orderId')
            ->leftJoin(['cc' => Table::CUSTOMERS],'cc.id = co.customerId')
            ->leftJoin(['cu' => CraftTable::USERS],'cu.id = cc.userId')
            ->leftJoin(['cs' => CraftTable::SITES],'cs.id = co.orderSiteId')
            ->leftJoin(['cos' => Table::ORDERSTATUSES],'cos.id = co.orderStatusId')
            ->leftJoin(['clis' => Table::LINEITEMSTATUSES],'clis.id = cli.lineitemStatusId')
            ->leftJoin(['cosa' => Table::ADDRESSES],'cosa.id = co.shippingAddressId')
            ->leftJoin(['coba' => Table::ADDRESSES],'coba.id = co.billingAddressId')
            ->leftJoin(['p' => $productVariantsQuery ], 'p.variantDetailsId = cli.purchasableId')
            ->where('co.isCompleted = 1');
            /*
                    AND co.datePaid > '2020-12-01'  and co.datePaid < '2021-01-01'
            */

            if ($startDate)
            {
                $query->andWhere("co.dateOrdered >= :dateOrderedStart", [ ':dateOrderedStart' => $startDate ]);
            }

            if ($endDate)
            {
                $query->andWhere("co.dateOrdered <= :dateOrderedEnd", [ ':dateOrderedEnd' => $endDate ]);
            }
            $query->orderBy('co.dateOrdered ASC');
            $purchases = $query->all();

            //print_r($purchases);

        return [
          'purchases' => $purchases,
          'totals' => []
          //'totals' => $purchasesTotals

        ];


        //print_r($query);
        $totals = ['qty','saleAmount'];
        $array = array();
        foreach ($query as $row)
        {

        }



    } // close get purchases by date







    /**
     * This function gets the product requested
     *
     * From any other plugin file, call it like this:
     *
     *     Commerceinsights::$plugin->products->getPurchasesByProduct($productId);
     *
     * @return mixed
     */
    public function getPurchasesByProduct($productId)
    {


        // query to get purchasableIds

        $purchasableIdsQuery = (new Query())->select('cv.id')->from(['cv' => Table::VARIANTS])
        ->where('cv.productId=:productId',[':productId'=>$productId])->all();


        $purchasableIds = array();
        foreach ($purchasableIdsQuery as $purchasableIdRow)
        {
            $purchasableIds[] = $purchasableIdRow['id'];
        }
        //print_r($purchasableIds);

        $query = (new Query())
            ->select([
              'co.id as orderId',
              'co.email as orderEmail',
              'co.orderSiteId as orderSiteId',
              'co.customerId as customerId',
              'cc.userId as userId',
              'cu.email as userEmail',
              'cu.firstname as userFirstName',
              'cu.lastName as userLastName',
              'co.gatewayId as orderGatewayId',
              'co.number as orderNumber',
              'co.reference as orderReference',
              'co.datePaid as orderDatePaid',
              'co.currency as orderCurrency',
              'co.paymentCurrency as orderPaymentCurrency',
              'cos.name as orderStatusName',
              'cos.handle as orderStatusHandle',
              'cos.color as orderStatusColor',
              'clis.name as lineItemStatusName',
              'clis.handle as lineItemStatusHandle',
              'clis.color as lineItemStatusColor',
              'cli.description as lineItemStatusDescription',
              'cli.options as lineItemOptions',
              'cli.qty as lineItemQty',
              'cli.total as lineItemTotal',
              'cli.subtotal as lineItemSubtotal',
              'cli.price as lineItemPrice',
              'cli.salePrice as lineItemSalePrice',
              'cli.saleAmount as lineItemSaleAmount',
              'cli.note as lineItemNote',
              'cs.name as siteName',
              'cs.handle as siteHandle',

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
            ->from(['cli' => Table::LINEITEMS])
            ->leftJoin(['co' => Table::ORDERS],'co.id = cli.orderId')
            ->leftJoin(['cc' => Table::CUSTOMERS],'cc.id = co.customerId')
            ->leftJoin(['cu' => CraftTable::USERS],'cu.id = cc.userId')
            ->leftJoin(['cs' => CraftTable::SITES],'cs.id = co.orderSiteId')
            ->leftJoin(['cos' => Table::ORDERSTATUSES],'cos.id = co.orderStatusId')
            ->leftJoin(['clis' => Table::LINEITEMSTATUSES],'clis.id = cli.lineitemStatusId')
            ->leftJoin(['cosa' => Table::ADDRESSES],'cosa.id = co.shippingAddressId')
            ->leftJoin(['coba' => Table::ADDRESSES],'coba.id = co.billingAddressId')
            ->where('co.isCompleted = 1')
            ->andWhere(['in','cli.purchasableId', $purchasableIds]);

            $purchases = $query->all();


/*
            $purchasesTotals = (new Query())
                ->select([
                  'clis.name',
                  'clis.handle',
                  'cli.qty',
                  'cli.total',
                  'cli.price',
                  'cli.salePrice',
                  'cli.saleAmount'
                  ]
                )
                ->from(['cli' => Table::LINEITEMS])
                ->leftJoin(['co' => Table::ORDERS],'co.id = cli.orderId')
                ->where('co.isCompleted = 1')
                ->andWhere(['in','cli.purchasableId', $purchasableIds])->sum(['cli.qty',
                'cli.total',
                'cli.price',
                'cli.salePrice',
                'cli.saleAmount']);
                */
    /*
            AND co.datePaid > '2020-12-01'  and co.datePaid < '2021-01-01'


    */
        return [
          'purchases' => $purchases,
          'totals' => []
          //'totals' => $purchasesTotals

        ];

          print_r($purchasesTotals);
        //print_r($query);
        $totals = ['qty','saleAmount'];
        $array = array();
        foreach ($query as $row)
        {

        }



    }
    // close get purchases by product id

}
