jQuery(document).ready(function($) {
    var urlParams = new URLSearchParams(window.location.search);
    var paramsToTrack = upt_params.trackedParams;
    var trackAll = upt_params.trackAll;
    var cookieLifetime = upt_params.cookieLifetime;
    var paramFieldMapping = upt_params.paramFieldMapping;
    var mappings = {};

    // Parse parameter to field mappings
    if (paramFieldMapping) {
        paramFieldMapping.split('\n').forEach(function(line) {
            var parts = line.split('=');
            if (parts.length === 2) {
                var params = parts[0].split(',').map(function(p) { return p.trim(); });
                var fieldSelector = parts[1].trim();
                mappings[fieldSelector] = params;
            }
        });
    }

    // Function to set cookie
    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/";
    }

    // Function to get cookie
    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for(var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
        }
        return null;
    }

    // Store parameters as cookies
    var storedParams = {};

    // Store URL parameters
    urlParams.forEach(function(value, key) {
        if (trackAll || paramsToTrack.includes(key)) {
            setCookie('upt_' + key, value, cookieLifetime);
            storedParams[key] = value;
        }
    });

    // Store current URL and referrer
    setCookie('upt_current_url', window.location.href, cookieLifetime);
    storedParams['current_url'] = window.location.href;

    var referrer = document.referrer || 'Direct';
    setCookie('upt_referrer', referrer, cookieLifetime);
    storedParams['referrer'] = referrer;

    // Autofill form fields from cookies
    Object.keys(mappings).forEach(function(fieldSelector) {
        var params = mappings[fieldSelector];
        var values = [];

        params.forEach(function(paramWithLabel) {
            var label = '';
            var paramName = paramWithLabel;

            // Check for label in curly braces
            var labelMatch = paramWithLabel.match(/^\{(.*?)\}(.*)$/);
            if (labelMatch) {
                label = labelMatch[1]; // Text inside curly braces
                paramName = labelMatch[2].trim(); // Parameter name after label
            }

            var value = getCookie('upt_' + paramName);
            if (value) {
                if (label) {
                    values.push(label + value);
                } else {
                    values.push(value);
                }
            }
        });

        if (values.length > 0) {
            var $field = $(fieldSelector);
            if ($field.length) {
                // Join the values with commas (or any separator you prefer)
                var currentVal = $field.val();
                var newVal = values.join(', ');

                // If the field already has a value, append to it
                if (currentVal) {
                    newVal = currentVal + ', ' + newVal;
                }

                $field.val(newVal);
            }
        }
    });

    // Send session data to server if any parameters were stored
    if (Object.keys(storedParams).length > 0) {
        $.post(upt_params.ajaxUrl, {
            action: 'upt_store_session_data',
            sessionData: storedParams
        });
    }
});