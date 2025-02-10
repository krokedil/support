<?php
namespace Krokedil\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<table class="wc_status_table widefat" cellspacing="0">
	<thead>
	<tr>
		<th colspan="6" data-export-label="Krokedil Support Request Log">
			<h2><?php echo esc_html( $name ); ?></h2>
		</th>
	</tr>
	<?php
	$report = get_option( 'krokedil_support_' . $slug, array() );
	if ( ! empty( $report ) ) {
		$report = array_reverse( json_decode( $report, true ) );
		?>
			<tr>
				<td ><strong><?php esc_html_e( 'Timestamp', 'krokedil-support' ); ?></strong></td>
				<td class="help"></td>
				<td ><strong><?php esc_html_e( 'Code', 'krokedil-support' ); ?></strong></td>
				<td ><strong><?php esc_html_e( 'Message', 'krokedil-support' ); ?></strong></td>
				<td ><strong><?php esc_html_e( 'Details', 'krokedil-support' ); ?></strong></td>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $report as $log ) {
			$timestamp = $log['timestamp'];
			$code      = $log['response']['code'];
			$message   = trim( wp_json_encode( $log['response']['message'] ), '"' );

			$extra = $log['response']['extra'];
			$extra = empty( $extra ) ? '' : wp_json_encode( $extra );

			?>
			<tr>
				<td><?php echo esc_html( $timestamp ); ?></td>
				<td class="help"></td>
				<td><?php echo esc_html( $code ); ?></td>
				<td><?php echo esc_html( trim( $message, '"' ) ); ?></td>
				<td><?php echo esc_html( trim( $extra, '"' ) ); ?></td>
			</tr>
			<?php
		}
	} else {
		?>
		</thead>
		<tbody>
			<tr>
				<td colspan="6" data-export-label="No errors"><?php esc_html_e( 'No error logs', 'krokedil-support' ); ?></td>
			</tr>
		<?php
	}
	?>
	</tbody>
</table>