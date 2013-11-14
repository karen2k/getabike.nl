DAM_SQUARE = lat: 52.373, lng: 4.893
DEFAULT_MAGNIFICATION = 14
MAX_DISTANCE = 12 # km

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
    Math.round(Math.acos(cosDist) * @R_KAVRAISKOGO)

coordinatesInAms = (latlon)->
	distance = GreatCircle.distance(latlon.lat, latlon.lng, DAM_SQUARE.lat, DAM_SQUARE.lng)
	# console.debug "#{distance}km from Dam Square ;-)"
	distance < MAX_DISTANCE

centerMap = (latlon) =>
	meMarker = L.marker(new L.LatLng(latlon.lat, latlon.lng),
		icon: L.mapbox.marker.icon
			'marker-color': 'bb0000'
			'marker-symbol': 'star-stroked'
			"marker-size": 'large'
		draggable: true
	)
	meMarker.addTo map
	
	


map = L.mapbox.map 'map', 'karenishe.map-pxxvu0dq',
	detectRetina: true
	retinaVersion: 'karenishe.map-2s2oc75l'
# .addControl(L.mapbox.shareControl())
.addControl(L.mapbox.geocoderControl('karenishe.map-pxxvu0dq'))

meMarker = null

if navigator.geolocation
	map.on 'locationfound', (e) =>

		# if not too far from Amsterdam
		# if Math.abs(e.latitude - 52.373) < .1 and Math.abs(e.longitude - 4.893) < .1
		if coordinatesInAms(e.latlng)
			# set map to bounds
			map.fitBounds e.bounds
			# locate user position marker
			centerMap e.latlng
		else
			centerMap DAM_SQUARE

	# if browser can't locate user
	map.on 'locationerror', () =>
		centerMap DAM_SQUARE
		# alert 'Enable geolocation service for your browser please'

	map.locate()
else
	# set map to Amsterdam
	map.setView [DAM_SQUARE.lat, DAM_SQUARE.lng], DEFAULT_MAGNIFICATION


rentalTemplateFn = Handlebars.compile($("#rentaltpl").html())

L.mapbox.markerLayer()
	.addTo(map)
	.on 'ready', (e) ->
		e.target.eachLayer (marker) ->
			content = rentalTemplateFn(marker.feature.properties)
			marker.bindPopup content,
				closeButton: true
				maxWidth: 200
	.loadURL('markers.geojson')



# $(document).on 'click', 'a.route', (e) =>
# 	$me = $(e.target)
# 
# 	myLatLng = meMarker.getLatLng()
# 
# 	$.ajax
# 		url: 'directions'
# 		type: 'post'
# 		dataType: 'json'
# 		data:
# 			from_lat: myLatLng.lat
# 			from_lng: myLatLng.lng
# 			to_lat: $me.attr('data-lat')
# 			to_lng: $me.attr('data-lng')
# 		success: (response) =>
# 			if response.status != 'OK'
# 				alert "Can't get directions"
# 				return false
# 
# 			response.routes[0]
# 
# 			console.log response.routes[0].bounds.northeast
# 			console.log response.routes[0].bounds.southwest
# 
# 
# 			bounds =
# 				'_northEast': response.routes[0].bounds.northeast
# 				'_southWest': response.routes[0].bounds.southwest
# 			
# 			map.fitBounds bounds
# 
# 			console.log response.routes[0]
# 
# 	false
# 
# 
