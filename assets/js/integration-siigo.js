(function($){

    const selectors = {
        siigoIntegrationFields: '#woocommerce_wc_siigo_integration_username, #woocommerce_wc_siigo_integration_access_key, #woocommerce_wc_siigo_integration_webhook_button',
        siigoIntegrationSandboxFields: '#woocommerce_wc_siigo_integration_sandbox_username, #woocommerce_wc_siigo_integration_sandbox_access_key',
        webhookButton: '#woocommerce_wc_siigo_integration_webhook_button',
        syncButton: '#woocommerce_wc_siigo_integration_sync_siigo_woo',
        webhookButtonHeader: 'h3#woocommerce_wc_siigo_integration_webhook_button',
        productsTitle: 'h3#woocommerce_wc_siigo_integration_products',
        environmentSelector: '#woocommerce_wc_siigo_integration_environment',
        accessKeyFieldTemplate: '#woocommerce_wc_siigo_integration_$_access_key'
    };

    const buttons = 'button.siigo-sync, button.siigo-sync-woo-siigo, button.siigo-sync-webhook';
    const premiumSurveyButton = 'button.siigo-send-premium-survey';
    const actionSyncProducts = 'integration_siigo_sync_products';
    const actionSyncProductsWooSiigo = 'integration_siigo_sync_woo_siigo';
    const actionSyncWebhook = 'integration_siigo_sync_webhook';
    const actionSendPremiumSurvey = 'integration_siigo_send_premium_survey';
    const actionDismissPremiumSurveyNotice = 'integration_siigo_dismiss_premium_survey_notice';

    function toggleFieldsByEnvironment() {
        const {
            siigoIntegrationFields,
            siigoIntegrationSandboxFields,
            webhookButton,
            syncButton,
            webhookButtonHeader,
            productsTitle,
            environmentSelector,
            accessKeyFieldTemplate
        } = selectors;

        const environmentElement = $(environmentSelector);

        if (!environmentElement.length) {
            return;
        }

        const isProduction = environmentElement.val() === '0';
        const integrationFields = isProduction ? siigoIntegrationFields : siigoIntegrationSandboxFields;
        const accessKeyField = accessKeyFieldTemplate.replace('$_', isProduction ? '' : 'sandbox_');

        $(siigoIntegrationSandboxFields + ',' + siigoIntegrationFields).closest('tr').hide();
        $(webhookButtonHeader).hide();

        $(integrationFields).closest('tr').show();
        $(webhookButtonHeader).toggle(isProduction);

        const accessKey = $(accessKeyField);
        const hasAccessKey = accessKey.length && accessKey.val().trim().length > 0;

        $(productsTitle).toggle(hasAccessKey);
        $(webhookButton).closest('tr').toggle(isProduction && hasAccessKey);
        $(syncButton).closest('tr').toggle(hasAccessKey);
    }

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
            confirmText: 'Vas a habilitar la sincronizacion por webhook',
            successText: 'Webhook habilitado'
        }
    }

    const premiumSurveyOptions = {
        q2: [
            { value: '', label: 'Selecciona una opcion' },
            { value: 'inventario', label: 'Inventario y stock desactualizado' },
            { value: 'facturacion', label: 'Errores al facturar en Siigo' },
            { value: 'sincronizacion_productos', label: 'Sincronizacion de productos' },
            { value: 'clientes_datos', label: 'Datos de cliente incompletos' },
            { value: 'soporte', label: 'Soporte y solucion de incidentes' },
            { value: 'otro', label: 'Otro' }
        ],
        q3: [
            { value: '', label: 'Selecciona una opcion' },
            { value: 'menos_1h', label: 'Menos de 1 hora' },
            { value: '1_3h', label: 'Entre 1 y 3 horas' },
            { value: '3_6h', label: 'Entre 3 y 6 horas' },
            { value: 'mas_6h', label: 'Mas de 6 horas' }
        ],
        q4: [
            { value: 'inventario_bidireccional', label: 'Inventario bidireccional en tiempo real' },
            { value: 'devoluciones_notas_credito', label: 'Devoluciones y notas credito automaticas' },
            { value: 'reportes_alertas', label: 'Reportes y alertas inteligentes' },
            { value: 'reglas_sync', label: 'Reglas avanzadas de sincronizacion' },
            { value: 'soporte_prioritario', label: 'Soporte prioritario' }
        ],
        q6: [
            { value: '', label: 'Selecciona una opcion' },
            { value: 'mensual', label: 'Suscripcion mensual' },
            { value: 'anual', label: 'Suscripcion anual' },
            { value: 'pago_unico', label: 'Pago unico' }
        ],
        q7: [
            { value: '', label: 'Selecciona una opcion' },
            { value: 'lt_49000', label: 'Menos de COP 49.000' },
            { value: '50000_99000', label: 'COP 50.000 - 99.000' },
            { value: '100000_199000', label: 'COP 100.000 - 199.000' },
            { value: 'gte_200000', label: 'COP 200.000 o mas' }
        ]
    };

    function buildSelectOptions(options) {
        return options
            .map((option) => `<option value="${option.value}">${option.label}</option>`)
            .join('');
    }

    function buildPremiumSurveyHtml() {
        const q1Options = Array.from({ length: 10 }, (_, index) => {
            const value = index + 1;
            return `<option value="${value}">${value}</option>`;
        }).join('');

        const q4Checkboxes = premiumSurveyOptions.q4.map((option) => {
            return `<label style="display:block;margin-bottom:6px;"><input type="checkbox" class="siigo-survey-q4" value="${option.value}" style="margin-right:6px;">${option.label}</label>`;
        }).join('');

        return `
            <div style="text-align:left;max-height:60vh;overflow:auto;padding-right:6px;">
                <label for="siigo-survey-q1" style="display:block;margin:8px 0 6px;">1. Que tan satisfecho estas con la version actual (1-10)? *</label>
                <select id="siigo-survey-q1" class="swal2-select" style="width:100%;margin:0 0 12px;">
                    <option value="">Selecciona una opcion</option>
                    ${q1Options}
                </select>

                <div id="siigo-survey-q1-motivo-wrapper" style="display:none;margin-bottom:12px;">
                    <label for="siigo-survey-q1-motivo" style="display:block;margin:8px 0 6px;"><strong>¿Por qué diste esa puntuación? (requerido) *</strong></label>
                    <textarea id="siigo-survey-q1-motivo" class="swal2-textarea" style="width:100%;margin:0;" rows="3" maxlength="500" placeholder="Cuéntanos qué podemos mejorar..."></textarea>
                </div>

                <label for="siigo-survey-q2" style="display:block;margin:8px 0 6px;">2. Cual es tu dolor principal hoy?</label>
                <select id="siigo-survey-q2" class="swal2-select" style="width:100%;margin:0 0 12px;">
                    ${buildSelectOptions(premiumSurveyOptions.q2)}
                </select>

                <label for="siigo-survey-q3" style="display:block;margin:8px 0 6px;">3. Cuanto tiempo semanal pierdes corrigiendo procesos manuales?</label>
                <select id="siigo-survey-q3" class="swal2-select" style="width:100%;margin:0 0 12px;">
                    ${buildSelectOptions(premiumSurveyOptions.q3)}
                </select>

                <label style="display:block;margin:8px 0 6px;">4. Elige hasta 3 funcionalidades premium que pagarias primero *</label>
                <div style="margin-bottom:12px;">${q4Checkboxes}</div>

                <label for="siigo-survey-q5" style="display:block;margin:8px 0 6px;">5. Cual de esas funcionalidades es la mas critica?</label>
                <input id="siigo-survey-q5" class="swal2-input" style="width:100%;margin:0 0 12px;" type="text" maxlength="120" placeholder="Ejemplo: Inventario en tiempo real">

                <label for="siigo-survey-q6" style="display:block;margin:8px 0 6px;">6. Modelo de cobro preferido</label>
                <select id="siigo-survey-q6" class="swal2-select" style="width:100%;margin:0 0 12px;">
                    ${buildSelectOptions(premiumSurveyOptions.q6)}
                </select>

                <label for="siigo-survey-q7" style="display:block;margin:8px 0 6px;">7. Que rango de precio mensual consideras razonable? *</label>
                <select id="siigo-survey-q7" class="swal2-select" style="width:100%;margin:0 0 12px;">
                    ${buildSelectOptions(premiumSurveyOptions.q7)}
                </select>

                <label for="siigo-survey-q8" style="display:block;margin:8px 0 6px;">8. Comentario abierto</label>
                <textarea id="siigo-survey-q8" class="swal2-textarea" style="width:100%;margin:0 0 12px;" maxlength="500" placeholder="Que deberia incluir la version premium para que la recomiendes?"></textarea>

                <label style="display:flex;align-items:center;gap:8px;">
                    <input id="siigo-survey-consent" type="checkbox"> Autorizo contacto para profundizar en mis respuestas.
                </label>
            </div>
        `;
    }

    function collectPremiumSurveyData() {
        const topFeatures = [];

        $('.siigo-survey-q4:checked').each(function() {
            topFeatures.push($(this).val());
        });

        const q1Score = parseInt($('#siigo-survey-q1').val(), 10);

        if (!$('#siigo-survey-q1').val() || !$('#siigo-survey-q7').val() || topFeatures.length === 0) {
            Swal.showValidationMessage('Completa satisfaccion (1-10), Top funcionalidades y rango de precio.');
            return false;
        }

        if (q1Score < 8) {
            const motivo = $('#siigo-survey-q1-motivo').val().trim();
            if (!motivo) {
                Swal.showValidationMessage('Por favor explica el motivo de tu baja satisfacción.');
                return false;
            }
        }

        if (topFeatures.length > 3) {
            Swal.showValidationMessage('Selecciona maximo 3 funcionalidades premium.');
            return false;
        }

        return {
            q1_score: q1Score,
            q1_motivo: $('#siigo-survey-q1-motivo').val().trim(),
            q2_pain_point: $('#siigo-survey-q2').val(),
            q3_time_loss: $('#siigo-survey-q3').val(),
            q4_top_features: topFeatures,
            q5_most_critical: $('#siigo-survey-q5').val(),
            q6_billing_model: $('#siigo-survey-q6').val(),
            q7_price_range: $('#siigo-survey-q7').val(),
            q8_open_feedback: $('#siigo-survey-q8').val(),
            consent_yes_no: $('#siigo-survey-consent').is(':checked') ? 'yes' : 'no'
        };
    }

    function sendPremiumSurveyResponse(nonce, surveyData) {
        $.ajax({
            data: {
                action: actionSendPremiumSurvey,
                nonce,
                ...surveyData
            },
            type: 'POST',
            url: ajaxurl,
            dataType: 'json',
            beforeSend: () => {
                Swal.fire({
                    title: 'Enviando respuesta',
                    didOpen: () => {
                        Swal.showLoading();
                    },
                    allowOutsideClick: false
                });
            },
            success: (response) => {
                if (response.status) {
                    Swal.fire({
                        icon: 'success',
                        text: response.message ?? 'Tu respuesta fue enviada correctamente.',
                        allowOutsideClick: false,
                        showCloseButton: true,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', response.message ?? 'No se pudo enviar la encuesta.', 'error');
                }
            },
            error: () => {
                Swal.fire('Error', 'No se pudo enviar la encuesta. Intenta nuevamente.', 'error');
            }
        });
    }

    function openPremiumSurveyModal(nonce) {
        if (!nonce) {
            Swal.fire('Error', 'No se pudo abrir la encuesta. Recarga la pagina e intenta nuevamente.', 'error');
            return;
        }

        Swal.fire({
            title: 'Encuesta version premium',
            html: buildPremiumSurveyHtml(),
            width: 760,
            showCancelButton: true,
            confirmButtonText: 'Enviar respuesta',
            cancelButtonText: 'Cancelar',
            focusConfirm: false,
            didOpen: () => {
                $('#siigo-survey-q1').on('change', function () {
                    const score = parseInt($(this).val(), 10);
                    if (score < 8) {
                        $('#siigo-survey-q1-motivo-wrapper').show();
                    } else {
                        $('#siigo-survey-q1-motivo-wrapper').hide();
                        $('#siigo-survey-q1-motivo').val('');
                    }
                });
            },
            preConfirm: collectPremiumSurveyData
        }).then((result) => {
            if (!result.isConfirmed || !result.value) {
                return;
            }

            sendPremiumSurveyResponse(nonce, result.value);
        });
    }

    function maybeOpenPremiumSurveyFromUrl() {
        if (typeof URLSearchParams === 'undefined') {
            return;
        }

        const params = new URLSearchParams(window.location.search);

        if (params.get('open_premium_survey') !== '1') {
            return;
        }

        const surveyTrigger = $(premiumSurveyButton).first();

        if (!surveyTrigger.length) {
            return;
        }

        openPremiumSurveyModal(surveyTrigger.data('nonce'));
    }

    function persistPremiumSurveyNoticeDismiss() {
        $(document).on('click', '.siigo-premium-survey-notice .notice-dismiss', function() {
            const notice = $(this).closest('.siigo-premium-survey-notice');
            const nonce = notice.data('dismissNonce');

            if (!nonce) {
                return;
            }

            $.ajax({
                data: {
                    action: actionDismissPremiumSurveyNotice,
                    nonce
                },
                type: 'POST',
                url: ajaxurl,
                dataType: 'json'
            });
        });
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

    $(document).on('click', premiumSurveyButton, function(e) {
        e.preventDefault();
        openPremiumSurveyModal($(this).data('nonce'));
    });

    $(selectors.environmentSelector).on('change', toggleFieldsByEnvironment);
    toggleFieldsByEnvironment();
    maybeOpenPremiumSurveyFromUrl();
    persistPremiumSurveyNoticeDismiss();
})(jQuery);