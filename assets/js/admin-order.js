jQuery(function ($) {
    const adminOrder = {
        init: function () {
            this.bindEvents();
        },

        bindEvents: function () {
            $(document).on('click', '#krokedil-support-export-order', this.handleOrderAction);
        },

        handleOrderAction: function (e) {
            e.preventDefault();
            const orderId = krokedil_support_admin_order_params.order_id;
            const { url, nonce } = krokedil_support_admin_order_params.ajax.export_order;
            const data = {
                action: 'krokedil_support_order_action',
                order_id: orderId,
                nonce: nonce
            };

            $.post(url, data, function (response) {
                if (response.success) {
                    // Download the response as a file.
                    try{
                        const jsonString = JSON.stringify(response.data, null, 2);
                        const blob = new Blob([jsonString], { type: 'application/json' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        const unixTimestamp = Math.floor(Date.now() / 1000);
                        a.href = url;
                        a.download = `${orderId}_${unixTimestamp}.json`;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                    }
                    catch(e){
                        console.error(e);
                    }
                } else {
                    console.error(response.data);
                }
            });
        }
    }

    adminOrder.init();
});
