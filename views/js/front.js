$(document).ready(function() {
    // Update rates when delivery address changes
    $(document).on('change', '#delivery-address input[type="radio"]', function() {
        updateEasyPostRates();
    });

    function updateEasyPostRates() {
        $.ajax({
            url: prestashop.urls.base_url + 'module/easypostshipping/rates',
            data: {
                id_cart: prestashop.cart.id,
                ajax: 1
            },
            beforeSend: function() {
                $('.easypost-rates-container').html('<div class="loader"></div>');
            },
            success: function(data) {
                $('.easypost-rates-container').html(data);
                updateOrderSummary();
            }
        });
    }

    function updateOrderSummary() {
        // Trigger PrestaShop's native summary update
        prestashop.emit('updateDeliveryForm');
    }
});