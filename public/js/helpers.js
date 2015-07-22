//Common utils function
$(document).ready(function(){
	 
	//Handle Session Timeout
	$(document).ajaxError(function (event, request, options) {
        if (request.status === 401) {
            $("<div title='Session Timed Out' style='vertical-align:middle; text-align:center'>Your Session has Timed out. Please Login to access.</div>").dialog({
                width: 300,
                height: 150,
                modal: true,
                buttons: {
                    Ok: function () {
						window.location.reload();
                        $(this).dialog("close");
                    }
                }
            });
        } else  if (request.status === 403) {
			window.location.reload();
        }
    });
	
	//ajax loader
	$('<div id="ajaxBusy" class="hide">Loading...</div>')
			.ajaxStart(function() {$(this).show();})
			.ajaxStop(function() {$(this).hide();})
			.appendTo('body');
			
	
});

function roundNumber(rnum, rlength) { // Arguments: number to round, number of decimal places
  var newnumber = Math.round(rnum*Math.pow(10,rlength))/Math.pow(10,rlength);
  return parseFloat(newnumber); // Output the result to the form field (change for your purposes)
}


/** 
 * Loads in a URL into a specified divName, and applies the function to 
 * all the links inside the pagination div of that page (to preserve the ajax-request) 
 * @param string href The URL of the page to load 
 * @param string divName The name of the DOM-element to load the data into
 * @param string loader The name of the DOM-element to show loading image 
 * @return boolean False To prevent the links from doing anything on their own. 
 */ 
function loadHtml(href, divSelector, loader, callback) {     
	if(typeof loader != 'undefined') $(loader).fadeIn(200);
	
	$(divSelector).load(href, function(response, status, xhr){ 
		if (status == "error") {
			var msg = "Sorry but there was an error: ";
		}
		
		var divPaginationLinks = divSelector+" .pagination a"; 
		$(divPaginationLinks).unbind("click").click(function() {      
			var thisHref = $(this).attr("href"); 
			if(thisHref!='#.')	loadHtml(thisHref, divSelector, loader, callback); 
			return false; 
		}); 
		
		if(typeof loader != 'undefined') $(loader).fadeOut(200);
		if(typeof callback != 'undefined') callback();
	}); 
	
	return false;
} 


/**
 * This function creates a new anchor element and uses location
 * properties (inherent) to get the desired URL data. Some String
 * operations are used (to normalize results across browsers). 
 */
function parseURL(url) {
    var a =  document.createElement('a');
    a.href = url;
    return {
        source: url,
        protocol: a.protocol.replace(':',''),
        host: a.hostname,
        port: a.port,
        query: a.search,
        params: (function(){
            var ret = {},
                seg = a.search.replace(/^\?/,'').split('&'),
                len = seg.length, i = 0, s;
            for (;i<len;i++) {
                if (!seg[i]) { continue; }
                s = seg[i].split('=');
                ret[s[0]] = s[1];
            }
            return ret;
        })(),
        file: (a.pathname.match(/\/([^\/?#]+)$/i) || [,''])[1],
        hash: a.hash.replace('#',''),
        path: a.pathname.replace(/^([^\/])/,'/$1'),
        relative: (a.href.match(/tp:\/\/[^\/]+(.+)/) || [,''])[1],
        segments: a.pathname.replace(/^\//,'').split('/')
    };
}

