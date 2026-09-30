<?php
/*
 * This is a plain php file, with very simple funcitonality that tests api response.
 * 
 */
// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Show the PHP error when this API connection test fails.
ini_set("display_errors","on");

echo "The code run here is: file_get_contents(\"https://api.unlimited-elements.com\"); <br><br> Response: <br><br>";


$response = file_get_contents("https://api.unlimited-elements.com");

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standalone PHP file. WordPress is not loaded, so esc_html() is not available.
echo "<pre>" . htmlspecialchars($response, ENT_QUOTES, "UTF-8") . "</pre>";
