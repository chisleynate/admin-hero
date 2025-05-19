<?php
if (!defined('ABSPATH')) {
    exit;
}

class Admin_Hero_Feature_Loader {
    private $features = [];

    /**
     * Load feature definitions from Free + any additional directories.
     *
     * @param string $features_dir Absolute path to Free plugin’s features folder.
     */
    public function load_features($features_dir) {
        // 1) Gather directories to scan
        $dirs = [];

        // Always include Free’s own features folder if it exists
        if (is_dir($features_dir)) {
            $dirs[] = rtrim($features_dir, '/\\') . '/';
        }

        // Include any extra feature directories (e.g. Pro) registered via filter
        $extra = apply_filters('admin_hero_feature_dirs', []);
        if (!is_array($extra)) {
            $extra = [ $extra ];
        }
        foreach ($extra as $d) {
            if (is_string($d) && is_dir($d)) {
                $dirs[] = rtrim($d, '/\\') . '/';
            }
        }

        // If no valid directories, nothing to load
        if (empty($dirs)) {
            return;
        }

        // 2) Include every PHP file in each feature subdirectory
        foreach ($dirs as $dir) {
            foreach (glob($dir . '*/{index.php,*.php}', GLOB_BRACE) as $feature_file) {
                include_once $feature_file;
            }
        }

        // 3) Collect all features registered via the filter
        $this->features = apply_filters('admin_hero_features', []);

        // 4) Validate & initialize each feature
        foreach ($this->features as $index => &$feature) {
            if (! $this->validate_feature($feature)) {
                unset($this->features[$index]);
                continue;
            }
            $this->initialize_feature($feature, $index);
        }
        unset($feature);

        // 5) Sort by priority
        usort($this->features, function($a, $b) {
            return ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50);
        });
    }

    /**
     * Ensure a feature has the required structure.
     */
    private function validate_feature($feature) {
        if (!is_array($feature) || empty($feature['id']) || empty($feature['name'])) {
            return false;
        }
        $feature['id']                = sanitize_key($feature['id']);
        $feature['name']              = sanitize_text_field($feature['name']);
        $feature['priority']          = absint($feature['priority'] ?? 50);
        $feature['settings']          = (array) ($feature['settings'] ?? []);
        $feature['sanitize_callback'] = $feature['sanitize_callback'] ?? 'sanitize_text_field';
        return true;
    }

    /**
     * Initialize a single feature: set enabled state, enqueue assets, register settings UI.
     */
    private function initialize_feature(&$feature, $index) {
        $user_id = get_current_user_id();
        $saved   = get_user_meta($user_id, "admin_hero_feature_{$feature['id']}", true);
        $feature['enabled'] = ($saved !== '') ? (bool) $saved : false;
        $this->features[$index] = $feature;

        // Enqueue callbacks if provided
        if (!empty($feature['enqueue_callback']) && is_callable($feature['enqueue_callback'])) {
            add_action('admin_enqueue_scripts', $feature['enqueue_callback']);
            add_action('wp_enqueue_scripts',  $feature['enqueue_callback']);
        } else {
            // Enqueue CSS if it exists
            $css = ADMIN_HERO_DIR . "features/{$feature['id']}/assets/{$feature['id']}.css";
            if (file_exists($css)) {
                add_action('admin_enqueue_scripts', function() use ($feature) {
                    wp_enqueue_style(
                        "admin-hero-{$feature['id']}",
                        ADMIN_HERO_URL . "features/{$feature['id']}/assets/{$feature['id']}.css",
                        [], ADMIN_HERO_VERSION
                    );
                });
            }
            // Enqueue JS if it exists
            $js = ADMIN_HERO_DIR . "features/{$feature['id']}/assets/{$feature['id']}.js";
            if (file_exists($js)) {
                add_action('admin_enqueue_scripts', function() use ($feature) {
                    wp_enqueue_script(
                        "admin-hero-{$feature['id']}",
                        ADMIN_HERO_URL . "features/{$feature['id']}/assets/{$feature['id']}.js",
                        ['admin-hero-js'], ADMIN_HERO_VERSION, true
                    );
                });
            }
        }

        // Register settings UI
        if (!empty($feature['settings_ui_callback']) && is_callable($feature['settings_ui_callback'])) {
            add_action('admin_hero_settings_ui', $feature['settings_ui_callback']);
        } else {
            $tmpl = ADMIN_HERO_DIR . "features/{$feature['id']}/templates/settings.php";
            if (file_exists($tmpl)) {
                add_action('admin_hero_settings_ui', function() use ($tmpl) {
                    include $tmpl;
                });
            }
        }
    }

    /**
     * Return all loaded features.
     *
     * @return array
     */
    public function get_features() {
        return $this->features;
    }

    /**
     * Return a single feature by ID or null.
     */
    public function get_feature($feature_id) {
        foreach ($this->features as $f) {
            if ($f['id'] === $feature_id) {
                return $f;
            }
        }
        return null;
    }
}
