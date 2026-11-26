/**
 * CRM Inquiry — client-side DataTable + filters + AJAX delete/restore
 */
(function () {
    if (window.toastr) {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 3500
        };
        if (window.INQUIRY_TOAST_MSG) {
            toastr.success(window.INQUIRY_TOAST_MSG);
        }
    }

    if (!(window.jQuery && jQuery.fn.DataTable && document.getElementById('inquiryTable'))) {
        return;
    }

    var cascade = window.INQUIRY_FILTER_CASCADE || { states: [], cities: [], areas: [] };
    var urlParams = new URLSearchParams(window.location.search || '');
    var filterState = {
        country_id: '',
        state_id: '',
        city_id: '',
        inquiry_source_id: '',
        inquiry_status_id: (urlParams.get('inquiry_status_id') || '').toString(),
        follow_up_status_id: (urlParams.get('follow_up_status_id') || '').toString(),
        record_status: 'active',
        filter: (urlParams.get('filter') || '').toString(),
        q: ''
    };

    function fillSelect($sel, items, parentVal, parentKey) {
        if (!$sel.length) return;
        var current = $sel.val();
        if (window.CrmSelect2 && typeof window.CrmSelect2.destroy === 'function') {
            window.CrmSelect2.destroy($sel);
        }
        $sel.empty().append('<option value="">All</option>');
        (items || []).forEach(function (it) {
            if (parentVal && parentKey && String(it[parentKey]) !== String(parentVal)) {
                return;
            }
            $sel.append(
                jQuery('<option></option>').attr('value', it.id).text(it.name)
            );
        });
        if (current && $sel.find('option[value="' + current + '"]').length) {
            $sel.val(current);
        } else {
            $sel.val('');
        }
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

    if (jQuery('#flt_state_id').length) {
        refreshCascade('init');
    }
    if (window.CrmSelect2 && typeof window.CrmSelect2.init === 'function') {
        window.CrmSelect2.init(jQuery('#inquiryFilterForm'));
    }

    jQuery(document).on('change', '#flt_country_id', function () {
        refreshCascade('country_id');
    });
    jQuery(document).on('change', '#flt_state_id', function () {
        refreshCascade('state_id');
    });

    function readFilters() {
        filterState.country_id = (jQuery('#flt_country_id').val() || '').toString();
        filterState.state_id = (jQuery('#flt_state_id').val() || '').toString();
        filterState.city_id = (jQuery('#flt_city_id').val() || '').toString();
        filterState.inquiry_source_id = (jQuery('#flt_inquiry_source_id').val() || '').toString();
        filterState.inquiry_status_id = (jQuery('#flt_inquiry_status_id').val() || '').toString();
        filterState.follow_up_status_id = (jQuery('#flt_follow_up_status_id').val() || '').toString();
        filterState.record_status = (jQuery('#flt_record_status').val() || 'active').toString();
        filterState.q = (jQuery('#flt_q').val() || '').toString().trim();
        // Keep dashboard quick-filter until user resets / changes filters
        if (!filterState.filter) {
            filterState.filter = '';
        }
    }

    // Prefill filter UI from dashboard / URL shortcuts
    if (filterState.inquiry_status_id && jQuery('#flt_inquiry_status_id').length) {
        jQuery('#flt_inquiry_status_id').val(filterState.inquiry_status_id);
    }
    if (filterState.follow_up_status_id && jQuery('#flt_follow_up_status_id').length) {
        jQuery('#flt_follow_up_status_id').val(filterState.follow_up_status_id);
    }

    var $table = jQuery('#inquiryTable');
    $table.addClass('crm-dt-table');

    var table = $table.DataTable({
        processing: false,
        serverSide: false,
        autoWidth: false,
        scrollX: false,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[2, 'desc']],
        deferRender: true,
        ajax: {
            url: window.INQUIRY_AJAX_URL,
            type: 'POST',
            data: function (d) {
                d.country_id = filterState.country_id || '';
                d.state_id = filterState.state_id || '';
                d.city_id = filterState.city_id || '';
                d.inquiry_source_id = filterState.inquiry_source_id || '';
                d.inquiry_status_id = filterState.inquiry_status_id || '';
                d.follow_up_status_id = filterState.follow_up_status_id || '';
                d.record_status = filterState.record_status || 'active';
                d.filter = filterState.filter || '';
                d.q = filterState.q || '';
            },
            dataSrc: 'data',
            error: function () {
                if (window.toastr) {
                    toastr.error('Failed to load inquiry data.');
                }
            }
        },
        columnDefs: [
            { orderable: false, searchable: false, targets: [0, 8, 9, 10] },
            { className: 'crm-col-sr text-center', targets: 0 },
            { className: 'crm-col-inqno', targets: 1 },
            { className: 'crm-col-date', targets: 2 },
            { className: 'crm-col-customer', targets: 3 },
            { className: 'crm-col-company', targets: 4 },
            { className: 'crm-col-phone', targets: 5 },
            { className: 'crm-col-location', targets: 6 },
            { className: 'crm-col-source', targets: 7 },
            { className: 'crm-col-status', targets: [8, 9] },
            { className: 'crm-col-action text-center', targets: 10 },
            { width: '42px', targets: 0 },
            { width: '78px', targets: 10 }
        ],
        createdRow: function (row) {
            jQuery(row).addClass('crm-dt-row');
        },
        language: {
            search: '',
            searchPlaceholder: 'Search...',
            lengthMenu: '_MENU_ per page',
            info: 'Showing _START_–_END_ of _TOTAL_',
            infoEmpty: 'No records to show',
            infoFiltered: '(filtered from _MAX_)',
            zeroRecords: 'No matching inquiries found',
            emptyTable: 'No inquiries yet',
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
                .attr('placeholder', 'Search inquiries...')
                .addClass('crm-dt-search');
        }
    });

    window.INQUIRY_DT = table;

    jQuery('#inquiryFilterForm').on('submit', function (e) {
        e.preventDefault();
        readFilters();
        table.ajax.reload();
    });

    jQuery('#inquiryFilterReset').on('click', function () {
        var form = document.getElementById('inquiryFilterForm');
        if (form) form.reset();
        refreshCascade('init');
        filterState = {
            country_id: '',
            state_id: '',
            city_id: '',
            inquiry_source_id: '',
            inquiry_status_id: '',
            follow_up_status_id: '',
            record_status: 'active',
            filter: '',
            q: ''
        };
        if (window.history && window.history.replaceState) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }
        if (jQuery('#flt_record_status').length) {
            jQuery('#flt_record_status').val('active').trigger('change');
        }
        if (jQuery('#flt_inquiry_source_id').length) {
            jQuery('#flt_inquiry_source_id').val('').trigger('change');
        }
        if (jQuery('#flt_inquiry_status_id').length) {
            jQuery('#flt_inquiry_status_id').val('').trigger('change');
        }
        if (jQuery('#flt_follow_up_status_id').length) {
            jQuery('#flt_follow_up_status_id').val('').trigger('change');
        }
        table.ajax.reload();
    });

    jQuery('#flt_q').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            jQuery('#inquiryFilterForm').trigger('submit');
        }
    });

    function toast(type, msg) {
        if (window.toastr) {
            if (type === 'error') toastr.error(msg);
            else toastr.success(msg);
            return;
        }
        alert(msg);
    }

    function paintStatusSelect(sel) {
        if (!sel) return;
        var opt = sel.options[sel.selectedIndex];
        var color = opt ? (opt.getAttribute('data-color') || '').trim() : '';
        if (color) {
            if (color.charAt(0) !== '#') color = '#' + color;
            sel.style.borderColor = color;
            sel.style.color = color;
            sel.style.backgroundColor = color + '18';
            sel.style.backgroundImage = "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M2.5 4.5L6 8l3.5-3.5'/%3E%3C/svg%3E\")";
            sel.style.backgroundRepeat = 'no-repeat';
            sel.style.backgroundPosition = 'right 10px center';
        } else {
            sel.style.borderColor = '';
            sel.style.color = '';
            sel.style.backgroundColor = '';
            sel.style.backgroundImage = '';
            sel.style.backgroundRepeat = '';
            sel.style.backgroundPosition = '';
        }
    }

    function refreshGridStatusColors(ctx) {
        var root = ctx || document;
        var nodes = root.querySelectorAll
            ? root.querySelectorAll('#inquiryTable .js-inq-grid-status')
            : [];
        for (var i = 0; i < nodes.length; i++) {
            paintStatusSelect(nodes[i]);
        }
    }

    $table.on('draw.dt', function () {
        refreshGridStatusColors(document);
    });

    jQuery(document).on('change', '#inquiryTable .js-inq-grid-status', function () {
        var sel = this;
        if (!window.INQUIRY_CAN_EDIT || !window.INQUIRY_STATUS_URL) {
            return;
        }
        var id = parseInt(sel.getAttribute('data-id') || '0', 10);
        var field = (sel.getAttribute('data-field') || '').toString();
        var prev = (sel.getAttribute('data-prev') || '0').toString();
        var statusId = (sel.value || '0').toString();
        if (!id || (field !== 'inquiry_status_id' && field !== 'follow_up_status_id')) {
            return;
        }
        if (statusId === prev) {
            paintStatusSelect(sel);
            return;
        }

        sel.disabled = true;
        var body = new FormData();
        body.append('id', String(id));
        body.append('field', field);
        body.append('status_id', statusId);

        fetch(window.INQUIRY_STATUS_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: body
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { okHttp: res.ok, data: data };
                });
            })
            .then(function (pack) {
                var data = pack.data || {};
                sel.disabled = false;
                if (data.ok) {
                    sel.setAttribute('data-prev', statusId);
                    paintStatusSelect(sel);
                    toast('success', data.message || 'Status updated.');
                } else {
                    sel.value = prev;
                    paintStatusSelect(sel);
                    toast('error', data.message || 'Status update failed.');
                }
            })
            .catch(function () {
                sel.disabled = false;
                sel.value = prev;
                paintStatusSelect(sel);
                toast('error', 'Network error. Please try again.');
            });
    });

    function ajaxInquiryAction(url, fallbackMsg) {
        var sep = url.indexOf('?') >= 0 ? '&' : '?';
        fetch(url + sep + 'ajax=1', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { okHttp: res.ok, data: data };
                });
            })
            .then(function (pack) {
                var data = pack.data || {};
                if (data.ok) {
                    toast('success', data.message || fallbackMsg);
                    table.ajax.reload(null, false);
                } else {
                    toast('error', data.message || 'Action failed.');
                }
            })
            .catch(function () {
                toast('error', 'Network error. Please try again.');
            });
    }

    function setFilterVisible(show) {
        var card = document.getElementById('inquiryFilterCard');
        var btn = document.getElementById('inquiryFilterToggle');
        if (!card || !btn) return;
        if (show) {
            card.removeAttribute('hidden');
            btn.classList.add('is-active');
            btn.setAttribute('aria-expanded', 'true');
            if (window.CrmSelect2 && typeof window.CrmSelect2.init === 'function') {
                window.CrmSelect2.init(jQuery('#inquiryFilterForm'));
            }
        } else {
            card.setAttribute('hidden', 'hidden');
            btn.classList.remove('is-active');
            btn.setAttribute('aria-expanded', 'false');
        }
    }

    function closeSettingsMenu() {
        var wrap = document.getElementById('inquirySettingsMenu');
        if (!wrap) return;
        var menu = wrap.querySelector('.crm-tool-menu');
        var btn = document.getElementById('inquirySettingsBtn');
        if (menu) menu.setAttribute('hidden', 'hidden');
        if (btn) btn.setAttribute('aria-expanded', 'false');
        wrap.classList.remove('is-open');
    }

    function toggleSettingsMenu() {
        var wrap = document.getElementById('inquirySettingsMenu');
        if (!wrap) return;
        var menu = wrap.querySelector('.crm-tool-menu');
        var btn = document.getElementById('inquirySettingsBtn');
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
        var sel = tmp.querySelector('select');
        if (sel) {
            var opt = sel.options[sel.selectedIndex];
            return opt ? String(opt.textContent || '').trim() : '';
        }
        return String(tmp.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function exportInquiryExcel() {
        closeSettingsMenu();
        var headers = [
            'Sr.', 'Inquiry No', 'Date', 'Customer', 'Company', 'Phone',
            'Location', 'Source', 'Inquiry Status', 'Follow Up'
        ];
        var rows = [];
        table.rows({ search: 'applied' }).every(function (rowIdx) {
            var d = this.data() || [];
            var line = [];
            for (var c = 0; c < 10; c++) {
                line.push(cellPlainText(d[c]));
            }
            line[0] = String(rowIdx + 1);
            rows.push(line);
        });

        function csvEscape(v) {
            var s = String(v == null ? '' : v);
            if (/[",\n\r]/.test(s)) {
                return '"' + s.replace(/"/g, '""') + '"';
            }
            return s;
        }

        var lines = [headers.map(csvEscape).join(',')];
        rows.forEach(function (r) {
            lines.push(r.map(csvEscape).join(','));
        });
        var bom = '\uFEFF';
        var blob = new Blob([bom + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
        var a = document.createElement('a');
        var stamp = new Date();
        var y = stamp.getFullYear();
        var m = String(stamp.getMonth() + 1).padStart(2, '0');
        var day = String(stamp.getDate()).padStart(2, '0');
        a.href = URL.createObjectURL(blob);
        a.download = 'inquiry-export-' + y + m + day + '.csv';
        document.body.appendChild(a);
        a.click();
        setTimeout(function () {
            URL.revokeObjectURL(a.href);
            a.remove();
        }, 0);
        if (window.toastr) {
            toastr.success('Excel export downloaded.');
        }
    }

    function printInquiryList() {
        closeSettingsMenu();
        var headers = [
            'Sr.', 'Inquiry No', 'Date', 'Customer', 'Company', 'Phone',
            'Location', 'Source', 'Inquiry Status', 'Follow Up'
        ];
        var bodyRows = [];
        table.rows({ search: 'applied' }).every(function (rowIdx) {
            var d = this.data() || [];
            var tds = '';
            for (var c = 0; c < 10; c++) {
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
            '<!DOCTYPE html><html><head><title>Inquiry List</title>' +
            '<style>' +
            'body{font-family:Calibri,Arial,sans-serif;padding:16px;color:#0f172a}' +
            'h1{font-size:18px;margin:0 0 12px}' +
            'table{width:100%;border-collapse:collapse;font-size:12px}' +
            'th,td{border:1px solid #cbd5e1;padding:6px 8px;text-align:left}' +
            'th{background:#f1f5f9}' +
            '@media print{body{padding:0}}' +
            '</style></head><body>' +
            '<h1>Inquiry List</h1>' +
            '<table><thead><tr>' +
            headers.map(function (h) { return '<th>' + h + '</th>'; }).join('') +
            '</tr></thead><tbody>' + bodyRows.join('') + '</tbody></table>' +
            '</body></html>'
        );
        win.document.close();
        win.focus();
        setTimeout(function () {
            win.print();
        }, 250);
    }

    // Open filters when arriving from dashboard shortcuts
    var openFiltersOnLoad = !!(
        filterState.filter ||
        filterState.inquiry_status_id ||
        filterState.follow_up_status_id
    );
    setFilterVisible(openFiltersOnLoad);

    jQuery('#inquiryFilterToggle').on('click', function () {
        var card = document.getElementById('inquiryFilterCard');
        var showing = card && !card.hasAttribute('hidden');
        setFilterVisible(!showing);
    });

    jQuery('#inquirySettingsBtn').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        toggleSettingsMenu();
    });

    jQuery('#inquiryExportExcel').on('click', function (e) {
        e.preventDefault();
        exportInquiryExcel();
    });

    jQuery('#inquiryPrintBtn').on('click', function (e) {
        e.preventDefault();
        printInquiryList();
    });

    function closeAllActionMenus(except) {
        jQuery('#inquiryTable .crm-action-menu.is-open').each(function () {
            if (except && this === except) return;
            jQuery(this).removeClass('is-open');
            jQuery(this).find('.crm-action-dropdown').attr('hidden', 'hidden');
            jQuery(this).find('.crm-action-dots').attr('aria-expanded', 'false');
        });
    }

    jQuery(document).on('click', '#inquiryTable .js-convert-customer', function (e) {
        e.preventDefault();
        e.stopPropagation();
        closeAllActionMenus();
        var href = this.getAttribute('href');
        if (!href) return;
        var go = function () { window.location.href = href; };
        if (window.ConfirmDelete && typeof ConfirmDelete.ask === 'function') {
            ConfirmDelete.ask(
                'Convert this inquiry to a customer? Inquiry status will be set to Converted.',
                go,
                { title: 'Convert to Customer?', yesLabel: 'Yes, Convert', yesHtml: '<i class="fa-solid fa-user-plus"></i> Yes, Convert', iconClass: 'fa-solid fa-user-plus' }
            );
            return;
        }
        if (window.confirm('Convert this inquiry to a customer?')) {
            go();
        }
    });

    jQuery(document).on('click', '#inquiryTable .crm-action-dots', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var menu = this.closest('.crm-action-menu');
        if (!menu) return;
        var open = menu.classList.contains('is-open');
        closeAllActionMenus();
        closeSettingsMenu();
        if (!open) {
            menu.classList.add('is-open');
            var dd = menu.querySelector('.crm-action-dropdown');
            if (dd) dd.removeAttribute('hidden');
            this.setAttribute('aria-expanded', 'true');
        }
    });

    jQuery(document).on('click', function (e) {
        var wrap = document.getElementById('inquirySettingsMenu');
        if (wrap && !wrap.contains(e.target)) {
            closeSettingsMenu();
        }
        if (!e.target.closest || !e.target.closest('#inquiryTable .crm-action-menu')) {
            closeAllActionMenus();
        }
    });

    document.addEventListener('click', function (e) {
        var delBtn = e.target.closest('#inquiryTable .btn-delete[data-ajax="1"]');
        if (delBtn) {
            closeAllActionMenus();
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            var delHref = delBtn.getAttribute('href');
            if (!delHref) return;
            var delName = delBtn.getAttribute('data-name') || 'inquiry';

            if (window.ConfirmDelete && typeof ConfirmDelete.ask === 'function') {
                ConfirmDelete.ask(
                    'Are you sure you want to delete "' + delName + '"? You can restore it later from Deleted filter.',
                    function () { ajaxInquiryAction(delHref, 'Inquiry deleted.'); },
                    { title: 'Soft Delete?', yesLabel: 'Yes, Delete' }
                );
                return;
            }

            if (window.confirm('Delete "' + delName + '"?')) {
                ajaxInquiryAction(delHref, 'Inquiry deleted.');
            }
            return;
        }

        var restoreBtn = e.target.closest('#inquiryTable .btn-restore[data-ajax="1"]');
        if (!restoreBtn) return;
        closeAllActionMenus();
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        var restoreHref = restoreBtn.getAttribute('href');
        if (!restoreHref) return;
        var restoreName = restoreBtn.getAttribute('data-name') || 'inquiry';

        if (window.ConfirmDelete && typeof ConfirmDelete.ask === 'function') {
            ConfirmDelete.ask(
                'Restore "' + restoreName + '" back to active?',
                function () { ajaxInquiryAction(restoreHref, 'Inquiry restored.'); },
                { title: 'Restore?', yesLabel: 'Yes, Restore', yesHtml: '<i class="fa-solid fa-rotate-left"></i> Yes, Restore', iconClass: 'fa-solid fa-rotate-left' }
            );
            return;
        }

        if (window.confirm('Restore "' + restoreName + '"?')) {
            ajaxInquiryAction(restoreHref, 'Inquiry restored.');
        }
    }, true);
})();
