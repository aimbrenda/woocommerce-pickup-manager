jQuery(document).ready(function($) {
    let flatpickrInstance = null;
    const $locationSelect = $('#pickup_location_id');
    const $dateInput = $('#pickup_date');
    const $locationDetails = $('#pickup_location_details');

    $locationSelect.on('change', function() {
        const locationId = $(this).val();

        if (!locationId) {
            if (flatpickrInstance) flatpickrInstance.destroy();
            flatpickrInstance = null;
            $dateInput.val('').attr('placeholder', wcMultidropScheduler.placeholderSelectLocation);
            $locationDetails.hide();
            $(document.body).trigger('update_checkout');
            return;
        }

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
                    if (flatpickrInstance) flatpickrInstance.destroy();

                    flatpickrInstance = flatpickr($dateInput[0], {
                        dateFormat: 'Y-m-d',
                        altInput: true,
                        altFormat: wcMultidropScheduler.dateFormat || 'Y-m-d',
                        minDate: response.data.minDate || 'today',
                        maxDate: response.data.maxDate,
                        enable: response.data.dates,
                        locale: { firstDayOfWeek: wcMultidropScheduler.startOfWeek ? parseInt(wcMultidropScheduler.startOfWeek) : 1 },
                        disableMobile: false,
                        onChange: function() {
                            $(document.body).trigger('update_checkout');
                        }
                    });

                    $dateInput.attr('placeholder', wcMultidropScheduler.placeholderSelectDate);
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
});
