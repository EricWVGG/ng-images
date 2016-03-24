<?php
/*
Plugin Name: Next Generation Images for Wordpress
Plugin URI: http://whiskyvangoghgo.com/projects/ng_images
Description: generates webp and jpeg2000 images
Version: 1.0
Author: Eric Jacobsen
Author URI: http://whiskyvangoghgo.com
License: GPL2

Copyright 2016  Eric Jacobsen  (email : projects@whiskyvangoghgo.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

    ng_image('http://wp.whiskyvangoghgo.com/wp-content/uploads/2016/03/11.jpg', ['alt' => 11, 'class' => 'narf']);
    do_shortcode( '[ng-image src="http://wp.whiskyvangoghgo.com/wp-content/uploads/2016/03/11.jpg"]' );

    todo: process post content after save

*/

wp_register_script('picturefill', plugins_url() . '/ng-images/picturefill.js', array(), '3.0.2'); // Conditionizr
wp_enqueue_script('picturefill'); 
wp_register_script('picturefill_typesupport', plugins_url() . '/ng-images/picturefill_typesupport.js', array('picturefill'), '3.0.2'); // Conditionizr
wp_enqueue_script('picturefill_typesupport'); 


define( 'NGIMAGES_WEBP_Q', 80 );
define( 'NGIMAGES_JP2_Q',  80 );

function ng_image($src, $meta = []) {
    if( !$filenames = get_ng_filenames($src) ) {
        // something went wrong… just output normal image and call it a day.
        ?>
            <img src="<?=$src?>" alt="<?=$meta['alt']?>" title="<?=$meta['title']?>" id="<?=$meta['id']?>" class="<?=$meta['class']?>" >
        <?
        return;
    }
    ?>
        <picture id="<?=$meta['id']?>" class="<?=$meta['class']?>" >
            <source srcset="<?=$filenames['webp']?>" type="image/webp">
            <source srcset="<?=$filenames['jp2']?>" type="image/jp2">
            <img srcset="<?=$src?>" alt="<?=$meta['alt']?>" title="<?=$meta['title']?>">
        </picture>
    <? // end output
}


add_shortcode('ng-image', function($atts) {
    ng_image($atts['src'], $atts);
});


function get_ng_filenames($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if( $extension == 'jpg' ) {
        $replace = '.jpg';
    } else if ($extension == 'jpeg') {
        $replace = '.jpeg';
    } else if ($extension == 'png') {
        $replace = '.png';
    } else return;

    return [
        'webp' => str_replace( $replace, '.webp', $filename),
        'jp2' => str_replace( $replace, '.jp2', $filename),
    ];
}


function ngimages_convert_image($image) {
    $path = wp_upload_dir()['basedir'] . '/' . $image;
    $filenames = get_ng_filenames($path);
    shell_exec("convert {$path} -quality " . NGIMAGES_WEBP_Q ." {$filenames['webp']}");
    shell_exec("convert {$path} -quality " . NGIMAGES_JP2_Q . " {$filenames['jp2']}");
}


function ngimages_clone_from_source($nodes, $image_node, $type) {
    $node = $nodes->createElement('source');
    $node->setAttribute('type', $type);
    $filenames = get_ng_filenames($image_node->getAttribute('src'));
    $src = ( $type == 'image/webp' ) ? $filenames['webp'] : $filenames['jp2'];
    $node->setAttribute('srcset', $src);
    return $node;
}


add_filter('wp_generate_attachment_metadata', function($image_data) {
   if(isset($image_data['file'])) {
       ngimages_convert_image($image_data['file']);
   }
   foreach( $image_data['sizes'] AS $size => $a ) {
       if( isset( $a['file'])) {
           ngimages_convert_image($a['file']);
       }
   }
   return $image_data;
});


function ngimages_save_post( $post_id ) {
    $post = get_post($post_id);
    if( $post->post_type != 'post' ) {
        return $post_id;
    }
    
    $nodes = new DOMDocument();
    @$nodes->loadHTML($post->post_content); // haha PHP has a cow over HTML5 tags
    
    foreach($nodes->getElementsByTagName('img') as $image_node) {
        if($image_node->parentNode->nodeName != 'picture') {
            $picture = $nodes->createElement('picture');
            $picture->setAttribute('class', $image_node->getAttribute('class'));
            $picture->setAttribute('id',    $image_node->getAttribute('id'));
            $picture->setAttribute('alt',   $image_node->getAttribute('alt'));
            $picture->setAttribute('title', $image_node->getAttribute('title'));

            $image_node->parentNode->replaceChild($picture, $image_node);
            
            $picture->appendChild(ngimages_clone_from_source($nodes, $image_node, 'image/jp2'));
            $picture->appendChild(ngimages_clone_from_source($nodes, $image_node, 'image/webp'));
            $picture->appendChild($image_node);
        }
    }
    
    // remove HTML wrappers
    $nodes->removeChild($nodes->doctype);           
    $nodes->replaceChild($nodes->firstChild->firstChild->firstChild, $nodes->firstChild);
    
    // watch that infinite loop…
    remove_action( 'save_post', 'ngimages_save_post' );
    wp_update_post( array( 'ID' => $post_id, 'post_content' => $nodes->saveHTML() ) );
    add_action('save_post', 'ngimages_save_post');   

    return $post_id;
}
add_action('save_post', 'ngimages_save_post');


function ng_picture($img, $meta = []) { // Wordpress media associative array
    if( !in_array($img['mime_type'], ['image/jpeg', 'image/png']) ) {
        // return normal image
        return wp_get_attachment_image($img['ID']);
    }
    return ng_image($img['url'], $meta);
}






