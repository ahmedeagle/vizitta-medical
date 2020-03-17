<!DOCTYPE html>
<html>
<head>
    <title>مقدمى الخدمات</title>
    <meta name="viewport" content="initial-scale=1.0">
    <meta charset="utf-8">
    <style>
        /* Always set the map height explicitly to define the size of the div
         * element that contains the map. */
        #map {
            height: 100%;
        }

        /* Optional: Makes the sample page fill the window. */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
<div id="map"></div>
<script>

    var result = {!! json_encode($results) !!}

    function initMap() {
        var myLatLng = {lat: 24.671969733916473, lng: 46.642518377441434};

        var map = new google.maps.Map(document.getElementById('map'), {
            zoom: 11,
            center: myLatLng
        });

        result.forEach(item => {

            var marker = new google.maps.Marker({
                    position: {lat: +item.latitude, lng: +item.longitude},
                    map: map,
                    title: item.name,
                    // icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png',
                    // icon: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                })
            ;
            marker.setMap(map);

        });

    };

</script>
<script
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDKZAuxH9xTzD2DLY2nKSPKrgRi2_y0ejs&language=ar&region=SA&callback=initMap"
    async
    defer></script>
</body>
</html>
