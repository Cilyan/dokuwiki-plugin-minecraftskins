<?php
/**
 * Load Template Plugin:
 * 
 * Loads the content of a page from a given namespace into the current
 * edited page doing some replacements on-the-fly.
 *
 * @author     Cilyan Olowen <gaknar@gmail.com>
 */
 
if(!defined('DOKU_INC')) die();
 
class action_plugin_minecraftskins extends DokuWiki_Action_Plugin {
 
    /**
     * Register the eventhandlers
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('FETCH_MEDIA_STATUS', 'BEFORE', $this, 'handle_fetch_media_status');
        
    }
    
    /**
     * Add information to JSINFO, required for the loadtemplate client side
     */
    function handle_fetch_media_status(&$event, $param) {
        $media = getID('media', false);
        $pos = strpos($media, '/');
        if($pos === false) {
            $pos = strpos($media, ':');
            if($pos === false) return;
        }
        $prefix = substr($media,0,$pos);
        $media = substr($media,$pos+1);
        $event->result = false;
        if ($prefix != '_head' and $prefix != '_skin') return;
        $event->stopPropagation();
        $event->preventDefault();
        $event->data['status'] == 200;
        $event->data['statusmessage'] == 'OK';
        $player = hsc($media);
        $size = $event->data['width'];
        if ($prefix == '_head') {
            if ($size == 0) $size = 48;
            if ($size < 8) $size = 8;
            if ($size > $this->GetConf('max_head_size')) $size = $this->GetConf('max_head_size');
            $this->_printHead($player, $size);
            exit;
        }
        if ($prefix == '_skin') {
            if ($size == 0) $size = 64;
            if ($size < 16) $size = 16;
            if ($size > $this->GetConf('max_skin_size')) $size = $this->GetConf('max_skin_size');
            $this->_printSkin($player, $size);
            exit;
        }
    }
    
    /**
     * Get the current skin for a given player
     * 
     * If the player has no skin available on Minecraft, the default minecraft
     * skin will be returned.
     */
    function _getSkin($player = 'char.png') {
        $http = new DokuHTTPClient();
        $skin = $http->get(
            'http://s3.amazonaws.com/MinecraftSkins/' . $player
        );
        if($skin === false) {
            $skin = file_get_contents(
                DOKU_PLUGIN.plugin_directory('minecraftskins')."/char.png"
            );
        }
        return $skin;
    }
    
    function _printHead($player, $size) {
        $skin = $this->_getSkin($player);
        $im = imagecreatefromstring($skin);
        $av = imagecreatetruecolor($size,$size);
        imagecopyresized($av,$im,0,0,8,8,$size,$size,8,8); // Face
        imagecolortransparent($im,imagecolorat($im,63,0)); // Black Hat Issue
        imagecopyresized($av,$im,0,0,40,8,$size,$size,8,8); // Accessories
        header('Status: 200 OK');
        header('Content-type: image/png');
        imagepng($av);
        imagedestroy($im);
        imagedestroy($av);
    }
    
    function _printSkin($player, $size) {
        $skin = $this->_getSkin($player);
        // Define ratio and sizes
        $dw = $size; // Size is width
        $dh = $dw*2; // Height is twice width (1:1 makes a character 16x32 px)
        $r  = $size/16.0; // Ratio of destination

        // Create source image from skin texture
        $isrc = imagecreatefromstring($skin);
        // Create mirrored image for left leg and arm
        $imir = imagecreatetruecolor(64,32);
        imagecopyresampled($imir,$isrc,0,0,64-1,0,64,32,-64,32);
        // Alocate target image
        $idst = imagecreatetruecolor($dw,$dh);
        imagesavealpha($idst,true);
        imagefill($idst,0,0,imagecolorallocatealpha($idst,0,0,0,127));

        // Front
        // Head
        imagecopyresized($idst,$isrc,$r*4,0,8,8,$r*8,$r*8,8,8);
        // Torso
        imagecopyresized($idst,$isrc,$r*4,$r*8,20,20,$r*8,$r*12,8,12);
        // Arm right
        imagecopyresized($idst,$isrc,0,$r*8,44,20,$r*4,$r*12,4,12);
        // Arm left
        imagecopyresized($idst,$imir,$r*12,$r*8,16,20,$r*4,$r*12,4,12);
        // Leg right
        imagecopyresized($idst,$isrc,$r*4,$r*8+$r*12,4,20,$r*4,$r*12,4,12);
        // Leg left
        imagecopyresized($idst,$imir,$r*8,$r*8+$r*12,56,20,$r*4,$r*12,4,12);
        // Accessories
        imagecopyresized($idst,$isrc,$r*4,0,40,8,$r*8,$r*8,8,8);

        header('Content-type: image/png');
        imagepng($idst);
        imagedestroy($isrc);
        imagedestroy($imir);
        imagedestroy($idst);
    }
}
//Setup VIM: ex: et ts=4 :
