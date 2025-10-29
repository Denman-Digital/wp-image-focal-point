<?php

/**
 * Handle Updates
 *
 * @since   2.0
 * @package image-focal-point
 */

namespace Image_Focal_Point;

use function Denman_Utils\v2\mini_markdown_parse;

class Image_Focal_Point_Update
{
	private static $instance = null;

	private $repo_version_branch = "main";

	private $remote_plugin_endpoint_base = "";

	private $did_fetch_remote_data = false;

	private function __construct()
	{
		$this->remote_plugin_endpoint_base = "https://raw.githubusercontent.com/Denman-Digital/image-focal-point/{$this->repo_version_branch}/";
		add_filter("update_plugins_image-focal-point", [$this, "update_plugins_image_focal_point_data"], 10, 1);
		add_filter("pre_set_site_transient_update_plugins", [$this, "modify_plugins_transient"], 10, 1);
		add_filter("plugins_api", [$this, "modify_plugin_details"], 99999, 3);
		add_filter("upgrader_post_install",  [$this, "post_install"], 10, 3);
		add_action('admin_head', [$this, "custom_admin_styles"]);
	}

	public static function instance(): Image_Focal_Point_Update
	{
		if (self::$instance == null) {
			self::$instance = new Image_Focal_Point_Update();
		}
		return self::$instance;
	}

	public function get_remote_plugin_data(): array
	{
		$remote_plugin_data = get_plugin_data($this->remote_plugin_endpoint_base . IFP_PLUGIN_FILE, true, false);
		if (!$remote_plugin_data) return [];
		return [
			"slug" => "image-focal-point",
			"plugin" => IFP_PLUGIN_BASENAME,
			"name" => $remote_plugin_data["Name"],
			"version" => $remote_plugin_data["Version"],
			"new_version" => $remote_plugin_data["Version"],
			"url" => $remote_plugin_data["PluginURI"],
			"package" => "https://github.com/Denman-Digital/image-focal-point/archive/{$this->repo_version_branch}.zip",
			"requires" => $remote_plugin_data["RequiresWP"],
			"require_php" => $remote_plugin_data["RequiresPHP"],
			"author" => $remote_plugin_data["Author"],
			"icons" => [
				"2x" => $this->remote_plugin_endpoint_base . "assets/icon-256x256.png",
				"1x" => $this->remote_plugin_endpoint_base . "assets/icon-128x128.png",
				"svg" => $this->remote_plugin_endpoint_base . "assets/icon.svg",
			],
			"banners" => [
				"high" => $this->remote_plugin_endpoint_base . "assets/banner-1544x500.jpg",
				"low" => $this->remote_plugin_endpoint_base . "assets/banner-772x250.jpg",
			]
		];
	}

		/**
	 * Get and parse changelog.md
	 * @since 2.2.8
	 * @return string
	 */
	public function get_remote_changelog(): string
	{
		// file_get_contents( $file, false, null, 0,
		$remote_changelog = file_get_contents($this->remote_plugin_endpoint_base . "changelog.md", false, null, 0, 8 * KB_IN_BYTES);
		if (!$remote_changelog) return $this->remote_plugin_endpoint_base . "changelog.md";

		$remote_changelog = mini_markdown_parse($remote_changelog);

		return sprintf(
			"<div class='denman-plugin-changelog'>%s</div>",
			$remote_changelog
		);
	}


	public function update_plugins_image_focal_point_data($value)
	{
		if ($data = $this->get_remote_plugin_data()) {
			$this->did_fetch_remote_data = true;
			$value = $data;
		}
		return $value;
	}

	public function modify_plugins_transient($transient)
	{
		// bail early if no response (error)
		if (!isset($transient->response)) {
			return $transient;
		}

		if (
			isset(
				$transient->checked,
				$transient->checked[IFP_PLUGIN_BASENAME],
				$transient->no_update[IFP_PLUGIN_BASENAME]
			)
			&& !$this->did_fetch_remote_data
		) {
			$remote_data = (object) $this->get_remote_plugin_data();
			$local_data = get_plugin_data(IFP_PLUGIN_PATH . "plugin.php", true, false);

			if (version_compare($remote_data->new_version, $local_data['Version'], '>')) {
				$transient->response[IFP_PLUGIN_BASENAME] = $remote_data;
				unset($transient->no_update[IFP_PLUGIN_BASENAME]);
			}
		}

		return $transient;
	}

	function modify_plugin_details($result, $action = null, $args = null)
	{
		if (!isset($args->slug) || $args->slug !== "image-focal-point" || $action !== 'plugin_information') {
			return $result;
		}
		$result = $this->get_remote_plugin_data();
		if (!is_array($result)) {
			return $result;
		}

		$local_data = get_plugin_data(IFP_PLUGIN_PATH . "plugin.php", true, false);

		$result = (object) $result;

		$changelog = sprintf(
			'<a href="%s">%s</a>',
			esc_url("https://github.com/Denman-Digital/image-focal-point/releases"),
			__("Full list of releases", "image-focal-point")
		);

		if ($remote_changelog = $this->get_remote_changelog()) {
			$changelog .= $remote_changelog;
		}

		$sections = [
			'description' => $local_data["Description"],
			'installation' => sprintf(
				// translators: %s: link URL
				__('<a href="%s" download>Download the latest release from GitHub</a>, and either install it through the Add New Plugins page in the WordPress admin, or manually extract the contents into your WordPress installations plugin folder.', "img-focal-point"),
				esc_url("https://github.com/Denman-Digital/image-focal-point/archive/{$this->repo_version_branch}.zip")
			),
			'changelog' => $changelog,
		];
		$result->sections = $sections;
		return $result;
	}


	/**
	 * Finalize install
	 * @param bool $_response
	 * @param array $_hook_extra
	 * @param array $result
	 * @return bool
	 */
	public function post_install(bool $response, array $_hook_extra, array $result): bool
	{
		// Remember if our plugin was previously activated
		$wasActivated = is_plugin_active(IFP_PLUGIN_BASENAME);

		if (isset($_hook_extra["plugin"]) && $_hook_extra["plugin"] === IFP_PLUGIN_BASENAME) {
			global $wp_filesystem;
			$pluginFolder = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname(IFP_PLUGIN_BASENAME);
			$wp_filesystem->move($result['destination'], $pluginFolder);
			$result['destination'] = $pluginFolder;

			if ($wasActivated) {
				$activate = activate_plugin(IFP_PLUGIN_BASENAME);
			}
		}
		return $response;
	}

		/**
	 * Admin styles.
	 */
	function custom_admin_styles()
	{
		?>
		<style>
			.denman-plugin-changelog {
				float: left;
				width: 100%;
				margin-bottom: 66px;
			}
			.denman-plugin-changelog h1,
			.denman-plugin-changelog h2,
			.denman-plugin-changelog h3,
			.denman-plugin-changelog h4,
			.denman-plugin-changelog h5,
			.denman-plugin-changelog h6 {
				margin: 0.25em 0;
				clear: none;
			}
		</style>
		<?php
	}
}

Image_Focal_Point_Update::instance();
