/**
 * Masters add/edit — AJAX save + parent/child selects
 */
(function () {
    var form = document.getElementById('masterAjaxForm');
    if (!form) return;

    var submitBtn = document.getElementById('masterAjaxSubmit');
    var busy = false;
    var cascade = window.MASTER_FORM_CASCADE || {};
    var selected = window.MASTER_FORM_SELECTED || {};

    function fillChildSelect($el, items, parentVal, parentKey, selectedId) {
        if (!$el.length) return;
        if (window.CrmSelect2 && typeof window.CrmSelect2.destroy === 'function') {
            window.CrmSelect2.destroy($el);
        }
        var label = $el.find('option:first').text() || 'Select';
        $el.empty().append(jQuery('<option></option>').attr('value', '').text(label));
        (items || []).forEach(function (it) {
            if (parentVal && parentKey && String(it[parentKey]) !== String(parentVal)) {
                return;
            }
            var opt = jQuery('<option></option>').attr('value', it.id).text(it.name);
            if (String(it.id) === String(selectedId)) {
                opt.prop('selected', true);
            }
            $el.append(opt);
        });
        if (window.CrmSelect2 && typeof window.CrmSelect2.rebind === 'function') {
            window.CrmSelect2.rebind($el);
        } else if (window.CrmSelect2 && typeof window.CrmSelect2.init === 'function') {
            window.CrmSelect2.init($el);
        }
    }

    function refreshFormCascade(keepSelected) {
        var $child = jQuery('#fld_product_subcategory_id');
        if (!$child.length) return;
        var parentVal = jQuery('#fld_product_category_id').val() || '';
        var sel = keepSelected ? (selected.product_subcategory_id || $child.val() || '') : '';
        fillChildSelect(
            $child,
            cascade.product_subcategories || [],
            parentVal,
            'product_category_id',
            sel
        );
    }

    if (window.jQuery) {
        jQuery(function () {
            refreshFormCascade(true);
            jQuery('#fld_product_category_id').on('change', function () {
                refreshFormCascade(false);
            });
        });
    }

    function toast(type, msg) {
        if (window.toastr) {
            toastr.options = toastr.options || {};
            toastr.options.closeButton = true;
            toastr.options.progressBar = true;
            toastr.options.positionClass = 'toast-top-right';
            toastr.options.timeOut = 3500;
            if (type === 'error') toastr.error(msg);
            else toastr.success(msg);
            return;
        }
        alert(msg);
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (busy) return;

        if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
            return;
        }

        busy = true;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.add('is-loading');
        }

        var fd = new FormData(form);
        fd.set('ajax', '1');

        fetch(form.action, {
            method: 'POST',
            body: fd,
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
                    toast('success', data.message || 'Saved successfully.');
                    var go = data.redirect || form.getAttribute('data-list-url');
                    setTimeout(function () {
                        window.location.href = go || document.referrer || './';
                    }, 450);
                    return;
                }
                toast('error', data.message || 'Save failed.');
            })
            .catch(function () {
                toast('error', 'Network error. Please try again.');
            })
            .finally(function () {
                busy = false;
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('is-loading');
                }
            });
    });
})();
