DAM_SQUARE = lat: 52.373, lng: 4.893
DEFAULT_MAGNIFICATION = 14
MAX_DISTANCE = 12 # km
FIT_N_RENTALS = 2

meMarker = null
map = null

# Old-fashined great-circle calculator based off of
# the Soviet spheroid formulae. Totally certain you can find this
# elsewhere and plug it in, but I already had it in my attic
GreatCircle =
  deg2rad: (deg) -> (deg * Math.PI / 180)
  rad2deg: (rad) -> (rad * 180 / Math.PI)
  R_KAVRAISKOGO: 6373 # in KM
  NINE_MINUTES_IN_RAD: 0.00261799388

  # Convert geo lat on the geoid to latitude on the Kavraisky spheroid
  latGeoToSpherical: (thetaGeoDeg) ->
    theta = @deg2rad(thetaGeoDeg)
    theta - (@NINE_MINUTES_IN_RAD * (Math.sin(2*theta)))

  distance : (lat1, lon1, lat2, lon2) ->
    lat1 = @latGeoToSpherical(lat1)
    lon1 = @deg2rad(lon1)
    lat2 = @latGeoToSpherical(lat2)
    lon2 = @deg2rad(lon2)
    deltaL = lon1 - lon2
    cosDist = Math.sin(lat1) * Math.sin(lat2) + Math.cos(lat1) * Math.cos(lat2) * Math.cos(deltaL)
    Math.acos(cosDist) * @R_KAVRAISKOGO

coordinatesInAms = (latlon)->
  distance = GreatCircle.distance(latlon.lat, latlon.lng, DAM_SQUARE.lat, DAM_SQUARE.lng)
  # console.debug "#{distance}km from Dam Square ;-)"
  distance < MAX_DISTANCE

centerAndFitMap = (latlon, closestMarkers) =>
  meMarker = L.marker(new L.LatLng(latlon.lat, latlon.lng),
    icon: L.mapbox.marker.icon
      'marker-color': 'bb0000'
      'marker-symbol': 'star-stroked'
      "marker-size": 'large'
    draggable: true
  )

  meMarker.addTo map
  closestMarkers.push(meMarker)
  interestAreaGrp = new L.featureGroup(closestMarkers)
  map.fitBounds(interestAreaGrp, paddingTopLeft: [3, 3], paddingBottomRight: [3, 3])
  
myPosition = DAM_SQUARE

map = L.mapbox.map 'map', 'karenishe.map-pxxvu0dq',
  detectRetina: true
  retinaVersion: 'karenishe.map-2s2oc75l'
# .addControl(L.mapbox.shareControl())
.addControl(L.mapbox.geocoderControl('karenishe.map-pxxvu0dq'))

rentalTemplateFn = Handlebars.compile($("#rentaltpl").html())

# Set myPosition either using geolocation or, if that fails,
# assume the person is on the Dam Square for now. Receives a callback
# that will be called once an approximate location becomes available
# or once geolocation fails
locateUser = ->
  promise = new $.Deferred()
  
  if navigator.geolocation
    map.on 'locationfound', (e) =>
      # if not too far from Amsterdam
      if coordinatesInAms(e.latlng)
        # locate user position marker
        myPosition = e.latlng
        console.debug 'User totally located, fitting'
        promise.resolve()
      else
        console.debug "Person not even close to AMS"
        promise.resolve()
        
    # if browser can't locate user
    map.on 'locationerror', =>
      console.error 'Geolocation off or declined'
      promise.resolve()
      
    map.locate()
  else
    console.error "No location support"
    promise.resolve()
  promise

# Sort the passed markers by their distance to myPosition
sortMarkersByDistance = (markers)->
  # Compute the distance to that rental from me
  for marker in markers
    markerCoords = marker.getLatLng()
    marker._distance = GreatCircle.distance(myPosition.lat, myPosition.lng, markerCoords.lat, markerCoords.lng)
    
  # Sort markers by distance
  # One of those moments where having Underscore is actually useful
  markers.sort (a, b)->
    return -1 if a._distance < b._distance 
    return 1 if a._distance > b._distance 
    0
  
# Once the rentals are loaded, populate the map
populateMap = (e) ->
  allRentalMarkers = []
  
  e.target.eachLayer (marker) ->
    allRentalMarkers.push(marker)
    content = rentalTemplateFn($.extend(marker.feature.properties, marker.feature.geometry))
    marker.bindPopup content, closeButton: true, maxWidth: 200
   
  $.when(
    locateUser()
  ).then ->
    sortMarkersByDistance(allRentalMarkers)
    # Grab the FIT_N_RENTALS closest rentals and add them to the fitting group
    closestRentals = allRentalMarkers[..FIT_N_RENTALS]
    # and center the map to them + the user location
    centerAndFitMap myPosition, closestRentals
  
# Load the markers
L.mapbox.markerLayer()
  .addTo(map)
  .on('ready', populateMap)
  .loadURL('markers.geojson')

# Directions
initDirections = ->
	directionsService = new google.maps.DirectionsService()
	directionsPolyline = null
	directionsPolylineOptions =
		color: '#000'
	getDirections = (coordinates, mode) ->
		if !meMarker?
			alert 'Wait please for geolocation'
			return

		switch mode
			when 'bicycling' then mode = google.maps.TravelMode.BICYCLING
			when 'transit' then mode = google.maps.TravelMode.TRANSIT
			when 'walking' then mode = google.maps.TravelMode.WALKING
			else mode = google.maps.TravelMode.DRIVING

		map.removeLayer(directionsPolyline) if directionsPolyline?
		request =
			origin: new google.maps.LatLng(meMarker._latlng.lat, meMarker._latlng.lng)
			destination: new google.maps.LatLng(coordinates[1], coordinates[0])
			travelMode: mode
		directionsService.route request, (result, status) ->
			if status == google.maps.DirectionsStatus.OK
				# console.log result
				# console.log map
				directionsPolylinePoints = []
				for point in result.routes[0].overview_path
					directionsPolylinePoints.push [point.lat(), point.lng()]
				directionsPolyline = L.polyline(directionsPolylinePoints, directionsPolylineOptions).addTo(map)
			else
				alert 'Looks like it\'s not possible to get directions there :('
	$(document).on 'click', '.directions_button', ->
		$me = $(this)
		coordinates = $me.attr('data-coordinates').split ','
		getDirections coordinates, $me.attr('data-mode')
		false


$ ->
	initDirections()