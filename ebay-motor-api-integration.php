<?php 
/**
 * Plugin Name:  eBay Motor API Integration
 * Description:  eBay Motor API Integration  
 * Version: 1.0.0
 * Author: Nimesh Gorfad
 * Author URI:  https://www.freelancer.com/u/nimeshgorfad?sb=t
 * Text Domain: ebay_mai  
 */
  
 
 include("ebay.php");		
 include("ebay-admin.php");
  
 
 function ebay_update_product_vehicales(){
	 
	 //vehicle_make
	 //vehicle_model
	 //vehicle_year
	 //vehicle_trim
	 
	// print_r( $_POST );
	 
	$what_do = $_POST['what_do']; 
	$woo_pros = isset( $_POST['woo_pro'] ) ? $_POST['woo_pro'] : array(); 
	
	if( empty($woo_pros) ){
		
		wp_send_json_error('Plese select products ');	
		die();
	}
	 
	/* $vehicle_make 	= 'BMW';
	$vehicle_model 	= '325e';
	$vehicle_year 	= '1984';
	$vehicle_trim 	= 'Base'; */
	
	$assign_IDs = array();
	
	if(!empty( $_POST['make'] )){
		$makes = $_POST['make'];
		
		foreach($makes as $k => $make){
			
			$vehicle_make 	= $make;
			$vehicle_model 	= $_POST['model'][$k];
			$vehicle_year 	= $_POST['year'][$k];
			$vehicle_trim 	= $_POST['trim'][$k];
			$term_name = "$vehicle_year, $vehicle_make, $vehicle_model, $vehicle_trim";
			  
			
			$meta_query =  array();
			$meta_query[] = array(	"key" => "vehicle_make",
									"value" => $vehicle_make,
									"compare" => '='
								);
			$meta_query[] = array(	"key" => "vehicle_model",
									"value" => $vehicle_model,
									"compare" => '='
								);
			$meta_query[] = array(	"key" => "vehicle_year",
									"value" => $vehicle_year,
									"compare" => '='
								);
			$meta_query[] = array(	"key" => "vehicle_trim",
								"value" => $vehicle_trim,
								"compare" => '='
							);
												
			  $meta_query["relation"] = "AND";

				$args = [
					"taxonomy" => "vehicles",
					"hide_empty" => false,
					"meta_query" => $meta_query,
				];

				$vehicles_term = get_terms($args);
				if( !empty( $vehicles_term ) ){
					
					$assign_IDs[] = $vehicles_term[0]->term_id;
					
				}else{
					
					$vehicles_term = wp_insert_term($term_name, 'vehicles');
					if (!is_wp_error($vehicles_term)) {
						
						$term_id = $vehicles_term['term_id'];
						$assign_IDs[] = $term_id;
						
						add_term_meta($term_id, 'vehicle_make', $vehicle_make);
						add_term_meta($term_id, 'vehicle_model', $vehicle_model);
						add_term_meta($term_id, 'vehicle_year', $vehicle_year);
						add_term_meta($term_id, 'vehicle_trim', $vehicle_trim);


	
					}
					 
				}
			
		 
		}
	}
	
	 
	if( !empty( $assign_IDs ) ){
		
		foreach($woo_pros as $post_id){
			
			if( "append" == $what_do ){
				wp_set_post_terms($post_id,$assign_IDs,'vehicles',true);	
			}else{
				wp_set_post_terms($post_id,$assign_IDs,'vehicles',false);	
			}
			
			if( isset( $_POST['ebay_weight'] ) ){
				
				update_post_meta($post_id,'_weight',$_POST['ebay_weight']);
			}
			
			if( isset( $_POST['ebay_width'] ) ){
				
				update_post_meta($post_id,'_width',$_POST['ebay_width']);
			}
			
			if( isset( $_POST['ebay_length'] ) ){
				
				update_post_meta($post_id,'_length',$_POST['ebay_length']);
			}
			
			if( isset( $_POST['ebay_height'] ) ){
				
				update_post_meta($post_id,'_height',$_POST['ebay_height']);
			}
			
			
		}
	}	
		 
	$total_terms = count($assign_IDs);
	
	wp_send_json_success( array('terms'=>$assign_IDs,'total_terms'=> $total_terms ) );
	die();
 }
 
 add_action('wp_ajax_ebay_update_product_vehicales', 'ebay_update_product_vehicales');
 
 

function ebay_fetch_tag_woo_product(){
	$tag_name = $_POST['tag'];
	  
	$term = get_term_by('name', $tag_name, 'product_tag');

    if (!$term) {
		
        echo '<p> Tag not found. </p>';
		die();
    }
	
	 $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1, // Fetch all products, adjust if needed
        'tax_query' => array(
            array(
                'taxonomy' => 'product_tag',
                'field'    => 'term_id',
                'terms'    => $term->term_id,
            ),
        ),
    );

    $query = new WP_Query($args);
	
	
		$products = array();
	  if ($query->have_posts()) {
		  echo '<table> <tbody >';
        while ($query->have_posts()) {
            $query->the_post();
			
			$pro_id = get_the_ID();
			//$permalink = get_permalink();
			$permalink = get_edit_post_link();
			
            $products[] = array(
                'id' => $pro_id,
                'title' => get_the_title(),
                'permalink' => $permalink,
            ); 
			
			$terms = get_the_terms($pro_id,'vehicles');
			$totla_vehicle = !empty($terms) ? "( Vehicles : ".count($terms)." )" : " ( Vehicles : 0 ) "; 
			echo '<tr>
				<td> <input type="checkbox" name="woo_pro[]" value="'.$pro_id.'" > </td> 
				<td> <a href="'.$permalink.'"> ( '.$pro_id .' ) </a>  '.get_the_title().' '.$totla_vehicle.' </td>
			</tr>';
			 
        }
		echo '</tbody> </table>';
        wp_reset_postdata();
        
    }else{
		echo "<p> No products found for this tag.'</p>";
	}
	
	$eBayMoter = new eBayMoter;
	
	if( !empty( $products ) ){
		
		$tag_name = str_replace("#","",$tag_name);
		
		
		$data = $eBayMoter->ebay_search_item($tag_name);
		 
		
		if( isset( $data["itemSummaries"] ) ){
		
			$fullItemId  = $data['itemSummaries'][0]['itemId']; 
			$itemIdArray = explode('|', $fullItemId);
			$itemId = $itemIdArray[1];
			 
			if( isset( $_POST['ebay_itemid']) ){
				$itemId = $_POST['ebay_itemid'];
			}
			$xmlResponse = $eBayMoter->ebay_product_compatibility($itemId);
			 
			
			if( isset( $xmlResponse['error'] ) ){
				echo '<p>'.$xmlResponse['error'].'</p>';
				die();
			}
			
			if (isset($xmlResponse->Item->ItemCompatibilityList)) {
				
				 $item = $xmlResponse->Item;
				 
				  $compatibilities = $xmlResponse->Item->ItemCompatibilityList->Compatibility;
				  
				  $Weight = $item->ShippingPackageDetails->WeightMajor;
				  $Width = $item->ShippingPackageDetails->PackageWidth;
				  $Length = $item->ShippingPackageDetails->PackageLength;
				  $Height = $item->ShippingPackageDetails->PackageDepth;
					
				  $Weight =  !empty($Weight) ?  eBayPoundsToKg($Weight) : '';
				  $Width  =  !empty($Width) ?  eBayInchesToCm($Width) : '';
				  $Length =  !empty($Length) ?  eBayInchesToCm($Length) : '';
				  $Height =  !empty($Height) ?  eBayInchesToCm($Height) : '';
				 
				  
				  
				  echo "<h3>Vehicle Compatibility:</h3>";
				 
				  echo "<p> Item ID : " . $item->ItemID . "<p>";
				  echo "<p>Title : " . $item->Title . "<p>";
				  echo "</p> Price : " . $item->SellingStatus->CurrentPrice . "<p>";				  
				  echo "</p> Vehicles Total : " . count( $compatibilities ) . "<p>";
				  
				  echo '<p><input type="checkbox" name="ebay_weight" value="'.$Weight.'"  > Weight : '.$Weight.' kg  </p>';
				  
				  echo '<p><input type="checkbox" name="ebay_width" value="'.$Width.'"  > Width : '.$Width.' cm  </p>';
				  
				  echo '<p><input type="checkbox" name="ebay_length" value="'.$Length.'"  > Length : '.$Length.' cm  </p>';
				  
				  echo '<p><input type="checkbox" name="ebay_height" value="'.$Height.'"  > Height/Depth : '.$Height.' cm  </p>';
					
					
				echo '<p> <input type="text" id="ebay_itemid" placeholder="Enter eBay product id/Item id " > <button type="button" class="button-primary " id="btn_searhc_ebay_item" value=""> Search </button> </p>';
	
			
				 echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>
					<thead>
						<tr>
							<th>Make</th>
							<th>Model</th>
							<th>Year</th>
							<th>Trim</th>
						</tr>
					</thead>
					<tbody>";
					
				foreach ($compatibilities as $compatibility) {
					echo "<tr>";

					$year = '';
					$make = '';
					$model = '';
					$trim = '';

					// Loop through the NameValueList for each compatibility entry to get the necessary data
					foreach ($compatibility->NameValueList as $nameValue) {
						$name = (string)$nameValue->Name;
						$value = (string)$nameValue->Value;

						// Assign values to the corresponding fields
						if ( in_array($name, array('Year','Cars Year') ) ) {
							$year = $value;
						} elseif ( in_array( $name, array('Make','Cars Make','Car Make') ) ) {
							$make = $value;
						} elseif (in_array( $name, array('Model','Cars Model') )) {
							$model = $value;
						} elseif ( in_array( $name, array('Trim','Type','Cars Type') )  ) {
							$trim = $value;
						}
					} 

					// Display the row with the extracted data
					echo '<td><input type="hidden" name="make[]" class="make_in" value="'.$make.'" >'.$make.' </td>';
					echo '<td><input type="hidden" name="model[]" value="'.$model.'" > '.$model.' </td>';
					echo '<td><input type="hidden" name="year[]" value="'.$year.'" >  '.$year.' </td>';
					echo '<td><input type="hidden" name="trim[]" value="'.$trim.'" > '.$trim.' </td>';
					 

					echo "</tr>";
				}
				echo '</tbody></table>';
				
			}else{
				echo '<p>Vehicle compatibility data not exits</p>';
				
				echo '<p> <input type="text" id="ebay_itemid" placeholder="Enter eBay product id/Item id " > <button type="button" class="button-primary " id="btn_searhc_ebay_item" value=""> Search </button> </p>';
				 
				die();
				
			}	
			 
		
		}else{
			
			if( isset( $data["errors"] )){ 
				echo '<h3> eBay API Error </h3>';
				foreach($data["errors"][0] as $ekey=>$error ){
					echo '<p> '.$ekey.' : ' .$error. ' </p>';
				}
				 
			}
		
			
		}
		
	
		
	}
	
	die();
	
}
add_action('wp_ajax_ebay_fetch_tag_product', 'ebay_fetch_tag_woo_product');
 
 function eBayPoundsToKg($pounds) {
    $kg = $pounds * 0.45359237; // 1 pound = 0.45359237 kilograms
    return round($kg, 2); // Round to 2 decimal places
}


function eBayInchesToCm($inches) {
    $cm = $inches * 2.54; // 1 inch = 2.54 centimeters
    return round($cm, 2); // Round to 2 decimal places
}
