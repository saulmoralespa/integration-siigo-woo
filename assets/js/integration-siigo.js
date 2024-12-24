(function($){

    const buttons = 'button.siigo-sync, button.siigo-sync-woo-siigo, button.siigo-sync-webhook';
    const actionSyncProducts = 'integration_siigo_sync_products';
    const actionSyncProductsWooSiigo = 'integration_siigo_sync_woo_siigo';
    const actionSyncWebhook = 'integration_siigo_sync_webhook';

    const messages = {
        [actionSyncProducts]: {
            title: 'Agendando sincronización',
            confirmText: 'Vas a sincronizar los productos desde Siigo',
            successText: 'La sincronización de productos desde Siigo se ha agendado correctamente'
        },
        [actionSyncProductsWooSiigo]: {
            title: 'Agendando sincronización',
            confirmText: 'Vas a sincronizar los productos hacia Siigo',
            successText: 'La sincronización de productos hacia Siigo se ha agendado correctamente'
        },
        [actionSyncWebhook]: {
            title: 'Habilitando webhook',
            successText: 'Webhook habilitado'
        }
    }

    $(buttons).click(function (e) {
        const self = $(this);

        let action = actionSyncWebhook

        if(self.hasClass('siigo-sync')){
            action = actionSyncProducts;
        } else if (self.hasClass('siigo-sync-woo-siigo')) {
            action = actionSyncProductsWooSiigo;
        }

        e.preventDefault();


        Swal.fire({
            title: "¿Estás seguro?",
            text: messages[action].confirmText,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Procede",
            cancelButtonText: "Cancela",
            reverseButtons: true
        }).then((result) => {

            if (!result.isConfirmed) return;

            $.ajax({
                data: {
                    action,
                    nonce: $(this).data("nonce")
                },
                type: 'POST',
                url: ajaxurl,
                dataType: "json",
                beforeSend : () => {
                    Swal.fire({
                        title: messages[action].title,
                        didOpen: () => {
                            Swal.showLoading()
                        },
                        allowOutsideClick: false
                    });
                },
                success: (r) => {
                    if (r.status){
                        Swal.fire({
                            icon: 'success',
                            text: messages[action].successText,
                            allowOutsideClick: false,
                            showCloseButton: true,
                            showConfirmButton: false
                        })
                    }else{
                        Swal.fire(
                            'Error',
                            r.message ?? 'Ha ocurrido un error inesperado',
                            'error'
                        );
                    }
                }
            });
        });
    });
})(jQuery);