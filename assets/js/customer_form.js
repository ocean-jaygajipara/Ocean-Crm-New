/**
 * Customer add/edit — location cascade + phone/whatsapp + autosize
 */
(function () {
    if (!(window.jQuery && document.getElementById('customerForm'))) {
        return;
    }
    var $ = window.jQuery;
    var data = window.CUSTOMER_FORM_CASCADE || {};
    var sel = window.CUSTOMER_FORM_SELECTED || {};

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

    $(function () {
        if (window.CrmSelect2 && typeof window.CrmSelect2.init === 'function') {
            window.CrmSelect2.init($('#customerForm'));
        }
        refreshChain('init');
        $(document).on('change', '#country_id', function () { refreshChain('country'); });
        $(document).on('change', '#state_id', function () { refreshChain('state'); });
        $(document).on('change', '#city_id', function () { refreshChain('city'); });

        var phoneRe = /[^0-9+\-() .\/]/g;
        function syncWhatsappFromPhone() {
            var $chk = $('#same_as_phone');
            var $wa = $('#whatsapp');
            var $phone = $('#phone');
            if (!$chk.length || !$wa.length || !$phone.length) return;
            if ($chk.is(':checked')) {
                $wa.val($phone.val() || '').prop('readonly', true);
            } else {
                $wa.prop('readonly', false);
            }
        }
        $(document).on('change', '#same_as_phone', function () {
            if ($(this).is(':checked')) syncWhatsappFromPhone();
            else $('#whatsapp').prop('readonly', false).val('');
        });
        $(document).on('input', '#customerForm .js-cust-phone', function () {
            var el = this;
            var max = parseInt(el.getAttribute('maxlength') || '40', 10) || 40;
            var cleaned = String(el.value || '').replace(phoneRe, '');
            if (cleaned.length > max) cleaned = cleaned.slice(0, max);
            if (el.value !== cleaned) el.value = cleaned;
            if (el.id === 'phone') syncWhatsappFromPhone();
        });
        syncWhatsappFromPhone();

        if (typeof window.autosize === 'function') {
            window.autosize($('#customerForm .autosize'));
        }
    });
})();
