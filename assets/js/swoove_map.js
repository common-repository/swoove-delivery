let map_;
let marker;
let billingAddressSet;
let ready = false;
let currentLat;
let currentLng;

jQuery(function ($) {
	$(document).ready(function () {
		let billingAddress = $('#billing_address_1');
		let shippingAddress = $('#shipping_address_1');
		let customerLat = $('#swoove_customer_lat');
		let customerLng = $('#swoove_customer_lng');
		let fName = $('#billing_first_name');
		let lName = $('#billing_last_name');
		let cMobile = $('#billing_phone');
		let cEmail = $('#billing_email')

		let address = billingAddress.val() || shippingAddress.val();
		billingAddressSet = address;
		ready = (address && fName.val() && lName.val() && cMobile.val());

		$('#billing_phone, #billing_first_name, #billing_last_name').bind('blur', function () {
			if (!address || !fName.val() || !lName.val() || !cMobile.val())
				return;
			else {
				ready = true;
				if (currentLng && currentLat)
					repositionMarker();
				else
					queryPlace(address);
			}
		});

		$('#billing_address_1, #billing_email').bind('blur', function () {
			let billingAddress1 = billingAddress.val();
			if (billingAddress1 && billingAddress1.length > 5) {
				address = billingAddress1;
				if (address !== billingAddressSet) {
					verifyReadiness();
					billingAddressSet = address;
					queryPlace(address);
				}
			}
		});

		if (address && address.length > 5) {
			queryPlace(address);
		}

		function verifyReadiness() {
			ready = (address && fName.val() && lName.val() && cMobile.val());
		}

		//Query and use current billing location points
		function queryPlace(placeAddress) {
			$.post('https://swoovedelivery.com/api/v1.0/extension/places/search/', {
				input: placeAddress
			}, function (response) {
				if (response.success) {
					let foundPlaces = response.responses;
					if (foundPlaces.length) {
						let selectedPlace = foundPlaces[0];
						if (selectedPlace) {
							$.post('https://swoovedelivery.com/api/v1.0/extention/places/process-location', selectedPlace, function (foundLocationResponse) {
								let foundLocationData = foundLocationResponse.responses;
								if (foundLocationData) {
									currentLat = foundLocationData.lat;
									currentLng = foundLocationData.lng;
									if (ready) repositionMarker();
								}
							});
						}
					}
				}
			});
		}

		function repositionMarker() {
			customerLat.val(currentLat);
			customerLng.val(currentLng);
			let cName = `${fName.val()} ${lName.val()}`;

			if (!cMobile.val()) {
				console.log('mobile/name not set!');
				return;
			}

			$.post('/wp_woo/?wc-ajax=save_customer_point', {
				lat: currentLat,
				lng: currentLng,
				name: cName,
				mobile: cMobile.val(),
				email: cEmail.val()
			});

			setTimeout(function () {
				$('body').trigger('update_checkout', {
					update_shipping_method: true
				});
			}, 1000);
		}
	})
});