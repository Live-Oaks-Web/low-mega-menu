<?php
/**
 * In-plugin Support / documentation screen.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Admin;

use LOW_MM\PostTypes\MegaMenuCPT;
use LOW_MM\Utils\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Renders Support docs under Mega Menus → Support.
 */
class SupportPage {

	/**
	 * Admin page slug.
	 */
	public const PAGE_SLUG = 'low-mm-support';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 25 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register Support submenu.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . MegaMenuCPT::POST_TYPE,
			__( 'Mega Menu Support', 'low-mega-menu' ),
			__( 'Support', 'low-mega-menu' ),
			Capabilities::MANAGE,
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Styles + tab script for the Support screen.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'mega_menu_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_register_style( 'low-mm-support', false, array(), LOW_MM_VERSION );
		wp_enqueue_style( 'low-mm-support' );
		wp_add_inline_style( 'low-mm-support', $this->css() );

		wp_register_script( 'low-mm-support', false, array(), LOW_MM_VERSION, true );
		wp_enqueue_script( 'low-mm-support' );
		wp_add_inline_script( 'low-mm-support', $this->js() );
	}

	/**
	 * Render the Support page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! Capabilities::can_manage() ) {
			return;
		}

		$tabs     = $this->tabs();
		$active   = $this->get_active_tab( array_keys( $tabs ) );
		$base_url = admin_url( 'edit.php?post_type=' . MegaMenuCPT::POST_TYPE . '&page=' . self::PAGE_SLUG );
		?>
		<div class="wrap low-mm-support">
			<header class="low-mm-support__hero">
				<p class="low-mm-support__eyebrow"><?php esc_html_e( 'LOW Mega Menu', 'low-mega-menu' ); ?></p>
				<h1><?php esc_html_e( 'Support & documentation', 'low-mega-menu' ); ?></h1>
				<p class="low-mm-support__lede">
					<?php esc_html_e( 'How the plugin fits together, plus a guide for every builder module. Administrators only.', 'low-mega-menu' ); ?>
				</p>
				<p class="low-mm-support__actions">
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . MegaMenuCPT::POST_TYPE ) ); ?>"><?php esc_html_e( 'All Mega Menus', 'low-mega-menu' ); ?></a>
					<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . MegaMenuCPT::POST_TYPE . '&page=' . SettingsPage::PAGE_SLUG ) ); ?>"><?php esc_html_e( 'Settings', 'low-mega-menu' ); ?></a>
					<a class="button" href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>"><?php esc_html_e( 'Site Navigation Menus', 'low-mega-menu' ); ?></a>
				</p>
			</header>

			<nav class="nav-tab-wrapper low-mm-support__tabs" aria-label="<?php esc_attr_e( 'Documentation sections', 'low-mega-menu' ); ?>">
				<?php foreach ( $tabs as $slug => $tab ) : ?>
					<a
						class="nav-tab <?php echo $active === $slug ? 'nav-tab-active' : ''; ?>"
						href="<?php echo esc_url( add_query_arg( 'tab', $slug, $base_url ) ); ?>"
						data-low-mm-tab="<?php echo esc_attr( $slug ); ?>"
					><?php echo esc_html( $tab['label'] ); ?></a>
				<?php endforeach; ?>
			</nav>

			<?php foreach ( $tabs as $slug => $tab ) : ?>
				<section
					class="low-mm-support__panel<?php echo $active === $slug ? ' is-active' : ''; ?>"
					id="low-mm-support-<?php echo esc_attr( $slug ); ?>"
					data-low-mm-panel="<?php echo esc_attr( $slug ); ?>"
					<?php echo $active === $slug ? '' : ' hidden'; ?>
				>
					<?php
					// Content is built from trusted plugin strings only.
					echo $tab['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</section>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * @param string[] $allowed Allowed tab slugs.
	 * @return string
	 */
	private function get_active_tab( array $allowed ): string {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( (string) $_GET['tab'] ) ) : 'overview'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return in_array( $tab, $allowed, true ) ? $tab : 'overview';
	}

	/**
	 * Tab definitions: label + pre-escaped HTML body.
	 *
	 * @return array<string, array{label:string,html:string}>
	 */
	private function tabs(): array {
		$modules = array(
			'link_list'  => array(
				'label' => __( 'Link List', 'low-mega-menu' ),
				'html'  => $this->module_doc(
					__( 'Link List', 'low-mega-menu' ),
					__( 'A vertical list of titled links with optional descriptions — ideal for “Resources”, “Programs”, or sitemap-style columns.', 'low-mega-menu' ),
					array(
						__( 'Add rows in the builder; each row has a title, URL, and optional description.', 'low-mega-menu' ),
						__( 'Descriptions can be rich HTML unless you enable plain-text-only.', 'low-mega-menu' ),
						__( 'Empty rows (no title and no URL) are skipped on the front end.', 'low-mega-menu' ),
					),
					array(
						__( 'Title + URL', 'low-mega-menu' ) => __( 'Required for a visible link. Title becomes the anchor text.', 'low-mega-menu' ),
						__( 'Description', 'low-mega-menu' ) => __( 'Optional supporting copy under the link.', 'low-mega-menu' ),
						__( 'Plain text only', 'low-mega-menu' ) => __( 'When enabled, description HTML is stripped to text.', 'low-mega-menu' ),
					),
					__( 'Keep descriptions short so columns stay scannable on desktop and in the mobile drawer.', 'low-mega-menu' )
				),
			),
			'post_query' => array(
				'label' => __( 'Post Query', 'low-mega-menu' ),
				'html'  => $this->module_doc(
					__( 'Post Query', 'low-mega-menu' ),
					__( 'Pulls a live list of posts (or another public post type) into the panel — great for “Latest news” or category highlights.', 'low-mega-menu' ),
					array(
						__( 'Choose post type, optional taxonomy + term, sort order, count, and offset.', 'low-mega-menu' ),
						__( 'Toggle thumbnail, date, category label, and excerpt per item.', 'low-mega-menu' ),
						__( 'Optional “View all” label + URL appear below the list.', 'low-mega-menu' ),
						__( 'Results are cached briefly and refresh when content or terms change.', 'low-mega-menu' ),
					),
					array(
						__( 'Post type', 'low-mega-menu' ) => __( 'Usually Post or Page; any public type works if registered.', 'low-mega-menu' ),
						__( 'Taxonomy / term', 'low-mega-menu' ) => __( 'Optional filter (e.g. category or custom taxonomy).', 'low-mega-menu' ),
						__( 'Sort', 'low-mega-menu' ) => __( 'Newest, oldest, sticky first, or title.', 'low-mega-menu' ),
						__( 'Count / offset', 'low-mega-menu' ) => __( 'How many items and where to start in the list.', 'low-mega-menu' ),
					),
					__( 'Prefer a specific category with a small count (3–5) so mega panels stay light and fast.', 'low-mega-menu' )
				),
			),
			'image'      => array(
				'label' => __( 'Image', 'low-mega-menu' ),
				'html'  => $this->module_doc(
					__( 'Image', 'low-mega-menu' ),
					__( 'Places a Media Library image in a column — logos, promo art, or a visual anchor next to links.', 'low-mega-menu' ),
					array(
						__( 'Pick an attachment from the Media Library.', 'low-mega-menu' ),
						__( 'Optional alt text overrides the media file alt when set.', 'low-mega-menu' ),
						__( 'Optional link URL wraps the image; can open in a new tab.', 'low-mega-menu' ),
					),
					array(
						__( 'Attachment', 'low-mega-menu' ) => __( 'Required image from the Media Library.', 'low-mega-menu' ),
						__( 'Alt text', 'low-mega-menu' ) => __( 'Accessibility text; falls back to the attachment alt.', 'low-mega-menu' ),
						__( 'Link URL', 'low-mega-menu' ) => __( 'Makes the image clickable when set.', 'low-mega-menu' ),
					),
					__( 'Use appropriately sized images; very large files slow the first paint of the panel.', 'low-mega-menu' )
				),
			),
			'cta'        => array(
				'label' => __( 'Call to Action', 'low-mega-menu' ),
				'html'  => $this->module_doc(
					__( 'Call to Action', 'low-mega-menu' ),
					__( 'A promotional card with heading, body, and button. Default card background uses Settings → Accent; you can override with a custom color or image.', 'low-mega-menu' ),
					array(
						__( 'Fill heading, body, and button label/URL.', 'low-mega-menu' ),
						__( 'Background can be a solid color or a Media Library image.', 'low-mega-menu' ),
						__( 'Optional colors for text, button text, and button background; otherwise theme/plugin palette applies.', 'low-mega-menu' ),
						__( 'Alignment: left, center, or right.', 'low-mega-menu' ),
					),
					array(
						__( 'Heading / body', 'low-mega-menu' ) => __( 'Main message; body may be rich text unless plain-text-only is on.', 'low-mega-menu' ),
						__( 'Button', 'low-mega-menu' ) => __( 'Label + URL for the primary CTA.', 'low-mega-menu' ),
						__( 'Background', 'low-mega-menu' ) => __( 'Color mode or image mode for the card surface.', 'low-mega-menu' ),
					),
					__( 'One strong CTA per panel usually works better than several competing buttons.', 'low-mega-menu' )
				),
			),
			'excerpt'    => array(
				'label' => __( 'Excerpt', 'low-mega-menu' ),
				'html'  => $this->module_doc(
					__( 'Page / Post Excerpt', 'low-mega-menu' ),
					__( 'Teases a single page or post with title, optional featured image, and excerpt — useful for featured content.', 'low-mega-menu' ),
					array(
						__( 'Select a source page or post.', 'low-mega-menu' ),
						__( 'Optionally override title and/or excerpt text.', 'low-mega-menu' ),
						__( 'Control image and excerpt visibility and excerpt length.', 'low-mega-menu' ),
						__( 'Rich text override can keep HTML in a custom excerpt when enabled.', 'low-mega-menu' ),
					),
					array(
						__( 'Source post', 'low-mega-menu' ) => __( 'The page/post to feature.', 'low-mega-menu' ),
						__( 'Custom title / excerpt', 'low-mega-menu' ) => __( 'Overrides the post title or excerpt when filled.', 'low-mega-menu' ),
						__( 'Excerpt length', 'low-mega-menu' ) => __( 'Word limit when using the automatic excerpt (0 = default).', 'low-mega-menu' ),
					),
					__( 'Link target uses the source permalink so visitors land on the full content.', 'low-mega-menu' )
				),
			),
			'scroll_to'  => array(
				'label' => __( 'Scroll To', 'low-mega-menu' ),
				'html'  => $this->module_doc(
					__( 'Scroll To', 'low-mega-menu' ),
					__( 'Links to a heading inside a page or post. On that same page it uses an in-page anchor; elsewhere it links to the permalink + heading.', 'low-mega-menu' ),
					array(
						__( 'Pick a source page/post, then choose a heading from the list (parsed from content).', 'low-mega-menu' ),
						__( 'Optional custom title overrides the heading text in the menu.', 'low-mega-menu' ),
						__( 'Optional supporting content can sit under the link.', 'low-mega-menu' ),
						__( 'Front-end JS scrolls smoothly and accounts for sticky headers / admin bar.', 'low-mega-menu' ),
					),
					array(
						__( 'Source + heading', 'low-mega-menu' ) => __( 'Required pair: which document and which heading index.', 'low-mega-menu' ),
						__( 'Title', 'low-mega-menu' ) => __( 'Label shown in the panel; defaults to the heading text.', 'low-mega-menu' ),
						__( 'Content', 'low-mega-menu' ) => __( 'Optional blurb under the link.', 'low-mega-menu' ),
					),
					__( 'If headings change after you save, re-open the module and reselect the heading so the index stays correct.', 'low-mega-menu' )
				),
			),
			'code'       => array(
				'label' => __( 'Code', 'low-mega-menu' ),
				'html'  => $this->module_doc(
					__( 'Code / Shortcode', 'low-mega-menu' ),
					__( 'Advanced escape hatch for raw HTML or shortcodes. Restricted to Administrators; treat it carefully.', 'low-mega-menu' ),
					array(
						__( 'Paste HTML and/or shortcodes into the content field.', 'low-mega-menu' ),
						__( 'Shortcode execution follows Settings → Safety (global) plus per-module inherit / on / off.', 'low-mega-menu' ),
						__( 'When execution is off, content is shown as escaped plain text.', 'low-mega-menu' ),
					),
					array(
						__( 'Content', 'low-mega-menu' ) => __( 'HTML and/or shortcode markup.', 'low-mega-menu' ),
						__( 'Shortcode execution', 'low-mega-menu' ) => __( 'inherit (use global setting), on, or off for this module only.', 'low-mega-menu' ),
					),
					__( 'Prefer built-in modules when possible. Only enable shortcodes when you trust every shortcode on the site.', 'low-mega-menu' )
				),
			),
		);

		return array_merge(
			array(
				'overview' => array(
					'label' => __( 'Overview', 'low-mega-menu' ),
					'html'  => $this->overview_html(),
				),
			),
			$modules
		);
	}

	/**
	 * Overview / getting started HTML.
	 *
	 * @return string
	 */
	private function overview_html(): string {
		$steps = array(
			array(
				'title' => __( 'Create a mega menu', 'low-mega-menu' ),
				'body'  => __( 'Go to Mega Menus → Add New, name it, then open the Builder to add columns and modules.', 'low-mega-menu' ),
			),
			array(
				'title' => __( 'Build the panel', 'low-mega-menu' ),
				'body'  => __( 'Choose a column layout, set panel width/animation, and drop modules into columns. Drag to reorder.', 'low-mega-menu' ),
			),
			array(
				'title' => __( 'Attach to navigation', 'low-mega-menu' ),
				'body'  => __( 'Open Appearance → Menus (or Mega Menus → Site Navigation Menus). Edit a top-level item and choose “Attach Mega Menu”. Save the menu.', 'low-mega-menu' ),
			),
			array(
				'title' => __( 'Tune Settings', 'low-mega-menu' ),
				'body'  => __( 'General: breakpoint, search, Divi override, accessibility, shortcode safety. Styling: panel max width, colors, custom CSS.', 'low-mega-menu' ),
			),
		);

		ob_start();
		?>
		<div class="low-mm-support__grid">
			<article class="low-mm-support__card low-mm-support__card--wide">
				<h2><?php esc_html_e( 'Getting started', 'low-mega-menu' ); ?></h2>
				<ol class="low-mm-support__steps">
					<?php foreach ( $steps as $i => $step ) : ?>
						<li>
							<span class="low-mm-support__step-num" aria-hidden="true"><?php echo esc_html( (string) ( $i + 1 ) ); ?></span>
							<div>
								<strong><?php echo esc_html( $step['title'] ); ?></strong>
								<p><?php echo esc_html( $step['body'] ); ?></p>
							</div>
						</li>
					<?php endforeach; ?>
				</ol>
			</article>

			<article class="low-mm-support__card">
				<h2><?php esc_html_e( 'Two different “menus”', 'low-mega-menu' ); ?></h2>
				<dl class="low-mm-support__defs">
					<div>
						<dt><?php esc_html_e( 'Mega Menus', 'low-mega-menu' ); ?></dt>
						<dd><?php esc_html_e( 'Panel content only — columns and modules you build here.', 'low-mega-menu' ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Site navigation menus', 'low-mega-menu' ); ?></dt>
						<dd><?php esc_html_e( 'WordPress link lists (Home, About…). Attach a mega menu panel to an item in that list.', 'low-mega-menu' ); ?></dd>
					</div>
				</dl>
			</article>

			<article class="low-mm-support__card">
				<h2><?php esc_html_e( 'Desktop & mobile', 'low-mega-menu' ); ?></h2>
				<p><?php esc_html_e( 'Below the mobile breakpoint (default 1024px), navigation uses a drawer with drill-down into panels. Above it, panels open as full-width mega dropdowns under the header.', 'low-mega-menu' ); ?></p>
				<p><?php esc_html_e( 'Search (when enabled) shows results in a mega panel on desktop and inside the drawer on mobile.', 'low-mega-menu' ); ?></p>
			</article>

			<article class="low-mm-support__card">
				<h2><?php esc_html_e( 'Divi & themes', 'low-mega-menu' ); ?></h2>
				<p><?php esc_html_e( 'On Divi, Settings → General can replace Divi’s primary navigation with the plugin shell so mega panels, search, and the mobile drawer work cleanly. Logo and top bar stay Divi’s.', 'low-mega-menu' ); ?></p>
				<p><?php esc_html_e( 'Styling colors and custom CSS apply to panels, search, and drawer chrome — not theme top-level nav links.', 'low-mega-menu' ); ?></p>
			</article>

			<article class="low-mm-support__card low-mm-support__card--wide">
				<h2><?php esc_html_e( 'Who can manage this', 'low-mega-menu' ); ?></h2>
				<p><?php esc_html_e( 'Creating and editing mega menus, the builder, settings, support docs, and attaching panels to nav items require an Administrator (manage_options). Mega menu content is not exposed in the REST API, sitemaps, or public queries.', 'low-mega-menu' ); ?></p>
			</article>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Shared module documentation layout.
	 *
	 * @param string               $title   Module title.
	 * @param string               $summary Short summary.
	 * @param string[]             $how     How it works bullets.
	 * @param array<string,string> $fields  Setting => description.
	 * @param string               $tip     Tip text.
	 * @return string
	 */
	private function module_doc( string $title, string $summary, array $how, array $fields, string $tip ): string {
		ob_start();
		?>
		<div class="low-mm-support__grid">
			<article class="low-mm-support__card low-mm-support__card--wide">
				<p class="low-mm-support__module-kicker"><?php esc_html_e( 'Builder module', 'low-mega-menu' ); ?></p>
				<h2><?php echo esc_html( $title ); ?></h2>
				<p class="low-mm-support__summary"><?php echo esc_html( $summary ); ?></p>
			</article>

			<article class="low-mm-support__card">
				<h3><?php esc_html_e( 'How it works', 'low-mega-menu' ); ?></h3>
				<ul class="low-mm-support__list">
					<?php foreach ( $how as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
			</article>

			<article class="low-mm-support__card">
				<h3><?php esc_html_e( 'Key settings', 'low-mega-menu' ); ?></h3>
				<dl class="low-mm-support__defs">
					<?php foreach ( $fields as $label => $desc ) : ?>
						<div>
							<dt><?php echo esc_html( $label ); ?></dt>
							<dd><?php echo esc_html( $desc ); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
			</article>

			<article class="low-mm-support__card low-mm-support__card--tip low-mm-support__card--wide">
				<h3><?php esc_html_e( 'Tip', 'low-mega-menu' ); ?></h3>
				<p><?php echo esc_html( $tip ); ?></p>
			</article>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Support screen CSS.
	 *
	 * @return string
	 */
	private function css(): string {
		return <<<'CSS'
.low-mm-support { max-width: 960px; }
.low-mm-support__hero {
	margin: 1.25rem 0 1.5rem;
	padding: 1.5rem 1.75rem;
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 4px;
}
.low-mm-support__eyebrow {
	margin: 0 0 0.35rem;
	font-size: 11px;
	font-weight: 600;
	letter-spacing: 0.08em;
	text-transform: uppercase;
	color: #646970;
}
.low-mm-support__hero h1 { margin: 0 0 0.5rem; padding: 0; font-size: 1.75rem; line-height: 1.25; }
.low-mm-support__lede { margin: 0 0 1rem; max-width: 40rem; color: #50575e; font-size: 14px; line-height: 1.55; }
.low-mm-support__actions { margin: 0; display: flex; flex-wrap: wrap; gap: 0.5rem; }
.low-mm-support__tabs { margin: 0 0 1.25rem; }
.low-mm-support__panel[hidden],
.low-mm-support__panel:not(.is-active) { display: none !important; }
.low-mm-support__grid {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 1rem;
}
.low-mm-support__card {
	margin: 0;
	padding: 1.15rem 1.25rem;
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 4px;
}
.low-mm-support__card--wide { grid-column: 1 / -1; }
.low-mm-support__card h2,
.low-mm-support__card h3 { margin: 0 0 0.65rem; padding: 0; font-size: 1.05rem; }
.low-mm-support__card p { margin: 0 0 0.75rem; color: #3c434a; line-height: 1.55; }
.low-mm-support__card p:last-child { margin-bottom: 0; }
.low-mm-support__module-kicker {
	margin: 0 0 0.25rem !important;
	font-size: 11px;
	font-weight: 600;
	letter-spacing: 0.06em;
	text-transform: uppercase;
	color: #646970;
}
.low-mm-support__summary { font-size: 15px; }
.low-mm-support__steps { list-style: none; margin: 0; padding: 0; display: grid; gap: 1rem; }
.low-mm-support__steps li { display: flex; gap: 0.85rem; align-items: flex-start; }
.low-mm-support__steps p { margin: 0.25rem 0 0; }
.low-mm-support__step-num {
	flex: 0 0 auto;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 1.75rem;
	height: 1.75rem;
	border-radius: 999px;
	background: #1d2327;
	color: #fff;
	font-size: 12px;
	font-weight: 600;
}
.low-mm-support__list { margin: 0; padding-left: 1.2rem; color: #3c434a; line-height: 1.55; }
.low-mm-support__list li { margin-bottom: 0.4rem; }
.low-mm-support__list li:last-child { margin-bottom: 0; }
.low-mm-support__defs { margin: 0; display: grid; gap: 0.85rem; }
.low-mm-support__defs dt { font-weight: 600; color: #1d2327; }
.low-mm-support__defs dd { margin: 0.2rem 0 0; color: #50575e; line-height: 1.5; }
.low-mm-support__card--tip {
	border-color: #c3c4c7;
	border-left: 3px solid #2271b1;
	background: #f6f7f7;
}
@media (max-width: 782px) {
	.low-mm-support__grid { grid-template-columns: 1fr; }
	.low-mm-support__card--wide { grid-column: auto; }
}
CSS;
	}

	/**
	 * Lightweight tab switching without full reload (URL still updates).
	 *
	 * @return string
	 */
	private function js(): string {
		return <<<'JS'
(function () {
	var root = document.querySelector('.low-mm-support');
	if (!root) return;
	var tabs = root.querySelectorAll('[data-low-mm-tab]');
	var panels = root.querySelectorAll('[data-low-mm-panel]');
	tabs.forEach(function (tab) {
		tab.addEventListener('click', function (event) {
			var slug = tab.getAttribute('data-low-mm-tab');
			if (!slug) return;
			event.preventDefault();
			tabs.forEach(function (t) {
				t.classList.toggle('nav-tab-active', t === tab);
			});
			panels.forEach(function (panel) {
				var on = panel.getAttribute('data-low-mm-panel') === slug;
				panel.classList.toggle('is-active', on);
				if (on) {
					panel.removeAttribute('hidden');
				} else {
					panel.setAttribute('hidden', '');
				}
			});
			if (window.history && window.history.replaceState) {
				var url = new URL(window.location.href);
				url.searchParams.set('tab', slug);
				window.history.replaceState({}, '', url.toString());
			}
		});
	});
})();
JS;
	}
}
