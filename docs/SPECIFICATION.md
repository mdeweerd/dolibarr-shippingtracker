# Specification - shippingtracker

Baseline specification of the existing behaviour of the ShippingTracker
module (1.0.0), written when the CI was set up: the document states what
exists today and is the reference for every commit and test. Anything
not stated here is out of scope.

## Purpose and context

ShippingTracker adds shipment tracking (AWB number, carrier, tracking
link) to supplier orders and supplier invoices in Dolibarr, with
automatic sync from the orders to their invoices. It is a Dolibarr
module (descriptor: core/modules/modShippingTracker.class.php, module
id 104300) deployed as htdocs/custom/shippingtracker.

## Hard constraints

- Target platform: Dolibarr 19.0 minimum (README); the CI exercises it
  against 24.x with PHP 8.3.
- Depends on modExpedition (carriers/shipping methods).
- Extrafields only, no SQL tables of its own; no core file modification;
  hooks and triggers only.
- Activation must stay replayable (extrafield creation guarded).

## Roles

- Dolibarr administrator: activates the module, reads the setup page.
- User holding the manage_shipping_tracking right: views and edits the
  tracking information on supplier orders and invoices.

## Functional requirements

- FR-1 Extrafields tracking_awb (varchar 64), carrier_code (varchar 16)
  and tracking_link (url 255) on commande_fournisseur and
  facture_fourn, created at activation
  (lib/handlers/shipping_handler.php, ensure_tracking_extrafields).
- FR-2 Hooks ordersuppliercard and invoicesuppliercard
  (class/actions_shippingtracker.class.php): a "Shipping Tracking"
  section with Modify/Save on the supplier order and invoice cards.
- FR-3 Tracking URL generated from the carrier tracking template
  ({TRACKID}) unless a direct link is entered.
- FR-4 Sync: tracking from a supplier order syncs to its linked
  supplier invoices on SUPPLIER_ORDER_MODIFY (core/triggers);
  a divergence between an invoice and its order warns on
  SUPPLIER_INVOICE_MODIFY.
- FR-5 Permission "Manage shipping tracking"
  (manage_shipping_tracking).
- FR-6 Setup page (admin/shippingtracker_setup.php, admin only) with
  the expedition dependency status.

## Non-functional requirements

- NFR-1 Activation replay: enabling an already-enabled module replays
  the extrafield creation without error (guarded creation).
- NFR-2 CSRF: every POST form and state-changing GET carries the
  token.
- NFR-3 Translations: every UI string in the lang files (fr_FR first,
  en_US second); no hardcoded string in the pages.
- NFR-4 CI: lint (php -l, pre-commit), static analysis (phpstan, phan,
  from generated baselines), instance tests and page smoke against a
  real Dolibarr - see .github/workflows.

## Acceptance criteria summary

Activation on a Dolibarr 24.0.4 demo instance (with the expedition
module enabled) succeeds; the setup page answers 200 with its marker
and no PHP error; the pre-commit hooks pass over the whole tree; the
analysers pass from their committed baselines and fail on new findings.

## Out of scope

- The Dolibarr expedition shipment tracking itself (this module tracks
  supplier shipments, not customer deliveries).
- Customer-side (not supplier) tracking.
- Notifications or emails about shipment status.
- Parcel-level (multi-parcel) tracking.
