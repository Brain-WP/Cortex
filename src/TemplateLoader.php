<?php namespace Brain\Cortex;

/**
 * Concrete implementation of TemplateLoaderInterface.
 *
 * Use a filter on `template_redirect` to make WordPress selected the wanted template.
 * Provide helper methods to load template files.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
class TemplateLoader implements TemplateLoaderInterface, HooksableInterface {

    use Hooksable;

    private $hooks;
    private $directories;
    private $function = 'require_once';
    private $template;
    private $unfiltered = FALSE;

    public function __construct( \Brain\Hooks $hooks ) {
        $this->hooks = $hooks;
    }

    /**
     * Method used to add a 'template_redirect' action hook to loadTemplate() method that will load
     * the given template.
     *
     * @param mixed $template   The template(s) to load
     * @param bool $unfiltered  If true found template is not filtered using core hooks
     * @return void
     * @access public
     */
    public function load( $template = NULL, $unfiltered = FALSE ) {
        $this->unfiltered = $unfiltered;
        if ( ! did_action( 'template_redirect' ) && ! empty( $template ) ) {
            $this->template = $template;
            $this->getHooks()->addAction(
                'cortex.template_load', 'template_redirect', [ $this, 'loadTemplate' ], 50
            )->runOnce();
            return TRUE;
        }
    }

    /**
     * Run on to 'template_redirect' include the saved template
     *
     * @return boolean
     */
    public function loadTemplate() {
        if ( current_filter() !== 'template_redirect' ) {
            return FALSE;
        }
        if ( $this->preLoad() ) {
            return $this->loadFile( $this->getTemplate(), TRUE );
        }
    }

    /**
     * Load a file from setted directories.
     *
     * @param string $template
     * @param boolaed $main_template
     * @return boolean
     */
    public function loadFile( $template = '', $main_template = FALSE ) {
        if ( ( ! is_string( $template ) && ! is_array( $template ) ) || empty( $template ) ) {
            return FALSE;
        }
        $this->setDirectories( $main_template );
        $path = $this->getTemplateFile( $template );
        if ( $main_template ) {
            $type = \Brain\getQueryType();
            if ( $type === 'attachment' ) {
                remove_filter( 'the_content', 'prepend_attachment' );
            } elseif ( empty( $type ) ) {
                $type = 'index';
            }
            if ( ! $this->unfiltered ) {
                $path = $this->getHooks()->filter(
                    'template_include', $this->getHooks()->filter( "{$type}_template", $path )
                );
            }
        }
        if ( empty( $path ) ) {
            if ( $main_template ) {
                return $this->doneAndExit( 'no_template_to_load' );
            }
            return FALSE;
        }
        $function = $this->getFunction();
        if ( is_callable( $function ) ) {
            $function( $path );
        } elseif ( is_string( $function ) ) {
            $function = 'do' . ucfirst( str_ireplace( '_o', 'O', $this->getFunction() ) );
            call_user_func( [ $this, $function ], $path );
        }
        if ( $main_template ) {
            return $this->doneAndExit( 'template_loaded' );
        }
        return TRUE;
    }

    /**
     * Set directories where to look for template files
     */
    public function setDirectories( $main_template = FALSE ) {
        $def = [ get_template_directory() ];
        if ( is_child_theme() ) {
            array_unshift( $def, get_stylesheet_directory() );
        }
        $dirs = $this->getHooks()->filter( 'cortex.template_dirs', $def, $this, $main_template );
        $directories = is_array( $dirs ) && ! empty( $dirs ) ? $dirs : $def;
        $this->directories = array_unique( $directories );
    }

    /**
     * Add a directory where to look for template files
     *
     * @param string $dir
     */
    public function addDir( $dir ) {
        if ( ! is_string( $dir ) ) {
            throw new \InvalidArgumentException;
        }
        if ( ! is_array( $this->getDirectories() ) ) $this->setDirectories();
        array_push( $this->directories, $dir );
    }

    /**
     * Set the function to be used to load the found template file
     *
     * @param callable $func
     */
    public function setFunction( $func ) {
        $funcs = [ 'require', 'include', 'require_once', 'include_once' ];
        if ( in_array( $func, $funcs, TRUE ) ) {
            $this->function = $func;
        } else {
            throw new \InvalidArgumentException;
        }
    }

    /**
     * Return the function to be used to load the found template file
     *
     * @return callable
     */
    public function getFunction() {
        $funcs = [ 'require', 'include', 'require_once', 'include_once' ];
        $func = $this->getHooks()->filter( 'cortex.template_include_function', $this->function );
        return ( in_array( $func, $funcs, TRUE ) ) ? $func : 'require_once';
    }

    /**
     * Return added directories
     *
     * @return array
     */
    public function getDirectories() {
        return $this->directories;
    }

    /**
     * Return currently registered template
     * @return string
     */
    public function getTemplate() {
        return $this->template;
    }

    /**
     * Loop throught all directroies and return the first template found
     *
     * @param string $template The template to look for
     */
    public function getTemplateFile( $template ) {
        if ( ! is_string( $template ) && ! is_array( $template ) ) {
            throw new \InvalidArgumentException;
        }
        if ( is_string( $template ) ) $template = explode( ',', $template );
        $templates = array_map( 'trim', $template );
        foreach ( $templates as $basename ) {
            $fullpath = $this->getTemplateFullPath( $basename );
            if ( ! empty( $fullpath ) ) return $fullpath;
        }
        return FALSE;
    }

    /**
     * Traverse the folders set in the class to find a specific template
     *
     * @param string $basename Template basepath
     */
    function getTemplateFullPath( $basename ) {
        if ( ! is_string( $basename ) ) return;
        foreach ( $this->getDirectories() as $directory ) {
            if ( ! is_string( $directory ) ) continue;
            $path = trailingslashit( $directory ) . $basename;
            if ( is_file( $path ) ) return $path;
        }
    }

    /**
     * Return true if WP_USE_THEMES constants is defined and true
     *
     * @return boolean
     */
    function useThemes() {
        return ( defined( 'WP_USE_THEMES' ) && WP_USE_THEMES );
    }

    /**
     * Do tasks core runs before loading a template file
     *
     * @return boolean
     */
    function preLoad() {
        if ( ! $this->useThemes() ) {
            $this->doneAndExit( 'dont_use_themes' );
        }
        $method = strtoupper( filter_input( INPUT_SERVER, 'REQUEST_METHOD' ) );
        if ( 'HEAD' === $method && apply_filters( 'exit_on_http_head', TRUE ) ) {
            $this->doneAndExit( 'on_http_head' );
        }
        if ( is_robots() ) {
            do_action( 'do_robots' );
            $this->doneAndExit( 'is_robots' );
        } elseif ( is_feed() ) {
            do_feed();
            $this->doneAndExit( 'is_feed' );
        } elseif ( is_trackback() ) {
            include( ABSPATH . 'wp-trackback.php' );
            $this->doneAndExit( 'is_trackback' );
        }
        return TRUE;
    }

    function doneAndExit( $hook = 'loader_done_and_exit' ) {
        $this->getHooks()->trigger( "cortex.exit_{$hook}" );
        exit();
    }

    function doInclude( $path ) {
        include $path;
    }

    function doRequire( $path ) {
        require $path;
    }

    function doIncludeOnce( $path ) {
        include_once $path;
    }

    function doRequireOnce( $path ) {
        require_once $path;
    }

}