var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/quote': {
                'Magento_InventoryInStorePickupFrontend/js/model/quote-ext': true,
            },
            'Magento_Checkout/js/model/shipping-rates-validator': {
                'Magento_InventoryInStorePickupFrontend/js/model/shipping-rates-validator-ext': true,
            },
        },
    },
};
