document.observe('dom:loaded', function() {
    $$('a[rel=popup]').each(function(el) {
        el.href = el.href + '?popup=1';
        el.onclick = function(linkEl) {
            var w = new Window({
                className:'zeno',
                title:'',
                width:820,
                height:480,
                minimizable:false,
                maximizable:false,
                showEffectOptions:{duration:0.4},
                hideEffectOptions:{duration:0.4},
                options: {method: 'get'},
                url: this.href,
            });
            w.setAjaxContent(this.href, {method: 'get'}, true, true);
            return false;
        }
    });
});