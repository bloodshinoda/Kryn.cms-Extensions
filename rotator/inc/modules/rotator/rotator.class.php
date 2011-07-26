<?php

class rotator extends modul {

    var $name = 'rotator';
    var $version = '0.0.5';
    var $owner = 'kryn.org';
    var $desc = 'Inhalte/Bilder rotieren lassen';
    
    public static $cacheDir = 'inc/cache/rotatorImages/';

    function contentRotate( $pConf ){
        global $tpl;

        kryn::addCss( 'rotator/css/contentRotator.'.$pConf['template'].'.css' );
        kryn::addJs( 'rotator/js/contentRotator.'.$pConf['template'].'.js' );

        $pages = dbTableFetch( 'system_pages', DB_FETCH_ALL, "prsn = ".$pConf['pageRsn'].' ORDER BY sort' );
        foreach( $pages as &$page ){
            $page['content'] = kryn::getPageContent( $page['rsn'] );
        }
        tAssign( 'pages', $pages );
        tAssign( 'pConf', $pConf );
        return tFetch( 'rotator/contentRotator/'.$pConf['template'].'.tpl' );
    }

    function imageRotaterDefault( $pConf ){
        kryn::addCss( 'rotator/css/imageRotatorDefault.'.$pConf['template'].'.css' );
        kryn::addJs( 'rotator/js/imageRotatorDefault.'.$pConf['template'].'.js' );

        $images = kryn::readFolder('inc/template/'.$pConf['folder'], true);
        natcasesort( $images );
        tAssign('images', $images );
        tAssign('folder', $pConf['folder']);

        return tFetch( 'rotator/imageRotatorDefault/'.$pConf['template'].'.tpl' );
    }

    function imageRotater( $pConf ){
        kryn::addCss( 'rotator/css/imageRotator.'.$pConf['template'].'.css' );
        kryn::addJs( 'rotator/js/imageRotator.'.$pConf['template'].'.js' );
        $images = array();

       if( empty($pConf['folder']) ) 
        		return;    
        
       $dir = str_replace('//', '/', $pConf['folder']);
        
        
       $cName = 'imageRotatorFolderCache-'.date('dmY').'-'.md5($pConf['folder']);
       if(class_exists('cache'))
		 	$files = cache::get($cName);
		 else		
		 	$files = kryn::getCache($cName);		
       if(!$files || empty($files)) {
        		$files = kryn::readFolder('inc/template/'.$pConf['folder'], true);
        		natcasesort( $files );
        		if(class_exists('cache'))
        			cache::set($cName, $files);        			
        		else
        			kryn::setCache($cName, $files);
       }
        
       if(empty($files))
        		return;

       foreach( $files as $file ){
            if( 
            	$file == '.' || $file == '..' || (
            	stripos($file, '.jpg') === false && stripos($file, '.jpeg') === false && stripos($file, '.png') === false && stripos($file, '.gif') === false)) {
            		continue;            	
            }
            $nfile = array(
                    'thump' => resizeImageCached( $dir.$file, $pConf['thumpSize'], true ),
            		  'file' => resizeImageCached( $dir.$file, $pConf['bigSize'], false )
            );
            $images[] = $nfile;
   
        }
        tAssign( 'images', $images );
        tAssign( 'pConf', $pConf );

        return tFetch( 'rotator/imageRotator/'.$pConf['template'].'.tpl' );
    }

    function getPlugins(){
        $plugins['contentRotate'] = array( 'Seiten', array(
            'template' =>	array(
                'label' => 'Template',
                'type' => 'files',
                'withExtension' => false,
                'directory' => 'inc/template/rotator/contentRotator/'
            ),
            'pageRsn' => array(
                'label' => 'Ordner',
                'type' => 'integer'
            )
        ));
        $plugins['imageRotaterDefault'] = array( 'Bilder', array(
            'template' =>	array(
                'label' => 'Template',
                'type' => 'files',
                'withExtension' => false,
                'directory' => 'inc/template/rotator/imageRotatorDefault/'
            ),
            'folder' => array(
                'label' => 'Datei-Ordner',
                'type' => 'string'
            ),
        ));
        $plugins['imageRotater'] = array( 'Bilder (autom. verkleinerung)', array(
            'template' =>	array(
                'label' => 'Template',
                'type' => 'files',
                'withExtension' => false,
                'directory' => 'inc/template/rotator/imageRotator/'
            ),
            'folder' => array(
                'label' => 'Datei-Ordner',
                'type' => 'string'
            ),
            'openWith' => array(
                'label' => 'Oeffnen mit',
                'type' => 'select'
            ),
            'thumpSize' => array(
                'label' => 'Thumpnail Groesse',
                'desc' => 'e.g. 50x50. Wird automatisch ausgeschnitten.',
                'type' => 'string'
            ),
            'bigSize' => array(
                'label' => 'Bildgroesse',
                'desc' => 'e.g. 1024x600. Die Grosse beim Oeffnen',
                'type' => 'string'
            )
        ));
        return $plugins;
    }
}

?>
