  jQuery( function($) {
	
	jQuery('.sel2').select2();
	var ajaxurl = ratcwp_php_vars.admin_url;
	var nonce   = ratcwp_php_vars.nonce;

	jQuery('.sel_pros').select2({

		ajax: {
			url: ajaxurl, // AJAX URL is predefined in WordPress admin
			dataType: 'json',
			type: 'POST',
			delay: 250, // delay in ms while typing when to perform a AJAX search
			data: function (params) {
				return {
					q: params.term, // search query
					action: 'cspsearchProducts', // AJAX action for admin-ajax.php
					nonce: nonce // AJAX nonce for admin-ajax.php
				};
			},
			processResults: function( data ) {
				var options = [];
				if ( data ) {
   
					// data is the array of arrays, and each of them contains ID and the Label of the option
					$.each( data, function( index, text ) { // do not forget that "index" is just auto incremented value
						options.push( { id: text[0], text: text[1]  } );
					});
   
				}
				return {
					results: options
				};
			},
			cache: true
		},
		multiple: true,
		placeholder: 'Choose Products',
		minimumInputLength: 3 // the minimum of symbols to input before perform a search
		
	});


	jQuery('.sel22').select2({

		ajax: {
			url: ajaxurl, // AJAX URL is predefined in WordPress admin
			dataType: 'json',
			type: 'POST',
			delay: 250, // delay in ms while typing when to perform a AJAX search
			data: function (params) {
				return {
					q: params.term, // search query
					action: 'cspsearchUsers', // AJAX action for admin-ajax.php
					nonce: nonce // AJAX nonce for admin-ajax.php
				};
			},
			processResults: function( data ) {
				var options = [];
				if ( data ) {
   
					// data is the array of arrays, and each of them contains ID and the Label of the option
					$.each( data, function( index, text ) { // do not forget that "index" is just auto incremented value
						options.push( { id: text[0], text: text[1]  } );
					});
   
				}
				return {
					results: options
				};
			},
			cache: true
		},
		multiple: false,
		placeholder: 'Choose Users',
		minimumInputLength: 3 // the minimum of symbols to input before perform a search
		
	});


	$('.save-variation-changes').prop('disabled', false);

	$('#ratcwp_enable_hide_pirce_registered').change(function () {
		if (this.checked) { 
			//  ^
			$('#userroles').fadeIn('fast');
		} else {
			$('#userroles').fadeOut('fast');
		}
	});

	$('#ratcwp_hide_price').change(function () {
		if (this.checked) { 
			//  ^
			$('#hp_price').fadeIn('fast');
		} else {
			$('#hp_price').fadeOut('fast');
		}
	});

	$('#wpiudacb_disable_add_to_cart').change(function () {
		if (this.value == 'inquire_us') { 
			//  ^
			$('.hp_cart').fadeIn('fast');
		} else {
			$('.hp_cart').fadeOut('fast');
		}
	});

	$('#ratcwp_apply_on_all_products').change(function () {
		if (this.checked) { 
			//  ^
			$('.hide_all_pro').fadeOut('fast');
		} else {
			$('.hide_all_pro').fadeIn('fast');
		}
	});


	//On Load

	if ($("#ratcwp_enable_hide_pirce_registered").is(':checked')) {
		$("#userroles").show();  // checked
	} else {
		$("#userroles").hide();
	}

	if ($("#ratcwp_hide_price").is(':checked')) {
		$("#hp_price").show();  // checked
	} else {
		$("#hp_price").hide();
	}

	if ($("#ratcwp_hide_cart_button").is(':checked')) {
		$(".hp_cart").show();  // checked
	} else {
		$(".hp_cart").hide();
	}	
	
	if ($("#wpiudacb_disable_add_to_cart").is(':selected') && $("#wpiudacb_disable_add_to_cart").value() == 'inquire_us') {
		$(".hp_cart").show();  // checked
	} else {
		$(".hp_cart").hide();
	}

	


	if ($("#ratcwp_apply_on_all_products").is(':checked')) {
		$(".hide_all_pro").hide();  // checked
	} else {
		$(".hide_all_pro").show();
	}

} );

  jQuery(function($) {
	$(".child").on("click",function() {
		$parent = $(this).prevAll(".parent");
		if ($(this).is(":checked")) {
			$parent.prop("checked",true);
		} else {
			var len = $(this).parent().find(".child:checked").length;
			$parent.prop("checked",len>0);
		}    
	});
	$(".parent").on("click",function() {
		$(this).parent().find(".child").prop("checked",this.checked);
	});
});



	
