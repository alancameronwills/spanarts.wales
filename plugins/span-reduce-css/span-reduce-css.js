function spanDiscoverCss() {
    let params = (new URL(document.location)).searchParams;
    let go = params.get('listcss');
    if (go) {
		let count = 0;
        let spanTimer = setInterval(() => {
			if (count++ > 60) clearInterval(spanTimer);
            let classes = {};
			let previous = sessionStorage['classList'] || "";
			if (previous) {
				previous.split(" ").forEach(c => {
					classes[c]=1;
				});
			}
            jQuery(document.body).find("*").each(function () {
                if (typeof (this.className) == "string") {
                    this.className.split(" ").forEach(c => {
                        classes[c] = 1;
                    });
                }
            });
			let classArray = Object.keys(classes).sort();
			sessionStorage['classList'] = classArray.join(" ");
            jQuery(document.body).append("<div style='display:none'>"
				+ classArray.length + "\r\n"
                + classArray.join("\r\n")
                + "</div>");
        }, 1000);
    }
}


jQuery(function () {
    jQuery(document).on("qodeAjaxPageLoad", spanDiscoverCss);
    spanDiscoverCss();
})
