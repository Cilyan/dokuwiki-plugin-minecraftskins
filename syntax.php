<?php
/**
 * DokuWiki Plugin minecraftskins (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Cilyan Olowen <gaknar@gmail.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class syntax_plugin_minecraftskins extends DokuWiki_Syntax_Plugin {
    /**
     * @return string Syntax mode type
     */
    public function getType() {
        return 'substition';
    }
    /**
     * @return int Sort order - Low numbers go before high numbers
     */
    public function getSort() {
        /* Between rss and media and possibly after avatar */
        return 316;
    }

    /**
     * Connect lookup pattern to lexer.
     *
     * @param string $mode Parser mode
     */
    public function connectTo($mode) {
        /* By-passes media links */
        $this->Lexer->addSpecialPattern("\{\{[^\}]+\}\}",$mode,'plugin_minecraftskins');
    }

    /**
     * Handle matches of the minecraftskins syntax
     *
     * @param string $match The match of the syntax
     * @param int    $state The state of the handler
     * @param int    $pos The position in the document
     * @param Doku_Handler    $handler The handler
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, &$handler){
        //$data = array();
        /* Mimic original media handling */
        $p = Doku_Handler_Parse_Media($match);
        $pos = strrpos($p['src'], '>');
        if($pos !== false){
            $prefix   = substr($p['src'],0,$pos);
            $p['src'] = substr($p['src'],$pos+1);
        }else{
            $prefix = '';
        }
        if ($prefix=='head' or $prefix=='skin') {
            $p['type'] = $prefix;
            $handler->addPluginCall(
                'minecraftskins',
                $p,
                $state,
                $pos,
                $match
            );
        }
        else {
            $handler->_addCall(
                $p['type'],
                array($p['src'], $p['title'], $p['align'], $p['width'],
                    $p['height'], $p['cache'], $p['linking']),
                $pos
            );
        }
        /* We already created the necessary call */
        return false;
    }

    /**
     * Render xhtml output or metadata
     *
     * @param string         $mode      Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer  $renderer  The renderer
     * @param array          $data      The data from the handler() function
     * @return bool If rendering was successful.
     */
    public function render($mode, &$renderer, $data) {
        if($mode != 'xhtml') return false;
        $renderer->doc .= '<img src="'.$this->mlnoclean(
            "_".$data['type'].":".$renderer->_xmlEntities($data['src']),
            array(
                'w'=>$data['width'],
                'h'=>$data['height'],
                'cache'=>$data['cache']
            )
        ).'"';
        $renderer->doc .= ' class="media'.$data['align'].'"';

        if ($data['title']) {
            $renderer->doc .= ' title="' . $data['title'] . '"';
            $renderer->doc .= ' alt="'   . $data['title'] .'"';
        }else{
            $renderer->doc .= ' alt=""';
        }

        if ( !is_null($data['width']) )
            $renderer->doc .= ' width="'.$renderer->_xmlEntities($data['width']).'"';

        if ( !is_null($data['height']) )
            $renderer->doc .= ' height="'.$renderer->_xmlEntities($data['height']).'"';

        $renderer->doc .= ' />';
        return true;
    }
    
    function mlnoclean($id = '', $more = '', $direct = true, $sep = '&amp;', $abs = false) {
        global $conf;
        $isexternalimage = media_isexternal($id);

        if(is_array($more)) {
            // add token for resized images
            if(!empty($more['w']) || !empty($more['h']) || $isexternalimage){
                $more['tok'] = media_get_token($id,$more['w'],$more['h']);
            }
            // strip defaults for shorter URLs
            if(isset($more['cache']) && $more['cache'] == 'cache') unset($more['cache']);
            if(empty($more['w'])) unset($more['w']);
            if(empty($more['h'])) unset($more['h']);
            if(isset($more['id']) && $direct) unset($more['id']);
            $more = buildURLparams($more, $sep);
        } else {
            $matches = array();
            if (preg_match_all('/\b(w|h)=(\d*)\b/',$more,$matches,PREG_SET_ORDER) || $isexternalimage){
                $resize = array('w'=>0, 'h'=>0);
                foreach ($matches as $match){
                    $resize[$match[1]] = $match[2];
                }
                $more .= $more === '' ? '' : $sep;
                $more .= 'tok='.media_get_token($id,$resize['w'],$resize['h']);
            }
            $more = str_replace('cache=cache', '', $more); //skip default
            $more = str_replace(',,', ',', $more);
            $more = str_replace(',', $sep, $more);
        }

        if($abs) {
            $xlink = DOKU_URL;
        } else {
            $xlink = DOKU_BASE;
        }

        // external URLs are always direct without rewriting
        if($isexternalimage) {
            $xlink .= 'lib/exe/fetch.php';
            $xlink .= '?'.$more;
            $xlink .= $sep.'media='.rawurlencode($id);
            return $xlink;
        }

        $id = idfilter($id);

        // decide on scriptname
        if($direct) {
            if($conf['userewrite'] == 1) {
                $script = '_media';
            } else {
                $script = 'lib/exe/fetch.php';
            }
        } else {
            if($conf['userewrite'] == 1) {
                $script = '_detail';
            } else {
                $script = 'lib/exe/detail.php';
            }
        }

        // build URL based on rewrite mode
        if($conf['userewrite']) {
            $xlink .= $script.'/'.$id;
            if($more) $xlink .= '?'.$more;
        } else {
            if($more) {
                $xlink .= $script.'?'.$more;
                $xlink .= $sep.'media='.$id;
            } else {
                $xlink .= $script.'?media='.$id;
            }
        }

        return $xlink;
    }
}

// vim:ts=4:sw=4:et:
