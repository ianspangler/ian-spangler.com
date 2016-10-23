///////////////////////////////		
// Set Variables
///////////////////////////////


var gridContainer = jQuery('.thumbs.masonry');
var colW;
var gridGutter = 0;
//var thumbWidth = 1000;
var widgetsHidden = false;


///////////////////////////////		
// Mobile Detection
///////////////////////////////

function isMobile(){
	
    return (
        (navigator.userAgent.match(/Android/i)) ||
		(navigator.userAgent.match(/webOS/i)) ||
		(navigator.userAgent.match(/iPhone/i)) ||
		(navigator.userAgent.match(/iPod/i)) ||
		(navigator.userAgent.match(/iPad/i)) ||
		(navigator.userAgent.match(/BlackBerry/))
    );
}

///////////////////////////////
// Project Filtering 
///////////////////////////////

function projectFilterInit() {

	//pre-select filter
	if (typeof(selected_skill_slug) != "undefined" && selected_skill_slug != "") { 
		var index = jQuery('#filterNav li[data-filter=".'+selected_skill_slug+'"]').index(); 
	} else {
		var index = 0; 
	}


	//jQuery("ul#filterNav").find("a").eq(index).click(); // TODO find a fix without this code

	var selector = jQuery("#filterNav").find("a").eq(index).attr('data-filter');
	filterProjects(selector);

	if ( !jQuery("#filterNav").find("a").eq(index).hasClass('selected') ) {
		jQuery('#filterNav').find('.selected').removeClass('selected');
		jQuery("#filterNav").find("a").eq(index).addClass('selected');
	}


	jQuery('#filterNav a').click(function(){
		var selector = jQuery(this).attr('data-filter');
		filterProjects(selector);

		if ( !jQuery(this).hasClass('selected') ) {
			///console.log("CLICKED");
			jQuery(this).parents('#filterNav').find('.selected').removeClass('selected');
			jQuery(this).addClass('selected');
		}
	
		return false;
	});	
	


	//draggable filter nav (for mobile)
	if(isMobile() && jQuery('select#filterNav').is(':visible')) {
		
		jQuery('select#filterNav').select2({minimumResultsForSearch: -1, placeholder: "Select"});
		//jQuery('select#filterNav').select2({minimumResultsForSearch: -1, placeholder: "Select"});

		if (typeof(selected_skill_name) != "undefined" && selected_skill_name != "") {
			//console.log("selected_skill_name: "+selected_skill_name);
			jQuery('select#filterNav').select2("val", selected_skill_name);
			var selector = jQuery("select#filterNav").select2({minimumResultsForSearch: -1}).find(":selected").data("filter");
			filterProjects(selector);
		}

		jQuery('select#filterNav').change(function(){
			var selector = jQuery("select#filterNav").select2({minimumResultsForSearch: -1}).find(":selected").data("filter");
			filterProjects(selector);

			return false;
		});
	}


}

function filterProjects(selector) {

	jQuery('#projects .thumbs').isotope({
		filter: selector,			
		hiddenStyle : {
	    	opacity: 0,
	    	scale : 1
		}			
	});
}


///////////////////////////////
// Project thumbs 
///////////////////////////////

function projectThumbInit() {
	
	setColumns();	
	gridContainer.isotope({		
		resizable: false,
		layoutMode: 'fitRows',
		masonry: {
			columnWidth: colW
		}
	});	

	//jQuery(".project.small").css("visibility", "visible");	
	jQuery("#floatingCirclesG").fadeOut("slow");

	if (!isMobile()) {

		//rollover thumbs
		jQuery(".project.small").hover(
			function() {
				//jQuery(this).find('button').css('border', '1px solid rgba(255,255,255,0.9)');
				//jQuery(this).find('.overlay').stop().fadeTo("fast", 0.8);
				
				/*jQuery(this).find('.description').stop().fadeTo("fast", 1);
				jQuery(this).find('img:last').attr('title','');	*/
			},
			function() {
				//jQuery(this).find('button').css('border', '1px solid rgba(255,255,255,0.5)');
				//jQuery(this).find('.overlay').stop().fadeTo("fast", 0);	
				//jQuery(this).find('.description').stop().fadeTo("fast", 0);	
			});	
	}

	if (show_intro == 'Y') {

		animateIn(); 
	} else { 

		showAll(); 
	}

	
}

function showAll() {

	/*jQuery('#letters_mask').hide();
	jQuery('#mainNav').show();
	jQuery('#footer').show();
	jQuery('.filter_wrap').show();
	jQuery('#sidebar').show();*/
	jQuery('.project.small').css('pointer-events', "auto");
	jQuery('.project.small').css('opacity', 1);
	jQuery('.logo_subline').show();

}

function animateIn() {

	if(!isMobile()) { //desktop

		//animate in main sidebar
		jQuery("#header .inside").delay(200).fadeIn(500);

		//animate my logo
		jQuery('#logo #i_left').delay(600).animate({height:0, top:'183px'}, 200, "easeInSine");
		jQuery('#logo #s_bottom').delay(780).animate({width:0, left:'168px'}, 100);
		jQuery('#logo #s_right').delay(860).animate({height:0}, 100);
		jQuery('#logo #s_middle').delay(940).animate({width:0}, 100);
		jQuery('#logo #s_left').delay(1020).animate({height:0}, 100);
		jQuery('#logo #s_top').delay(1100).animate({width:0, left:'183px'}, 100);
		jQuery('#logo #i_right').delay(1260).animate({height:0, top:'183px'}, 300, "easeOutSine");

		jQuery('.logo_subline').delay(1600).fadeIn(600);

		//animate in main nav and filter nav
		jQuery('#mainNav').delay(1600).fadeIn(1000);
		//jQuery('ul#filterNav').delay(1800).fadeIn(1000);
		jQuery('.filter_wrap').delay(2600).animate({opacity: 1}, 800);
		jQuery('.projectPage').delay(2600).animate({opacity: 1}, 800);

		var projects_delay = 2600;

	}
	else { //mobile

		//animate in main sidebar and filter nav
		///jQuery("#header").delay(200).animate({opacity: 1}, 450, "easeOutSine");
		jQuery("#header .inside").delay(200).fadeIn(450, "easeOutSine");
		jQuery('.filter_wrap').delay(800).animate({opacity: 1}, 450, "easeOutSine");
		jQuery('.projectPage').delay(1600).animate({opacity: 1}, 800);
		//jQuery('.filter_wrap').fadeIn(500);

		var projects_delay = 1000;
	}
	

	//animate in widgets and footer
	jQuery('#sidebar').delay(1600).fadeIn(1000);
	jQuery('#footer').delay(1800).fadeIn(1000);


	//animate in project thumbs 
	jQuery(".project.small").each(function() {

		//jQuery(this).css('margin-top', (jQuery(this).height()/4)+'px');
		//jQuery(this).css('margin-left', (jQuery(this).width()/4)+'px');
		//jQuery(this).css('width', '50%');

		if (jQuery(this).is(':visible')) {

			jQuery(this).delay(projects_delay).animate({opacity: 1}, 400, "easeOutSine", 
					function() {  });

			projects_delay += 250;
		}
	});


}

///////////////////////////////////
// Theme fixed position adjustment
///////////////////////////////////
function sidebarAbsolute(firstRun) {
	var viewH = jQuery(window).height(), screenH = jQuery(document).height(), header = jQuery("#header");
	if ( header.height() > viewH && header.height() < screenH ) {
		if (firstRun) { screenH = screenH + 200; }
		header.css( { "position" : "absolute", "height" : screenH + "px" } );
	} else {
		header.attr("style", "");
	}
}

///////////////////////////////////
// Relocate Elements
///////////////////////////////////

function relocateElements()
{	
	if(jQuery('#container').width() <= 768) {
		jQuery('#sidebar').insertAfter(jQuery('#content'));	
		widgetsHidden = true;
	}
	else if(widgetsHidden) {
		jQuery('#sidebar').insertAfter(jQuery('#mainNav'));
	} 	
}

///////////////////////////////
// Isotope Grid Resize
///////////////////////////////

function setColumns()
{	
	/*var columns;
	columns = Math.ceil(gridContainer.width()/thumbWidth);
	colW = Math.floor(gridContainer.width() / columns);
	jQuery('.thumbs.masonry .project.small').each(function(id){
		jQuery(this).css('width',colW-gridGutter+'px');
	});*/

	var columns = 2;

	if(isMobile()) { //mobile
		columns = 1;
	}

	colW = Math.floor(gridContainer.width() / columns);
	jQuery('.thumbs.masonry .project.small').each(function(id){
		jQuery(this).css('width',colW-gridGutter+'px');
	});

}

function gridResize() {	
	setColumns();
	gridContainer.isotope({
		resizable: false,
		masonry: {
			columnWidth: colW
		}
	});		
}

///////////////////////////////
// SlideNav
///////////////////////////////

function setSlideNav(){
	jQuery("a.menuToggle").pageslide({ direction: "left"});
}

///////////////////////////////
// Initialize Page
///////////////////////////////	

jQuery.noConflict();
jQuery(window).ready(function(){
	jQuery(".videoContainer").fitVids();
});

jQuery(window).load(function(){	
	
	projectFilterInit();	
	projectThumbInit();	
	//sidebarAbsolute(1);
	relocateElements();
	setSlideNav();

	if (isMobile()) {
		//fix background for iOS
		jQuery(window).scroll(function() {
		  	var scrolledY = jQuery(window).scrollTop();
		  	jQuery('body.custom-background').css('background-position', '50% ' + scrolledY + 'px');
			//console.log(jQuery('body.custom-background').css('background-position'));
		});
	}

	
	jQuery(window).smartresize(function(){
		gridResize();
		sidebarAbsolute();
		relocateElements();
	});		


	jQuery('.contact a').tooltip(
	{
		content: function() {
	
			var html = '<div class="pointer-container"><div class="pointer"></div></div>'
			html += '<div class="content">';
			html += jQuery(this).attr('title');
			html += '</div>';
			return html;

		},
		show: { duration: 150, delay: 20 },

		tooltipClass: "contact_tooltip",

		position: {my: "center bottom-12", at: "center bottom-12", collision: "flipfit",
					using: function( position, feedback ) {

		            jQuery(this).css( position );
		            jQuery('.pointer-container')
		                .addClass( feedback.vertical )
		                .addClass( feedback.horizontal );
		                ///.appendTo( this );
		        }
		}
	});

});


function launchPopup(width,height) {
	
	event.preventDefault();
	window.open(event.target, 'dialog', 'toolbar=0,status=0,scrollbars=no,width='+width+',height='+height+',resize=no'); 

}

// SET EMAIL LINK
var username = "ian";
var hostname = "ian-spangler.com";
jQuery('.email_link').attr('href', "mailto:" + username + "@" + hostname);



//old tooltip code
/*jQuery('.project.small a').tooltip(
{
		content: function() {
	
			var html = '<div class="pointer-container"><div class="pointer"></div></div>'
			html += '<div class="content">';
			html += '<h3>'+jQuery(this).attr('title')+'</h3>';
			html += '<p>'+jQuery(this).parent().find('.description p').html()+'</p>';
			html += '</div>';
			return html;

		},
		show: { duration: 300, delay: 20 },
		tooltipClass: "project_tooltip",
		position: {my: "left+3 bottom+48", at: "left+3 bottom+48", collision: "flipfit",
					using: function( position, feedback ) {

		            jQuery(this).css( position );
		            jQuery('.pointer-container')
		                .addClass( feedback.vertical )
		                .addClass( feedback.horizontal );
		                ///.appendTo( this );
		        }
		}
});*/




