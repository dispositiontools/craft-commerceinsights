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
use craft\commerce\elements\Variant as VariantElement;
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
     * This function gets the products details for export.
     *
     * From any other plugin file, call it like this:
     *
     *     Commerceinsights::$plugin->products->getProductsDataForExport();
     *
     * @return mixed
     */
    public function getProductsDataForExport($options)
    {

      $siteId = "*";

      if( array_key_exists('siteId', $options ))
      {
        $siteId = $options['siteId'];
      }
     
      $productQuery = ProductElement::find();
      $productQuery->siteId($siteId);
      
    
      if( array_key_exists('typeId', $options ))
      {
        $productQuery->typeId( $options['typeId'] );
      }

      $productQuery->orderBy("typeId asc");
      $products = $productQuery->all();
      $exportArray = [];
      
      foreach($products as $product)
      {
        /*
        echo "Product name: ".$product->title;
        echo "\n";
        echo "Product type: ".$product->type->name;
        echo "\n";
        */

        $variantCountId = 0;
        foreach( $product->getVariants() as $variant )
        {
          
          $variantCountId++;
          /*
          echo "Variant: ".$variant->title;
          echo "\n";
          echo "Variant id: ".$variant->id;
          echo "\n";
          echo "Variant price: ".$variant->price;
          echo "\n";
          echo "Variant stock: ".$variant->stock;
          echo "\n";
          echo "Variant sku: ".$variant->sku;
          echo "\n";
          echo "Variant weight: ".$variant->weight;
          echo "\n";
          echo "Variant width: ".$variant->width;
          echo "\n";
          echo "Variant length: ".$variant->length;
          echo "\n";
          echo "Variant depth: ".$variant->height;
          echo "\n";

          echo "Variant minQty: ".$variant->minQty;
          echo "\n";

          echo "Variant maxQty: ".$variant->maxQty;
          echo "\n";

          echo "Variant Has unlimited stock: ".$variant->hasUnlimitedStock;
          echo "\n";



          echo "product Has freeShipping: ".$product->freeShipping;
          echo "\n";


          echo "product promotable: ".$product->promotable;
          echo "\n";

          echo "product availableForPurchase: ".$product->availableForPurchase;
          echo "\n";

          echo "product enabled: ".$product->enabled;
          echo "\n";


          echo "variant enabled: ".$variant->enabled;
          echo "\n";


          echo "product Created ".$product->dateCreated->format("Y-m-d");
          echo "\n";

          echo "product updated ".$product->dateUpdated->format("Y-m-d");
          echo "\n";

          echo "Variant Created ".$variant->dateCreated->format("Y-m-d");
          echo "\n";

          echo "Variant updated ".$variant->dateUpdated->format("Y-m-d");
          echo "\n";
          */




          if($variantCountId == 1 )
          {
            $productId = $product->id;
            $productTitle = $product->title;
            $productTypeId = $product->type->id;
            $productType = $product->type->name;
            $productEnabled = $product->enabled;
            $productFreeShipping = $product->freeShipping;
            $productPromotable = $product->promotable;
            $productAvailableForPurchase = $product->availableForPurchase;
         
            $productCreated = $product->dateCreated->format("Y-m-d");
            $productUpdated = $product->dateUpdated->format("Y-m-d");


            $exportRow = [

              "Product id" => $productId,
              "Product title" => $productTitle ,
              "Product type" => $productType,
              "Product type id" => $productTypeId,
              "Product enabled" => $productEnabled,
              "Product free shipping" => $productFreeShipping,
              "Product promotable" => $productPromotable,
              "Product available for purchase" => $productAvailableForPurchase,
           
              "Product created" => $productCreated,
              "Product updated" => $productUpdated,
              
              "Variant id" => "",
              "Variant title" => "",
              "Variant price" => "",
              "Variant stock" => "",
              "Variant sku" => "",
              "Variant weight" => "",
              "Variant width" => "",
              "Variant length" => "",
              "Variant depth" => "",
              "Variant minQty" => "",
              "Variant maxQty" => "",
              "Variant has unlimited stock" => "",
              "Variant enabled" => "",
              "Variant created" => "",
              "Variant updated" => "",
            ];

            $exportArray[] = $exportRow;
          }

          $productId = $product->id;
          $productTitle = "";
          $productType = "";
          $productTypeId = "";
          $productEnabled = "";
          $productFreeShipping = "";
          $productPromotable = "";
          $productAvailableForPurchase ="";
       
          $productCreated = "";
          $productUpdated = "";

              $exportRow = [

                "Product id" => $productId,
                "Product title" => " ",
                "Product type" => $productType,
                "Product type id" => $productTypeId,
                "Product enabled" => $productEnabled,
                "Product free shipping" => $productFreeShipping,
                "Product promotable" => $productPromotable,
                "Product available for purchase" => $productAvailableForPurchase,
            
                "Product created" => $productCreated,
                "Product updated" => $productUpdated,

                "Variant id" => $variant->id,
                "Variant title" => $variant->title,
                
                "Variant price" => $variant->price,
                "Variant stock" => $variant->stock,
                "Variant sku" => $variant->sku,
                "Variant weight" => $variant->weight,
                "Variant width" => $variant->width,
                "Variant length" => $variant->length,
                "Variant depth" => $variant->height,
                "Variant minQty" => $variant->minQty,
                "Variant maxQty" => $variant->maxQty,
                "Variant has unlimited stock" => $variant->hasUnlimitedStock,
                "Variant enabled" => $variant->enabled,
                "Variant created" => $variant->dateCreated->format("Y-m-d"),
                "Variant updated" => $variant->dateUpdated->format("Y-m-d"),
              ];
              $exportArray[] = $exportRow;
     
         

         



          

         
        


        }



       // echo "\n \n";
      }



      return $exportArray;
    }


    /**
 * This function gets the donation details
 *
 * From any other plugin file, call it like this:
 *
 *     Commerceinsights::$plugin->products->getDonationDetails();
 *
 * @return mixed
 */

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
 * This function gets the best selling products 
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


     $orderQuery = OrderElement::find()->isCompleted(true);
     /*
     if($startDate && $endDate)
     {
         $orderQuery->dateOrdered(['and', ">= {$startDate}", "<= {$endDate}"]);
     }
     elseif($startDate)
     {
           $orderQuery->dateOrdered(">= {$startDate}");
     }
     elseif($endDate)
     {
       $orderQuery->dateOrdered("<= {$endDate}");
     }
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

        //print_r($purchasableIds);
/*

SELECT cv.sku,
cv.id as variantId,
cp.id as productId,
cvc.title as variantTitle,
cpc.title as productTitle
FROM craft_commerce_variants as cv
LEFT JOIN craft_commerce_products as cp on "cp"."id" = "cv"."productId"
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
          ->leftJoin(['cp' => Table::PRODUCTS],'[[cp.id]] = [[cv.productId]]')
          ->leftJoin(['cpt' => Table::PRODUCTTYPES],'[[cpt.id]] = [[cp.typeId]]')
          ->leftJoin(['cvc' => CraftTable::CONTENT],'[[cvc.elementId]] = [[cv.id]]')
          ->leftJoin(['cpc' => CraftTable::CONTENT],'[[cpc.elementId]] = [[cp.id]]')
          ->where('[[co.isCompleted]] = true');



        $query = (new Query())
            ->select([
              /*
              'p.productTypeId',
              'p.productTypeName',
              'p.productTitle',
              'p.variantTitle',
              */
              'co.id as orderId',
              'co.email as orderEmail',
              'co.orderSiteId as orderSiteId',
              'co.customerId as customerId',
              'cu.id as userId',
              'cu.email as userEmail',
              'cu.firstName as userFirstName',
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
              'cli.purchasableId as purchasableId' ,
              'cli.snapshot as purchasableSnapshot' ,
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

              'cosa.organization as shippingOrganization',
              'cosa.firstName as shippingFirstName',
              'cosa.lastName as shippingLastName',
              'cosa.addressLine1 as shippingAddressLine1',
              'cosa.addressLine2 as shippingAddressLine2',
              'cosa.administrativeArea as shippingAdministrativeArea',
              'cosa.locality as shippingLocality',
              'cosa.countryCode as shippingCountryCode',
              'cosa.postalCode as shippingPostalCode',

              'coba.organization as billingOrganization',
              'coba.firstName as billingFirstName',
              'coba.lastName as billingLastName',
              'coba.addressLine1 as billingAddressLine1',
              'coba.addressLine2 as billingAddressLine2',
              'coba.administrativeArea as billingAdministrativeArea',
              'coba.locality as billingLocality',
              'coba.countryCode as billingCountryCode',
              'coba.postalCode as billingPostalCode',
          ]
            )
            ->from(['cli' => Table::LINEITEMS])
            ->leftJoin(['co' => Table::ORDERS],'[[co.id]] = [[cli.orderId]]')
            ->leftJoin(['cu' => CraftTable::USERS],'[[cu.id]] = [[co.customerId]]')
            ->leftJoin(['cs' => CraftTable::SITES],'[[cs.id]] = [[co.orderSiteId]]')
            ->leftJoin(['cos' => Table::ORDERSTATUSES],'[[cos.id]] = [[co.orderStatusId]]')
            ->leftJoin(['clis' => Table::LINEITEMSTATUSES],'[[clis.id]] = [[cli.lineItemStatusId]]')
            ->leftJoin(['cosa' => CraftTable::ADDRESSES],'[[cosa.id]] = [[co.shippingAddressId]]')
            ->leftJoin(['coba' => CraftTable::ADDRESSES],'[[coba.id]] = [[co.billingAddressId]]')
            //->leftJoin(['p' => $productVariantsQuery ], '[[p.variantDetailsId]] = [[cli.purchasableId]]')
            ->where('[[co.isCompleted]] = true');
            /*
                    AND co.datePaid > '2020-12-01'  and co.datePaid < '2021-01-01'
            */

            if ($startDate)
            {
                $query->andWhere('DATE([[co.dateOrdered]]) >= :dateOrderedStart', [ ':dateOrderedStart' => $startDate ]);
            }

            if ($endDate)
            {
                $query->andWhere('DATE([[co.dateOrdered]]) <= :dateOrderedEnd', [ ':dateOrderedEnd' => $endDate ]);
            }
            $query->orderBy('[[co.dateOrdered]] ASC');
            $purchases = $query->all();

             $productTypes = [];

            foreach ($purchases as $key => $purchase)
            {

              $purchasableSnapshot = json_decode($purchase['purchasableSnapshot'], true);



            if (  array_key_exists('purchasableId',$purchasableSnapshot ) && $purchasableSnapshot['purchasableId'] == $donationPurchasableId )
            {
              $productTypes[ $purchasableSnapshot['sku'] ] = "Donation";
            }
            elseif ( !array_key_exists('product',$purchasableSnapshot ) )
              {
                $productTypes[$purchasableSnapshot['sku']] = $purchasableSnapshot['sku'];
              }
              elseif(!array_key_exists($purchasableSnapshot['product']['typeId'], $productTypes))
              {
                  $productType = Commerce::getInstance()->ProductTypes->getProductTypeById($purchasableSnapshot['product']['typeId']);
                  if($productType)
                  {
                      $productTypes[ $purchasableSnapshot['product']['typeId'] ] = $productType->name;
                      unset( $productType );
                  }
                  else
                  {
                      $productTypes[ $purchasableSnapshot['product']['typeId'] ] = $snapshotArray['product']['typeId'];
                  }


              }

              if (  array_key_exists('purchasableId',$purchasableSnapshot ) && $purchasableSnapshot['purchasableId'] == $donationPurchasableId )
              {
                  $donationVariantId =  $donationPurchasableId."-".$purchase['lineItemSubtotal'];
                  $donationVariantTitle = "Donation - ".$purchase['lineItemSubtotal'];
                $purchases[$key]['variantTitle'] = $donationVariantTitle;
                $purchases[$key]['productTitle'] = "Donation";
                $purchases[$key]['productTypeId'] = $donationPurchasableId;
                $purchases[$key]['productTypeName'] = "Donation";
              }
              else
              {
                $purchases[$key]['variantTitle'] = $purchasableSnapshot['title'];
                $purchases[$key]['productTitle'] = $purchasableSnapshot['product']['title'];
                $purchases[$key]['productTypeId'] = $purchasableSnapshot['product']['typeId'];
                $purchases[$key]['productTypeName'] = $productTypes[ $purchasableSnapshot['product']['typeId'] ];
              }
              

              unset($purchases[$key]['purchasableSnapshot']);
              unset($purchase);
            }




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
        ->where('[[cv.productId]]=:productId',[':productId'=>$productId])->all();


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
              'cu.id as userId',
              'cu.email as userEmail',
              'cu.firstName as userFirstName',
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

              'cosa.organization as shippingOrganization',
              'cosa.firstName as shippingFirstName',
              'cosa.lastName as shippingLastName',
              'cosa.addressLine1 as shippingAddressLine1',
              'cosa.addressLine2 as shippingAddressLine2',
              'cosa.administrativeArea as shippingAdministrativeArea',
              'cosa.locality as shippingLocality',
              'cosa.countryCode as shippingCountryCode',
              'cosa.postalCode as shippingPostalCode',

              'coba.organization as billingOrganization',
              'coba.firstName as billingFirstName',
              'coba.lastName as billingLastName',
              'coba.addressLine1 as billingAddressLine1',
              'coba.addressLine2 as billingAddressLine2',
              'coba.administrativeArea as billingAdministrativeArea',
              'coba.locality as billingLocality',
              'coba.countryCode as billingCountryCode',
              'coba.postalCode as billingPostalCode',
          ]
            )
            ->from(['cli' => Table::LINEITEMS])
            ->leftJoin(['co' => Table::ORDERS],'[[co.id]] = [[cli.orderId]]')
            ->leftJoin(['cu' => CraftTable::USERS],'[[cu.id]] = [[co.customerId]]')
            ->leftJoin(['cs' => CraftTable::SITES],'[[cs.id]] = [[co.orderSiteId]]')
            ->leftJoin(['cos' => Table::ORDERSTATUSES],'[[cos.id]] = [[co.orderStatusId]]')
            ->leftJoin(['clis' => Table::LINEITEMSTATUSES],'[[clis.id]] = [[cli.lineItemStatusId]]')
            ->leftJoin(['cosa' => CraftTable::ADDRESSES],'[[cosa.id]] = [[co.shippingAddressId]]')
            ->leftJoin(['coba' => CraftTable::ADDRESSES],'[[coba.id]] = [[co.billingAddressId]]')
            ->where('[[co.isCompleted]] = true')
            ->andWhere(['in','[[cli.purchasableId]]', $purchasableIds]);

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
