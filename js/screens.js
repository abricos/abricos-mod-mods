/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	yahoo: ['dom'],
	mod:[
        {name: 'sys', files: ['container.js', 'wait.js']}
	]	
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;
	
	var initScreen = function(elScreen, src, title){
		title = title || 'Screen '+src;
		E.on(elScreen, 'click', function(evt){
			E.preventDefault(evt);
			
			var lw = new Brick.widget.LayWait(elScreen);

			var imgBig = document.createElement('img');
			imgBig.onload = function(){
				lw.hide();
				
				var w = imgBig.width*1,
					h = imgBig.height*1;
				
				if (w == 0 || h == 0){ return; }
				w += 20; h += 50;
				
				var pnl = new YAHOO.widget.Panel("wait", { 
					'width': w+'px', 'height': h+'px',
					close: true, 
					draggable:false, 
					zindex:4000,
					modal:false,
					visible:false,
					overflow: true
				});
				pnl.setHeader(title);
				pnl.setBody('<img src="'+src+'" />');
				pnl.render(document.body);
				pnl.center();
				pnl.show();
				E.on(pnl.element, 'click', function(){
					pnl.hide();
					pnl.destroy();
				});
			};
			imgBig.src = src;
		});
	};
	
	NS.API.initScreens = function(){
		var els = Dom.getElementsByClassName('screen');
		for (var i=0;i<els.length;i++){
			var elScreen = els[i],
				elImgs = Dom.getElementsByClassName('thumb', 'img', elScreen),
				elAs = Dom.getElementsByClassName('bshowbig', 'a', elScreen);
			
			if (!elImgs[0] || !elAs[0]){ continue; }
			
			initScreen(elScreen, elAs[0].href, elAs[0].title);
		}
	};

};