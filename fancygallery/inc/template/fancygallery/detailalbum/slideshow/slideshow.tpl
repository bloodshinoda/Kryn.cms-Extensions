{addJs file="kryn/mootools-core.js"}
{addJs file="kryn/mootools-more.js"}
{addJs file="fancygallery/js/slideshow.js"}
{addCss file="fancygallery/css/detailalbum/slideshow/default.css"}

<!-- Override default stylesheet properties -->
<style>
    .fancygallery_slide .slideshow { width: {$pConf.width}px; height: {$pConf.height}px; margin: 0 auto; margin-bottom: 65px; }
    .fancygallery_slide .slideshow-images { width: {$pConf.width}px; height: {$pConf.height}px; }
    .fancygallery_slide .slideshow-images a img { margin: 0; padding: 0; border: 0; }
    .fancygallery_slide .slideshow .slideshow-thumbnails img { max-width: 50px; max-height: 40px; }
    
    .fancygallery_slide .title { text-align: center; }
</style>


<div class="fancygallery_slide">
    {if $pConf.addtitle}<h2 class="title">{$album.title}</h2>{/if}
    <div id="fancygallery{$rsn}" class="slideshow"><img /></div>
    <div style="clear: both;"></div>
</div>


<script type="text/javascript">
    var data = {};
    var options = {};
    
    function addImage(file, thumb, title, description)
    {
        data[file] = {
            caption : description,
            thumbnail : thumb
        };
    }
    
    window.addEvent('domready', function() {
        
        {foreach $images as $image}
            addImage("{$image.hash}", "t/{$image.hash}", "{$image.title}", "{$image.description}");
        {/foreach}
        
        // (boolean or Fx options object: default false) 
        // Whether to show captions. 
        options.captions = {$pConf.captions} == 1;
        
        // Center (boolean: default true)
        // Whether the show should attempt to center images. 
        options.center = {$pConf.center} == 1;
        
        // Controller (boolean or Fx options object: default false)
        // Whether to show controller. 
        options.controller = {$pConf.controller} == 1;
        
        // Loop (boolean: default true)
        // Should the show loop. 
        options.loop = {$pConf.loop} == 1;
        
        // Preload (boolean : default false)
        // Optional preloading loads all images at start rather than streaming image by image. 
        options.preload = {$pConf.preload} == 1;
        
        // Loader (boolean or Fx options object: default object)
        // Show the loader graphic for images being loaded. 
        options.loader = true;
        
        // Thumbnails (boolean or Fx options object: default false)
        // Whether to show thumbnails.
        options.thumbnails = {$pConf.thumbnails} == 1;
        
        // Width (boolean or integer: default false) 
        // Optional width value for the show as a whole integer, 
        // if a width value is not given the width of the default image will be used. 
        options.width = {$pConf.width};
        
        // Height (boolean or integer: default false) 
        // Optional height value for the show as a whole integer, 
        // if a height value is not given the height of the default image will be used. 
        options.height = {$pConf.height};
        
        // Delay (integer: default 2000) 
        // The delay between slide changes in milliseconds (1000 = 1 second).
        {if $pConf.delay}options.delay = {$pConf.delay};{/if}
        
        // Duration (integer: default 750)
        // The duration of the effect in milliseconds (1000 = 1 second).
        {if $pConf.duration}options.duration = {$pConf.duration};{/if}
        
        // hu (string)
        // Path to the image directory, relative or absolute, default is the root directory of the website, 
        // use an empty string for the same directory as the webpage. 
        options.hu = "inc/upload/fancygallery/{$album.hash}/";
        
        // Overlap (boolean: default true)
        // Whether images overlap in the basic show, or if the first image transitions out before the second transitions in. 
        options.overlap = false;
        
        // Resize (boolean or string: default "fill")
        // Whether the show should attempt to resize images, based on the shortest side (default) or longest side ("fit")
        // or resize without preserving proportions ("stretch"). Set to false to disable image resizing.
        options.resize = "fit";  
        
        //alert(dump(options));
        var myShow = new Slideshow('fancygallery{$rsn}', data, options);
    });
</script>