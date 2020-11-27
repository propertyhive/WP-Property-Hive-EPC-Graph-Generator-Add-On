jQuery(document).ready(function()
{
	jQuery('a.generate-epc').click(function(e)
	{
		e.preventDefault();

		jQuery(this).html('Generating EPC...');
		jQuery(this).attr('disabled', 'disabled');

		var eer_current = jQuery('#eer_current').val();
		var eer_potential = jQuery('#eer_potential').val();
		var eir_current = jQuery('#eir_current').val();
		var eir_potential = jQuery('#eir_potential').val();

		var eer_array = [eer_current, eer_potential].filter(Number);
		var eir_array = [eir_current, eir_potential].filter(Number);

		// Prevent submission if no values have been entered, or only one of current and potential values has been entered for an EPC type
		if ( (eer_array.length == 0 && eir_array.length == 0) || eer_array.length == 1 || eir_array.length == 1 )
		{
			alert("Please ensure values are present for Current and Potential ratings");
			return false;
		}

		jQuery.ajax({
		  	type: "POST",
		  	url: ph_epc_graph_generator_ajax_object.ajax_url,
		  	data: { 
		  		action: 'propertyhive_generate_epc_graph',
		  		post_id: ph_epc_graph_generator_ajax_object.post_id, 
		  		eer_current: eer_current, 
		  		eer_potential: eer_potential,
		  		eir_current: eir_current,
		  		eir_potential: eir_potential
		  	},
		  	success: function(response)
		  	{
		  		// return includes URL and attachment ID
		  		if ( response.success != 'undefined' && response.success == true )
		  		{
		  			var existing_epc_attachment_ids = jQuery('#epc_attachment_ids').val();
		  			if ( existing_epc_attachment_ids != '' )
		  			{
		  				existing_epc_attachment_ids += ',';
		  			}
		  			existing_epc_attachment_ids += response.attachment_id;

		  			jQuery('#epc_attachment_ids').val(existing_epc_attachment_ids);

		  			mediaHTML = '<li id="epc_' + response.attachment_id + '">';
                    mediaHTML += '<div class="hover"><div class="attachment-delete"><a href=""></a></div><div class="attachment-edit"><a href=""></a></div></div>';
                    mediaHTML += '<a href="' + response.url + '" target="_blank"><img src="' + response.url + '" alt="" width="150" height="150"></a></li>';
                    
                    jQuery('#property_epcs_grid ul').append(mediaHTML);

                    jQuery('#eer_current').val('');
					jQuery('#eer_potential').val('');
					jQuery('#eir_current').val('');
					jQuery('#eir_potential').val('');
		  		}
		  		else
		  		{
		  			if ( response.error != 'undefined' )
		  			{
			  			alert("An error occured whilst trying to generate the EPC:\n\n" + response.error);
			  		}
			  		else
			  		{
			  			alert("An error occured whilst trying to generate the EPC");
			  		}
		  		}

		  		jQuery('a.generate-epc').html('Generate EPC');
				jQuery('a.generate-epc').attr('disabled', false);
		  	}
		});
	});
});