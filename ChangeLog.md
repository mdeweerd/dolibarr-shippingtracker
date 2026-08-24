# ShippingTracker Module - Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] - 2026-08-25

### Added

- Initial release of ShippingTracker module
- Adds tracking functionality to supplier orders (commande_fournisseur)
- Adds tracking functionality to supplier invoices (facture_fournisseur)
- Creates extrafields for tracking_awb, tracking_link, and carrier_code
- Implements hooks for ordersuppliercard and invoicesuppliercard contexts
- Implements triggers for SUPPLIER_ORDER_MODIFY and SUPPLIER_INVOICE_MODIFY
- Auto-sync tracking from orders to linked invoices
- Automatic tracking URL generation based on carrier templates
- Permission system for managing shipping tracking
- Admin setup page for module configuration
- Comprehensive documentation (README.md)
- Multi-language support (English and French)

### Technical Details

- Module ID: 104300
- Module family: other
- Dependencies: modExpedition
- Compatible with Dolibarr 19.0+

---

[1.0.0]: https://github.com/mdeweerd/dolibarr-shippingtracker
