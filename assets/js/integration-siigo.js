(function($){

    const buttons = 'button.siigo-sync, button.siigo-sync-webhook';
    const actionSyncProducts = 'integration_siigo_sync_products';
    const actionSyncWebhook = 'integration_siigo_sync_webhook';

    const messages = {
        [actionSyncProducts]: {
            title: 'Sincronizando productos',
            successTitle: 'Productos sincronizados'
        },
        [actionSyncWebhook]: {
            title: 'Habilitando webhook',
            successTitle: 'Webhook habilitado'
        }
    }

    $(buttons).click(function (e) {
        const self = $(this);
        const action = self.hasClass('siigo-sync') ? actionSyncProducts : actionSyncWebhook;

        e.preventDefault();

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
                        title: messages[action].successTitle,
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
})(jQuery);