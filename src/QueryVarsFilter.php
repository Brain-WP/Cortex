<?php namespace Brain\Cortex;

use Brain\Request;

/**
 * Class used to filter based on WordPress accepted vars and a series of settings.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
class QueryVarsFilter implements RequestableInterface {

    use Requestable;

    private $request;

    protected static $wp_vars = [
        'm', 'p', 'posts', 'w', 'cat', 'withcomments', 'withoutcomments', 's', 'search', 'exact',
        'sentence', 'calendar', 'page', 'paged', 'more', 'tb', 'pb', 'author', 'order', 'orderby',
        'year', 'monthnum', 'day', 'hour', 'minute', 'second', 'name', 'category_name', 'tag',
        'feed', 'author_name', 'static', 'pagename', 'page_id', 'error', 'comments_popup',
        'attachment', 'attachment_id', 'subpost', 'subpost_id', 'preview', 'robots', 'taxonomy',
        'term', 'cpage', 'post_type', 'offset', 'posts_per_page', 'posts_per_archive_page',
        'showposts', 'nopaging', 'post_type', 'post_status', 'category__in', 'category__not_in',
        'category__and', 'tag__in', 'tag__not_in', 'tag__and', 'tag_slug__in', 'tag_slug__and',
        'tag_id', 'post_mime_type', 'perm', 'comments_per_page', 'post__in', 'post__not_in',
        'post_parent__in', 'post_parent__not_in', 'tax_query', 'meta_query', 'date_query'
    ];

    public function __construct( Request $request ) {
        $this->request = $request;
    }

    /**
     * Return valid WordPress query variables
     *
     * @return array
     */
    public function getWPVars() {
        return static::$wp_vars;
    }

    function filter( Array $vars = [ ], Array $args = [ ] ) {
        $default = [
            'qsmerge'        => TRUE,
            'autocustomvars' => TRUE,
            'customvars'     => [ ],
            'skipvars'       => [ ],
        ];
        $args = wp_parse_args( $args, $default );
        if ( $args['qsmerge'] ) {
            $vars = array_merge( $vars, $this->getRequest()->query() );
        }
        if ( $args['autocustomvars'] === TRUE ) {
            $allow = array_filter( array_merge( $this->getWPVars(), array_keys( $vars ) ) );
        } else {
            $allow = $this->getWPVars();
            if ( is_array( $args['customvars'] ) && $args['customvars'] !== [ ] ) {
                $allow = array_filter( array_merge( $allow, $args['customvars'] ) );
            }
        }
        if ( is_array( $args['skipvars'] ) && $args['skipvars'] !== [ ] ) {
            $allow = array_filter( array_diff( $allow, $args['skipvars'] ) );
        }
        return array_intersect_key( $vars, array_flip( array_filter( $allow, 'is_string' ) ) );
    }

}