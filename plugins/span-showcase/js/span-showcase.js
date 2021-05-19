class SpanShowcase {
    /**
     * Short for getElementById
     * @param {string} id
     */
    g(id) { return document.getElementById(id); }

    /**
     * Find an existing DOM element or create one
     * @param {*} typeName HTML element type - div etc
     * @param {*} className CSS class or space-separated classes
     * @param {*} id ID of existing element to find or new element to create
     * @param {*} parent append to children of this element
     * @param {*} insertAt (optional) insert before this child
     */
    getOrCreate(typeName, className, id, parent, insertAt) {
        let thing = this.g(id);
        if (thing) return thing;
        thing = document.createElement(typeName);
        thing.id = id;
        if (className) {
            thing.className = className;
        }
        parent.insertBefore(thing, insertAt);
        return thing;
    }
    /**
     * Prepend Span URL if prefix not specified
     * @param {*} u 
     */
    imgUrl(u) {
        if (u.indexOf("http") == 0 || u.indexOf("./") == 0) return u;
        else return "https://www.span-arts.org.uk/wp-content/uploads/" + u;
    }
    locUrl(u) {
        if (u.indexOf("http") == 0 || u.indexOf("./") == 0) return u;
        else return "https://www.span-arts.org.uk/" + u;
    }


    constructor(showcaseDiv, codeUrl, srcUrl, singletonId) {
        let tagLists = {};
        const list = this.getOrCreate("div", null, showcaseDiv, document.body);
        fetch(`${codeUrl}/data/summaries.txt`)
            .then(r => r.json())
            .then(top => {

                top.items.forEach(item => {
                    let url = item.chunk ? srcUrl + item.id : "#";
                    let img = item.img ? `<img class="itemimg" src="${this.imgUrl(item.img)}"/>` : "";
                    let script = `<a href="${url}" target="_new">${img}<img src="${codeUrl}/img/link.png" class="linkicon"/></a><h3>${item.t}</h3>${item.chunk.replace(/<.*/, "")}`;
                    if (item.tags) {
                        item.tags.forEach(tag => {
                            if (!tagLists[tag]) tagLists[tag] = [];
                            tagLists[tag].push({ s: script, i: item.img });
                        });
                    } else {
                        if (!tagLists["new"]) tagLists["new"] = [];
                        tagLists["new"].push({ s: script, i: item.img });
                    }

                });
                let offset = 0;
                for (let tag in top.tags) {

                    let tagList = tagLists[tag];
                    let extraHeadPics = "";
                    for (let ix = 0; ix < Math.min(5, tagList.length); ix++) {
                        let index = (ix + offset) % tagList.length;
                        if (tagList[index].i != top.tags[tag].i) {
                            extraHeadPics += `<img src="${this.imgUrl(tagList[index].i)}"/>`;
                        }
                    }
                    let script = `<div id="s${tag}" onclick="${singletonId}.toggle('${tag}')"  class='sectionhead'>` +
                        `<div><h2>${top.tags[tag].h}</h2>${top.tags[tag].p}</div>` +
                        `<div class="subhead"><img src="${this.imgUrl(top.tags[tag].i)}"/>` +
                        `<div class='extraHeadPics'>${extraHeadPics}</div></div>` +
                        `<div class="expander"><img id="i${tag}" src="${codeUrl}/img/expand.png"/></div></div>`;
                    script += `<div class='sectionbody' style="display:none" id='${tag}'>`;
                    tagList.forEach(item => {
                        script += `<div class="item">${item.s}</div>`;
                    });
                    script += "</div>";
                    let section = document.createElement("section");
                    section.innerHTML = script;
                    list.append(section);
                    offset += 7;
                };
            });

        jQuery("#back_to_top").click(() => spanShowcase.collapseAll()).each(() => {
            // If there is a back-to-top, we don't need ours
            jQuery("#defaultCollapse").hide();
        })

    }

    collapse(section) {
        section.expanded = false;
        section.style.display = "none";
        jQuery("#i" + section.id).removeClass("collapse");
    }
    expand(section) {
        section.expanded = true;
        section.style.display = "flex";
        jQuery("#i" + section.id).addClass("collapse");
        this.g("s" + section.id).scrollIntoView({ behavior: "smooth", block: "start" });
    }
    toggle(tag) {
        let section = this.g(tag);
        if (section.expanded) {
            this.collapse(section);
        } else {
            this.expand(section);
        }
    }
    collapseAll() {
        document.querySelectorAll(".sectionbody").forEach(s => {
            if (s.expanded) {
                this.collapse(s);
            }
        });
        window.scrollTo(0, 0);
    }

}

function createSpanShowcase (showcaseDiv, codeUrl, srcUrl) {
    const singletonId = "spanShowcase";
    window[singletonId] = new SpanShowcase(showcaseDiv, codeUrl, srcUrl, singletonId);
}

