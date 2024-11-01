<div class="wrap tot-settings-page tot-debug-error">

	<h1>Error Details</h1>

	<?php
		$stored_error = get_transient( 'tot_error_' . $_GET['tot-error'] );

		if($stored_error === false) {
	?>

			<p>Error has expired.</p>

	<?php
		}else {
			$decoded_error = json_decode($stored_error);
			echo '<pre>';
			echo htmlspecialchars(print_r($decoded_error, true));
			echo '</pre>';
		}
	?>

</div>