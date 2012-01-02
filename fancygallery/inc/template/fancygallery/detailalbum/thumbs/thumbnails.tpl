{addJs file="kryn/mootools-core.js"}
{addJs file="slimbox/slimbox.js"}
{addCss file="slimbox/slimbox.css"}

<style>
    .fancygallery_thumbs .thumb { float: left; margin: 10px 10px 0 0; }
</style>

<div class="fancygallery_thumbs">
    
    {foreach $album.images as $image}
    <div class="thumb">
        <a rel="lightbox-{$rsn}" title="{$image.description}" href="{$image.imgLoc}"><img src="{$image.thumbLoc}" alt="{$image.description}" /></a>
    </div>
    {/foreach}
    <div style="clear: both;"></div>
</div>
