function mapMarker(latitude, longitude, map, draggable) {
// 	console.log('oneMap with ' + i + ', caption: ' + marker[i]['caption'] + ', addr: ' + marker[i]['address'] + ', type:' + marker[i]['type'] + ', lat:' + marker[i]['lat'] + ', long:' + marker[i]['longitude']);
	if (typeof latitude == 'undefined') return;
	var latitude = parseFloat(latitude);  
	var longitude = parseFloat(longitude);
 	console.log('oneMap with ' + map + ", " + latitude + ", " + longitude);
 	if (Math.abs(latitude) < 0.01) if (Math.abs(longitude) < 0.01) return;
 		var map =
		new google.maps.Map(document.getElementById(map), {
			center: new google.maps.LatLng(latitude, longitude),
			zoom: 18, mapTypeId: 'roadmap', scrollwheel: false
		});
	var point = new google.maps.LatLng(latitude, longitude);
	var icon = {};
	var marker = new google.maps.Marker({
		draggable: draggable,
		map: map,
		position: point,
		animation: google.maps.Animation.DROP,
		icon: icon.icon
	});
	if (draggable) {
		google.maps.event.addListener(marker, 'dragend', function (event) {
			document.getElementById("latitude").value = this.getPosition().lat();
			document.getElementById("longitude").value = this.getPosition().lng();
			$('#latitude, #longitude').css('color', '#f33');
		});
	}
}

function isScrolledIntoView(elem)
{
    var docViewTop = $(window).scrollTop();
    var docViewBottom = docViewTop + $(window).height();

    var elemTop = $(elem).offset().top;
    var elemBottom = elemTop + $(elem).height();
// 	console.log('(elemBottom <= docViewBottom) && (elemTop >= docViewTop): ' + (elemBottom <= docViewBottom) && (elemTop >= docViewTop));
    return ((elemBottom <= (docViewBottom + 300)) && (elemTop >= docViewTop));
}

var mapDone = [];
function checkMaps() {
// 	console.log('checking...');
	$('.SASMap').each(function(index){
		var th = $(this);
// 		if (isScrolledIntoView(th)) th.stop().delay(2000).animate({'opacity':1}, 500); else th.stop().animate({'opacity':0}, 500)
		if (isScrolledIntoView(th)) {
			console.log('index is ' + index);
			if (!mapDone[index]) { mapMarker(marker[index].latitude, marker[index].longitude, 'map' + index, false); mapDone[index] = true; }
		}
	});
}

$(document).ready(function(){
	if ($('#map0').length > 0) { // showing all records
		checkMaps();
	    for (var i = 0; i < marker.length; i++) mapDone[i] = false;
		$(window).scroll(checkMaps);
	}
	var modifyMap = $('#modifyMap');
	if (modifyMap.length > 0) { // editing a single record
		modifyMap.height($('#modify').height() * 1.3);
		$('#modifyMapNotice').offset({top: modifyMap.offset().top + 20, left: modifyMap.offset().left + 80});
		mapMarker(marker[0].latitude, marker[0].longitude, 'modifyMap', true);
	}
});

