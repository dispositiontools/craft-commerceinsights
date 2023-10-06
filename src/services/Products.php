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
        $result = 'Products by product type';

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


    public function getDonationDetails()
    {
        $donationElement = Commerce::getInstance()->getDonation();
        if ($donationElement)
        {
          $donationPurchasableId =  $donationElement->id;
          $isDonationAvailable = $donationElement->isAvailable;
        }
        else{
          $donationPurchasableId = 0;
          $isDonationAvailable = false;
        }

        return [
          "element" => $donationElement,
          "donationPurchasableId" => $donationPurchasableId,
          "isDonationAvailable" => $isDonationAvailable
        ];
    }


/**
 * This function gets the product requested
 *
 * From any other plugin file, call it like this:
 *
 *     Commerceinsights::$plugin->products->getBestSellingProducts($startDate,$endDate);
 *
 * @return mixed
 */
 public function getBestSellingProducts($startDate=false,$endDate=false)
 {

    $donationDetails = $this->getDonationDetails();
    $donationPurchasableId =  $donationDetails['donationPurchasableId'];
    $isDonationAvailable = $donationDetails['isDonationAvailable'];




     $orderQuery = OrderElement::find()->isCompleted(true);/*
     if($startDate && $endDate)
     {
         $orderQuery->dateOrdered(['and', ">= {$startDate}", "< {$endDate}"]);
     }
     elseif($startDate){
           $orderQuery->dateOrdered(">= {$startDate}");
     }elseif($endDate)
     {
       $orderQuery->dateOrdered("<= {$endDate}");
     }


      echo $orderQuery->getRawSql();
      die();
      */
     
     if ($startDate)
     {
      $orderQuery->andWhere("DATE(dateOrdered) >= :dateOrderedStart", [ ':dateOrderedStart' => $startDate ]);
     }

     if ($endDate)
     {
      $orderQuery->andWhere("DATE(dateOrdered) <= :dateOrderedEnd", [ ':dateOrderedEnd' => $endDate ]);
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

             /*
             
              If there are donations then there will not be a product array in the snapshot. the snapshot will look more like this:
              {"price":0,"sku":"DONATION-CC3","description":"Donation","purchasableId":6364,"cpEditUrl":"#","options":{"donationAmount":"20","giftaid":"Yes"},"sales":[]}
             */
            if (  array_key_exists('purchasableId',$snapshotArray ) && $snapshotArray['purchasableId'] == $donationPurchasableId )
            {
              $productTypes[ $snapshotArray['sku'] ] = "Donation";
            }
            elseif ( !array_key_exists('product',$snapshotArray ) )
              {
                $productTypes[$snapshotArray['sku']] = $snapshotArray['sku'];
              }
             elseif(!array_key_exists($snapshotArray['product']['typeId'], $productTypes))
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

             // If donation: 
             if (  array_key_exists('purchasableId',$snapshotArray ) && $snapshotArray['purchasableId'] == $donationPurchasableId )
             {
                  if(!array_key_exists( $donationPurchasableId, $products))
                  {

                      $productData = [

                        'productId'     => $donationPurchasableId,
                        'productTypeName' => $productTypes[ $snapshotArray['sku'] ],
                        'productTitle'  => "Donation",
                        'qty'  => $lineItem->qty,
                        'subtotal'  => $lineItem->subtotal,
                        'total'  => $lineItem->total,


                      ];

                      $products[ $donationPurchasableId ] = $productData;
                  }
                  else
                  {
                    $products[ $donationPurchasableId ]['subtotal'] = $products[ $donationPurchasableId ]['subtotal'] + $lineItem->subtotal;
                    $products[ $donationPurchasableId ]['total'] = $products[ $donationPurchasableId ]['total'] + $lineItem->total;
                    $products[ $donationPurchasableId ]['qty'] = $products[ $donationPurchasableId ]['qty'] + $lineItem->qty;

                  }


                  
                  $donationVariantId =  $donationPurchasableId."-".$lineItem->subtotal;
                  $donationVariantTitle = "Donation - ".$lineItem->subtotal;
                  if(!array_key_exists( $donationVariantId , $variants))
                    {
      
                        $variantData = [
                          'productId'     => $donationPurchasableId,
                          'productTypeName' => $productTypes[ $snapshotArray['sku'] ],
                          'productTitle'  => "Donation",
                          'variantId'     => $donationVariantId,
                          'variantTitle'  => $donationVariantTitle,
                          'qty'  => $lineItem->qty,
                          'subtotal'  => $lineItem->subtotal,
                          'total'  => $lineItem->total,
      
      
                        ];
      
                        $variants[ $donationVariantId ] = $variantData;
                    }
                    else
                    {
                      $variants[ $donationVariantId ]['subtotal'] = $variants[ $donationVariantId ]['subtotal'] + $lineItem->subtotal;
                      $variants[ $donationVariantId ]['total'] = $variants[ $donationVariantId ]['total'] + $lineItem->total;
                      $variants[ $donationVariantId ]['qty'] = $variants[ $donationVariantId ]['qty'] + $lineItem->qty;
      
                    }


             }
             elseif(array_key_exists('product', $snapshotArray)  )
             {
                  // if there is a product 
                    if(    !array_key_exists( $snapshotArray['product']['id'], $products))
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
             }
            

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

      $donationDetails = $this->getDonationDetails();
      $donationPurchasableId =  $donationDetails['donationPurchasableId'];
      $isDonationAvailable = $donationDetails['isDonationAvailable'];



      $orderQuery = OrderElement::find()->isCompleted(true);
     
     if ($startDate)
     {
      $orderQuery->andWhere("DATE(dateOrdered) >= :dateOrderedStart", [ ':dateOrderedStart' => $startDate ]);
     }

     if ($endDate)
     {
      $orderQuery->andWhere("DATE(dateOrdered) <= :dateOrderedEnd", [ ':dateOrderedEnd' => $endDate ]);
     }
  
     $orders = $orderQuery->all();

     $products = [];

     $variants = [];
     $ordersCount = count($orders);
     $customers = [];
     $qtyTotal = 0;
     $totalTotal = 0;
     $subtotalTotal = 0;
     $productTypes = [];

     $productPurchases = [];
     $variantPurchases = [];

     foreach($orders AS $order)
     {
         $customerId = $order->customerId;
         
         $customer = $order->getCustomer();
         
         $userId = $customer->userId;
         $orderUserDetails = [
             'userId' => '',	
             'userEmail' => '',	
             'userFirstName' => '',	
             'userLastName' => '',
         ];
         if($userId)
         {
             $orderUser =  Craft::$app->users->getUserById($userId);
             if($orderUser)
             {
                 $orderUserDetails = [
                      'userId' => $orderUser->id,
                      'userEmail' => $orderUser->email,
                      'userFirstName' => $orderUser->firstName,
                      'userLastName' => $orderUser->lastName,
                  ];
             }
         }
         
         $orderStatus = $order->orderStatus;
         
         $orderSite = $order->orderSite;
        
         $orderDetails = [
             'orderEmail' => $order->email,
             'orderId' => $order->id,
             'customerId' => $customerId,
             'orderGatewayId' => $order->gatewayId,
             'orderReference' => $order->reference,
             'orderNumber' => $order->number,
             'orderSiteId' => $order->siteId,
             'siteName' => $orderSite->name,
             'siteHandle' => $orderSite->handle,
             'orderDatePaid' => "",
             'orderDateOrdered' => $order->dateOrdered->format("Y-m-d H:i:s"),
             'orderTotalPrice' => $order->totalPrice,
             'orderTotalPaid' => $order->totalPaid,
             'orderAmountDue' => $order->totalPrice - $order->totalPaid,
             'orderCurrency'=> $order->currency,
             'orderPaymentCurrency' => $order->paymentCurrency,
             'orderStatusName' => $orderStatus->name,
             'orderStatusHandle' => $orderStatus->handle,
             'orderStatusColor' => $orderStatus->color,
             
             
             
             
         ];
         
         if($order->datePaid)
         {
             $orderDetails['orderDatePaid'] =  $order->datePaid->format("Y-m-d H:i:s");
         }
         
         $shippingAddress = $order->shippingAddress;
         
         
       
         $billingAddress = $order->billingAddress;
         
         
         $addressBillingDetails = [
            'billingBusinessName' => "",
            'billingFirstName' => "",
            'billingLastName' => "",
            'billingAddress1' => "",
            'billingAddress2' => "",
            'billingAddress3' => "",
            'billingCity' => "",
            'billingPostcod' => "",
        ];
        
        $addressShippingDetails = [
            'shippingBusinessName' => "",
            'shippingFirstName' => "",
            'shippingLastName' => "",
            'shippingAddress1' => "",
            'shippingAddress2' => "",
            'shippingAddress3' => "",
            'shippingCity' => "",
            'shippingPostcode' => "",
         ];
         
         if($billingAddress){
             $addressBillingDetails = [
                 'billingBusinessName' => $billingAddress->businessName ,
                 'billingFirstName' => $billingAddress->firstName ,
                 'billingLastName' => $billingAddress->lastName ,
                 'billingAddress1' => $billingAddress->address1 ,
                 'billingAddress2' => $billingAddress->address2 ,
                 'billingAddress3' => $billingAddress->address3 ,
                 'billingCity' => $billingAddress->city ,
                 'billingPostcode' => $billingAddress->zipCode ,
             ];

         }
         
         if($shippingAddress){
              $addressShippingDetails = [
                  'shippingBusinessName' => $billingAddress->businessName ,
                  'shippingFirstName' => $billingAddress->firstName ,
                  'shippingLastName' => $billingAddress->lastName ,
                  'shippingAddress1' => $billingAddress->address1 ,
                  'shippingAddress2' => $billingAddress->address2 ,
                  'shippingAddress3' => $billingAddress->address3 ,
                  'shippingCity' => $billingAddress->city ,
                  'shippingPostcode' => $billingAddress->zipCode ,
              ];

          }
        

           
         $exportCustomer = array_merge(
             $addressBillingDetails, $addressShippingDetails
         );
         
         

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
             
             
             
                 
    
              
             $lineItemStatus =  $lineItem->lineItemStatus;

             $lineItemStatusName = "";
             $lineItemStatusHandle="";
             $lineItemStatusColor = "";
             $lineItemStatusId="";
             
             if ( $lineItemStatus )
             {  
                $lineItemStatusId =  $lineItemStatus->id;
                 $lineItemStatusName =  $lineItemStatus->name;
                  $lineItemStatusHandle=$lineItemStatus->handle;
                  $lineItemStatusColor = $lineItemStatus->color;
     
             }
                    $lineItemDetails = [
                          'lineItemStatusName' => $lineItemStatusName ,
                          'lineItemStatusHandle' => $lineItemStatusHandle,
                          'lineItemStatusColor' => $lineItemStatusColor,
                          'lineItemStatusId' => $lineItemStatusId,
                          'lineItemOptions' => json_encode($lineItem->options),
                          'lineItemQty' => $lineItem->qty,
                          'lineItemTotal' => $lineItem->total,
                          'lineItemSubtotal' => $lineItem->subtotal,
                          'lineItemPrice' => $lineItem->price,
                          'lineItemSalePrice' => $lineItem->salePrice,
                          'lineItemSaleAmount' => $lineItem->saleAmount,
                          'lineItemNote' => $lineItem->note,
                          'lineItemPrivateNote' => $lineItem->privateNote,
                      ];
                     
                            
           
          

             /*
             
              If there are donations then there will not be a product array in the snapshot. the snapshot will look more like this:
              {"price":0,"sku":"DONATION-CC3","description":"Donation","purchasableId":6364,"cpEditUrl":"#","options":{"donationAmount":"20","giftaid":"Yes"},"sales":[]}
             */
            if (  array_key_exists('purchasableId',$snapshotArray ) && $snapshotArray['purchasableId'] == $donationPurchasableId )
            {
              $productTypes[ $snapshotArray['sku'] ] = "Donation";
            }
            elseif ( !array_key_exists('product',$snapshotArray ) )
            {
              $productTypes[$snapshotArray['sku']] = $snapshotArray['sku'];
            }
            elseif(!array_key_exists($snapshotArray['product']['typeId'], $productTypes))
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

             // If donation: 
             if (  array_key_exists('purchasableId',$snapshotArray ) && $snapshotArray['purchasableId'] == $donationPurchasableId )
             {

                      $productData = [

                        'productId'     => $donationPurchasableId,
                        'productTypeName' => $productTypes[ $snapshotArray['sku'] ],
                        'productTitle'  => "Donation",
                      ];

                  $donationVariantId =  $donationPurchasableId."-".$lineItem->subtotal;
                  $donationVariantTitle = "Donation - ".$lineItem->subtotal;

                        $variantData = [
                          'productId'     => $donationPurchasableId,
                          'productTypeName' => $productTypes[ $snapshotArray['sku'] ],
                          'productTitle'  => "Donation",
                          'variantId'     => $donationVariantId,
                          'variantTitle'  => $donationVariantTitle,      
                        ];
      
             }
             elseif(array_key_exists('product', $snapshotArray)  )
             {

      
                        $productData = [
                          'productId'     => $snapshotArray['product']['id'],
                          'productTypeName' => $productTypes[ $snapshotArray['product']['typeId'] ],
                          'productTitle'  => $snapshotArray['product']['title'],
                        ];
      
                        $variantData = [
                          'productId'     => $snapshotArray['product']['id'],
                          'productTypeName' => $productTypes[ $snapshotArray['product']['typeId'] ],
                          'productTitle'  => $snapshotArray['product']['title'],
                          'variantId'     => $snapshotArray['id'],
                          'variantTitle'  => $snapshotArray['title'],      
                        ];
      

             }
            $fullProductDetails = array_merge($productData,$orderDetails,$orderUserDetails,$lineItemDetails,$exportCustomer);
            $fullVariantDetails = array_merge($variantData,$orderDetails,$orderUserDetails,$lineItemDetails,$exportCustomer);
             unset($productData);
             unset($variantData);
             unset($snapshotArray);
             unset($lineItemDetails);
             
             
             $productPurchases[] = $fullProductDetails;
             $variantPurchases[] = $fullVariantDetails;

             unset($fullProductDetails);
              unset($fullVariantDetails);
         } // end foreach lineitem

     }// end foreach order
     
     
     

   
        $return =  [
          'productPurchases' => $productPurchases,
          'variantPurchases' => $variantPurchases,
          'totals' => [
            'customers' => count($customers),
            'products' => count($products),
            'variants' => count($variants),
            'productTypes' => count($productTypes),

          ]
          //'totals' => $purchasesTotals

        ];

        return $return;


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
            
            
            $totals = [
                'qty' => 0,
                'total' => 0,
            ];
            foreach($purchases as $purchase)
            {
                $totals['qty'] +=  $purchase['lineItemQty'];
                $totals['total'] += $purchase['lineItemTotal'];
            }

        return [
          'purchases' => $purchases,
          'totals' => $totals
          //'totals' => $purchasesTotals

        ];




    }
    // close get purchases by product id

}
