<?php

$datePattern = '<view:month|week|day>/<year:\d+>/<month:\d+>/<day:\d+>';

return [
  'commerceinsights/customers'     => 'commerceinsights/cp/customers',
  'commerceinsights/customers/summary'     => 'commerceinsights/cp/customers-summary',
  'commerceinsights/customers/rfm'     => 'commerceinsights/cp/customers-rfm',

  'commerceinsights/products'     => 'commerceinsights/cp/products',
  'commerceinsights/products/best-selling'     => 'commerceinsights/cp/products-best-selling',




  'commerceinsights/transactions'     => 'commerceinsights/cp/transactions',

  'commerceinsights/products/purchases'  => 'commerceinsights/cp/products-purchases-by-order-dates',
  'commerceinsights/products/types'     => 'commerceinsights/cp/producttypes',
  'commerceinsights/products/<productId:\d+>'            => 'commerceinsights/cp/product',
  'commerceinsights/products/<productTypeSlug:\w+>'     => 'commerceinsights/cp/producttype',
];
