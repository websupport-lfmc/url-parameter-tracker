jQuery(document).ready(function ($) {
  var urlParams = new URLSearchParams(window.location.search);
  var paramsToTrack = upt_params.trackedParams;
  var trackAll = upt_params.trackAll;
  var cookieLifetime = upt_params.cookieLifetime;
  var paramFieldMapping = upt_params.paramFieldMapping;
  var mappings = {};

  // console.log("URL Parameters:", window.location.search);
  // console.log("Parameters to Track:", paramsToTrack);
  // console.log("Track All:", trackAll);
  // console.log("Cookie Lifetime:", cookieLifetime);
  // console.log("Parameter to Field Mapping Raw:", paramFieldMapping);

  // Parse parameter to field mappings
  if (paramFieldMapping) {
    paramFieldMapping.split("\n").forEach(function (line, index) {
      line = line.trim();
      if (line === "") return; // Skip empty lines

      // Split only at the first '=>'
      var indexOfArrow = line.indexOf("=>");
      if (indexOfArrow === -1) {
        // console.warn("Invalid mapping line (expected format 'parameter=>selector'):", line);
        return;
      }

      var paramsPart = line.substring(0, indexOfArrow).trim();
      var fieldSelector = line.substring(indexOfArrow + 2).trim();

      if (!paramsPart || !fieldSelector) {
        // console.warn("Invalid mapping line (empty parameter or selector):", line);
        return;
      }

      // Split multiple parameters separated by commas
      var params = paramsPart.split(",").map(function (p) {
        return p.trim();
      });

      // Initialize the array if the selector already exists
      if (!mappings[fieldSelector]) {
        mappings[fieldSelector] = [];
      }

      mappings[fieldSelector] = mappings[fieldSelector].concat(params);

      // console.log("Mapping Line " + (index + 1) + ":");
      // console.log("  Parameters:", params);
      // console.log("  Field Selector:", fieldSelector);
    });
  } else {
    // console.warn("No parameter to field mappings found.");
  }

  // Function to set cookie
  function setCookie(name, value, days) {
    var expires = "";
    if (days) {
      var date = new Date();
      date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
      expires = "; expires=" + date.toUTCString();
    }
    document.cookie =
      name + "=" + encodeURIComponent(value) + expires + "; path=/";
    // console.log("Set Cookie:", name, "=", value);
  }

  // Function to get cookie
  function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(";");
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i].trim();
      if (c.indexOf(nameEQ) === 0) {
        var value = decodeURIComponent(c.substring(nameEQ.length, c.length));
        // console.log("Get Cookie:", name, "=", value);
        return value;
      }
    }
    // console.log("Get Cookie:", name, "not found.");
    return null;
  }

  // Store parameters as cookies
  var storedParams = {};

  // Store URL parameters
  urlParams.forEach(function (value, key) {
    if (trackAll || paramsToTrack.includes(key)) {
      setCookie("upt_" + key, value, cookieLifetime);
      storedParams[key] = value;
    }
  });

  // Store current URL and referrer
  setCookie("upt_current_url", window.location.href, cookieLifetime);
  storedParams["current_url"] = window.location.href;

  var referrer = document.referrer || "Direct";
  setCookie("upt_referrer", referrer, cookieLifetime);
  storedParams["referrer"] = referrer;

  // Autofill form fields from cookies
  Object.keys(mappings).forEach(function (fieldSelector) {
    var params = mappings[fieldSelector];
    var values = [];

    params.forEach(function (param) {
      var label = "";
      var paramName = param;

      // Check if param has a label in the format {Label Text}paramName
      var labelMatch = param.match(/^\{([^}]+)\}(.*)$/);
      if (labelMatch) {
        label = labelMatch[1];
        paramName = labelMatch[2];
      }

      var value = getCookie("upt_" + paramName);
      if (value) {
        if (label) {
          value = label + value;
        }
        values.push(value);
      }
    });

    if (values.length > 0) {
      var $field = $(fieldSelector);
      if ($field.length) {
        var currentVal = $field.val();
        var newVal = values.join(",");

        // If the field already has a value, append to it
        if (currentVal) {
          newVal = currentVal + "," + newVal;
        }

        // console.log("Autofilling Field:", fieldSelector, "with value:", newVal);
        $field.val(newVal);
      } else {
        // console.warn("Field not found for selector:", fieldSelector);
      }
    } else {
      // console.log("No values to autofill for selector:", fieldSelector);
    }
  });

  // Send session data to server if any parameters were stored
  if (Object.keys(storedParams).length > 0) {
    // console.log("Sending session data to server:", storedParams);
    $.post(upt_params.ajaxUrl, {
      action: "upt_store_session_data",
      sessionData: storedParams,
    })
      .done(function (response) {
        // console.log("Session data stored successfully:", response);
      })
      .fail(function (error) {
        // console.error("Error storing session data:", error);
      });
  }
});
