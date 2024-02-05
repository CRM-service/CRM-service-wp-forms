/*
* @Author: Timi Wahalahti
* @Date:   2018-04-04 16:36:49
* @Last Modified by:   Timi Wahalahti
* @Last Modified time: 2019-07-01 15:28:52
*/

jQuery(document).ready(($) => {
  // Tooltips
  tippy('span.status', {
    arrow: true,
  	arrowType: 'sharp',
  	size: 'small',
  	placement: 'top-start',
  });

  // Defaults.
  let form_fields = [];
  let module_fields = [];

  // Run module field select disable in case we have data from databse.
  disable_module_fields_used();

  // if db module, get fields
  if ($('select[name="crmservice_module"] option:selected').length) {
    $.ajax({
      type: 'GET',
      dataType: 'json',
      data: { module: $('select[name="crmservice_module"] option:selected').val() },
      url: `${crmservice.root}crmservice/v1/module/fields`,
      beforeSend(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', crmservice.nonce);
      },
      success(data) {
        module_fields = data;
        maybe_show_module_field_notices();
      },
    });
  }

  // Listen module field select changes.
  $(document).on('change', '#crmservice-integration-settings .row-field .col-module select', function () {
    $(this).parent('.col-module').find('input').val(this.value); // clone value to hidden field
    $('#crmservice-integration-settings .row-field .col-module select option').attr('disabled', false); // de-disable all options
    disable_module_fields_used(); // re-disable selected fields from select fields
  });

  // Listen form select change and get available fields for selected form.
  $(document).on('change', 'select[name="crmservice_form"]', function () {
	  $('.crmservice-metabox-connections-wrap .row-field:not(.row-field-base)').remove();

	  $.ajax({
      type: 'GET',
      dataType: 'json',
      data: { form: this.value },
      url: `${crmservice.root}crmservice/v1/form/fields`,
      beforeSend(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', crmservice.nonce);
      },
      success(data) {
        form_fields = data.fields;
        populate_form_fields();
      },
    });
  });

  // Listen module select change and get available fields for selected module.
  $(document).on('change', 'select[name="crmservice_module"]', function () {
    $('.crmservice-metabox-connections-wrap .col-module select').empty().append('<option>Select</option>');
    $('.crmservice-metabox-static-fields-wrap .col-module select').empty().append('<option>Select</option>');

    $.ajax({
		   type: 'GET',
		   dataType: 'json',
		   data: { module: this.value },
		   url: `${crmservice.root}crmservice/v1/module/fields`,
		   beforeSend(xhr) {
		    	xhr.setRequestHeader('X-WP-Nonce', crmservice.nonce);
		    },
		   success(data) {
		   	module_fields = data;
        populate_module_fields();
		   },
    });
  });

  // Listen module field selection changes and list options below if field type is select or multiselect.
  $(document).on('change', '.crmservice-metabox-connections-wrap .col-module select, .crmservice-metabox-static-fields-wrap .col-module select', function () {
    // Hide select options.
    $(this).closest('.col-module').find('p.select-options').hide();
    $(this).closest('.col-module').find('p.uitype-user-relation').hide();

    selection_type = $(this).find('option:selected').attr('data-type');
    if (selection_type === 'Select' || selection_type === 'MultiSelect') {
      module_field = _.findWhere(module_fields, { name: $(this).find('option:selected').val() });

      options = $.map(module_field.picklist_values, (val, i) => i);

      // Show possible selection values
      $(this).closest('.col-module').find('p.select-options span').html(options.join(', '));
      $(this).closest('.col-module').find('p.select-options').show();
    }

    selection_uitype = $(this).find('option:selected').attr('data-uitype');
    if (selection_uitype !== null && selection_uitype !== undefined) {
      selection_uitype = selection_uitype.toString();
      if (selection_uitype === '53' || selection_uitype === '700' || selection_uitype === '702' || selection_uitype === '704' || selection_uitype === '101') {
        $(this).closest('.col-module').find('p.uitype-user-relation').show();
      }
    }
  });

  // Listen static field add and clone from base
  $('.crmservice-metabox-static-fields-wrap .row-footer button').on('click', (e) => {
    e.preventDefault();

    x = $('.crmservice-metabox-static-fields-wrap .row-field').length;
    x++;

    row = $('.crmservice-metabox-static-fields-wrap .row-field-base').clone();
    row = $(row);

    row.removeAttr('style').removeClass('row-field-base');
    row.find('select[name="crmservice_static_fields[0]"]').attr('name', `crmservice_static_fields[${x}]`);
    row.find('input[name="crmservice_static_fields[0]"]').attr('name', `crmservice_static_fields[${x}]`);
    row.find('input[name="crmservice_static_fields_values[0]"]').attr('name', `crmservice_static_fields_values[${x}]`);

    $('.crmservice-metabox-static-fields-wrap .row-footer').before(row);
  });

  // Listen module field change
  $(document).on('change', '#crmservice-integration-settings .col-module select', function () {
    $(`input[name="${$(this).attr('name')}"]`).val(this.value);
  });

  // Listen static fields remove
  $(document).on('click', '.crmservice-metabox-static-fields-wrap .row-field button.delete', function (e) {
    e.preventDefault();
    $(this).closest('.row-field').remove();
  });

  // Function to disable module fields used elsewhere.
  function disable_module_fields_used() {
    $('#crmservice-integration-settings .row-field .col-module select').each(function () {
      if (this.value !== '0') {
        $(`#crmservice-integration-settings .row-field .col-module select option[value="${this.value}"]`).attr('disabled', 'true');
      }
    });
  } // end disable_module_fields_used

  // Function to show warnign on init state if fields is select, multiselect or user relation.
  function maybe_show_module_field_notices() {
    $('.crmservice-metabox-connections-wrap .row-field .col-module select, .crmservice-metabox-static-fields-wrap .row-field .col-module select').each(function () {
      selection_type = $(this).find('option:selected').attr('data-type');
      if (selection_type === 'Select' || selection_type === 'MultiSelect') {
        module_field = _.findWhere(module_fields, { name: $(this).find('option:selected').val() });

        options = $.map(module_field.picklist_values, (val, i) => i);

        // Show possible selection values
        $(this).closest('.col-module').find('p.select-options span').html(options.join(', '));
        $(this).closest('.col-module').find('p.select-options').show();
      }

      selection_uitype = $(this).find('option:selected').attr('data-uitype');
      if (selection_uitype !== null && selection_uitype !== undefined) {
        selection_uitype = selection_uitype.toString();
        if (selection_uitype === '53' || selection_uitype === '700' || selection_uitype === '702' || selection_uitype === '704' || selection_uitype === '101') {
          $(this).closest('.col-module').find('p.uitype-user-relation').show();
        }
      }
    });
  } // end maybe_show_module_field_notices

  // Make form field rows.
  function populate_form_fields() {
    $('.crmservice-metabox-connections-wrap').show();
    $('.crmservice-metabox-static-fields-wrap').show();

    x = 0;
    $.each(form_fields, (i, item) => {
      row = $('.crmservice-metabox-connections-wrap .row-field-base').clone();
      row = $(row);

      row.removeAttr('style').removeClass('row-field-base');
      row.attr('id', `form-row-${i}`).find('.col-form p').html(item);

      row.find('input[name="form_field"]').attr('name', `crmservice_connections[${x}][form_field]`).val(i);
      row.find('[name="module_field"]').attr('name', `crmservice_connections[${x}][module_field]`);

      $('.crmservice-metabox-connections-wrap').append(row);

      x++;
    });
  } // end function populate_form_fields

  // Set module field select options.
  function populate_module_fields() {
    $('.crmservice-metabox-connections-wrap').show();
    $('.crmservice-metabox-static-fields-wrap').show();

    $.each(module_fields, (i, item) => {
	    $('.crmservice-metabox-connections-wrap .col-module select').append($('<option>', {
	        value: item.name,
	        text: `${item.label} (${item.type})`, // TODO: translate type
	        'data-type': item.type,
	    }));

	    $('.crmservice-metabox-static-fields-wrap .col-module select').append($('<option>', {
	        value: item.name,
	        text: `${item.label} (${item.type})`, // TODO: translate type
	        'data-type': item.type,
	    }));
    });

    // Hide select options.
    $('.crmservice-metabox-connections-wrap .col-module p.select-options').hide();
    $('.crmservice-metabox-connections-wrap .col-module p.uitype-user-relation').hide();
  } // end function populate_module_fields
});
