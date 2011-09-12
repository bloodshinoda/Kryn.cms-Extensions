{capture name=fancygalleryNavi}
  {if $pages > 1 }
  <div class="navi">
    {section name=fgpage start=1 loop=$pages+1 max=$pConf.maxPages}
      {if $currentPage == $smarty.section.fgpage.index }
        <span>{$smarty.section.fgpage.index}</span>
      {else}
        <a href="{$page|@realUrl}/{$smarty.section.fgpage.index}/">{$smarty.section.fgpage.index}</a>
      {/if}
    {/section}
  </div>
  {/if}
{/capture}

<div class="fancygallery">
  {$smarty.capture.fancygalleryNavi}
  
  {foreach from=$albums item=album name="fgLoop"}
  {if $smarty.foreach.fgLoop.index ne 0 and $smarty.foreach.fgLoop.index % 2 == 0}<div class="clr"></div>{/if}
  <div class="album">
    <img src="{$album.images[0].thumbLoc}" alt="Album" width="53" height="53" />
    <div class="album-title"><a href="{$pConf.detailPage|realUrl}/{$album.rsn}/{$album.title|escape:"rewrite"}">{$album.title}</a></div>
    <div class="album-description">{$album.description}</div>
    <div id="album-modified_{$album.rsn}" class="album-modified"><span class="album-modified-time"></span></div>
  </div>
  <script type="text/javascript">HumanTimes.addElement("album-modified_{$album.rsn}", {$album.modified});</script>
  {/foreach}
  <div class="clr"></div>
 
  {$smarty.capture.fancygalleryNavi}
</div>
{if $debug}<pre>{$debug}</pre>{/if}