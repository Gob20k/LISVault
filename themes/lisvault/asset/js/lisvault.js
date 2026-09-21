document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.querySelector('.lv-menu-toggle');
    var nav = document.getElementById('main-nav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var isOpen = nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    if (!window.pdfjsLib) {
        return;
    }
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    var canvases = document.querySelectorAll('.lv-card-thumb[data-pdf-src]');
    if (canvases.length === 0) {
        return;
    }

    var renderThumbnail = function (canvas) {
        var url = canvas.getAttribute('data-pdf-src');
        if (!url || canvas.dataset.rendered === 'true') {
            return;
        }
        canvas.dataset.rendered = 'true';

        pdfjsLib.getDocument(url).promise.then(function (pdf) {
            return pdf.getPage(1);
        }).then(function (page) {
            var targetWidth = canvas.parentElement.clientWidth || 220;
            var unscaled = page.getViewport({ scale: 1 });
            var scale = targetWidth / unscaled.width;
            var viewport = page.getViewport({ scale: scale });

            canvas.width = viewport.width;
            canvas.height = viewport.height;

            var context = canvas.getContext('2d');
            return page.render({ canvasContext: context, viewport: viewport }).promise;
        }).then(function () {
            canvas.closest('.lv-card-media').classList.add('lv-has-thumb');
        }).catch(function () {
            // PDF failed to load/render — the icon underneath stays visible, nothing else to do.
        });
    };

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    renderThumbnail(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { rootMargin: '200px' });

        canvases.forEach(function (canvas) { observer.observe(canvas); });
    } else {
        canvases.forEach(renderThumbnail);
    }
});