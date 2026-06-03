(function () {
    var DEFS         = OBRISOWI_MESSENGERS.defs;
    var PHS          = OBRISOWI_MESSENGERS.placeholders;
    var DELETE_LABEL = OBRISOWI_MESSENGERS.deleteLabel;
    var EMPTY_MSG    = OBRISOWI_MESSENGERS.emptyMsg;
    var nextIdx      = parseInt( OBRISOWI_MESSENGERS.nextIdx, 10 );

    var tbody    = document.getElementById('sw-tbody');
    var addKey   = document.getElementById('sw-add-key');
    var addLabel = document.getElementById('sw-add-label');
    var addUrl   = document.getElementById('sw-add-url');
    var addBtn   = document.getElementById('sw-add-btn');
    var addIcon  = document.getElementById('sw-add-icon');

    function updateAddPreview() {
        var d = DEFS[addKey.value] || {};
        if (addIcon) addIcon.src = d.icon_url || '';
        addUrl.placeholder = PHS[addKey.value] || '';
        addLabel.placeholder = d.label || '';
    }
    addKey.addEventListener('change', updateAddPreview);
    updateAddPreview();

    addBtn.addEventListener('click', function () {
        var key     = addKey.value;
        var d       = DEFS[key] || {};
        var label   = addLabel.value.trim() || d.label || key;
        var url     = addUrl.value.trim();
        var ph      = PHS[key] || '';
        var iconUrl = d.icon_url || '';

        removeEmptyRow();
        var tr = buildRow(nextIdx, key, iconUrl, label, url, ph);
        tbody.appendChild(tr);
        initRow(tr);
        nextIdx++;

        addLabel.value = '';
        addUrl.value   = '';
        updateAddPreview();
    });

    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('.sw-delete-btn');
        if (!btn) return;
        btn.closest('tr').remove();
        if (!tbody.querySelector('tr.sw-row')) showEmptyRow();
    });

    /* Drag-and-drop */
    var dragged = null;
    tbody.addEventListener('dragstart', function (e) {
        var row = e.target.closest('tr.sw-row');
        if (!row) return;
        dragged = row;
        setTimeout(function () { if (dragged) dragged.classList.add('sw-dragging'); }, 0);
    });
    tbody.addEventListener('dragend', function () {
        if (dragged) dragged.classList.remove('sw-dragging');
        dragged = null;
    });
    tbody.addEventListener('dragover', function (e) {
        e.preventDefault();
        if (!dragged) return;
        var over = e.target.closest('tr.sw-row');
        if (over && over !== dragged) {
            var mid = over.getBoundingClientRect().top + over.getBoundingClientRect().height / 2;
            tbody.insertBefore(dragged, e.clientY < mid ? over : over.nextSibling);
        }
    });

    /* Re-index inputs before submit so PHP receives rows in visual order */
    document.getElementById('sw-form').addEventListener('submit', function () {
        tbody.querySelectorAll('tr.sw-row').forEach(function (row, i) {
            row.querySelectorAll('input[name^="messengers["]').forEach(function (input) {
                input.name = input.name.replace(/^messengers\[\d+\]/, 'messengers[' + i + ']');
            });
        });
    });

    tbody.querySelectorAll('tr.sw-row').forEach(initRow);

    function initRow(row) {
        row.setAttribute('draggable', 'true');
    }

    function removeEmptyRow() {
        var e = document.getElementById('sw-empty-row');
        if (e) e.remove();
    }

    function showEmptyRow() {
        var tr = document.createElement('tr');
        tr.id = 'sw-empty-row';
        tr.innerHTML = '<td colspan="5" class="sw-empty-msg">' + escHtml(EMPTY_MSG) + '</td>';
        tbody.appendChild(tr);
    }

    var TRASH_BTN =
        '<button type="button" class="sw-delete-btn" title="' + escAttr(DELETE_LABEL) + '">' +
        '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
        '<polyline points="3 6 5 6 21 6"/>' +
        '<path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>' +
        '<path d="M10 11v6"/><path d="M14 11v6"/>' +
        '<path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>' +
        '</svg></button>';

    function buildRow(idx, key, iconUrl, label, url, ph) {
        var tr = document.createElement('tr');
        tr.className = 'sw-row';
        tr.innerHTML =
            '<td class="sw-handle">&#9776;</td>' +
            '<td class="sw-icon-cell"><img class="sw-admin-icon" src="' + escAttr(iconUrl) + '" alt="" loading="lazy" decoding="async"></td>' +
            '<td>' +
                '<input type="hidden" name="messengers[' + idx + '][key]" value="' + escAttr(key) + '">' +
                '<input type="text" name="messengers[' + idx + '][label]" value="' + escAttr(label) + '" class="sw-label-input">' +
            '</td>' +
            '<td><input type="text" name="messengers[' + idx + '][url]" value="' + escAttr(url) + '" placeholder="' + escAttr(ph) + '" class="sw-url-input"></td>' +
            '<td class="sw-delete-cell">' + TRASH_BTN + '</td>';
        return tr;
    }

    function escAttr(str) {
        return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
})();
