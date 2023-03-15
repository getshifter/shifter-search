<?php

/**
 * Plugin Name:       Shifter Search
 * Description:       Shifter Search enables an improved search experience driven by Algolia's powerful algorithms and insights. Easily customize forms and search results pages to add related items, improve engagement, and drive conversions with advanced features such as recommendations and filtering.
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Shifter
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       shifter-search
 *
 * @package           create-block
 */

class ShifterSearch
{
    const   OPTION_NAME = 'shifter_search';
    const   CSS_PATH = [
        'theme'          => 'assets/css/satellite-min.css',
    ];
    const   JS_PATH = [
        'client'         => 'assets/js/vendor/algoliasearch-lite.umd.js',
        'instant-search' => 'assets/js/vendor/instantsearch.production.min.js',
        'search'         => 'assets/js/algolia-search.js',
    ];
    const   VERSION = '1.0.0';

    private $options;
    private $hide_options_page = false;

    public function __construct()
    {
        $env = getenv();
        if (isset($env['ALGOLIA_APP_ID']) && isset($env['ALGOLIA_KEY']) && isset($env['ALGOLIA_INDEX'])) {
            $this->options = [
                'algoliaAppID'     => $env['ALGOLIA_APP_ID'],
                'algoliaSearchKey' => $env['ALGOLIA_KEY'],
                'algoliaIndexName' => $env['ALGOLIA_INDEX'],
            ];
            $this->hide_options_page = true;
        } else if (getenv('ALGOLIA_APP_ID') && getenv('ALGOLIA_KEY') && getenv('ALGOLIA_INDEX')) {
            $this->options = [
                'algoliaAppID'     => getenv('ALGOLIA_APP_ID'),
                'algoliaSearchKey' => getenv('ALGOLIA_KEY'),
                'algoliaIndexName' => getenv('ALGOLIA_INDEX'),
            ];
            $this->hide_options_page = true;
        } else if (defined('ALGOLIA_APP_ID') && defined('ALGOLIA_KEY') && defined('ALGOLIA_INDEX')) {
            $this->options = [
                'algoliaAppID'     => ALGOLIA_APP_ID,
                'algoliaSearchKey' => ALGOLIA_KEY,
                'algoliaIndexName' => ALGOLIA_INDEX,
            ];
            $this->hide_options_page = true;
        } else {
            $this->options = [
                'algoliaAppID'     => get_option('ShifterAlgoliaAppID'),
                'algoliaSearchKey' => get_option('ShifterSearchKey'),
                'algoliaIndexName' => get_option('ShifterAlgoliaIndexName'),
            ];
        }
    }

    public function add_hooks()
    {
        add_action('init', [$this, 'init']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        if (!$this->hide_options_page) {
            add_action('admin_init', [$this, 'admin_init']);
            add_action('admin_menu', [$this, 'admin_menu'], 102);
        }
    }

    public function init()
    {
        if (is_array($this->options)) {
            $this->register_block();
        }
    }

    public function admin_enqueue_scripts($hook_suffix)
    {
        if ('post.php' === $hook_suffix || 'post-new.php' === $hook_suffix) {
            $this->enqueue_scripts();
        }
    }

    public function enqueue_scripts()
    {
        $pluginURL = plugin_dir_url(__FILE__);
        wp_enqueue_style(
            'algolia-search-theme',
            $pluginURL . self::CSS_PATH['theme'],
            [],
            self::VERSION
        );
        wp_enqueue_script(
            'algolia-client',
            $pluginURL . self::JS_PATH['client'],
            [],
            self::VERSION,
            true
        );
        wp_enqueue_script(
            'algolia-instant-search',
            $pluginURL . self::JS_PATH['instant-search'],
            [],
            self::VERSION,
            true
        );
        wp_enqueue_script(
            'algolia-search-scrypt',
            $pluginURL . self::JS_PATH['search'],
            ['algolia-client', 'algolia-instant-search'],
            self::VERSION,
            true
        );

        $inline_js  = '';
        foreach ($this->options as $key => $value) {
            $inline_js .= sprintf("var %s = '%s';\n", $key, esc_attr($value));
        }
        wp_add_inline_script('algolia-search-scrypt', $inline_js, 'before');
    }

    /**
     * Registers the block using the metadata loaded from the `block.json` file.
     * Behind the scenes, it registers also all assets so they can be enqueued
     * through the block editor in the corresponding context.
     *
     * @see https://developer.wordpress.org/reference/functions/register_block_type/
     */
    private function register_block()
    {
        if (!function_exists('register_block_type')) {
            // Block editor is not available.
            return;
        }

        register_block_type(__DIR__ . '/build');
    }

    public function admin_init()
    {
        add_action('admin_menu', [$this, 'admin_menu']);
        register_setting(self::OPTION_NAME, 'ShifterAlgoliaAppID');
        register_setting(self::OPTION_NAME, 'ShifterSearchKey');
        register_setting(self::OPTION_NAME, 'ShifterAlgoliaIndexName');
    }

    public function admin_menu()
    {
        add_menu_page(
            'Shifter Search',
            'Shifter Search',
            'manage_options',
            'shifter-search',
            [$this, 'settings_page']
        );
    }

    public function settings_page()
    {
        $options = [
            "ShifterAlgoliaAppID"     => "App ID",
            "ShifterAlgoliaIndexName" => "Index Name",
            "ShifterSearchKey" => "Search Key",
        ];
?>


        <div class="wrap">

            <h1>Shifter</h1>

            <div class="card">
                <h2>Algolia Settings</h2>

                <form method="post" action="options.php">
                    <?php settings_fields(self::OPTION_NAME); ?>
                    <?php do_settings_sections(self::OPTION_NAME); ?>
                    <table class="form-table">
                        <?php foreach ($options as $key => $title) { ?>
                            <tr valign="top">
                                <th scope="row"><?php echo ucfirst($title); ?></th>
                                <td>
                                    <input type="text" name="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr(get_option($key)); ?>" />
                                </td>
                            </tr>
                        <?php } ?>
                    </table>

                    <?php submit_button(); ?>

                </form>
            </div>
        </div>
<?php
    }
}

$shifter_search = new ShifterSearch();
$shifter_search->add_hooks();
