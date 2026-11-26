/**
 * Customer list — DataTable + filters + three-dot actions
 */
(function () {
    if (window.toastr) {
        toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 3500 };
        if (window.CUSTOMER_TOAST_MSG) toastr.success(window.CUSTOMER_TOAST_MSG);
    }
    if (!(window.jQuery && jQuery.fn.DataTable && document.getElementById('customerTable'))) {
        return;
    }

    var cascade = window.CUSTOMER_FILTER_CASCADE || { states: [], cities: [] };
    var filterState = {
        customer_type_id: '',
        country_id: '',
        state_id: '',
        city_id: '',
        record_status: 'active',
        q: ''
    };

    function fillSelect($sel, items, parentVal, parentKey) {
        if (!$sel.length) return;
        if (window.CrmSelect2 && typeof window.CrmSelect2.destroy === 'function') {
            window.CrmSelect2.destroy($sel);
        }
        $sel.empty().append('<option value="">All</option>');
        (items || []).forEach(function (it) {
            if (parentVal && parentKey && String(it[parentKey]) !== String(parentVal)) return;
            $sel.append(jQuery('<option></option>').attr('value', it.id).text(it.name));
        });
        $sel.val('');
        if (window.CrmSelect2 && typeof window.CrmSelect2.rebind === 'function') {
            window.CrmSelect2.rebind($sel);
        }
    }

    function refreshCascade(changed) {
        var countryId = jQuery('#flt_country_id').val() || '';
        var stateId = jQuery('#flt_state_id').val() || '';
        if (changed === 'country_id' || changed === 'init') {
            fillSelect(jQuery('#flt_state_id'), cascade.states, countryId, 'country_id');
            stateId = jQuery('#flt_state_id').val() || '';
            fillSelect(jQuery('#flt_city_id'), cascade.cities, stateId, 'state_id');
        } else if (changed === 'state_id') {
            fillSelect(jQuery('#flt_city_id'), cascade.cities, stateId, 'state_id');
        }
    }

    refreshCascade('init');
    if (window.CrmSelect2 && typeof window.CrmSelect2.init === 'function') {
        window.CrmSelect2.init(jQuery('#customerFilterForm'));
    }
    jQuery(document).on('change', '#flt_country_id', function () { refreshCascade('country_id'); });
    jQuery(document).on('change', '#flt_state_id', function () { refreshCascade('state_id'); });

    function readFilters() {
        filterState.customer_type_id = (jQuery('#flt_customer_type_id').val() || '').toString();
        filterState.country_id = (jQuery('#flt_country_id').val() || '').toString();
        filterState.state_id = (jQuery('#flt_state_id').val() || '').toString();
        filterState.city_id = (jQuery('#flt_city_id').val() || '').toString();
        filterState.record_status = (jQuery('#flt_record_status').val() || 'active').toString();
        filterState.q = (jQuery('#flt_q').val() || '').toString().trim();
    }

    var table = jQuery('#customerTable').DataTable({
        processing: false,
        serverSide: false,
        autoWidth: false,
        scrollX: false,
        pageLength: 10,
        order: [[1, 'desc']],
        ajax: {
            url: window.CUSTOMER_AJAX_URL,
            type: 'POST',
            data: function (d) {
                d.customer_type_id = filterState.customer_type_id || '';
                d.country_id = filterState.country_id || '';
                d.state_id = filterState.state_id || '';
                d.city_id = filterState.city_id || '';
                d.record_status = filterState.record_status || 'active';
                d.q = filterState.q || '';
            },
            dataSrc: 'data'
        },
        columnDefs: [
            { orderable: false, searchable: false, targets: [0, 7] },
            { className: 'text-center', targets: [0, 7] },
            { width: '42px', targets: 0 },
            { width: '78px', targets: 7 }
        ],
        language: {
            search: '',
            searchPlaceholder: 'Search...',
            lengthMenu: '_MENU_ per page',
            info: 'Showing _START_–_END_ of _TOTAL_',
            zeroRecords: 'No matching customers found',
            emptyTable: 'No customers yet',
            paginate: {
                previous: '<i class="fa-solid fa-chevron-left"></i>',
                next: '<i class="fa-solid fa-chevron-right"></i>'
            }
        },
        dom: '<"crm-dt-toolbar"<"crm-dt-left"l><"crm-dt-right"f>>rt<"crm-dt-footer"<"crm-dt-info"i><"crm-dt-pager"p>>',
        drawCallback: function () {
            var api = this.api();
            var info = api.page.info();
            api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                cell.innerHTML = '<span class="crm-sr">' + (info.start + i + 1) + '</span>';
            });
            jQuery(api.table().container()).find('.dataTables_filter input')
                .attr('placeholder', 'Search customers...')
                .addClass('crm-dt-search');
        }
    });

    function setFilterVisible(show) {
        var card = document.getElementById('customerFilterCard');
        var btn = document.getElementById('customerFilterToggle');
        if (!card || !btn) return;
        if (show) {
            card.removeAttribute('hidden');
            btn.classList.add('is-active');
            btn.setAttribute('aria-expanded', 'true');
        } else {
            card.setAttribute('hidden', 'hidden');
            btn.classList.remove('is-active');
            btn.setAttribute('aria-expanded', 'false');
        }
    }
    function closeSettingsMenu() {
        var wrap = document.getElementById('customerSettingsMenu');
        if (!wrap) return;
        var menu = wrap.querySelector('.crm-tool-menu');
        var btn = document.getElementById('customerSettingsBtn');
        if (menu) menu.setAttribute('hidden', 'hidden');
        if (btn) btn.setAttribute('aria-expanded', 'false');
        wrap.classList.remove('is-open');
    }

    function toggleSettingsMenu() {
        var wrap = document.getElementById('customerSettingsMenu');
        if (!wrap) return;
        var menu = wrap.querySelector('.crm-tool-menu');
        var btn = document.getElementById('customerSettingsBtn');
        if (!menu || !btn) return;
        var open = !menu.hasAttribute('hidden');
        if (open) {
            closeSettingsMenu();
        } else {
            menu.removeAttribute('hidden');
            btn.setAttribute('aria-expanded', 'true');
            wrap.classList.add('is-open');
        }
    }

    function cellPlainText(html) {
        var tmp = document.createElement('div');
        tmp.innerHTML = html == null ? '' : String(html);
        return String(tmp.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function exportCustomerExcel() {
        closeSettingsMenu();
        var headers = ['Sr.', 'Customer No', 'Type', 'Name', 'Company', 'Phone', 'City'];
        var rows = [];
        table.rows({ search: 'applied' }).every(function (rowIdx) {
            var d = this.data() || [];
            var line = [];
            for (var c = 0; c < 7; c++) {
                line.push(cellPlainText(d[c]));
            }
            line[0] = String(rowIdx + 1);
            rows.push(line);
        });
        function csvEscape(v) {
            var s = String(v == null ? '' : v);
            if (/[",\n\r]/.test(s)) return '"' + s.replace(/"/g, '""') + '"';
            return s;
        }
        var lines = [headers.map(csvEscape).join(',')];
        rows.forEach(function (r) { lines.push(r.map(csvEscape).join(',')); });
        var bom = '\uFEFF';
        var blob = new Blob([bom + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
        var a = document.createElement('a');
        var stamp = new Date();
        var y = stamp.getFullYear();
        var m = String(stamp.getMonth() + 1).padStart(2, '0');
        var day = String(stamp.getDate()).padStart(2, '0');
        a.href = URL.createObjectURL(blob);
        a.download = 'customer-export-' + y + m + day + '.csv';
        document.body.appendChild(a);
        a.click();
        setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 0);
        if (window.toastr) toastr.success('Excel export downloaded.');
    }

    function printCustomerList() {
        closeSettingsMenu();
        var headers = ['Sr.', 'Customer No', 'Type', 'Name', 'Company', 'Phone', 'City'];
        var bodyRows = [];
        table.rows({ search: 'applied' }).every(function (rowIdx) {
            var d = this.data() || [];
            var tds = '';
            for (var c = 0; c < 7; c++) {
                var txt = c === 0 ? String(rowIdx + 1) : cellPlainText(d[c]);
                tds += '<td>' + jQuery('<div/>').text(txt).html() + '</td>';
            }
            bodyRows.push('<tr>' + tds + '</tr>');
        });
        var win = window.open('', '_blank');
        if (!win) {
            if (window.toastr) toastr.error('Please allow pop-ups to print.');
            return;
        }
        win.document.write(
            '<!DOCTYPE html><html><head><title>Customer List</title>' +
            '<style>' +
            'body{font-family:Calibri,Arial,sans-serif;padding:16px;color:#0f172a}' +
            'h1{font-size:18px;margin:0 0 12px}' +
            'table{width:100%;border-collapse:collapse;font-size:12px}' +
            'th,td{border:1px solid #cbd5e1;padding:6px 8px;text-align:left}' +
            'th{background:#f1f5f9}' +
            '@media print{body{padding:0}}' +
            '</style></head><body>' +
            '<h1>Customer List</h1>' +
            '<table><thead><tr>' +
            headers.map(function (h) { return '<th>' + h + '</th>'; }).join('') +
            '</tr></thead><tbody>' + bodyRows.join('') + '</tbody></table>' +
            '</body></html>'
        );
        win.document.close();
        win.focus();
        setTimeout(function () { win.print(); }, 250);
    }

    setFilterVisible(false);
    jQuery('#customerFilterToggle').on('click', function () {
        var card = document.getElementById('customerFilterCard');
        setFilterVisible(!(card && !card.hasAttribute('hidden')));
    });

    jQuery('#customerSettingsBtn').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        toggleSettingsMenu();
    });
    jQuery('#customerExportExcel').on('click', function (e) {
        e.preventDefault();
        exportCustomerExcel();
    });
    jQuery('#customerPrintBtn').on('click', function (e) {
        e.preventDefault();
        printCustomerList();
    });

    jQuery('#customerFilterForm').on('submit', function (e) {
        e.preventDefault();
        readFilters();
        table.ajax.reload();
    });
    jQuery('#customerFilterReset').on('click', function () {
        var form = document.getElementById('customerFilterForm');
        if (form) form.reset();
        refreshCascade('init');
        filterState = { customer_type_id: '', country_id: '', state_id: '', city_id: '', record_status: 'active', q: '' };
        if (jQuery('#flt_record_status').length) jQuery('#flt_record_status').val('active').trigger('change');
        if (jQuery('#flt_customer_type_id').length) jQuery('#flt_customer_type_id').val('').trigger('change');
        table.ajax.reload();
    });

    function closeAllActionMenus(except) {
        jQuery('#customerTable .crm-action-menu.is-open').each(function () {
            if (except && this === except) return;
            jQuery(this).removeClass('is-open');
            jQuery(this).find('.crm-action-dropdown').attr('hidden', 'hidden');
            jQuery(this).find('.crm-action-dots').attr('aria-expanded', 'false');
        });
    }
    jQuery(document).on('click', '#customerTable .crm-action-dots', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var menu = this.closest('.crm-action-menu');
        if (!menu) return;
        var open = menu.classList.contains('is-open');
        closeAllActionMenus();
        if (!open) {
            menu.classList.add('is-open');
            var dd = menu.querySelector('.crm-action-dropdown');
            if (dd) dd.removeAttribute('hidden');
            this.setAttribute('aria-expanded', 'true');
        }
    });
    jQuery(document).on('click', function (e) {
        var settingsWrap = document.getElementById('customerSettingsMenu');
        if (settingsWrap && !settingsWrap.contains(e.target)) {
            closeSettingsMenu();
        }
        if (!e.target.closest || !e.target.closest('#customerTable .crm-action-menu')) {
            closeAllActionMenus();
        }
    });

    function ajaxAction(url, fallbackMsg) {
        var sep = url.indexOf('?') >= 0 ? '&' : '?';
        fetch(url + sep + 'ajax=1', {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.ok) {
                    if (window.toastr) toastr.success(data.message || fallbackMsg);
                    table.ajax.reload(null, false);
                } else if (window.toastr) {
                    toastr.error(data.message || 'Action failed.');
                }
            })
            .catch(function () {
                if (window.toastr) toastr.error('Network error.');
            });
    }

    document.addEventListener('click', function (e) {
        var delBtn = e.target.closest('#customerTable .btn-delete[data-ajax="1"]');
        if (delBtn) {
            e.preventDefault();
            closeAllActionMenus();
            var href = delBtn.getAttribute('href');
            var name = delBtn.getAttribute('data-name') || 'customer';
            var go = function () { ajaxAction(href, 'Customer deleted.'); };
            if (window.ConfirmDelete && typeof ConfirmDelete.ask === 'function') {
                ConfirmDelete.ask('Delete "' + name + '"?', go, { title: 'Soft Delete?', yesLabel: 'Yes, Delete' });
            } else if (window.confirm('Delete "' + name + '"?')) go();
            return;
        }
        var restoreBtn = e.target.closest('#customerTable .btn-restore[data-ajax="1"]');
        if (!restoreBtn) return;
        e.preventDefault();
        closeAllActionMenus();
        var rh = restoreBtn.getAttribute('href');
        var rn = restoreBtn.getAttribute('data-name') || 'customer';
        var restore = function () { ajaxAction(rh, 'Customer restored.'); };
        if (window.ConfirmDelete && typeof ConfirmDelete.ask === 'function') {
            ConfirmDelete.ask('Restore "' + rn + '"?', restore, { title: 'Restore?', yesLabel: 'Yes, Restore', iconClass: 'fa-solid fa-rotate-left' });
        } else if (window.confirm('Restore "' + rn + '"?')) restore();
    }, true);
})();
