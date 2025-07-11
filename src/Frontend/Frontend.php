<?php
/**
 * Frontend Class
 *
 * @package SmartThemeSwitcher
 * @since 1.0.0
 */

namespace SmartThemeSwitcher\Frontend;

use SmartThemeSwitcher\ThemeSwitcher;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend Class.
 *
 * Handles frontend-related functionality.
 *
 * @since 1.0.0
 */
class Frontend {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Initialize hooks.
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init_hooks() {
		$theme_switcher = new ThemeSwitcher();
		$settings = get_option( 'smart_theme_switcher_settings', array() );
		$preview_enabled = isset( $settings['enable_preview'] ) && $settings['enable_preview'] === 'yes';
		$preview_theme = $theme_switcher->get_preview_theme();

		if ( $preview_enabled && $theme_switcher->can_user_preview() && $preview_theme ) {
			// Add preview banner.
			add_action( 'wp_body_open', array( $this, 'add_preview_banner' ) );
			
			// Add compatibility notices.
			add_action( 'wp_body_open', array( $this, 'add_compatibility_notice' ) );
		}
	}

	/**
	 * Add preview banner to the frontend.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_preview_banner() {
		// Get current preview theme.
		$theme_switcher = new ThemeSwitcher();
		$preview_theme_slug = $theme_switcher->get_preview_theme();
		
		if ( ! $preview_theme_slug ) {
			return;
		}

		// Get theme info.
		$preview_theme = wp_get_theme( $preview_theme_slug );
		
		// Get all themes for dropdown.
		$themes = $theme_switcher->get_available_themes();
		
		// Get current URL without query parameters.
		$current_url = remove_query_arg( $theme_switcher->get_query_param_name() ); 
		?>
		<div id="sts-preview-banner" class="sts-preview-banner frontend">
			<div class="sts-preview-banner-inner">
				<div class="sts-preview-info">
					<span class="sts-preview-label"><?php esc_html_e( 'Preview Mode:', 'smart-theme-switcher' ); ?></span>
					<span class="sts-preview-theme"><?php echo esc_html( $preview_theme->get( 'Name' ) ); ?></span>
				</div>
				
				<div class="sts-preview-actions">
					<div class="sts-theme-select-wrapper">
						<label for="sts-theme-select" class="screen-reader-text"><?php esc_html_e( 'Select Theme', 'smart-theme-switcher' ); ?></label>
						<select id="sts-theme-select" class="sts-theme-select">
							<option value=""><?php esc_html_e( 'Switch Theme...', 'smart-theme-switcher' ); ?></option>
							<?php foreach ( $themes as $slug => $name ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $slug, $preview_theme_slug ); ?>>
									<?php echo esc_html( $name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					
					<a href="<?php echo esc_url( $current_url ); ?>" class="sts-exit-preview-button button button-secondary">
						<?php esc_html_e( 'Exit Preview', 'smart-theme-switcher' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Add compatibility notice for block/classic theme mismatches.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_compatibility_notice() {
		// Get current preview theme.
		$theme_switcher = new ThemeSwitcher();
		$preview_theme_slug = $theme_switcher->get_preview_theme();
		
		if ( ! $preview_theme_slug ) {
			return;
		}

		// Get preview theme and active theme.
		$preview_theme = wp_get_theme( $preview_theme_slug );
		$active_theme = wp_get_theme();
		
		// Check if one is block-based and the other is classic.
		$preview_is_block = $this->is_block_theme( $preview_theme_slug );
		$active_is_block = $this->is_block_theme( $active_theme->get_stylesheet() );
		
		// Only show notice if there's a mismatch.
		if ( $preview_is_block !== $active_is_block ) {
			$message = $preview_is_block
				? __( 'You are previewing a block theme while your active theme is classic. Some layouts may appear differently.', 'smart-theme-switcher' )
				: __( 'You are previewing a classic theme while your active theme is block-based. Some layouts may appear differently.', 'smart-theme-switcher' );
			
			// Output notice.
			?>
			<div id="sts-compatibility-notice" class="sts-compatibility-notice">
				<div class="sts-notice-inner">
					<span class="dashicons dashicons-info"></span>
					<p><?php echo esc_html( $message ); ?></p>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Check if a theme is block-based.
	 *
	 * @since 1.0.0
	 * @param string $theme_slug Theme slug.
	 * @return bool
	 */
	private function is_block_theme( $theme_slug ) {
		// Function exists in WP 5.9+.
		if ( function_exists( 'wp_is_block_theme' ) ) {
			// Temporarily switch themes to check.
			$current_theme = wp_get_theme();
			switch_theme( $theme_slug );
			$is_block = wp_is_block_theme();
			switch_theme( $current_theme->get_stylesheet() );
			
			return $is_block;
		}
		
		// Fallback for older WP versions.
		$theme = wp_get_theme( $theme_slug );
		return is_readable( $theme->get_file_path( 'templates/index.html' ) );
	}
}