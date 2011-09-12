{addJs file="kryn/mootools-core.js"}
{addJs file="kryn/mootools-more.js"}
{addJs file="fancygallery/js/slideshow.js"}
<script type="text/javascript">{literal}
var data = {};
var options = {};

function addImage(file, thumb, title, description)
{
	data[file] = {
		caption : description,
		thumbnail : thumb
	};
}

function setOption(v, val)
{
	options.v = val;
}

function dump(arr,level) {
	var dumped_text = "";
	if(!level) level = 0;
	
	//The padding given at the beginning of the line.
	var level_padding = "";
	for(var j=0;j<level+1;j++) level_padding += "    ";
	
	if(typeof(arr) == 'object') { //Array/Hashes/Objects 
		for(var item in arr) {
			var value = arr[item];
			
			if(typeof(value) == 'object') { //If it is an array,
				dumped_text += level_padding + "'" + item + "' ...\n";
				dumped_text += dump(value,level+1);
			} else {
				dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
			}
		}
	} else { //Stings/Chars/Numbers etc.
		dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
	}
	return dumped_text;
}


window.addEvent('domready', function() {{/literal}
{foreach from=$images item=image name="fgImageLoop"}
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

$('fancygallery').setStyle('width', {$pConf.width});
$('fancygallery').setStyle('margin-bottom', 60);

//alert(dump(options));
var myShow = new Slideshow('fancygallery', data, options);
{literal}});{/literal}
</script>
<style>
/*.slideshow {literal}{{/literal} width: {$pConf.width}px; height: {$pConf.height}px; margin: 0 auto; margin-bottom: 65px; {literal}}{/literal}
.slideshow-images {literal}{{/literal} width: {$pConf.width}px; height: {$pConf.height}px; {literal}}{/literal}
.slideshow-images a img {literal}{{/literal} margin: 0; padding: 0; border: 0; {literal}}{/literal}
.slideshow .slideshow-thumbnails img {literal}{{/literal} max-width: 50px; max-height: 40px; {literal}}{/literal}*/
</style>
<div>
	<div id="fancygallery" class="slideshow"><img /></div>
</div>
<div style="clear: both;"></div>
{if $debug}<pre>{$debug}</pre>{/if}