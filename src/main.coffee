map = L.mapbox.map 'map', 'karenishe.map-pxxvu0dq',
	detectRetina: true
	retinaVersion: 'karenishe.map-2s2oc75l'

meMarker = null

if navigator.geolocation
	map.on 'locationfound', (e) =>
		# e.latlng.lat = 52.373
		# e.latlng.lng = 4.893
		# map.setView [e.latlng.lat, e.latlng.lng], 16
		# console.log e.bounds
		map.fitBounds e.bounds

		meMarker = L.marker(new L.LatLng(e.latlng.lat, e.latlng.lng),
			icon: L.mapbox.marker.icon
				'marker-color': 'bb0000'
				'marker-symbol': 'star-stroked'
				"marker-size": 'large'
			draggable: true
		)
		meMarker.addTo map
		# 
		# L.mapbox.markerLayer()
		# 	.addTo(map)
		# 	.setGeoJSON
		# 		type:'Feature'
		# 		geometry:
		# 			type: 'Point'
		# 			coordinates: [e.latlng.lng, e.latlng.lat]
		# 		properties:
		# 			'marker-color': '#b00'
		# 			'marker-symbol': 'star-stroked'
		# 			"marker-size": 'large'
		
		# qq = map.markerLayer.setGeoJSON
		# 		type:'Feature'
		# 		geometry:
		# 			type: 'Point'
		# 			coordinates: [e.latlng.lng, e.latlng.lat]
		# 		properties:
		# 			'marker-color': '#b00'
		# 			'marker-symbol': 'star-stroked'
		# 			"marker-size": 'large'
		# 	# .on 'ready', (e) =>
		# 	# 	console.log e
		# 	# 	# marker.option.dragging = true
		# console.log qq
	map.on 'locationerror', () =>
		alert 'Enable geolocation service for your browser please'

	map.locate()
else
	map.setView [52.373, 4.893], 14

# if 0
# 	markers = new L.MarkerClusterGroup()
# 
# 	for (var i = 0; i < addressPoints.length; i++) {
# 		var a = addressPoints[i];
# 		var title = a[2];
# 		var marker = L.marker(new L.LatLng(a[0], a[1]), {
# 			title: title
# 		});
# 		marker.bindPopup(title);
# 		markers.addLayer(marker);
# 	}
# 
# 	map.addLayer(markers);
# else
L.mapbox.markerLayer()
	.addTo(map)
	.on 'ready', (e) ->
		e.target.eachLayer (marker) ->
			content =
				"<h1>#{marker.feature.properties.title}</h1>" +
				(if marker.feature.properties.address then "<p class='address'>#{marker.feature.properties.address}</p>" else '') +
				(if marker.feature.properties.phone then "<p class='phone'>#{marker.feature.properties.phone}</p>" else '') +
				(if marker.feature.properties.url then "<a href='http://#{marker.feature.properties.url}' target='_blank' class='url'>www.#{marker.feature.properties.url}</p>" else '')
				# + "<a href='#' class='route' data-lat='#{marker.feature.geometry.coordinates[1]}' data-lng='#{marker.feature.geometry.coordinates[0]}'>Walking route</a>"
			marker.bindPopup content,
				closeButton: false
				maxWidth: 200
	.loadURL('places.geojson')
	

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
