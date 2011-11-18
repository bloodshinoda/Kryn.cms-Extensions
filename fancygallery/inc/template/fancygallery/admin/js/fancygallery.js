/**
 * @author Ferdi
 */

var Log = function( pVal ){
    logger(pVal);
}

var fgFileNameConvert = function(pFile, pSecParam)
{
	rName = pSecParam.substr((pSecParam.indexOf(_sid) + _sid.length + 2));
	rName = rname.substr(0, rName.length - 1);
	Log('rName: '+rName);
	
	return rName;
};

var fancygallery_fancygallery = new Class({
	
	initialize: function(pWindow)
	{
		this.win = pWindow;
		this.win.content.setStyle('overflow', 'hidden');
		this.pGlobal = _path+'admin/fancygallery/global/';
		this.pImages = 'inc/upload/fancygallery/';
		this._addHookToClose();
		this._createLayout();
		
		this.currentAlbum = 0;
		this.imageFields = new Array();
		this.numImages = 0;
		
		// Fire keyUp for employer population
		this.searchCategories.fireEvent('keyup');
		this.searchAlbums.fireEvent('keyup');
	},
	
	_addHookToClose: function()
	{
		// Delete upload dir on close
		this.win.closer.addEvent('click', function() {
			// Delete files from upload dir
			new Request.JSON({
				url: _path+'admin/files/deleteFile',
				noCache: 1
			}).post({
				path: '/fancygallery/tempUpload/',
				name: _sid
			});
		}.bind(this));
	},
	
	_createLayout: function()
	{
		var _pImgs = _path+'inc/template/admin/images/';
		var _pIcons = _pImgs+'icons/';
		
		// Add buttons (Left of tabs)
			this.addGroup = this.win.addButtonGroup();
			this.addGroup.box.setStyle('left', 10);
			this.btnCategoryAdd = this.addGroup.addButton(_('Add category'), _pIcons+'folder_add.png', this.addCategory.bind(this));
			this.btnAlbumAdd = this.addGroup.addButton(_('Add album'), _pIcons+'image_add.png', this.addAlbum.bind(this));
		
		// Create tabs panel
			var boxNavi = this.win.addTabGroup();
			this.tabGroup = boxNavi;
			boxNavi.box.setStyle('left', 114);
			
		// Tabs
			this.tabButtons = new Hash();
			this.tabButtons['general'] = boxNavi.addButton(_('General'), _pIcons+'brick.png', this.setType.bindWithEvent(this, 'general'));
			this.tabButtons['images'] = boxNavi.addButton(_('Images'), _pIcons+'application_view_tile.png', this.setType.bindWithEvent(this, 'images'));
			this.tabButtons['order'] = boxNavi.addButton(_('Order'), _pIcons+'arrow_switch.png', this.setType.bindWithEvent(this, 'order'));
		
			// Hide tab buttons
			this.tabGroup.hide();
			
		// Save button
			this.saveGroup = this.win.addButtonGroup();
			this.saveGroup.box.setStyle('left', 120);
			this.btnSave = this.saveGroup.addButton(_('Save'), _pIcons+'disk.png', this.saveAlbum.bindWithEvent(this));
			this.btnDelete = this.saveGroup.addButton(_('Delete'), _pIcons+'bomb.png', this.deleteAlbum.bindWithEvent(this));
			
			// Hide save group
			this.saveGroup.hide();

		// Tree Categories
			this.treeCategories = new Element('div', {
				style: "position: absolute; top: 0; left: 0; height: 50%; width: 200px;"
			}).inject(this.win.content);
			
			new Element('div', {
				style: "position: absolute; left: 3px; top: 8px; font-weight: bold; font-size: 12px;",
				text: _('Categories')
			}).inject(this.treeCategories);
			
			this.listCategoriesWrapper = new Element('div', {
				'class': 'fg-list',
				style: 'position: absolute; bottom: 0; left: 3px; right: 0; top: 47px;'
			})
			.addEvent('mouseover', this.setInfoFields.bindWithEvent(this, { h: _('Categories'), i: _('Select a category to view it\'s albums. If you want to see all albums, select \'All categories\'') }))
			.addEvent('mouseout', this.setInfoFields.bindWithEvent(this, {}))
			.inject(this.treeCategories);
			
			this.listCategories = new Element('select', {
				'size': 2,
				style: 'height: 100%; width: 100%;'
			})
			.addEvent('change', this.doSearchAlbums.bindWithEvent(this))
			.addEvent('click', this.doSearchAlbums.bindWithEvent(this))
			.inject(this.listCategoriesWrapper);
			
			this.searchCategories = new Element('input', {
				'class': 'text fg-search',
				value: _('Search ...')
			})
			.addEvent('focus', function() {
				if(this.value == _('Search ...'))
				{
					this.value = '';
					this.setStyle('color', 'gray');
				}
			})
			.addEvent('blur', function() {
				if(this.value == '') 
				{
					this.value = _('Search ...');
					this.setStyle('color', 'silver');
				}
			})
			.addEvent('keyup', this.doSearchCategories.bindWithEvent(this))
			.inject(this.treeCategories);
		
		// Tree Albums	
			this.treeAlbums = new Element('div', {
				style: 'position: absolute; bottom: 3px; left: 0; height: 50%; width: 200px;'
			}).inject(this.win.content);
			
			new Element('div', {
				style: "position: absolute; left: 3px; top: 8px; font-weight: bold; font-size: 12px;",
				text: _('Albums')
			}).inject(this.treeAlbums);
			
			this.listAlbumsWrapper = new Element('div', {
				'class': 'fg-list',
				style: 'position: absolute; bottom: 0; left: 3px; right: 0; top: 47px;'
			})
			.addEvent('mouseover', this.setInfoFields.bindWithEvent(this, { h: _('Albums'), i: _('Select an album to view it\'s settings, the images with titles and descriptions and to order the images.') }))
			.addEvent('mouseout', this.setInfoFields.bindWithEvent(this, {}))
			.inject(this.treeAlbums);
			
			this.listAlbums = new Element('select', {
				'size': 2,
				style: 'height: 100%; width: 100%;'
			})
			.addEvent('change', this.loadAlbum.bindWithEvent(this))
			.addEvent('click', this.loadAlbum.bindWithEvent(this))
			.inject(this.listAlbumsWrapper);
			
			this.searchAlbums = new Element('input', {
				'class': 'text fg-search',
				value: _('Search ...')
			})
			.addEvent('focus', function() {
				if(this.value == _('Search ...'))
				{
					this.value = '';
					this.setStyle('color', 'gray');
				}
			})
			.addEvent('blur', function() {
				if(this.value == '')
				{
					this.value = _('Search ...');
					this.setStyle('color', 'silver');
				}
			})
			.addEvent('keyup', this.doSearchAlbums.bindWithEvent(this))
			.inject(this.treeAlbums);
		
		// Main content pane
			this.mainContentPane = new Element('div', {
				'class': 'fg-contentPane'
			}).inject(this.win.content);
			
			new Element('div', {
				style: 'padding: 25px; font-size: 25px; color: gray;',
				text: _('Please choose an album')
			}).inject(this.mainContentPane);
			
		// Info panel
			this.infoPanel = new Element('div', {
				'class': 'fg-infoPanel'
			}).inject(this.mainContentPane);
			
			this.infoPanelHeader = new Element('div', {
				style: 'position: absolute; left: 5px; top: 8px; right: 5px; font-weight: bold; font-size: 12px; color: #666; border-bottom: 1px solid #ddd;',
			}).inject(this.infoPanel);
			
			this.infoPanelBody = new Element('div', {
				style: 'position: absolute; left: 10px; top: 25px; right: 10px; font-size: 12px; color: #999;'
			}).inject(this.infoPanel);
			
			// Set default value for info field
			this.setInfoFields(null, {});
			
		// Tab panes
			this.tabPanes = new Hash(); // Overal panes
			this.tabFields = new Hash(); // Fields per pane
			
			this.tabPanes['general'] = new Element('div', {
				'class': 'fg-panel'
			}).inject(this.mainContentPane);
			this.tabPanes['images'] = new Element('div', {
				'class': 'fg-panel'
			}).inject(this.mainContentPane);
			this.tabPanes['order'] = new Element('div', {
				'class': 'fg-panel',
				id: 'sortable-images'
			}).inject(this.mainContentPane);
			
		// Create layouts for tabs
			this._createTabGeneralLayout();
			this._createTabImagesLayout();
			this._createTabOrderLayout();
	},
	
	_createTabGeneralLayout: function()
	{
		var panel = this.tabPanes['general'];
		
		this.tabFields['general'] = new Hash();
		
		// Title
		this.tabFields['general']['title'] = new ka.field({
			label: _('Title'),
			type: 'text',
			empty: false
		}).inject(panel);
		this.tabFields['general']['title'].main
		.addEvent('mouseover', this.setInfoFields.bindWithEvent(this, { h: _('Title'), i: _('Title of this album') }))
		.addEvent('mouseout', this.setInfoFields.bindWithEvent(this, {}));
		
		// Description
		this.tabFields['general']['description'] = new ka.field({
			label: _('Description'),
			type: 'textarea',
			height: '50px',
			empty: true
		}).inject(panel);
		this.tabFields['general']['description'].main
		.addEvent('mouseover', this.setInfoFields.bindWithEvent(this, { h: _('Description'), i: _('Description of this album') }))
		.addEvent('mouseout', this.setInfoFields.bindWithEvent(this, {}));
		
		// Category
		this.tabFields['general']['category'] = new ka.field({
			label: _('Category'),
			type: 'select'
		}).inject(panel);
		this.tabFields['general']['category'].main
		.addEvent('mouseover', this.setInfoFields.bindWithEvent(this, { h: _('Category'), i: _('Select the category this album belongs to. When you change the category, you should save and reload the album list by clicking the appropriate category.') }))
		.addEvent('mouseout', this.setInfoFields.bindWithEvent(this, {}));
		
		// Hidden
		this.tabFields['general']['hidden'] = new ka.field({
			label: _('Hidden'),
			type: 'checkbox'
		}).inject(panel);
		this.tabFields['general']['hidden'].main
		.addEvent('mouseover', this.setInfoFields.bindWithEvent(this, { h: _('Hidden'), i: _('Is the album hidden? When hidden this album will not be shown at the frontend. Hidden overrules \'Hide from ...\' date.') }))
		.addEvent('mouseout', this.setInfoFields.bindWithEvent(this, {}));
		
		// Show from
		this.tabFields['general']['show'] = new ka.field({
			label: _('Show from ...'),
			type: 'datetime',
		}).inject(panel);
		this.tabFields['general']['show'].main
		.addEvent('mouseover', this.setInfoFields.bindWithEvent(this, { h: _('Show from ...'), i: _('If you want to show this album from a certain date (and time), set the date (and time) here.') }))
		.addEvent('mouseout', this.setInfoFields.bindWithEvent(this, {}));
		
		// Hide from
		this.tabFields['general']['hide'] = new ka.field({
			label: _('Hide from ...'),
			type: 'datetime'
		}).inject(panel);
		this.tabFields['general']['hide'].main
		.addEvent('mouseover', this.setInfoFields.bindWithEvent(this, { h: _('Hide from ...'), i: _('If you want to hide this album from a certain date (and time), set the date (and time) here.') }))
		.addEvent('mouseout', this.setInfoFields.bindWithEvent(this, {}));
		
		// Created by
		this.tabFields['general']['created'] = new ka.field({
			label: _('Created'),
			type: 'html',
			desc: ' '
		}).inject(panel);
		this.tabFields['general']['created'].main
		.addEvent('mouseover', this.setInfoFields.bindWithEvent(this, { h: _('Created'), i: _('This album was created on the shown date by the shown user.') }))
		.addEvent('mouseout', this.setInfoFields.bindWithEvent(this, {}));
		
		// Modified by
		this.tabFields['general']['modified'] = new ka.field({
			label: _('Last modified'),
			type: 'html',
			desc: ' '
		}).inject(panel);
		this.tabFields['general']['modified'].main
		.addEvent('mouseover', this.setInfoFields.bindWithEvent(this, { h: _('Modified'), i: _('This album was last modified on the shown date by the shown user.') }))
		.addEvent('mouseout', this.setInfoFields.bindWithEvent(this, {}));
	},
	
	_createTabImagesLayout: function()
	{
		this.tabFields['images'] = new Hash();
		
		// Shortcuts
		var panel = this.tabPanes['images'];
		
		// Uploader
		this.tabFields['images']['upload'] = new ka.field({
			label: 'Upload new image(s)',
			type: 'multiUpload',
			savepath: '/fancygallery/tempUpload/',
			uploadpath: 'admin/backend/window/sessionbasedFileUpload/',
			fileNameConverter: 'fgFileNameConvert',
			upload: '_uploadCallback',
		}, panel, {win: this.win});
		this.tabFields['images']['upload'].main.getElement('div.ka-field-field').getElement('input').setStyle('width', '90%');
		this.tabFields['images']['upload'].obj
		.addEvent('success', function(pFile, pSecParam) { this._uploadCallback(pFile, pSecParam) }.bind(this));
		
		this.multiUploadFileContainer = this.tabFields['images']['upload'].main.getElement('div.multiUpload-fileContainer');
		
		// Uploaded images
		this.imageContainer = new Element('div', {
			'class': 'image-container'
		}).inject(panel);
	},
	
	_createTabOrderLayout: function()
	{
		this.imageOrderContainer = this.tabPanes['order'];
	},
	
	_uploadCallback: function(pFile, pFileLocation)
	{
		this.tabButtons['images'].startTip(_('Adding image(s) ...'));
		
		new Request.JSON({
			url: this.pGlobal+'add/image',
			noCache: 1,
			
			onComplete: function(res) {
				if(res)
				{
					this.tabButtons['images'].stopTip(_('Added!'));
					
					// Remove from upload object
					this.multiUploadFileContainer.empty();
					
					// Add to own list of images
					this.addImageToContainer(res.rsn, res.albumHash, res.hash, res.title, res.description, res.hidden);
					
					// Make it sortable
					this._createSortableImages();
				}
				else
					this.tabButtons['images'].stopTip(_('Adding images failed!'));
				
			}.bind(this)
		}).post({
			fileLocation: pFileLocation,
			fileName: pFile.name,
			fileType: pFile.type,
			album: this.currentAlbum
		});
	},
	
	doSearchCategories: function()
	{
		lastSearch = this.lastSearchCategories;
		
		if(lastSearch) // Cancel request
			lastSearch.cancel();
		
		var list = this.listCategories;
		
		var q = this.searchCategories.value;
		if(q == _('Search ...') || q == '')
			q = '*';
		
		lastSearch = new Request.JSON({
			url: this.pGlobal+'search/categories',
			noCache: 1,
			
			onComplete: function(res)
			{
				list.empty();
				new Element('option', {
					text: _('All categories'),
					value: 0
				}).inject(list);
				if(res)
				{
					res.each(function(item) {
						new Element('option', {
							text: item.title,
							value: item.rsn
						}).inject(list);
					});
				}
			}.bind(this)
		}).post({
			q: q
		});
		
		this.lastSearchCategories = lastSearch;
	},
	
	doSearchAlbums: function()
	{
		lastSearch = this.lastSearchAlbums;
		
		if(lastSearch) // Cancel request
			lastSearch.cancel();
		
		var list = this.listAlbums;
		
		var q = this.searchAlbums.value;
		if(q == _('Search ...') || q == '')
			q = '*';
		
		lastSearch = new Request.JSON({
			url: this.pGlobal+'search/albums',
			noCache: 1,
			
			onComplete: function(res)
			{
				list.empty();
				if(res)
				{
					res.each(function(item) {
						new Element('option', {
							text: item.title,
							value: item.rsn,
							selected: item.rsn == this.currentAlbum
						}).inject(list);
					}.bind(this));
				}
			}.bind(this)
		}).post({
			c: this.listCategories.value,
			q: q
		});
		
		this.lastSearchAlbums = lastSearch;
	},
	
	loadAlbum: function()
	{
		lastLoadAlbum = this.lastLoadAlbum;
		lastLoadAlbumLastModified = this.lastLoadAlbumLastModified
		
		if(lastLoadAlbum) // Cancel request
			lastLoadAlbum.cancel();
		if(lastLoadAlbumLastModified) // Cancel request, started by a save
			lastLoadAlbumLastModified.cancel();
		
		// Save clicked album
		this.currentAlbum = this.listAlbums.value;
		
		// Load album data
		lastLoadAlbum = new Request.JSON({
			url: this.pGlobal+'load/album',
			noCache: 1,
			
			onComplete: function(res)
			{
				if(res)
				{
					// Update category list
					// Empty current list
					var slcCat = this.tabFields['general']['category'].select;
					slcCat.empty();
					
					// Use values from this.listCategories
					this.listCategories.getElements('option').each(function(element) {
						if(element.value == 0)
							element.text = _('No category');
						
						slcCat.add(element.value, element.text);
					});
					slcCat.setValue(res.category, false);
					
					// Put data into fields
					this.tabFields['general']['title'].setValue(res.title);
					this.tabFields['general']['description'].setValue(res.description);
					//this.tabFields['general']['category'].setValue(res.category);
					this.tabFields['general']['hidden'].setValue(res.hidden);
					if(res.show_)
						this.tabFields['general']['show'].setValue(res.show_);
					if(res.hide_)
						this.tabFields['general']['hide'].setValue(res.hide_);
					
					var dateCreated = new Date();
					dateCreated.setTime(res.created * 1000);
					var strCreated = dateCreated.format('%a, %d %b %Y, %H:%M') + ' ' + _('by') + ' ' + res.creator;
					
					var dateModified = new Date();
					dateModified.setTime(res.modified * 1000);
					var strModified = dateModified.format('%a, %d %b %Y, %H:%M') + ' ' + _('by') + ' ' + res.modifier;
					
					this.tabFields['general']['created'].main.getElement('div.desc').set('text', strCreated);
					this.tabFields['general']['modified'].main.getElement('div.desc').set('text', strModified);
					
					// Show tabs and save group
					this.tabGroup.show();
					this.saveGroup.show();
					
					// Enable general tab
					this.setType(null, 'general');
				}
				else
				{
					// Hide tabs and save group
					this.tabGroup.hide();
					this.saveGroup.hide();
				}
			}.bind(this)
		}).post({
			a: this.currentAlbum
		});
		
		// Save request
		this.lastLoadAlbum = lastLoadAlbum;
		
		// Load images
		this.loadAlbumImages();
	},
	
	loadAlbumImages: function()
	{
		lastLoadImages = this.lastLoadImages;
		
		if(lastLoadImages) // Cancel request
			lastLoadImages.cancel();
		
		lastLoadImages = new Request.JSON({
			url: this.pGlobal+'load/images',
			noCache: 1,
			
			onComplete: function(res)
			{
				if(!res.images)
					return;
				
				// Empty fields
				this.imageContainer.empty();
				this.imageOrderContainer.empty();
				this.imageFields = new Array();
				this.numImages = 0;
				
				res.images.each(function(img, index) {
					this.addImageToContainer(img.rsn, res.albumHash, img.hash, img.title, img.description, img.hidden);
				}.bind(this));
				
				this._createSortableImages();
			}.bind(this)
		}).post({
			a: this.currentAlbum
		});
		
		this.lastLoadImages = lastLoadImages;
	},
	
	addImageToContainer: function(rsn, albumHash, imgHash, title, description, hidden)
	{
		var index = this.numImages;
		this.imageFields[index] = {};
		this.imageFields[index].rsn = rsn; // Save rsn
		this.imageFields[index].hidden = hidden; // Save hidden
		
		// Image container
		var imgCont = new Element('div', {
			'class' : 'image-block'
		}).inject(this.imageContainer);
		
		// Image block (left)
		var imgHolder = new Element('div', {
			'class': 'image-holder',
			'style': 'position: relative;'
		}).inject(imgCont);
		
		// Image inside image block
		new Element('img', {
			src: this.pImages + albumHash + '/t/' + imgHash,
			'class': 'image-thumb'
		})
		.addEvent('mouseover', function() { this.imageFields[index].imgButtons.show(); }.bind(this))
		.addEvent('mouseout', function() { this.imageFields[index].imgButtons.hide(); }.bind(this))
		.inject(imgHolder);
		
		var divImgButtons = new Element('div', {
			style: 'position: absolute; right: 5px; top: 2px; border: 0;'
		})
		.addEvent('mouseover', function() { this.imageFields[index].imgButtons.show(); }.bind(this))
		.inject(imgHolder)
		.hide();
		this.imageFields[index].imgButtons = divImgButtons;
		
		var iconHidden = new Element('img', {
			src: _path+'inc/template/admin/images/icons/eye_bw.png',
			style: 'cursor: pointer; position: absolute; left: 2px; top: 2px;'
		})
		.addEvent('mouseover', this.setInfoFields.bindWithEvent(this, {h: 'Visibility', i: 'This image is hidden in this album. Click this button to make this image visible in this album.'}))
		.addEvent('mouseout', this.setInfoFields.bindWithEvent(this, {}))
		.addEvent('mouseover', function() { this.imageFields[index].imgButtons.show(); }.bind(this))
		.addEvent('mouseout', function() { this.imageFields[index].imgButtons.hide(); }.bind(this))
		.addEvent('click', this.showImage.bindWithEvent(this, index))
		.inject(imgHolder);
		
		// When visible, hide icon
		if(hidden == 0)
			iconHidden.hide();
		
		var btnHide = new Element('img', {
			src: _path+'inc/template/admin/images/icons/eye.png',
			'class': 'image-button'
		})
		.addEvent('mouseover', this.setInfoFields.bindWithEvent(this, {h: 'Visibility', i: 'This image is visible in the album. Click this button to hide this image.'}))
		.addEvent('mouseout', this.setInfoFields.bindWithEvent(this, {}))
		.addEvent('click', this.hideImage.bindWithEvent(this, index))
		.inject(divImgButtons);
		
		var btnShow = new Element('img', {
			src: _path+'inc/template/admin/images/icons/eye_bw.png',
			'class': 'image-button'
		})
		.addEvent('mouseover', this.setInfoFields.bindWithEvent(this, {h: 'Visibility', i: 'This image is hidden in this album. Click this button to make this image visible in this album.'}))
		.addEvent('mouseout', this.setInfoFields.bindWithEvent(this, {}))
		.addEvent('click', this.showImage.bindWithEvent(this, index))
		.inject(divImgButtons);
		
		// Hide btnHide or btnShow according to hidden
		if(hidden == 1)
			btnHide.hide();
		else
			btnShow.hide();
		
		var btnDelete = new Element('img', {
			src: _path+'inc/template/admin/images/icons/bomb.png',
			'class': 'image-button'
		})
		.addEvent('mouseover', this.setInfoFields.bindWithEvent(this, {h: _('Delete image'), i: _('Click to delete this image from this album')}))
		.addEvent('mouseout', this.setInfoFields.bindWithEvent(this, {}))
		.addEvent('click', this.deleteImage.bindWithEvent(this, index))
		.inject(divImgButtons);
		
		this.imageFields[index].iconHidden = iconHidden;
		this.imageFields[index].btnHide = btnHide;
		this.imageFields[index].btnShow = btnShow;
		this.imageFields[index].btnDelete = btnDelete;
		
		// Options fields
		var imgFields = new Element('div', {
			'class': 'image-fields',
		}).inject(imgCont);
		
		// Title
		this.imageFields[index].title = new ka.field({
			label: _('Title'),
			type: 'text',
			value: title,
			panel_width: '100%'
		}).inject(imgFields);
		
		// Description
		this.imageFields[index].description = new ka.field({
			label: _('Description'),
			type: 'textarea',
			height: '3.5em',
			value: description,
			panel_width: '100%'
		}).inject(imgFields);
		
		// ORDER PART
		var oImg = new Element('img', {
			src: this.pImages + albumHash + '/t/' + imgHash,
			id: 'oi_'+index
		})
		.inject(this.imageOrderContainer);
		
		if(hidden == 1)
			oImg.addClass('image-hidden');
		
		this.imageFields[index].oImg = oImg;
		
		this.numImages++;
	},
	
	_createSortableImages: function()
	{
		this.tabFields['order'] = {};
		this.tabFields['order'].sortable = new Sortables('sortable-images', {
			clone: true,
			revert: true,
			
			initialize: function() {},
			
			onStart: function(el, clone) {},
			
			onSort: function(el, clone) 
			{
				sorted = true; 
			},
			
			onComplete: function(el, clone) 
			{
				if(sorted)
					sorted = false;
			}
			
		});
	},
	
	showImage: function(event, index)
	{
		this.tabButtons['images'].startTip(_('Showing image ...'));
		var iF = this.imageFields[index];
		
		new Request.JSON({
			url: this.pGlobal+'save/hidden',
			noCache: 1,
			
			onComplete: function(res)
			{
				if(res)
				{
					this.tabButtons['images'].stopTip(_('Shown'));
					this.imageFields[index].hidden = 0;
					
					iF.iconHidden.hide();
					iF.btnHide.show();
					iF.btnShow.hide();
					iF.oImg.removeClass('image-hidden');
				}
				else
					this.tabButtons['images'].stopTip(_('Failed'));
				
			}.bind(this)
		}).post({
			rsn: iF.rsn,
			hidden: 0
		});
	},
	
	hideImage: function(event, index)
	{
		this.tabButtons['images'].startTip(_('Hiding image ...'));
		var iF = this.imageFields[index];
		
		new Request.JSON({
			url: this.pGlobal+'save/hidden',
			noCache: 1,
			
			onComplete: function(res)
			{
				if(res)
				{
					this.tabButtons['images'].stopTip(_('Hidden'));
					this.imageFields[index].hidden = 1;
					
					iF.iconHidden.show();
					iF.btnHide.hide();
					iF.btnShow.show();
					iF.oImg.addClass('image-hidden');
				}
				else
					this.tabButtons['images'].stopTip(_('Failed'));
				
			}.bind(this)
		}).post({
			rsn: iF.rsn,
			hidden: 1
		});
	},
	
	deleteImage: function(event, index)
	{
		this.tabButtons['images'].startTip(_('Deleting image ...'));
		
		new Request.JSON({
			url: this.pGlobal+'delete/image',
			noCache: 1,
			
			onComplete: function(res)
			{
				if(res)
				{
					this.tabButtons['images'].stopTip(_('Deleted'));
					
					// Reload image list
					this.loadAlbumImages();
				}
				else
					this.tabButtons['images'].stopTip(_('Failed'));
			}.bind(this)
		}).post({
			rsn: this.imageFields[index].rsn
		});
	},
	
	loadAlbumLastModified: function()
	{
		lastLoadAlbumLastModified = this.lastLoadAlbumLastModified
		
		if(lastLoadAlbumLastModified) // Cancel request
			lastLoadAlbumLastModified.cancel();
		
		lastLoadAlbumLastModified = new Request.JSON({
			url: this.pGlobal+'load/lastModified',
			noCache: 1,
			
			onComplete: function(res)
			{
				var dateModified = new Date();
				dateModified.setTime(res.modified * 1000);
				var strModified = dateModified.format('%a, %d %b %Y, %H:%M') + ' ' + _('by') + ' ' + res.modifier;
				
				this.tabFields['general']['modified'].main.getElement('div.desc').set('text', strModified);
			}.bind(this)
		}).post({
			a: this.currentAlbum
		});
		
		this.lastLoadAlbumLastModified = lastLoadAlbumLastModified;
	},
	
	addCategory: function() 
	{
		this.win._prompt(_('Category title'), '', function(res){
			if(!res)
				return;
			
			this.btnCategoryAdd.startTip(_('Creating ...'));
			
			new Request.JSON({
				url: this.pGlobal+'add/category',
				noCache: 1,
				
				onComplete: function(res) 
				{
					if(res)
					{
						this.btnCategoryAdd.stopTip(_('Created'));
						this.doSearchCategories();
					}
					else
						this.btnCategoryAdd.stopTip(_('Already exists'));
				}.bind(this)
			}).post({
				title: res
			});
		}.bind(this));
	},
	
	addAlbum: function()
	{
		this.win._prompt(_('Album title (Selected category will be used)'), '', function(res){
			if(!res)
				return;
			
			this.btnAlbumAdd.startTip(_('Creating ...'));
			
			new Request.JSON({
				url: this.pGlobal+'add/album',
				noCache: 1,
				
				onComplete: function(res)
				{
					if(res)
					{
						this.btnAlbumAdd.stopTip(_('Created'));
						this.doSearchAlbums();
					}
					else
						this.btnAlbumAdd.stopTip(_('Failed'));
				}.bind(this)
			}).post({
				c: this.listCategories.value,
				title: res
			});
		}.bind(this));
	},
	
	setInfoFields: function(event, info)
	{
		if(!info.h)
		{
			info.h = _('Info');
			info.i = ''
		}
		
		this.infoPanelHeader.set('text', info.h);
		this.infoPanelBody.set('html', info.i);
	},
	
	setType: function(event, type)
	{
		this.type = type;
		this.tabButtons.each(function(button, id) {
			button.setPressed(false);
			this.tabPanes[id].setStyle('display', 'none');
		}.bind(this));
		
		this.tabButtons[type].setPressed(true);
		this.tabPanes[type].setStyle('display', 'block');
		
		if(type == 'order')
			this.setInfoFields(null, {h: 'Ordering', i: 'To order the images in this album, drag the image to the desired place and drop it. <br /><strong style="color: #d33;">Note:</strong> hidden images are marked with a red border.'});
	},
	
	saveAlbum: function() 
	{
		this.albumGeneralSaved = false;
		this.albumImagesInfoSaved = false;
		this.albumImagesOrderSaved = false;
		
		this.btnSave.startTip(_('Saving ...'));
		this.saveAlbumGeneral();
		this.saveAlbumImagesInfo();
		this.saveAlbumImagesOrder();
	},
	
	saveAlbumGeneral: function()
	{
		// Save general options
		new Request.JSON({
			url: this.pGlobal+'save/album',
			noCache: 1,
			
			onComplete: function(res)
			{
				if(res)
				{
					this.albumGeneralSaved = true;
					this.saveAlbumSuccess();
					
					// Load new last modified time
					this.loadAlbumLastModified();
					
					// Reload album list, title could be updated
					this.searchAlbums.fireEvent('keyup');
				}
				else
				{
					// Show failed tooltips
					this.tabButtons['general'].startTip(_('Failed'));
					this.tabButtons['general'].stopTip(_('Failed'));
					this.btnSave.stopTip(_('Failed'));
				}
			}.bind(this)
		}).post({
			rsn: this.currentAlbum,
			title: this.tabFields['general']['title'].getValue(),
			description: this.tabFields['general']['description'].getValue(),
			category: this.tabFields['general']['category'].getValue(),
			hidden: this.tabFields['general']['hidden'].getValue(),
			show: this.tabFields['general']['show'].getValue(),
			hide: this.tabFields['general']['hide'].getValue()
		});
	},
	
	saveAlbumImagesInfo: function()
	{
		// Save images
		var imgs = {};
		
		this.imageFields
		.each(function(fields, index) {
			var info = {};
			
			info.rsn = fields.rsn;
			info.title = fields.title.getValue();
			info.description = fields.description.getValue();
			
			imgs[index] = info;
		});
		
		// Send request to save
		new Request.JSON({
			url: this.pGlobal+'save/imagesInfo',
			noCache: 1,
			
			onComplete: function(res) {
				if(res)
				{
					this.albumImagesInfoSaved = true;
					this.saveAlbumSuccess();
				}
				else
				{
					// Show failed tooltips
					this.tabButtons['images'].startTip(_('Failed'));
					this.tabButtons['images'].stopTip(_('Failed'));
					this.btnSave.stopTip(_('Failed'));
				}
			}.bind(this)
		}).post({
			info: imgs
		});
	},
	
	saveAlbumImagesOrder: function()
	{
		var oImgs = {};
		
		this.imageOrderContainer.getElements('img')
		.each(function(img, index) {
			var info = {};
			
			var id = parseInt(img.id.substring(3));
			info.rsn = this.imageFields[id].rsn;
			info.order = index;
			
			oImgs[index] = info;
		}.bind(this));
		
		Log(oImgs);
		
		// Send request to save order
		new Request.JSON({
			url: this.pGlobal+'save/imagesOrder',
			noCache: 1,
			
			onComplete: function(res) {
				if(res)
				{
					this.albumImagesOrderSaved = true;
					this.saveAlbumSuccess();
				}
				else
				{
					// Show failed tooltips
					this.tabButtons['order'].startTip(_('Failed'));
					this.tabButtons['order'].stopTip(_('Failed'));
					this.btnSave.stopTip(_('Failed'));
				}
			}.bind(this)
		}).post({
			info: oImgs
		});
	},
	
	saveAlbumSuccess: function()
	{
		if(this.albumGeneralSaved && this.albumImagesInfoSaved && this.albumImagesOrderSaved)
		{
			this.btnSave.stopTip(_('Saved'));
			this.loadAlbumImages();
		}
	},
	
	deleteAlbum: function()
	{
		this.win._confirm(_('Are you sure you want to delete this album?'), function(res) {
			if(!res) // Cancel pressed
				return;
			
			// Set tooltip
			this.btnDelete.startTip(_('Deleting ...'));
			
			// Request delete
			new Request.JSON({
				url: this.pGlobal+'delete/album',
				noCache: 1,
				
				onComplete: function(res)
				{
					if(res)
					{
						this.currentAlbum = 0;
						
						// Stop tip
						this.btnDelete.stopTip(_('Deleted'));
						
						// Hide windows
						this.tabGroup.hide();
						this.saveGroup.hide();
						
						// Hide panels
						this.tabPanes.each(function(pane) {
							pane.setStyle('display', 'none');
						});
						
						// Refresh album list
						this.searchAlbums.fireEvent('keyup');
					}
					else
						this.btnDelete.stopTip(_('Failed'));
				}.bind(this)
			}).post({
				a: this.currentAlbum
			})
			
		}.bind(this));
	},
	
	blergh: function()
	{
		Log("Blergh");
	}
	
});

/** Image usage
<img src="{$path}{imageResize file=$imagePath dimension="250x500"}"/>
or
<img src="{$path}{imageResize file=$imagePath dimensin="50x50" thumbnail="1"}"/>
*/
