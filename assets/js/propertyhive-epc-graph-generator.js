jQuery(document).ready(function()
{
	jQuery('a.generate-epc').click(function(e)
	{
		e.preventDefault();

		var eer_current = jQuery('#eer_current').val();
		var eer_potential = jQuery('#eer_potential').val();
		var eir_current = jQuery('#eir_current').val();
		var eir_potential = jQuery('#eir_potential').val();

		if ( eer_current == '' || eer_potential == '' || eir_current == '' || eir_potential == '' )
		{
			alert("Please ensure all values are present");
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
		  	}
		});
	});
});