<?php

/*
Plugin Name: Impozycja
Description: Impozycja  plików PDF z integracją NBDesigner
Version: 1.0
Author: Krzysztof Piątkowski
License: GPLv2
*/

namespace NBDImposer;

if (!defined('ABSPATH')) {
    wp_die("No direct access!");
}

include __DIR__ . '/vendor/autoload.php';

if (!class_exists('NBDImposer\Plugin')) {
    
    class Plugin
    {
        const VERSION = "1.0.0";
        const NAME = "nbdesigner_impose";
        
        /**
         * @var string
         */
        private static $path = "";
        
        /**
         * @var string
         */
        private static $url = "";
        
        public static function run()
        {
            self::$url = plugins_url('', __FILE__);
            self::$path = WP_PLUGIN_DIR . '/' . Plugin::NAME;
            
            PresetPostType::getInstance()->init();
            NBDIntegrator::getInstance()->init();
        }
        
        public static function path()
        {
            return self::$path;
        }
        
        public static function url()
        {
            return self::$url;
        }

    }
    
    Plugin::run();
    
}