jQuery(document).ready(function($) {
	
	 $('#clean_vehicles').on('submit', function(event) {
            // Prevent the default form submission
            event.preventDefault();

            // Show confirmation dialog
            if (confirm('Are you sure you want to Clean all vehicles data?')) {
                // If confirmed, submit the form
                this.submit();
            }
        });
		
		
		// Search by eBay tag
		jQuery("#btn_searhc_tags").click(function(){
			jQuery("#frm_compat").hide()
			var tag = jQuery("#product_tag").val();
			
			$.ajax({
				url: ajax.ajaxurl, // Provided by WordPress
				method: "POST",
				//dataType: "json",   
				data: {
					action: "ebay_fetch_tag_product",
					tag: tag,
				 
				},
				beforeSend: function () {
					$("#btn_searhc_tags").text("searching...").prop("disabled", true);
				},
				success: function (response) {
					/* if (response.success) {
						 
					} else {
						alert(response.data || "Failed to get.");
					} */
					jQuery("#frm_compat").show();
					jQuery("#tag_products_res").html(response);
				},
				complete: function () {
					$("#btn_searhc_tags").text("Search").prop("disabled", false);
				},
			}); 
			
		});
		// Search with ebay itemId
		jQuery(document).on("click","#btn_searhc_ebay_item",function(){
			
			jQuery("#frm_compat").hide()
			var tag = jQuery("#product_tag").val();
			var ebay_itemid = jQuery("#ebay_itemid").val();
			
			$.ajax({
				url: ajax.ajaxurl, // Provided by WordPress
				method: "POST",
				//dataType: "json",   
				data: {
					action: "ebay_fetch_tag_product",
					tag: tag,
					ebay_itemid: ebay_itemid,
				 
				},
				beforeSend: function () {
					$("#btn_searhc_tags").text("searching...").prop("disabled", true);
				},
				success: function (response) {
					 
					jQuery("#frm_compat").show();
					jQuery("#tag_products_res").html(response);
				},
				complete: function () {
					$("#btn_searhc_tags").text("Search").prop("disabled", false);
				},
			}); 
			
			
		});
		 
		
		jQuery("#frm_compat").submit(function(){
			
			var send_data = jQuery(this).serialize();

			$.ajax({
				url: ajax.ajaxurl, // Provided by WordPress
				method: "POST",
				dataType: "json",   
				data: send_data,
				beforeSend: function () {
					jQuery("#btn_up_vehi").text("Processing...").prop("disabled", true);
				},
				success: function (response) {
					if (response.success) {
						 alert('Data has been updated');
					} else {
						alert(response.data);
					} 
					 
				},
				complete: function () {
					jQuery("#btn_up_vehi").text("Update to product").prop("disabled", false);
				},
			});
			
			
			return false;
		});
		 
		
		
});
	
			