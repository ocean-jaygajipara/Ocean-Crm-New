/**
 * Inquiry add/edit — location cascade + product details rows + Select2
 */
(function () {
    if (!(window.jQuery && document.getElementById('inquiryForm'))) {
        return;
    }

    var $ = window.jQuery;
    var data = window.INQUIRY_FORM_CASCADE || {};
    var sel = window.INQUIRY_FORM_SELECTED || {};

    function fillSelect($el, items, parentKey, parentVal, selectedId) {
        if (!$el.length) return;
        if (window.CrmSelect2 && typeof window.CrmSelect2.destroy === 'function') {
            window.CrmSelect2.destroy($el);
        }
        var html = '<option value="">Select</option>';
        (items || []).forEach(function (it) {
            if (parentKey && String(it[parentKey]) !== String(parentVal)) return;
            var s = String(it.id) === String(selectedId) ? ' selected' : '';
            html += '<option value="' + it.id + '"' + s + '>' + $('<div/>').text(it.name).html() + '</option>';
        });
        $el.html(html);
        if (window.CrmSelect2 && typeof window.CrmSelect2.rebind === 'function') {
            window.CrmSelect2.rebind($el);
        }
    }

    function refreshChain(from) {
        var c = $('#country_id').val() || '';
        var s = $('#state_id').val() || '';
        var ci = $('#city_id').val() || '';
        if (from === 'country' || from === 'init') {
            fillSelect($('#state_id'), data.states, 'country_id', c, from === 'init' ? sel.state_id : '');
            if (from !== 'init') { s = ''; ci = ''; }
        }
        if (from === 'country' || from === 'state' || from === 'init') {
            fillSelect($('#city_id'), data.cities, 'state_id', from === 'init' ? (sel.state_id || s) : s, from === 'init' ? sel.city_id : '');
            if (from !== 'init') ci = '';
        }
        if (from === 'country' || from === 'state' || from === 'city' || from === 'init') {
            fillSelect($('#area_id'), data.areas, 'city_id', from === 'init' ? (sel.city_id || ci) : ci, from === 'init' ? sel.area_id : '');
        }
    }

    function bindAllSelect2() {
        if (!(window.CrmSelect2 && typeof window.CrmSelect2.init === 'function')) return;
        window.CrmSelect2.init($('#inquiryForm'));
    }

    function syncEmptyState() {
        var hasRows = $('#inqReqProductRows .inq-pd-row').length > 0;
        var $empty = $('#inqPdEmpty');
        if (!$empty.length) return;
        if (hasRows) {
            $empty.attr('hidden', true);
        } else {
            $empty.removeAttr('hidden');
        }
    }

    function addProductFromPicker() {
        var $picker = $('#inqProductPicker');
        if (!$picker.length) return;
        var id = String($picker.val() || '');
        if (!id) return;
        var name = $.trim($picker.find('option:selected').text() || '');
        if (!name) return;

        var tpl = document.getElementById('inqReqProductRowTpl');
        var $wrap = $('#inqReqProductRows');
        if (!tpl || !$wrap.length) return;

        var node = tpl.content.cloneNode(true);
        $wrap.append(node);
        var $row = $wrap.find('.inq-pd-row').last();
        $row.find('input[name="product_id[]"]').val(id);
        $row.find('.inq-pd-name').text(name);
        $row.find('input[name="qty[]"]').val('1');
        $row.find('input[name="line_requirements[]"]').val('');

        $picker.val('').trigger('change');
        syncEmptyState();
    }

    $(function () {
        if (window.toastr && window.INQUIRY_FORM_ERROR) {
            toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 5000 };
            toastr.error(window.INQUIRY_FORM_ERROR);
        }
        bindAllSelect2();
        refreshChain('init');
        bindAllSelect2();
        $('#country_id').on('change', function () { refreshChain('country'); });
        $('#state_id').on('change', function () { refreshChain('state'); });
        $('#city_id').on('change', function () { refreshChain('city'); });

        $('#inqProductPicker').on('change', function () {
            addProductFromPicker();
        });

        $(document).on('click', '#inqReqProductRows .inq-req-remove', function () {
            $(this).closest('.inq-pd-row').remove();
            syncEmptyState();
        });

        syncEmptyState();
    });
})();
