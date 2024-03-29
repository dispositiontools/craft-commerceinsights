# Release Notes for Commerce Insights

## 4.0.2 - 2023-01-12

### Fixed
- Fixed an issue with the subnav items not showing the correct current page. Fixing issue [#6](https://github.com/dispositiontools/craft-commerceinsights/issues/6)

### Updated
- Now showing the amounts in the base currency number format. Fixing issue [#7](https://github.com/dispositiontools/craft-commerceinsights/issues/7)

## 4.0.1 - 2022-11-03

### Fixed
- Updated the plugin to work with PostgreSql. Fixing issue [#4](https://github.com/dispositiontools/craft-commerceinsights/issues/5)

## 4.0.0 - 2022-10-23

### Updated
- Upgraded for Craft 4
- The purchases by date report now looked at the Date Ordered date rather than the Date Paid date. This is because the Date Paid date is removed when a refund is issued which means certain orders weren't being included in this report. The report still only includes orders that are marked as complete.
- Addresses format has changed to reflect the new addresses in Craft 4
- As Customers are now simply Users some of the links to the Commerce Customers page now link directly to the User page
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
