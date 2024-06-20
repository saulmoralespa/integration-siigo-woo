(function($){
    $('button.siigo-sync').click(function (e) {

        e.preventDefault();

        $.ajax({
            data: {
                action: 'integration_siigo_sync_products',
                nonce: $(this).data("nonce")
            },
            type: 'POST',
            url: ajaxurl,
            dataType: "json",
            beforeSend : () => {
                Swal.fire({
                    title: 'Sincronizando productos',
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
                        title: 'Productos sincronizados',
                        allowOutsideClick: false,
                        showCloseButton: true,
                        showConfirmButton: false
                    })
                }else{
                    Swal.fire(
                        'Error',
                        r.message,
                        'error'
                    );
                }
            }
        });
    });
})(jQuery);