/*
* @Author: Timi Wahalahti
* @Date:   2018-04-04 16:36:49
* @Last Modified by:   Timi Wahalahti
* @Last Modified time: 2018-04-07 13:43:53
*/

jQuery( document ).ready(function( $ ) {

	// Defaults.
	var form_fields = [];
	var module_fields = [];

	// Run module field select disable in case we have data from databse.
	disable_module_fields_used();

	// Listen module field select changes.
	$(document).on('change', '.crmservice-metabox-connections-wrap .row-field .col-module select', function() {
		$(this).parent('.col-module').find('input').val( this.value ); // clone value to hidden field
		$('.crmservice-metabox-connections-wrap .row-field .col-module select option').attr('disabled', false); // de-disable all options
		disable_module_fields_used(); // re-disable selected fields from select fields
	});

	// Listen form select change and get available fields for selected form.
	$(document).on('change', 'select[name="crmservice_form"]', function() {
	  $('.crmservice-metabox-connections-wrap .row-field:not(.row-field-base)').remove();

	  console.log( this.value );
	  $.ajax({
			type: 'GET',
			dataType: 'json',
			data: { form: this.value },
			url: crmservice.root + 'crmservice/v1/form/fields',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', crmservice.nonce );
			},
			success: function(data) {
				form_fields = data['fields'];
				populate_form_fields();
			}
		});
	});

	// Listen module select change and get available fields for selected module.
	$(document).on('change', 'select[name="crmservice_module"]', function() {
		$('.crmservice-metabox-connections-wrap .col-module select').empty().append('<option>Select</option>');

		console.log( this.value );
		$.ajax({
		   type: 'GET',
		   dataType: 'json',
		   data: { module: this.value },
		   url: crmservice.root + 'crmservice/v1/module/fields',
		   beforeSend: function ( xhr ) {
		    	xhr.setRequestHeader( 'X-WP-Nonce', crmservice.nonce );
		    },
		   success: function(data) {
		   	module_fields = data;
				populate_module_fields();
		   }
		});
	});

	// Function to disable module fields used elsewhere.
	function disable_module_fields_used() {
		$('.crmservice-metabox-connections-wrap .row-field .col-module select').each(function() {
			if ( '0' !== this.value ) {
				$('.crmservice-metabox-connections-wrap .row-field .col-module select option[value="' + this.value + '"]').attr('disabled', 'true');
			}
		});
	} // end disable_module_fields_used

	// Make form field rows.
	function populate_form_fields() {
		console.log( form_fields );

		x = 0;
		$.each( form_fields, function(i, item) {
			row = $('.crmservice-metabox-connections-wrap .row-field-base').clone();
			row = $(row);

			row.removeAttr('style').removeClass('row-field-base');
			row.attr('id', 'form-row-' + i).find('.col-form p').html( item );

			row.find('input[name="form_field"]').attr('name', 'crmservice_connections[' + x + '][form_field]').val( i );
			row.find('[name="module_field"]').attr('name', 'crmservice_connections[' + x + '][module_field]');

			$('.crmservice-metabox-connections-wrap').append(row);

			x++;
		});
	} // end function populate_form_fields

	// Set module field select options.
	function populate_module_fields() {
		console.log( module_fields );

		$.each( module_fields, function(i, item) {
	    $('.crmservice-metabox-connections-wrap .col-module select').append( $('<option>', {
	        value: item.name,
	        text : item.label
	    }));
		});
	} // end function populate_module_fields
});
