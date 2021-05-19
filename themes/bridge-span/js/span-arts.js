
/** WPML standard language switcher doesn't take account of
 *  Bridge partial load. Without this, switcher is stuck with
 * link it had when first page was loaded. */
 function languageSwitcherFixer() {
    let target = "";
    let m;
    if (m = window.location.pathname.match(/\/cy(\/.*)/)) {
        target = m[1];
    } else {
        target = "/cy" + window.location.pathname;
    }
    jQuery(".wpml-ls-link").attr("href", target);
    jQuery(".wpml-ls-native").parent().attr("href", target);
}


/** Called on partial load of page. 
 * Bridge doesn't generally reload header and footer, but
 * this is called on content load. */
function spanfixes() {

    languageSwitcherFixer();

    /* === Colour titles === */
    // event titles and borders
    //jQuery(".eventItem").each(function(i){this.classList.add("colour" + (i%3))});

    // section heads
    function colorTitle(i) { this.classList.add("colour" + (i % 3)); }
    jQuery(".vc_row:not(.bigheads) h1:not(.ls-layer)").each(colorTitle);
    jQuery(".colour .eventItem").each(colorTitle);
    jQuery(".colour h2, .colour h3, .colour h4").each(colorTitle);

    /* === Click through event items === */
    jQuery(".eventItem").each(function () {
        // Target location is in data-url
        if (this.dataset.url) {
            this.addEventListener("click", function () {
                if (!window.inhibitClick) location.assign(this.dataset.url);
            });
        }

        //if (jQuery(this).height() > 330) {
        let jqtitle = jQuery(this).find(".eventTitle");
        let t = jqtitle.text();
        let bits = t.split(/:| -/, 2);
        if (bits.length > 1) {
            jqtitle.html(bits[0] + `<br/><span class="eventSubtitle">${bits[1]}</span>`);
        }
        //}
    });

    jQuery(".staff .eventItem").each(function () {
        let jqthis = jQuery(this);
        let titleText = jqthis.find(".eventTitle").text();
        let subtitle = jqthis.find(".subtitle");
        if (subtitle.text().startsWith(titleText)) {
            subtitle.text(subtitle.text().replace(titleText, "").replace(/^[ ,]+/, ""));
        }
    });

    /*
        jQuery(".eventList").filter(".horizontal").draggable({
            axis: "x",
            drag: function (event, ui) {
                ui.position.left = Math.min(0, Math.max(ui.position.left, 0 - this.lastElementChild.offsetLeft))
            },
            stop: function () { window.inhibitClick = window.setTimeout(() => { window.inhibitClick = null; }, 500); }
        });
    */

    /* JQuery UI drag doesn't  work with touchscreens. So here's our drag code. */
    var mousedown = false, clickX, origX, tstart, clientX;
    jQuery(".eventList").filter(".horizontal").on({
        'mousemove': function (e) {
            if (mousedown) {
                let jtarget = jQuery(this);
                //jtarget.css('cursor', 'row-resize');
                jtarget.scrollLeft(origX + (clickX - e.pageX));

                // inhibit description display:
                jtarget.addClass("ui-draggable-dragging");
            }
        },
        'mousedown': function (e) {
            e.preventDefault();
            mousedown = true;
            clickX = e.pageX;
            origX = jQuery(this).scrollLeft();
            tstart = Date.now();
            clientX = e.clientX;
        },
        'mouseup': function () {
            if (mousedown) {
                mousedown = false;
                let jtarget = jQuery(this);
                jtarget.removeClass("ui-draggable-dragging");
                // Interpret this as a click if we haven't dragged very far and only briefly:
                if (!(Date.now() - tstart < 500 && Math.abs(jtarget.scrollLeft() - origX) < 20)) {
                    window.inhibitClick = window.setTimeout(() => { window.inhibitClick = null; }, 500);
                }
                else {
                    // But wait ... it might be a click at one end or the other:
                    let leftEdge = jtarget.offset().left;
                    let rightEdge = leftEdge + jtarget.width();
                    if (clientX - leftEdge < 40) kickSideways(this, -1);
                    else if (rightEdge - clientX < 40) kickSideways(this, 1);
                    //jtarget.css('cursor', 'auto');
                }
            }
        },
        'mouseleave': function () {
            mousedown = false;
            jQuery(this).removeClass("ui-draggable-dragging");
        }
    });

    jQuery(".sa_scrollButton").click (function(e) {
        let direction = this.className.indexOf("sa_scrollerLeft")>=0 ? 1 : -1;
        let ul = jQuery(this).parent().children("ul")[0];
        if (ul) kickSideways(ul, direction);
    });

    function kickSideways(target, direction) {
        window.inhibitClick = window.setTimeout(() => { window.inhibitClick = null; }, 500);
        let jtarget = jQuery(target);
        let width = target.clientWidth || 400;
        clearTimeout(window.smoothScrollTimeout);
        jtarget.addClass("smoothie");
        jtarget.scrollLeft(jtarget.scrollLeft() + (width / 2) * direction);
        window.smoothScrollTimeout = setTimeout(() => jtarget.removeClass("smoothie"), 500);
    }

    function addScrollButton(jparent, glyph, style) {
        let r = document.createElement("div");
        r.className = "scrollButton";
        r.innerHTML = glyph;
        r.style = style;
        jparent.append(r);
    }

    // Show chevrons
    //jQuery(".eventList.horizontal").parent().children(".scrollButton").remove();
    jQuery(".eventList.horizontal").filter(function () {
        return this.scrollWidth < this.offsetWidth && this.scrollWidth < 900;
    }).each(function () {
        jQuery(this).hide();
        /*let jparent = jQuery(this).parent();
        addScrollButton(jparent, "&nbsp;❱", "left:auto;right:0;margin-right:-20px;");
        addScrollButton(jparent, "❰&nbsp;", "left:0;right:auto;margin-left:-20px");
        */
    });

    jQuery(".eventDescription").each(function () {
        let ut = jQuery(this).html();
        ut = ut.replace(/<\/div>|<\/h.>|<\/p>|<br.*?>/g, "¬¬¬");
        ut = ut.replace(/<.*?>/sg, "");
        ut = ut.replace(/&nbsp;/g, " ");
        ut = ut.replace(/(¬¬¬\s*)+/gs, "¬¬¬");
        ut = ut.replace(/^[\s¬]+/, "").replace(/[\s¬]+$/, "");
        ut = ut.replace(/http.?:\/\/[^\s]*/, "");
        ut = ut.replace(/¬¬¬/g, "<br/>");
        ut += "<div style='line-height:6px;margin:0;'>&nbsp;</div>";
        jQuery(this).html(ut.trim());
    });

    jQuery(".boxcolumns.showSomeBelow").each(function() {
        this.addEventListener("click", sa_expandShowSomeBelowBox);
    })

    if (headerSize && !window.shimheadersize) {
        window.shimheadersize = headerSize;
        headerSize = function (e) {
            window.shimheadersize(e);
            let logowrapper = $j("header:not(.centered_logo.centered_logo_animate) .logo_wrapper");
            if (logowrapper.length) {
                let h = logowrapper.height();
                logowrapper.css("margin-top", (Math.min(0,e-22))+"px");
            }
        }
    }

    
	// Add accessibility titles to social icons
	const socials = ["Facebook", "Twitter", "Instagram", "YouTube", "Vimeo"];
	const socialsRE = socials.map(x => new RegExp(x.toLowerCase(), 'i'));
	jQuery(".q_social_icon_holder").children("a").each(function() {
		let href = this.getAttribute("href");
		for (let i = 0; i<socials.length; i++) {
			if (href.match(socialsRE[i])) {
				this.setAttribute("title", socials[i]);
				break;
			}
		}
	});
	jQuery(".socialButtonAM").each(function() {
		this.setAttribute("title", "AM");
	});
	jQuery(".search_button").each(function() {
		this.setAttribute("title", "Search | Chwilio");
	});
	
};

function sa_expandShowSomeBelowBox(o, e) {
    let box = jQuery(o.target).parents(".vc_column-inner")[0];
    if (!box) return;

    if (/expanded/.test(box.className)) {
        box.classList.remove("expanded");
    } else {
        box.classList.add("expanded");
    }
}

jQuery(function () {
    jQuery(document).on('qodeAjaxPageLoad', spanfixes);
    spanfixes();
});

window.onresize = () => {
    if (window.resizing) return;
    window.resizing = setTimeout(() => {
        window.resizing = null;
        spanfixes();
    }, 1000);
}


