map = L.mapbox.map 'map', 'karenishe.map-pxxvu0dq',
	detectRetina: true
	retinaVersion: 'karenishe.map-2s2oc75l'
# .addControl(L.mapbox.shareControl())
.addControl(L.mapbox.geocoderControl('karenishe.map-pxxvu0dq'))

meMarker = null

if navigator.geolocation
	map.on 'locationfound', (e) =>

		# if not too far from Amsterdam
		if Math.abs(e.latitude - 52.373) < .1 and Math.abs(e.longitude - 4.893) < .1
			# set map to bounds
			map.fitBounds e.bounds

			# locate user position marker
			meMarker = L.marker(new L.LatLng(e.latlng.lat, e.latlng.lng),
				icon: L.mapbox.marker.icon
					'marker-color': 'bb0000'
					'marker-symbol': 'star-stroked'
					"marker-size": 'large'
				draggable: true
			)
			meMarker.addTo map

	# if browser can't locate user
	map.on 'locationerror', () =>
		# alert 'Enable geolocation service for your browser please'

	map.locate()
else
	# set map to Amsterdam
	map.setView [52.373, 4.893], 14


L.mapbox.markerLayer()
	.addTo(map)
	.on 'ready', (e) ->
		e.target.eachLayer (marker) ->
			content =
				"<h1>#{marker.feature.properties.title}</h1>" +
				(if marker.feature.properties.address then "<p class='address'>#{marker.feature.properties.address}</p>" else '') +
				(if marker.feature.properties.phone then "<p class='phone'>#{marker.feature.properties.phone}</p>" else '') +
				(if marker.feature.properties.url then "<a href='http://#{marker.feature.properties.url}' target='_blank' class='url'>www.#{marker.feature.properties.url}</a>" else '') +
				"<iframe src='marker_counter.html?marker_id=#{marker.feature.properties.id}' class='iframe'></iframe>"
				# + "<a href='#' class='route' data-lat='#{marker.feature.geometry.coordinates[1]}' data-lng='#{marker.feature.geometry.coordinates[0]}'>Walking route</a>"
			# marker.
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
