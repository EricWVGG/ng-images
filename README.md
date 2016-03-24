Next Generation Images for Wordpress
====================================

JPEG2000 and WEBP helpers for Wordpress.

## Description

After ten years of waiting, we’re finally getting support for modern lossy image compression in browsers! And just like you’d expect, they can’t agree on a standard! Awesome!

Apple brought us the format we’ve been waiting for — JPEG 2000. Thanks to copyrights and patents, none of the other browser makers are getting on board.
http://caniuse.com/#feat=jpeg2000

Google came up with a fancy play on their WebM video format to introduce WebP. Nobody else cares.
http://caniuse.com/#feat=webp

Microsoft is (typically) doing its own thing. Mozilla has decided to punt for another decade. In the meantime, I want these to use these in Wordpress projects today, so here we go…

### Requirements

PHP with shell_exec() enabled

ImageMagick on your web server

### How does it work?

We’re taking advantage of the &lt;picture&gt; tag. You can read about it at http://www.useragentman.com/blog/2015/01/14/using-webp-jpeg2000-jpegxr-apng-now-with-picturefill-and-modernizr/

Whenever an image is uploaded to Wordpress, this plugin will make WEBP and JP2 copies of the image and all its thumbnails.

When a post with an &lt;img&gt; tag is saved, it will parse the post content and replace it with a &lt;picture&gt; tag.

There is also a shortcode helper and PHP function for template authors.

### How to use…

shortcode: [ng-picture src="/wp-content/uploads/someimage.jpg" alt="my image" class="herp derp"]

PHP with Wordpress Media Object: ng_picture($image, array('alt' => 'my image', 'class' => 'herp derp'));

PHP with image url: ng_picture('/wp-content/uploads/someimage.jpg', array('alt' => 'my image', 'class' => 'herp derp'));

### Coming Soon

Shortcode should probably support lookup from image ID.

### Frequently Asked Questions

> What’s up with all the snark

Ten years man, out of fucks.