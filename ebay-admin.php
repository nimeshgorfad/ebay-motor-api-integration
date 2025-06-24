<?php

// Create the admin menu
add_action('admin_menu', 'ebay_oauth_generator_menu');

function ebay_oauth_generator_menu()
{

	add_menu_page(
		'eBay Motor',
		'eBay Motor',
		'manage_options',
		'ebay-oauth-generator',
		'ebay_oauth_generator_page',
		'dashicons-admin-network',
		100
	);

	add_submenu_page(
		'ebay-oauth-generator', // Parent slug
		'eBay Motor Map',       // Page title
		'eBay Motor Map',       // Menu title
		'manage_options',       // Capability
		'ebay-motor-map',       // Menu slug
		'ebay_motor_map_page_callback' // Callback function
	);



}

function ebay_motor_map_page_callback()
{

	wp_enqueue_script('ebay-script', plugins_url('js/ebay-admin.js', __FILE__), array('jquery'), '0.1.5');

	wp_localize_script('ebay-script', 'ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'ajax_url' => admin_url('admin-ajax.php')));


	$eBayMoter = new eBayMoter;

	echo '<div class="wrap">';
	echo '<h1>eBay Motor Data Maping</h1>';
	echo '<p>Welcome to the eBay Motor Map page.</p>';

	// Output the page content
	echo '<form method="post" action="">';

	echo '<label for="product_tag">Enter Product Tags:</label>';

	echo '<p><input type="text" id="product_tag" > </p>';

	echo '<p><button type="button" class="button-primary " id="btn_searhc_tags" value=""> Search </button></p>';

	echo '</form>';

	echo '<form method="post" action="" id="frm_compat"  style="display:none">';
	echo '<h3>List of Product.</h3>';
	echo '<div id="tag_products_res"> </div>';


	echo '<p> <input type="radio" name="what_do" value="append" checked > Append </p>';
	echo '<p> <input type="radio" name="what_do" value="replace" > Replace </p>';
	echo '<p>
				<input type="hidden" name="action" value="ebay_update_product_vehicales" />
				<button type="submit" id="btn_up_vehi" class="button-primary " value=""> Update to product </button></p>';

	echo '</form>';

	echo '</div>';




}

//add_action('wp_ajax_ebay_fetch_tag_product', 'ebay_fetch_tag_product');

function ebay_fetch_tag_product()
{
	$tag = $_POST['tag'];


	$eBayMoter = new eBayMoter;
	$data = $eBayMoter->ebay_search_item($tag);

	if (isset($data["itemSummaries"])) {

		$itemSummaries = $data["itemSummaries"];

		foreach ($itemSummaries as $items) {

			$itemId = $items['itemId'];
			$title = $items['title'];

			echo '<tr>
				<td> <input type="checkbox" name="ebay_items[]" value="' . $itemId . '" > </td> 
				<td> ' . $title . ' </td>
			</tr>';


		}

	} else {

		if (isset($data["errors"])) {
			foreach ($data["errors"][0] as $ekey => $error) {
				echo '<p> ' . $ekey . ' : ' . $error . ' </p>';
			}

		}

	}

	die();
}


// Render the admin page
function ebay_oauth_generator_page()
{
	// Handle form submission
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ebay_oauth_submit'])) {


		update_option('ebay_client_id', sanitize_text_field($_POST['ebay_client_id']));
		update_option('ebay_dev_id', sanitize_text_field($_POST['ebay_dev_id']));
		update_option('ebay_client_secret', sanitize_text_field($_POST['ebay_client_secret']));

		echo '<div class="updated"><p>Settings saved!</p></div>';

	}

	// Retrieve saved options
	$clientId = get_option('ebay_client_id', '');
	$ebay_dev_id = get_option('ebay_dev_id', '');
	$ebay_client_secret = get_option('ebay_client_secret', '');
	$redirectUri = admin_url('admin.php?page=ebay-oauth-generator');
	$redirectUri = admin_url();
	$scopes = 'https://api.ebay.com/oauth/api_scope';
	$customState = 'nkgebay' . time();
	$customState = 'nkgebay';


	$authUrl = '';
	if ($clientId && $redirectUri && $scopes) {

		$encodedRedirectUri = urlencode($redirectUri);

		$authUrl = "https://auth.ebay.com/oauth2/authorize?"
			. "response_type=code"
			. "&client_id={$clientId}"
			. "&redirect_uri={$encodedRedirectUri}"
			. "&scope=" . urlencode($scopes)
			. "&state={$customState}";

		$authUrl = "https://auth.ebay.com/oauth2/authorize?"
			. "response_type=code"
			. "&client_id={$clientId}"
			. "&redirect_uri={$encodedRedirectUri}"
			. "&scope={$scopes}&state={$customState}";


	}


	?>
	<div class="wrap">
		<h1>eBay OAuth Redirect Generator</h1>
		<form method="POST" action="">
			<table class="form-table">
				<tr>
					<th scope="row"><label for="your_client_id">App ID (Client ID)
						</label></th>
					<td><input name="ebay_client_id" id="ebay_client_id" type="text" class="regular-text"
							value="<?php echo $clientId; ?>" required></td>
				</tr>

				<tr>
					<th scope="row"><label for="ebay_dev_id">Dev ID
						</label></th>
					<td><input name="ebay_dev_id" id="ebay_dev_id" type="text" class="regular-text"
							value="<?php echo $ebay_dev_id; ?>" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="ebay_client_secret">Cert ID (Client Secret)</label></th>
					<td><input name="ebay_client_secret" id="ebay_client_secret" type="text" class="regular-text"
							value="<?php echo $ebay_client_secret; ?>"></td>
				</tr>
				<tr>
					<th scope="row"> Redirect Uri
					</th>
					<td><?php echo $redirectUri ?></td>
				</tr>
			</table>
			<?php submit_button('Save', 'primary', 'ebay_oauth_submit'); ?>
		</form>

		<?php if (isset($authUrl)): ?>

			<a href="<?php echo $authUrl; ?>"> Connect to eBay </a>
			<p><textarea readonly rows="3" class="large-text"><?php echo $authUrl; ?></textarea></p>
		<?php endif; ?>
	</div>
	<?php


	//$eBayMoter = new eBayMoter;
	//ebay_search_item();
	// ebay_product_compatibility();
	// Refresh Token
	//$eBayMoter->ebay_refresh_token_nkg();

}

// Update token data..

add_action("admin_init", function () {

	if (isset($_GET['state']) && isset($_GET['code']) && "nkgebay" == $_GET['state']) {

		$code = $_GET['code'];

		$client_id = get_option('ebay_client_id', '');
		$client_secret = get_option('ebay_client_secret', '');
		$ebay_dev_id = get_option('ebay_dev_id', '');
		$redirectUri = admin_url('admin.php?page=ebay-oauth-generator');
		$redirect_uri = admin_url();

		$authorization_code = $code;

		$token_url = "https://api.ebay.com/identity/v1/oauth2/token";

		$post_data = http_build_query([
			"grant_type" => "authorization_code",
			"code" => $authorization_code,
			"redirect_uri" => $redirect_uri
		]);

		$auth_header = base64_encode("$client_id:$client_secret");

		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $token_url,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $post_data,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				"Content-Type: application/x-www-form-urlencoded",
				"Authorization: Basic $auth_header"
			],
		]);

		$response = curl_exec($curl);

		// Check for cURL errors
		if ($response === false) {

			$errorMessage = curl_error($ch);
			echo "Error fetching item details: $errorMessage";
			curl_close($curl);
			return array("error" => $errorMessage);
			exit;
		}

		curl_close($curl);

		$response_data = json_decode($response, true);


		if (isset($response_data['access_token'])) {

			$expires_in = $response_data['expires_in'];
			update_option("ebay_access_token", $response_data['access_token']);
			update_option("ebay_token_type", $response_data['token_type']);
			update_option("ebay_expires_in", $response_data['expires_in']);
			update_option("ebay_refresh_token", $response_data['refresh_token']);

			$ebay_expires_in_next = time() + $expires_in;
			update_option("ebay_expires_in_next", $ebay_expires_in_next);

			header("Location: admin.php?page=ebay-motor-map");
			die();

			/* echo "Access Token: " . $response_data['access_token'] . "\n";
			echo "Token Type: " . $response_data['token_type'] . "\n";
			echo "Expires In: " . $response_data['expires_in'] . " seconds\n";
			echo "Refresh Token: " . $response_data['refresh_token'] . "\n"; */


		} else {

			echo "Error: Unable to fetch access token.\n";
			echo "Response: " . $response . "\n";

		}

		die();

	}

});







