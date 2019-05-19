# InventoryInStorePickupCheckoutApi module

The `InventoryInStorePickupCheckoutApi` module provides service contracts for checkout and shipment implementation of In-Store Pickup functionality.

This module is part of the new inventory infrastructure. The
[Inventory Management overview](https://devdocs.magento.com/guides/v2.3/inventory/index.html)
describes the MSI (Multi-Source Inventory) project in more detail.

## Installation details

This module is installed as part of Magento Open Source. Unless a custom implementation for `InventoryInStorePickupCheckoutApi`
is provided by a 3rd-party module, the module cannot be deleted or disabled.

## Extensibility

The `InventoryInStorePickupCheckoutApi` module contains extension points and APIs that 3rd-party developers
can use to provide customization of In-Store Pickup functionality

### Public APIs

Public APIs are defined in the `Api` and `Api/Data` directories.

### REST endpoints

The `etc/webapi.xml` file defines endpoints for configuring in store pickup for sources.
