# Release Notes for Commerce Insights (Craft CMS V3)

## 1.0.15 - 2024-01-14

### Updated
- Fixing an issue when an order has no billing address - with thanks to [pixelmachine](https://github.com/pixelmachine) fixing issue [#12](https://github.com/dispositiontools/craft-commerceinsights/issues/12)

## 1.0.14 - 2023-10-06

### Added
- Added Support for verbb/gift-voucher - with thanks to [pixelmachine](https://github.com/pixelmachine)


## 1.0.13 - 2023-10-06

### Fixed
- Fixed an issue with the products purchased between dates report on MySQL when set to ONLY_FULL_GROUP_MODE. Fixing issue [#10](https://github.com/dispositiontools/craft-commerceinsights/issues/10)

### Added
- Added Qty and Total totals to the purchasers table
- Added Nav highlighting 


## 1.0.12 - 2023-05-13

### Updated
- The Best Selling Products report caused an error when the order line item snapshot didn't have a product attached. This happens when using a Donation or can also happen when using a custom purchasable. The report now shows a donation as a donation product type. 

### Changed
- The Best Selling Products report, the Which products when and who by report and the transactions report now correctly show all the records within the date range. Fixing issue [#8](https://github.com/dispositiontools/craft-commerceinsights/issues/8)

## 1.0.11 - 2022-10-23

### Changed
- The purchases by date report now looked at the Date Ordered date rather than the Date Paid date. This is because the Date Paid date is removed when a refund is issued which means certain orders weren't being included in this report. The report still only includes orders that are marked as complete.
- Corrected a couple of links in the dashboard reports


## 1.0.10 - 2022-10-09

### Updated
- Corrected an issue with incorrectly calculated best selling product.
- Corrected an issue an error on the best selling products page when a product had been deleted. Fixing issue [#3](https://github.com/dispositiontools/craft-commerceinsights/issues/3) with thanks to maxpdesign and totov.

## 1.0.9 - 2022-05-01

### Added
- Added a new report to quickly see, and export, best selling products and variants between two dates.

## 1.0.8 - 2022-04-23

### Updated
- Corrected a max() ValueError on install with no orders.

## 1.0.7 - 2022-03-06

### Updated
- Corrected an only_full_group_by error on MySQL 8+. Fixing issue [#2](https://github.com/dispositiontools/craft-commerceinsights/issues/2) with thanks to [WHITE](https://github.com/WHITE-developer)

## 1.0.6 - 2022-02-23
- corrected a division by zero issue when viewing customers on a new install. Fixing issue [#1](https://github.com/dispositiontools/craft-commerceinsights/issues/1).

## 1.0.5 - 2021-09-13

## 1.0.4 - 2021-09-13
 - New tag to push tags 1.0.2 and 1.0.3 to the plugin store which were added between submitting to the store and being approved.

## 1.0.3 - 2021-09-11

## 1.0.2 - 2021-09-11

### Updated
- Update the readme and plugin icon.

## 1.0.1 - 2021-08-28

### Added
- Added code to check whether Craft Commerce is installed.

## 1.0.0 - 2021-08-26
Initial release of of Disposition Tools Commerce Insights.
View and export
- Customer summaries
- Transactions (including refunds)
- Products and who bought them
- Which products were bought when and who by
