# Shipping Tracker Module for Dolibarr

## Overview

The ShippingTracker module adds tracking number and carrier information to supplier orders and supplier invoices in Dolibarr ERP/CRM. This module allows you to track shipments from suppliers and automatically sync tracking information between linked orders and invoices.

## Features

- **Tracking Information**: Add Air Waybill (AWB) number, carrier code, and tracking URL to supplier orders and invoices
- **Carrier Selection**: Select from all carriers/shipping methods configured in the Expedition module
- **Automatic URL Generation**: Automatically generates tracking URLs based on carrier tracking templates
- **Auto-Sync**: Tracking information from supplier orders is automatically synced to linked supplier invoices
- **Manual Override**: Direct tracking URLs can be entered if needed
- **Warning System**: Warns when an invoice has different tracking than its linked order

## Requirements

- Dolibarr 19.0 or higher
- Expedition module must be enabled (for carrier configuration)

## Installation

1. Copy the `shippingtracker` directory to `htdocs/custom/` in your Dolibarr installation
2. Log in to Dolibarr as an administrator
3. Go to **Home > Setup > Modules/Applications**
4. Find **ShippingTracker** in the list (under "Other" family)
5. Click **Activate**
6. The module will automatically create the required extrafields

## Usage

### For Supplier Orders

1. Navigate to a supplier order (Purchases > Supplier Orders)
2. Open the order card
3. In the **Shipping Tracking** section:
   - Click **Modify** to edit tracking information
   - Enter the **Tracking Number (AWB)**
   - Select the **Sending Method** (carrier)
   - Optionally enter a custom **Tracking Link** (if not provided, it will be generated from the carrier)
   - Click **Save**

4. The tracking information will be automatically synced to all linked supplier invoices

### For Supplier Invoices

1. Navigate to a supplier invoice (Purchases > Supplier Invoices)
2. Open the invoice card
3. In the **Shipping Tracking** section:
   - View the tracking information (inherited from the linked order)
   - Click **Modify** to edit tracking information directly on the invoice
   - Changes on the invoice will not affect the linked order

### Viewing Tracking Links

- Tracking links are displayed as clickable URLs
- Click on a tracking link to open the carrier's tracking page in a new tab
- If the carrier has a tracking URL template configured in the Expedition module, the link is generated automatically

## Configuration

1. Go to **Home > Setup > Modules/Applications > ShippingTracker**
2. The module must be activated
3. The Expedition module must be enabled for carrier selection to work

### Carrier Configuration

Carriers are configured in the Expedition module:
1. Go to **Expedition > Setup > Shipping Methods**
2. Add or edit shipping methods
3. For each carrier, configure the **Tracking URL** template (e.g., `https://www.fedex.com/tracking?tracknumbers={TRACKID}`)
4. The `{TRACKID}` placeholder will be replaced with the actual tracking number

## Permissions

The module adds the following permission:
- **Manage shipping tracking**: Allows users to view and edit tracking information on supplier orders and invoices

Assign this permission to users through:
- **Home > Users & Groups > Users** > Edit user > Permissions tab
- **Home > Users & Groups > Groups** > Edit group > Permissions tab

## Technical Details

### Extrafields

The module creates the following extrafields for both `commande_fournisseur` (supplier orders) and `facture_fournisseur` (supplier invoices):

| Field Name | Type | Size | Description |
|------------|------|------|-------------|
| `tracking_awb` | varchar | 64 | Tracking number / Air Waybill |
| `tracking_link` | url | 255 | Tracking URL |
| `carrier_code` | varchar | 16 | Carrier/shipment method code |

### Hooks

The module uses the following hooks:
- `ordersuppliercard`: To display and manage tracking on supplier order cards
- `invoicesuppliercard`: To display and manage tracking on supplier invoice cards

### Triggers

The module uses the following triggers:
- `SUPPLIER_ORDER_MODIFY`: To sync tracking from orders to linked invoices when an order is modified
- `SUPPLIER_INVOICE_MODIFY`: To check consistency between invoice and order tracking

## Troubleshooting

### Module not appearing in the setup page

- Check that the module directory is in the correct location: `htdocs/custom/shippingtracker/`
- Verify that the module descriptor file exists: `htdocs/custom/shippingtracker/core/modules/modShippingTracker.class.php`
- Clear the Dolibarr cache

### Tracking information not saving

- Ensure the user has the **Manage shipping tracking** permission
- Check that the extrafields were created successfully (Setup > Custom Fields)
- Verify that the module is activated

### Carriers not appearing in the dropdown

- Ensure the Expedition module is enabled
- Check that shipping methods are configured in Expedition > Setup > Shipping Methods
- Verify that the shipping methods have the **Active** checkbox checked

### Tracking URL not generating

- Check that the carrier has a **Tracking URL** template configured
- Verify that the template contains the `{TRACKID}` placeholder
- Ensure both the AWB number and carrier code are provided

## Security

- The module follows Dolibarr's security best practices
- All actions are protected by Dolibarr's CSRF token system
- Permissions are checked before allowing edits
- Input is sanitized using Dolibarr's input validation functions

## License

This module is licensed under the GNU General Public License (GPL) version 3 or later.

## Repository

- **GitHub**: https://github.com/mdeweerd/dolibarr-shippingtracker

## Support

For support, issues, or feature requests:
- GitHub: https://github.com/mdeweerd/dolibarr-shippingtracker

## Version History

See the [ChangeLog.md](ChangeLog.md) file for detailed version history.
