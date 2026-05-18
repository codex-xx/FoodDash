(function () {
  function toNumber(value) {
    const parsed = parseFloat(value);
    return Number.isFinite(parsed) ? parsed : null;
  }

  function toDisplayValue(value) {
    if (!Number.isFinite(value)) {
      return '';
    }

    return value.toFixed(6);
  }

  function initRestaurantLocationMap() {
    const config = window.FoodDashRestaurantLocationMapConfig || {};
    const mapElement = document.getElementById(config.mapElementId || 'restaurant-location-map');

    if (!mapElement || typeof window.L === 'undefined') {
      return;
    }

    const latitudeInput = document.getElementById(config.latitudeInputId || 'restaurant_latitude');
    const longitudeInput = document.getElementById(config.longitudeInputId || 'restaurant_longitude');
    const addressInput = document.getElementById(config.addressInputId || 'restaurant_address');
    const useCurrentLocationButton = document.getElementById(config.useCurrentLocationButtonId || 'useCurrentLocationBtn');
    const statusElement = document.getElementById(config.statusElementId || 'locationStatus');

    const initialLatitude = toNumber(mapElement.dataset.initialLat || latitudeInput?.value || '');
    const initialLongitude = toNumber(mapElement.dataset.initialLng || longitudeInput?.value || '');
    const initialAddress = (mapElement.dataset.initialAddress || addressInput?.value || '').trim();

    const fallbackCenter = [0, 0];
    const fallbackZoom = 2;
    const mapCenter = initialLatitude !== null && initialLongitude !== null
      ? [initialLatitude, initialLongitude]
      : fallbackCenter;

    const map = L.map(mapElement, {
      scrollWheelZoom: true,
      zoomControl: true
    }).setView(mapCenter, initialLatitude !== null && initialLongitude !== null ? 16 : fallbackZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let marker = null;
    let reverseGeocodeToken = 0;
    let editingEnabled = !!config.editableByDefault;
    let currentLocation = {
      latitude: initialLatitude,
      longitude: initialLongitude,
      address: initialAddress
    };

    function setMapInteractivity(enabled) {
      const shouldEnable = !!enabled;

      if (map.dragging) {
        if (shouldEnable) {
          map.dragging.enable();
        } else {
          map.dragging.disable();
        }
      }

      if (map.scrollWheelZoom) {
        if (shouldEnable) {
          map.scrollWheelZoom.enable();
        } else {
          map.scrollWheelZoom.disable();
        }
      }

      if (map.doubleClickZoom) {
        if (shouldEnable) {
          map.doubleClickZoom.enable();
        } else {
          map.doubleClickZoom.disable();
        }
      }

      if (map.boxZoom) {
        if (shouldEnable) {
          map.boxZoom.enable();
        } else {
          map.boxZoom.disable();
        }
      }

      if (map.keyboard) {
        if (shouldEnable) {
          map.keyboard.enable();
        } else {
          map.keyboard.disable();
        }
      }

      if (map.touchZoom) {
        if (shouldEnable) {
          map.touchZoom.enable();
        } else {
          map.touchZoom.disable();
        }
      }

      mapElement.style.cursor = shouldEnable ? 'grab' : 'default';
    }

    function setStatus(message, kind = 'muted') {
      if (!statusElement) {
        return;
      }

      statusElement.className = 'form-text mt-2 text-' + kind;
      statusElement.textContent = message;
    }

    function updateFormFields(latitude, longitude, address) {
      currentLocation = {
        latitude: latitude,
        longitude: longitude,
        address: typeof address === 'string' ? address : currentLocation.address
      };

      if (latitudeInput) {
        latitudeInput.value = toDisplayValue(latitude);
      }

      if (longitudeInput) {
        longitudeInput.value = toDisplayValue(longitude);
      }

      if (typeof address === 'string' && addressInput) {
        addressInput.value = address;
      }
    }

    function moveMarkerTo(latitude, longitude, options = {}) {
      const numericLatitude = toNumber(latitude);
      const numericLongitude = toNumber(longitude);

      if (numericLatitude === null || numericLongitude === null) {
        return;
      }

      ensureMarker(numericLatitude, numericLongitude);
      updateFormFields(numericLatitude, numericLongitude, addressInput ? addressInput.value : '');

      if (!options.skipReverseGeocode) {
        reverseGeocode(numericLatitude, numericLongitude);
      }
    }

    function attachDragHandler(markerInstance) {
      markerInstance.on('dragend', function (event) {
        const position = event.target.getLatLng();
        moveMarkerTo(position.lat, position.lng);
      });
    }

    function ensureMarker(latitude, longitude) {
      const latLng = [latitude, longitude];

      if (!marker) {
        marker = L.marker(latLng, { draggable: true }).addTo(map);
        attachDragHandler(marker);
      } else {
        marker.setLatLng(latLng);
      }

      if (marker && marker.dragging) {
        if (editingEnabled) {
          marker.dragging.enable();
        } else {
          marker.dragging.disable();
        }
      }

      map.setView(latLng, 16);
    }

    map.on('click', function (event) {
      if (!editingEnabled) {
        return;
      }

      moveMarkerTo(event.latlng.lat, event.latlng.lng);
    });

    async function reverseGeocode(latitude, longitude, silent = false) {
      const token = ++reverseGeocodeToken;

      if (!silent) {
        setStatus('Looking up the address for this pin...', 'primary');
      }

      try {
        const response = await fetch(
          'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' +
            encodeURIComponent(latitude) +
            '&lon=' +
            encodeURIComponent(longitude) +
            '&zoom=18&addressdetails=1',
          {
            headers: {
              Accept: 'application/json'
            }
          }
        );

        if (!response.ok) {
          throw new Error('Reverse geocoding failed');
        }

        const data = await response.json();

        if (token !== reverseGeocodeToken) {
          return;
        }

        const displayAddress = (data.display_name || '').trim();
        if (displayAddress) {
          updateFormFields(latitude, longitude, displayAddress);
          setStatus('Location updated. You can fine-tune the pin before saving.', 'success');
        } else {
          setStatus('Location updated, but no formatted address was returned.', 'warning');
        }
      } catch (error) {
        if (token !== reverseGeocodeToken) {
          return;
        }

        setStatus('Could not resolve the address automatically. You can still save the coordinates.', 'warning');
      }
    }

    async function forwardGeocode(address) {
      const response = await fetch(
        'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' + encodeURIComponent(address),
        {
          headers: {
            Accept: 'application/json'
          }
        }
      );

      if (!response.ok) {
        throw new Error('Forward geocoding failed');
      }

      const results = await response.json();
      return Array.isArray(results) && results.length > 0 ? results[0] : null;
    }

    function setLocation(latitude, longitude, address, options = {}) {
      const numericLatitude = toNumber(latitude);
      const numericLongitude = toNumber(longitude);

      if (numericLatitude === null || numericLongitude === null) {
        return;
      }

      ensureMarker(numericLatitude, numericLongitude);
      updateFormFields(numericLatitude, numericLongitude, typeof address === 'string' ? address : addressInput?.value || '');

      if (!options.skipReverseGeocode) {
        reverseGeocode(numericLatitude, numericLongitude);
      }
    }

    function setEditingEnabled(enabled) {
      editingEnabled = !!enabled;
      setMapInteractivity(editingEnabled);

      if (marker && marker.dragging) {
        if (editingEnabled) {
          marker.dragging.enable();
        } else {
          marker.dragging.disable();
        }
      }
    }

    function isEditingEnabled() {
      return editingEnabled;
    }

    function getCurrentLocation() {
      return {
        latitude: currentLocation.latitude,
        longitude: currentLocation.longitude,
        address: currentLocation.address
      };
    }

    function reset(latitude, longitude, address) {
      const numericLatitude = toNumber(latitude);
      const numericLongitude = toNumber(longitude);

      if (numericLatitude === null || numericLongitude === null) {
        if (marker) {
          map.removeLayer(marker);
          marker = null;
        }

        map.setView(fallbackCenter, fallbackZoom);
        setStatus('Set the pin using the map or your current location.', 'muted');
        return;
      }

      setLocation(numericLatitude, numericLongitude, address, { skipReverseGeocode: true });
      setStatus('Restaurant location restored from the form.', 'muted');
    }

    async function useCurrentLocation() {
      if (!navigator.geolocation) {
        setStatus('Your browser does not support geolocation.', 'danger');
        return;
      }

      setStatus('Requesting your current location permission...', 'primary');

      navigator.geolocation.getCurrentPosition(
        function (position) {
          const latitude = position.coords.latitude;
          const longitude = position.coords.longitude;
          setLocation(latitude, longitude, addressInput ? addressInput.value : '');
          reverseGeocode(latitude, longitude);
        },
        function (error) {
          setStatus(error.message || 'Unable to access your current location.', 'danger');
        },
        {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 0
        }
      );
    }

    if (useCurrentLocationButton) {
      useCurrentLocationButton.addEventListener('click', useCurrentLocation);
    }

    if (initialLatitude !== null && initialLongitude !== null) {
      setLocation(initialLatitude, initialLongitude, initialAddress, { skipReverseGeocode: true });
      setStatus('Drag the pin to fine-tune the location or use your current position.', 'muted');
    } else if (initialAddress) {
      setStatus('Finding this restaurant on the map from the saved address...', 'muted');

      forwardGeocode(initialAddress)
        .then(function (result) {
          if (result && result.lat && result.lon) {
            setLocation(result.lat, result.lon, result.display_name || initialAddress, { skipReverseGeocode: true });
            setStatus('Drag the pin to fine-tune the location or use your current position.', 'muted');
            return;
          }

          if (!marker) {
            marker = L.marker(fallbackCenter, { draggable: true }).addTo(map);
            attachDragHandler(marker);
          }

          setStatus('Your saved address is ready. Use the map button or drag the pin to set coordinates.', 'muted');
        })
        .catch(function () {
          if (!marker) {
            marker = L.marker(fallbackCenter, { draggable: true }).addTo(map);
            attachDragHandler(marker);
          }

          setStatus('Your saved address is ready. Use the map button or drag the pin to set coordinates.', 'muted');
        });
    } else {
      if (!marker) {
        marker = L.marker(fallbackCenter, { draggable: true }).addTo(map);
        attachDragHandler(marker);
      }

      setStatus('Choose a location using the map or your current position.', 'muted');
    }

    setEditingEnabled(editingEnabled);

    window.FoodDashRestaurantLocationMap = {
      setLocation: setLocation,
      reset: reset,
      setEditingEnabled: setEditingEnabled,
      isEditingEnabled: isEditingEnabled,
      getCurrentLocation: getCurrentLocation
    };
  }

  document.addEventListener('DOMContentLoaded', initRestaurantLocationMap);
})();