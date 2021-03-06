
/** hide _fid */
if(window.history.replaceState){l=window.location.toString();u=l.indexOf('_fid=');if(u!=-1){u=l.substr(0,u)+l.substr(u+10);if(u.substr(u.length-1)=='?'||u.substr(u.length-1)=='&')u=u.substr(0,u.length-1);window.history.replaceState('',document.title,u)}}

function executeLoadQueue() {
	$.each(onLoadQueue, function(i, c) {
		c();
	});
	$.each(onLoadQueuePersistent, function(i, c) {
		c();
	});
	onLoadQueue = [];
}
$(executeLoadQueue);

Nette.addError = function(elem, message) {
	if (elem.focus) {
		elem.focus();
	}
	if (message) {
		var id = $(elem).closest('form').data('errors');
		var $node = $(id);
		if (id && $node) {
			$node.text(message);

		} else {
			alert(message);
		}
	}
};

if (typeof console == "undefined") {
    window.console = {
        log: function() {},
        info: function() {},
        error: function() {},
        warn: function() {},
    };
}

var dropdownIsOpened = false;
function openDropdown() {
	dropdownIsOpened = true;
	$("header .dropdown-trigger").addClass('hover');
	$("header .dropdown").addClass('open');
}

function closeDropdown() {
	$("header .dropdown-trigger").removeClass('hover');
	$("header .dropdown").removeClass('open');
	$("header .dropdown li").removeClass('hover');
	dropdownIsOpened = false;
}

onLoadQueue.push(function() {
	$.nette.init();
    $.nette.ext('mics', {
		complete: function() {
            clearInterval(ticker);
			closeDropdown();
			window.scrollTo(0, 0);
			executeLoadQueue();
		}
	});

	/*if (typeof NREUMQ !== 'undefined') {
	    $.nette.ext('newrelic', {
	        start: function() {
		        NREUMQ = [];
		        NREUMQ.push(["mark", "firstbyte", new Date().getTime()]);
	        },
			complete: function() {
				// TODO: move this so it properly computes load time and DOM time
				NREUMQ.push(["nrfj", "beacon-3.newrelic.com", "518208ceaf", "1993103", "MVZTNkdWWkFVUUFdWwgcchdGQ1tfG3RHW1oSCXkNWFJEU1NXD1BRAFJEDkE=", 0, 10, new Date().getTime(), "", "", "", "", ""]);
				NREUMQ.push(["load", new Date().getTime()]);

				var e = document.createElement("script");
				e.type = "text/javascript";
				e.src = (("http:" === document.location.protocol) ? "http:" : "https:") + "//" + "d1ros97qkrwjf5.cloudfront.net/42/eum/rum.js";
				document.body.appendChild(e);
	        }
	    });
	}*/

	$.nette.ext('spinner', {
	    start: function () {
	    	$("#spinner").removeClass('hidden');
	    },
	    complete: function() {
			$("#spinner").addClass('hidden');
	    }
	});

	$.nette.ext('ga', {
		success: function (payload) {
			var url = window.location.href;
			if (url && !$.nette.ext('redirect')) {
				_gaq.push(['_trackPageview', url]);
			}
		}
	});


	/** highlight anchor if in correct format */
	if (location.hash.indexOf('#hl-') === 0) {
		$(location.hash).addClass("anchor-highlight");
	}

	var isMouseOver = false;
	$("header .dropdown .expandable").on('click', function(e) {
		var $row = $(e.target).parent();
		$row.parent().find('li').removeClass('hover');
		$row.addClass('hover');
	});
	$("header .dropdown-trigger").on('click', function() {
		if (dropdownIsOpened) {
			closeDropdown();
		} else {
			openDropdown();
		}
		return false;
	});

	$("body").on('click', function(e) {
		closeDropdown();
	});
	$("header .dropdown>li").on('click', function(e) {
		e.stopPropagation();
	});
	$("header .dropdown").menuAim({
	     activate: function(row) {
	     	$(row).addClass('hover');
	     },
	     deactivate: function(row) {
	     	$(row).removeClass('hover');
	     },
	     rowSelector: "> li.expandable",
	     submenuSelector: "*",
	     submenuDirection: "right"
	 });

	//console.info('Ahoj,\n\nočividně tě zajímá, jak funguje Khanova škola uvnitř. Nechceš s námi spolupracovat?\nNapiš nám na dev@khanovaskola.cz.\n\nS pozdravem,\nMikuláš Dítě');
});
