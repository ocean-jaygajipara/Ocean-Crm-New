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
    var filterState = {
        country_id: '',
        state_id: '',
        city_id: '',
        inquiry_source_id: '',
        inquiry_status_id: '',
        follow_up_status_id: '',
        record_status: 'active',
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
    }

    var $table = jQuery('#inquiryTable');
    $table.addClass('crm-dt-table');

    var table = $table.DataTable({
        processing: false,
        serverSide: false,
        autoWidth: false,
        scrollX: true,
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
            { orderable: false, searchable: false, targets: [0, 11] },
            { className: 'crm-col-sr text-center', targets: 0 },
            { className: 'crm-col-action text-center', targets: 11 }
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
            q: ''
        };
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

    document.addEventListener('click', function (e) {
        var delBtn = e.target.closest('#inquiryTable .btn-delete[data-ajax="1"]');
        if (delBtn) {
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
