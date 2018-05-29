var objMap, objMarker, objCircle, mapOptions, addressField, latlngField;
var geocoder=new google.maps.Geocoder();

function showAddress(val)
{
    geocoder.geocode({'address':val},
    function(results, status)
    {
        if(status==google.maps.GeocoderStatus.OK) {
            geocode(results[0].geometry.location, false);
        } else {
            alert("Sorry but Google Maps could not find this location.");
        }
    });
}

function geocode(position, isSetField)
{
    objMap.panTo(position);
    geocoder.geocode({latLng:position},
    function(responses)
    {
        if(responses && responses.length>0)
        {
            var town, zip, locality, postal_town;
            var strAddress = '';
            var arrAddress = responses[0].address_components;
            $.each(arrAddress, function (i, address_component) {
                if (address_component.types[0] == "locality") {
                    town = address_component.long_name;
                }

                if (address_component.types[0] == "postal_code") {
                    zip = address_component.long_name;
                }

                if (address_component.types[0] == "route") {
                    locality = address_component.long_name;
                }

                if (address_component.types[0] == "postal_town") {
                    postal_town = address_component.long_name;
                }
            });

            if (zip != '' && typeof zip != 'undefined') {
                strAddress = zip;
            } else if (town != '' && typeof town != 'undefined') {
                strAddress = town;
            } else if (locality != '' && typeof locality != 'undefined') {
                strAddress = locality;
            } else if (postal_town != '' && typeof postal_town != 'undefined') {
                strAddress = postal_town;
            }

            // Store town or zip to given field
            if (addressField) {
                var addressIdField = addressField.replace('_autocomplete', '');
                if (isSetField) {
                    if (strAddress) {
                        $(addressField).val(strAddress);
                        $(addressIdField).val(strAddress);
                    } else {
                        $(addressField).val('');
                        $(addressIdField).val('');
                    }
                }
                $(latlngField).val(responses[0].geometry.location.lat()+', '+responses[0].geometry.location.lng())
            }

            showMarker(responses[0].geometry.location.lat(), responses[0].geometry.location.lng());
            showCircle(responses[0].geometry.location.lat(), responses[0].geometry.location.lng());
            objMap.setZoom(9);

            if (strAddress == '' || typeof strAddress === 'undefined') {
                alert('Sorry but Google Maps could not determine the approximate postal address of this location.');
            }
        } else {
            alert('Sorry but Google Maps could not determine the approximate postal address of this location.');
        }
    });
}

function initialize()
{
    mapOptions={zoom:9,
            zoomControl: true,
            panControl:false,
            streetViewControl: false,
            mapTypeControl: false,
            
            scaleControl: false,
            scrollwheel: false,
            disableDoubleClickZoom: true,
            draggable: false,
            
            mapTypeId:google.maps.MapTypeId.ROADMAP
            };

    objMap = new google.maps.Map(document.getElementById('googlemaps'), mapOptions);
}

function initializeForAdDetail(allowDraggable)
{
    mapOptions={zoom:12,
            zoomControl: true,
            panControl:false,
            streetViewControl: false,
            mapTypeControl: false,
            
            scaleControl: false,
            scrollwheel: false,
            disableDoubleClickZoom: true,
            
            mapTypeId:google.maps.MapTypeId.ROADMAP,
            draggable: allowDraggable
            };

    objMap = new google.maps.Map(document.getElementById('googlemaps'), mapOptions);
}

function initializeDragable(field, pointField)
{
    if (typeof field !== 'undefined') {
        addressField = field;
    }

    if (typeof pointField !== 'undefined') {
        latlngField = pointField;
    }

    google.maps.event.addListener(objMarker,'dragend',
    function(e)
    {
        var point = objMarker.getPosition();
        geocode(point, true);
    });
}

function showCircle(lat, lng)
{
    var objLatLng = new google.maps.LatLng(lat, lng);

    if (objCircle)
        objCircle.setMap(null);

    objMap.setCenter(objLatLng);
    objCircle = new google.maps.Circle({
        center:objLatLng,
        radius:10000, //10 km.
        strokeColor:"#9bcc21",
        strokeOpacity:1,
        strokeWeight:2,
        fillColor:"#9bcc21",
        fillOpacity:0.7
      });

    objCircle.setMap(objMap);
}

function showMarker(lat, lng)
{
    var objLatLng = new google.maps.LatLng(lat,lng);
    var image = 'marker_icon.png';

    if (objMarker) {
        //if marker already was created change positon
        objMarker.setPosition(objLatLng);
    } 
    else
    {
        objMap.setCenter(objLatLng);
        objMarker = new google.maps.Marker({
            position:objLatLng,
            //icon:image,
            map:objMap,
            draggable:true,
            animation:google.maps.Animation.DROP
        });
    }
    
    objMarker.setMap(objMap);
}

function showCustomMarker(lat, lng, markerImage)
{
    var objLatLng = new google.maps.LatLng(lat,lng);

    if (objMarker) {
        //if marker already was created change positon
        objMarker.setPosition(objLatLng);
    } 
    else
    {
        objMap.setCenter(objLatLng);
        objMarker = new google.maps.Marker({
            position:objLatLng,
            icon:markerImage,
            map:objMap,
            draggable:false,
            animation:google.maps.Animation.DROP
        });
    }
    
    objMarker.setMap(objMap);
}