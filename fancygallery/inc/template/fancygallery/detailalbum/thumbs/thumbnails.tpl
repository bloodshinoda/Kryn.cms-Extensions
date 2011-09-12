{addJs file="kryn/mootools-core.js"}
{addJs file="slimbox/slimbox.js"}
{addCss file="slimbox/slimbox.css"}

<style>
    .fancygallery_thumbs .thumb { float: left; margin: 10px 10px 0 0; }
</style>

<div class="fancygallery_thumbs">
    
    {foreach $images as $image}
    <div class="thumb">
        <a rel="lightbox-{$rsn}" title="{$image.description}" href="inc/upload/fancygallery/{$album.hash}/{$image.hash}"><img src="inc/upload/fancygallery/{$album.hash}/t/{$image.hash}" alt="{$image.description}" /></a>
    </div>
    {/foreach}
    <div style="clear: both;"></div>
</div>
