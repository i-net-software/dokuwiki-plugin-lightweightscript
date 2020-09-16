<?php
/**
 * DokuWiki Plugin lightweightscript (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  i-net /// software <tools@inetsoftware.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_lightweightscript extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
       $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handle_tpl_metaheader_output');
       $controller->register_hook('JS_SCRIPT_LIST', 'BEFORE', $this, 'handle_js_script_list');
       $controller->register_hook('TOOLBAR_DEFINE', 'BEFORE', $this, 'handle_js_toolbar');

       // $controller->register_hook('JS_CACHE_USE', 'BEFORE', $this, 'handle_use_cache');
    }

    /**
     * Insert an extra script tag for users that have AUTH_EDIT or better
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function handle_tpl_metaheader_output(Doku_Event &$event, $param) {
        global $ID;
        global $conf;
        
        // add script if user has better auth than AUTH_EDIT
        if ( auth_quickaclcheck( $ID ) >= AUTH_EDIT ) {
            $event->data['script'][] = array(
                'type'=> 'text/javascript', 'charset'=> 'utf-8', '_data'=> '',
                'src' => DOKU_BASE.'lib/exe/js.php'.'?t='.rawurlencode($conf['template']).'&type=admin&tseed='.$tseed
            )  + ($conf['defer_js'] ? [ 'defer' => 'defer'] : []);
        }
        
        // The first one is the static JavaScript block. PageSpeed says it would be good to print this first.
        _tpl_metaheaders_action( array( 'script' => array( array_shift($event->data['script']) ) ) );
    }

    /**
     * Hacking the toolbar for the requested script.
     * If it is NOT the previously added admin script, remove the toolbar
     * because the user has no edit rights.
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function handle_js_toolbar(Doku_Event &$event, $param) {
        global $INPUT;
        
        if ( $INPUT->str('type')  != 'admin' ) {
            $data = array();
            
            // Remove the toolbar and do not add (which is done after this function) the default buttons
            $event->data = &$data;
            $event->preventDefault();
            
            return false;
        } else {
            // Only defaults if admin, the whole header part will be removed.
            // This is a bit hacky ...
            ob_clean();
        }
    }
    
    /**
     * This function serves debugging purposes and has to be enabled in the register phase
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function handle_use_cache(Doku_Event &$event, $param) {
        $event->preventDefault();
        return false;
    }

    /**
     * Finally, handle the JS script list. The script would be fit to do even more stuff / types
     * but handles only admin and default currently.
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function handle_js_script_list(Doku_Event &$event, $param) {
        global $INPUT;
        
        switch( $INPUT->str('type') ) {
            
            case 'admin':
                // Filter for admin scripts
                $event->data = array_filter( $event->data, array($this, 'filter_admin_scripts') );
                break;
            
            default:
                // Filter for the-rest-if-us scripts
                $event->data = array_filter( $event->data, array($this, 'filter_user_scripts') );
        }
    }
    
    /**
     * A simple filter function to check the input string against a list of path-parts that are allowed
     *
     * @param string    $str   the script file to check against the list
     * @param mixed     $list  the list of path parts to test
     * @return boolean
     */
    private function includeFilter( $str, $list ) {
        
        foreach( $list as $entry ) {
            if ( strpos( $str, $entry ) ) return true;
        }
        
        return false;
    }
    
    /**
     * A simple filter function to check the input string against a list of path-parts that are allowed
     * Is the inversion of includeFilter( $str, $list )
     *
     * @param string    $str   the script file to check against the list
     * @param mixed     $list  the list of path parts to test
     * @return boolean
     */
    private function excludeFilter( $str, $list ) {
        return !$this->includeFilter( $str, $list );
    }

    /**
     * Filters scripts that are intended for admins only
     *
     * @param string    $script   the script file to check against the list
     * @return boolean
     */
    private function filter_admin_scripts( $script ) {
        return $this->includeFilter( $script, array(
            
            '/lib/scripts/fileuploader',
            'jquery.ui.datepicker.js',
            '/lib/scripts/',
            
            // Plugins
            '/lib/plugins/tag/',
            '/lib/plugins/extension/',
            '/lib/plugins/move/',
            '/lib/plugins/styling/',
            '/lib/plugins/sectionedit/',
            '/lib/plugins/searchindex/',
            '/lib/plugins/acl/',
            '/lib/plugins/pagequery/',
            '/lib/plugins/colorpicker/',
            '/lib/plugins/sync/',
            '/lib/plugins/multiorphan/',
            '/lib/plugins/color/',
            '/lib/plugins/usermanager/',
            '/lib/plugins/edittable/',
            '/lib/plugins/edittable/',
            '/lib/plugins/include/',
            '/lib/plugins/toctweak/',
            '/lib/plugins/searchindex/',
            '/lib/plugins/fastwiki/',
            
        )) && $this->excludeFilter( $script, array(
            '/lib/scripts/script.js', // a core script
            '/lib/scripts/page.js', // a core script for footnotes
            'jquery.cookie.js',
        ));
    }

    /**
     * Filters scripts that are intended for users only
     *
     * @param string    $script   the script file to check against the list
     * @return boolean
     */
    private function filter_user_scripts( $script ) {
        return !$this->filter_admin_scripts( $script );
    }
}

// vim:ts=4:sw=4:et:
