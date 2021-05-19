function smfreplaceLinks() {
    let t = jQuery(smf_outer)[0];
    let links = Array.from(t.getElementsByTagName("A"));
    let tour = [];
    links.forEach(link => {
        let content = link.innerHTML;
        let parent = link.parent;
        let href = link.getAttribute("href");
        let match = href.match(/https:\/\/deep-map.*place=(.*)/);
        let place = match && match[1];
        if (place) {
            let span = document.createElement("span");
            span.innerHTML = content;
            span.className = "mapFrameLink";
            span.title = "Show on map"
            link.replaceWith(span);
            span.setAttribute("onclick", "smfgo('" + place + "')");
            tour.push(place);
        }
    });
    return tour;
}
function smfinit() {
    smfinsertcss();
    smfinsertMap();
    let places = smfreplaceLinks();
    setTimeout(() => {
        document.getElementById("ifr").contentWindow.postMessage({ op: "tour", places: places }, "*");
    }, 6000);
}
function smfgo(target) {
    let f = document.getElementById("ifr");
    f.contentWindow.postMessage({ op: "gotoPlace", placeKey: target, show: false }, "*");
}

function smfinsertcss() {
    var head = document.getElementsByTagName('head')[0];
    var script = document.createElement('link');
    script.rel = "stylesheet";
    script.type = "text/css";
    script.href = "/wp-content/plugins/span-map-frame/includes/css/span-map-frame.css"
    head.appendChild(script);
}

function smfinsertMap() {
    let body = jQuery(smf_outer)[0];
    let content = jQuery(smf_inner)[0];
    let grid = document.createElement("div");
    grid.className = "mapFrameGrid";
    let contentContainer = document.createElement("div");
    contentContainer.className = "mapFrameContent";
    grid.append(contentContainer);
    contentContainer.append(content);
    body.append(grid);
    let frameDiv = document.createElement("div");
    frameDiv.innerHTML = '<iframe id="ifr" src="https://deep-map.azurewebsites.net/?notrack=1&noindex=1&nouser=1&nosearch=2&project=' + smf_project + '" class="mapFrame"></iframe>';
    grid.append(frameDiv);
}



jQuery(function () {
    jQuery(document).on('qodeAjaxPageLoad', smfinit);
    smfinit();
});



