<?php
/**
 * Code module render template.
 *
 * @package LOW_MM
 * @var string $content
 */

defined( 'ABSPATH' ) || exit;
?>
<pre class="low-mm-module low-mm-code"><code><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped before template. ?></code></pre>
