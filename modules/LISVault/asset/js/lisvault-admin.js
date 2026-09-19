document.addEventListener('DOMContentLoaded', function () {
    // ---- Module -> Item Set sync (unchanged, still working) ----
    var moduleSelect = document.getElementById('lisvault-module-select');
    if (moduleSelect) {
        moduleSelect.addEventListener('change', function () {
            var itemSetId = this.value;
            var rows = document.querySelectorAll('#item-sets .selector-child');
            rows.forEach(function (row) {
                var checkbox = row.querySelector('input[type="checkbox"]');
                var rowId = row.getAttribute('data-value');
                if (checkbox && rowId) {
                    checkbox.checked = (rowId === itemSetId);
                }
            });
        });
    }

    // ---- Hide the "Class" field, by its visible label text ----
    // Robust against markup changes since it doesn't depend on a class/id.
    document.querySelectorAll('.field').forEach(function (field) {
        var label = field.querySelector('.field-meta label, label');
        if (label && label.textContent.trim().toLowerCase().startsWith('class')) {
            field.style.display = 'none';
        }
    });

    // ---- Simplify the media section to a single plain "Choose File" ----
    // Hides every ingester tab except Upload (no HTML/URL/oEmbed/YouTube),
    // and auto-opens one blank upload slot on the Add page so admins don't
    // need an extra click before they see the file picker.
    var ingesterTabs = document.querySelectorAll('.ingester-tabs li a');
    ingesterTabs.forEach(function (tab) {
        var ingesterType = (tab.getAttribute('data-ingester') || tab.textContent.trim().toLowerCase());
        if (ingesterType.indexOf('upload') === -1) {
            var li = tab.closest('li');
            if (li) li.style.display = 'none';
        } else {
            tab.click();
        }
    });

    var addMediaButton = document.querySelector('#media-list .add-media, .o-icon-add.button');
    var existingMediaBlocks = document.querySelectorAll('#media-list .resource-values');
    if (addMediaButton && existingMediaBlocks.length === 0) {
        addMediaButton.click();
    }
});