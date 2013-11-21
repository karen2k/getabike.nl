(function() {
  var DAM_SQUARE, DEFAULT_MAGNIFICATION, FIT_N_RENTALS, GreatCircle, MAX_DISTANCE, centerAndFitMap, coordinatesInAms, initDirections, locateUser, map, meMarker, myPosition, populateMap, rentalTemplateFn, sortMarkersByDistance,
    _this = this;

  DAM_SQUARE = {
    lat: 52.373,
    lng: 4.893
  };

  DEFAULT_MAGNIFICATION = 14;

  MAX_DISTANCE = 12;

  FIT_N_RENTALS = 2;

  meMarker = null;

  map = null;

  GreatCircle = {
    deg2rad: function(deg) {
      return deg * Math.PI / 180;
    },
    rad2deg: function(rad) {
      return rad * 180 / Math.PI;
    },
    R_KAVRAISKOGO: 6373,
    NINE_MINUTES_IN_RAD: 0.00261799388,
    latGeoToSpherical: function(thetaGeoDeg) {
      var theta;
      theta = this.deg2rad(thetaGeoDeg);
      return theta - (this.NINE_MINUTES_IN_RAD * (Math.sin(2 * theta)));
    },
    distance: function(lat1, lon1, lat2, lon2) {
      var cosDist, deltaL;
      lat1 = this.latGeoToSpherical(lat1);
      lon1 = this.deg2rad(lon1);
      lat2 = this.latGeoToSpherical(lat2);
      lon2 = this.deg2rad(lon2);
      deltaL = lon1 - lon2;
      cosDist = Math.sin(lat1) * Math.sin(lat2) + Math.cos(lat1) * Math.cos(lat2) * Math.cos(deltaL);
      return Math.acos(cosDist) * this.R_KAVRAISKOGO;
    }
  };

  coordinatesInAms = function(latlon) {
    var distance;
    distance = GreatCircle.distance(latlon.lat, latlon.lng, DAM_SQUARE.lat, DAM_SQUARE.lng);
    return distance < MAX_DISTANCE;
  };

  centerAndFitMap = function(latlon, closestMarkers) {
    var interestAreaGrp;
    meMarker = L.marker(new L.LatLng(latlon.lat, latlon.lng), {
      icon: L.mapbox.marker.icon({
        'marker-color': 'bb0000',
        'marker-symbol': 'star-stroked',
        "marker-size": 'large'
      }),
      draggable: true
    });
    meMarker.addTo(map);
    closestMarkers.push(meMarker);
    interestAreaGrp = new L.featureGroup(closestMarkers);
    return map.fitBounds(interestAreaGrp, {
      paddingTopLeft: [3, 3],
      paddingBottomRight: [3, 3]
    });
  };

  myPosition = DAM_SQUARE;

  map = L.mapbox.map('map', 'karenishe.map-pxxvu0dq', {
    detectRetina: true,
    retinaVersion: 'karenishe.map-2s2oc75l'
  }).addControl(L.mapbox.geocoderControl('karenishe.map-pxxvu0dq'));

  rentalTemplateFn = Handlebars.compile($("#rentaltpl").html());

  locateUser = function() {
    var promise,
      _this = this;
    promise = new $.Deferred();
    if (navigator.geolocation) {
      map.on('locationfound', function(e) {
        if (coordinatesInAms(e.latlng)) {
          myPosition = e.latlng;
          console.debug('User totally located, fitting');
          return promise.resolve();
        } else {
          console.debug("Person not even close to AMS");
          return promise.resolve();
        }
      });
      map.on('locationerror', function() {
        console.error('Geolocation off or declined');
        return promise.resolve();
      });
      map.locate();
    } else {
      console.error("No location support");
      promise.resolve();
    }
    return promise;
  };

  sortMarkersByDistance = function(markers) {
    var marker, markerCoords, _i, _len;
    for (_i = 0, _len = markers.length; _i < _len; _i++) {
      marker = markers[_i];
      markerCoords = marker.getLatLng();
      marker._distance = GreatCircle.distance(myPosition.lat, myPosition.lng, markerCoords.lat, markerCoords.lng);
    }
    return markers.sort(function(a, b) {
      if (a._distance < b._distance) {
        return -1;
      }
      if (a._distance > b._distance) {
        return 1;
      }
      return 0;
    });
  };

  populateMap = function(e) {
    var allRentalMarkers;
    allRentalMarkers = [];
    e.target.eachLayer(function(marker) {
      var content;
      allRentalMarkers.push(marker);
      content = rentalTemplateFn($.extend(marker.feature.properties, marker.feature.geometry));
      return marker.bindPopup(content, {
        closeButton: true,
        maxWidth: 200
      });
    });
    return $.when(locateUser()).then(function() {
      var closestRentals;
      sortMarkersByDistance(allRentalMarkers);
      closestRentals = allRentalMarkers.slice(0, +FIT_N_RENTALS + 1 || 9e9);
      return centerAndFitMap(myPosition, closestRentals);
    });
  };

  L.mapbox.markerLayer().addTo(map).on('ready', populateMap).loadURL('markers.geojson');

  initDirections = function() {
    var directionsPolyline, directionsPolylineOptions, directionsService, getDirections;
    directionsService = new google.maps.DirectionsService();
    directionsPolyline = null;
    directionsPolylineOptions = {
      color: '#000'
    };
    getDirections = function(coordinates, mode) {
      var request;
      if (meMarker == null) {
        alert('Wait please for geolocation');
        return;
      }
      switch (mode) {
        case 'bicycling':
          mode = google.maps.TravelMode.BICYCLING;
          break;
        case 'transit':
          mode = google.maps.TravelMode.TRANSIT;
          break;
        case 'walking':
          mode = google.maps.TravelMode.WALKING;
          break;
        default:
          mode = google.maps.TravelMode.DRIVING;
      }
      if (directionsPolyline != null) {
        map.removeLayer(directionsPolyline);
      }
      request = {
        origin: new google.maps.LatLng(meMarker._latlng.lat, meMarker._latlng.lng),
        destination: new google.maps.LatLng(coordinates[1], coordinates[0]),
        travelMode: mode
      };
      return directionsService.route(request, function(result, status) {
        var directionsPolylinePoints, point, _i, _len, _ref;
        if (status === google.maps.DirectionsStatus.OK) {
          directionsPolylinePoints = [];
          _ref = result.routes[0].overview_path;
          for (_i = 0, _len = _ref.length; _i < _len; _i++) {
            point = _ref[_i];
            directionsPolylinePoints.push([point.lat(), point.lng()]);
          }
          return directionsPolyline = L.polyline(directionsPolylinePoints, directionsPolylineOptions).addTo(map);
        } else {
          return alert('Looks like it\'s not possible to get directions there :(');
        }
      });
    };
    return $(document).on('click', '.directions_button', function() {
      var $me, coordinates;
      $me = $(this);
      coordinates = $me.attr('data-coordinates').split(',');
      getDirections(coordinates, $me.attr('data-mode'));
      return false;
    });
  };

  $(document).ready(function() {
    return initDirections();
  });

}).call(this);
