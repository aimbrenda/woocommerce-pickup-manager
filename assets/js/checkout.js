jQuery(document).ready(function($) {
    let flatpickrInstance = null;
    const $locationSelect = $('#pickup_location_id');
    const $dateInput = $('#pickup_date');
    const $dateField = $('#pickup_date_field');
    const $locationDetails = $('#pickup_location_details');
    const $deliveryNote = $('#delivery_note_details');
    const locationsDataRaw = $('#pickup_locations_data').val();
    let locationsData = [];

    if (locationsDataRaw) {
        try {
            locationsData = JSON.parse(locationsDataRaw);
        } catch (e) {
            console.warn('Invalid pickup locations payload, falling back to empty list.', e);
            locationsData = [];
        }
    }

    function getSelectedOption(locationId) {
        return locationsData.find(function(item) {
            return String(item.id) === String(locationId);
        });
    }

    function resetDatePicker() {
        if (flatpickrInstance) {
            flatpickrInstance.destroy();
        }
        flatpickrInstance = null;
        $dateInput.val('');
    }

    function setPickupMode() {
        $dateField.show().addClass('validate-required');
        $dateInput.prop('required', true).attr('placeholder', wcMultidropScheduler.placeholderSelectDate);
        $deliveryNote.hide().empty();
    }

    function setDeliveryMode() {
        resetDatePicker();
        $dateField.hide().removeClass('validate-required');
        $dateInput.prop('required', false).attr('placeholder', wcMultidropScheduler.placeholderSelectLocation);
        $locationDetails.hide().empty();
    }

    function renderDeliveryNote(processingDate) {
        if (!processingDate) {
            $deliveryNote.html('<p style="margin:0;">' + wcMultidropScheduler.deliveryNoDate + '</p>').slideDown();
            return;
        }

        const noteText = wcMultidropScheduler.deliveryNoteTemplate.replace('%s', processingDate);
        $deliveryNote.html('<p style="margin:0;">' + noteText + '</p>').slideDown();
    }

    // CRITICAL: Clear chosen shipping method when location changes
    function clearChosenShipping() {
        // Remove the chosen shipping method from session storage
        sessionStorage.removeItem('wc_shipping_chosen');
        
        // Also clear any radio button selection
        $('input[name^="shipping_method"]').prop('checked', false);
        
        console.log('Cleared chosen shipping method');
    }

    // CRITICAL: Intercept WooCommerce's update_order_review AJAX to ensure pickup_location_id is included
    $(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function() {
        const locationId = $locationSelect.val();
        if (locationId && $locationSelect.attr('name') !== 'pickup_location_id') {
            $locationSelect.attr('name', 'pickup_location_id');
        }
    });

    $locationSelect.on('change', function() {
        const locationId = $(this).val();
        const selectedOption = getSelectedOption(locationId);

        // CRITICAL: Ensure name attribute is ALWAYS set
        $(this).attr('name', 'pickup_location_id');

        // CRITICAL: Clear the chosen shipping method
        clearChosenShipping();

        if (!locationId) {
            resetDatePicker();
            $dateInput.val('').attr('placeholder', wcMultidropScheduler.placeholderSelectLocation);
            $locationDetails.hide().empty();
            $deliveryNote.hide().empty();
            $dateField.show();
            $(document.body).trigger('update_checkout');
            return;
        }

        const fulfillmentType = selectedOption?.fulfillment_type || 'pickup';

        if (fulfillmentType === 'delivery') {
            setDeliveryMode();

            $.ajax({
                url: wcMultidropScheduler.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_location_details',
                    nonce: wcMultidropScheduler.nonce,
                    location_id: locationId
                },
                success: function(response) {
                    if (response.success && response.data) {
                        renderDeliveryNote(response.data.processingDateFormatted || '');
                    } else {
                        renderDeliveryNote('');
                    }
                    // Trigger checkout update AFTER we have the location
                    $(document.body).trigger('update_checkout');
                }
            });

            return;
        }

        setPickupMode();

        // Fetch and display location details
        $.ajax({
            url: wcMultidropScheduler.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_location_details',
                nonce: wcMultidropScheduler.nonce,
                location_id: locationId
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $locationDetails.html(response.data.html).slideDown();
                }
            }
        });

        // Fetch available dates
        $.ajax({
            url: wcMultidropScheduler.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_available_pickup_dates',
                nonce: wcMultidropScheduler.nonce,
                location_id: locationId
            },
            success: function(response) {
                if (response.success && response.data.dates) {
                    resetDatePicker();

                    flatpickrInstance = flatpickr($dateInput[0], {
                        dateFormat: 'Y-m-d',
                        altInput: true,
                        altFormat: wcMultidropScheduler.dateFormat || 'Y-m-d',
                        minDate: response.data.minDate || 'today',
                        maxDate: response.data.maxDate,
                        enable: response.data.dates,
                        locale: { firstDayOfWeek: wcMultidropScheduler.startOfWeek ? Number.parseInt(wcMultidropScheduler.startOfWeek, 10) : 1 },
                        disableMobile: false,
                        onChange: function() {
                            $(document.body).trigger('update_checkout');
                        }
                    });

                    $dateInput.attr('placeholder', wcMultidropScheduler.placeholderSelectDate);
                    // Trigger checkout update after flatpickr is initialized
                    setTimeout(function() {
                        $(document.body).trigger('update_checkout');
                    }, 200);
                }
            },
            error: function() {
                alert(wcMultidropScheduler.errorLoadDates);
            }
        });
    });

    // CRITICAL: Force include pickup_location_id in WooCommerce AJAX requests
    $(document).ajaxSend(function(event, jqxhr, settings) {
        // Check if this is a WooCommerce update_order_review request
        if (settings.url && settings.url.indexOf('wc-ajax=update_order_review') > -1) {
            const locationId = $locationSelect.val();
            if (locationId) {
                // Check if pickup_location_id is already in the data
                if (settings.data && settings.data.indexOf('pickup_location_id=') === -1) {
                    // Append pickup_location_id
                    settings.data += '&pickup_location_id=' + encodeURIComponent(locationId);
                    console.log('Added pickup_location_id to AJAX request:', locationId);
                }
            }
        }
    });
});