(function($){
    $( document.body ).on( 'blur change', '#billing_dni, #shipping_dni', function(){
        const maxLengthNit = 9;
        const minLengthDocument = 7;
        const self = $(this);
        const wrapper = $(this).closest( '.form-row' );
        const container = self.closest('div');
        const typeDocumentField = container.find('select[name$="type_document"]');

        if((typeDocumentField.val() === 'NIT' &&
            self.val().length !== maxLengthNit) ||
            self.val().length < minLengthDocument
        ) {
            wrapper.addClass( 'woocommerce-invalid');
        }

    });
})(jQuery);