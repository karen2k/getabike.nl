map = L.mapbox.map 'map', 'karenishe.map-pxxvu0dq',
  detectRetina: true
  retinaVersion: 'karenishe.map-2s2oc75l'

DAM_SQUARE = lat: 52.373, lng: 4.893
DEFAULT_MAGNIFICATION = 14
MAX_DISTANCE = 12 # km

centerMap = (latlon)->
  ico = L.mapbox.marker.icon 'marker-color': 'bb0000', 'marker-symbol': 'star-stroked', "marker-size": 'large'
  point = new L.LatLng latlon.lat, latlon.lng, icon: ico
  myLocationMarker = L.marker point, draggable: true
  return myLocationMarker.addTo(map)

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

# Check whether this latlon is within sensible great-circle distance from Amsterdam
coordinatesInAms = (latlon)->
  distance = GreatCircle.distance(latlon.lat, latlon.lng, DAM_SQUARE.lat, DAM_SQUARE.lng)
  console.debug "#{distance}km from Dam Square ;-)"
  distance < MAX_DISTANCE
  
if navigator.geolocation
  map.on 'locationfound', (e) =>
    if coordinatesInAms(e.latlng)
      map.fitBounds e.bounds
      centerMap(e.latlng)
    else
      console.debug "Looks like you are way outside of Amsterdam at the moment, we won't be able to route you"
      centerMap(DAM_SQUARE)
  map.on 'locationerror', () =>
    console.error "Geolocation denied or failed, so reverting"
    centerMap(DAM_SQUARE)
  map.locate()
else
  centerMap(DAM_SQUARE)

rentalTemplateFn = Handlebars.compile($("#rentaltpl").html())

L.mapbox.markerLayer()
  .addTo(map)
  .on 'ready', (e) ->
    e.target.eachLayer (marker) ->
      popupHtml = rentalTemplateFn(marker.feature.properties)
      marker.bindPopup popupHtml, closeButton: true, maxWidth: 200
  .loadURL('places.geojson')
