function spanAddBanner() {
    if (document.cookie.match("spanBannerClosed")) return;
    if (jQuery("#spanBetaBanner").length) return;
	let pp = window.location.pathname.match(/\/cy\//) 
		? ["Hoffi'n gwefan newydd?", 
        "Mae'n dal i gael ei ddatblygu ...", 
        "Rhowch wybod i ni beth 'dych chi'n feddwl!"]
		: ["Like our new website?", 
		   "It's still being developed ...", 
		   "Let us know what you think!"];
    let css = jQuery("<style>#spanBetaBanner {position:fixed;display:flex;flex-wrap:wrap;justify-content:space-around;width:100%;bottom:0px;left:0px;" 
    + "background-color:aliceblue;color:gray;z-index:1000; line-height:2em;text-align:center;" 
    + "box-shadow:0 -5px 5px 0 rgba(120,120,120,0.5);}" 
    + "#spanBannerCloser {border:1px solid grey;cursor:pointer;line-height:1.5;font-size:small;position:absolute;width:20px;top:50%;margin-top:-10px;right:2px;text-align:center;border-radius:4px;}"
    + "#spanBannerCloser:hover {box-shadow:inset 0 0 10px #000000}</style>");
    let banner = jQuery("<div id='spanBetaBanner'>" 
    + `<div style='color:#ff4040'>${pp[0]}</div> <div style='color:#ff8419'>${pp[1]}</div> <div>`
        + `<a href='mailto:info@span-arts.org.uk?subject=gwefan%20newydd%20/%20new%20website'>${pp[2]} &nbsp;&nbsp;`
		+ `<i class='qode_icon_font_awesome fa fa-external-link'></i></a></div>`);
	
    let closer = jQuery("<div id='spanBannerCloser' onclick='spanCloseBanner()'>X<div>");
    banner.append(closer);
    jQuery("body").append(css, banner);    
}
function spanCloseBanner () {
    jQuery("#spanBetaBanner")[0].style.display="none";
    document.cookie = "spanBannerClosed=1;path=/";
}

jQuery(function() {
    jQuery(document).on("qodeAjaxPageLoad", spanAddBanner);
    spanAddBanner();
})
