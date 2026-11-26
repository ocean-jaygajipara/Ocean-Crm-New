/**
 * Global Select2 — every <select> (except .no-select2 / Flatpickr)
 * Use: window.CrmSelect2.init(root) | window.CrmSelect2.rebind($el)
 */
(function ($) {
    if (!($ && $.fn && $.fn.select2)) {
        return;
    }

    function placeholderOf($el) {
        var blank = $el.find('option[value=""]').first().text();
        if (blank) {
            return blank.replace(/^--\s*/, '').replace(/\s*--$/, '').trim() || 'Select';
        }
        return 'Search & select';
    }

    function focusOpenSearch() {
        var field = document.querySelector('.select2-container--open .select2-search__field');
        if (!field) return;
        field.setAttribute('placeholder', 'Type to search…');
        try {
            field.focus({ preventScroll: true });
        } catch (e) {
            field.focus();
        }
    }

    function destroySelect2($el) {
        if (!$el || !$el.length) return;
        if ($el.data('select2')) {
            try {
                $el.select2('destroy');
            } catch (e) { /* ignore */ }
        }
        $el.removeClass('select2-hidden-accessible')
            .removeAttr('data-select2-id')
            .removeAttr('aria-hidden')
            .removeAttr('tabindex');
        $el.next('.select2-container').remove();
        $el.parent().find('> .select2-container').remove();
    }

    function bindSelect2($el) {
        if (!$el.length || $el.hasClass('no-select2')) {
            return;
        }
        // Never wrap Flatpickr month/year controls — Select2 breaks month selection
        if ($el.hasClass('flatpickr-monthDropdown-months') || $el.closest('.flatpickr-calendar').length) {
            $el.addClass('no-select2');
            return;
        }

        // Re-bind cleanly if already initialized (option lists may have changed)
        if ($el.data('select2')) {
            destroySelect2($el);
        }

        var hasBlank = $el.find('option[value=""]').length > 0;
        var inLength = $el.closest('.dataTables_length').length > 0;
        var isMulti = !!$el.prop('multiple');
        $el.select2({
            width: inLength ? 'style' : '100%',
            placeholder: placeholderOf($el) || (isMulti ? 'Select…' : 'Search & select'),
            allowClear: hasBlank && !isMulti,
            closeOnSelect: !isMulti,
            minimumResultsForSearch: inLength ? Infinity : 0,
            dropdownParent: $(document.body),
            language: {
                noResults: function () { return 'No matching option'; },
                searching: function () { return 'Searching…'; }
            }
        });
    }

    function initAllSelects(root) {
        var $root = root ? $(root) : $(document);
        $root.find('select').each(function () {
            bindSelect2($(this));
        });
        // If root itself is a select
        if ($root.is('select')) {
            bindSelect2($root);
        }
    }

    // Select2 hides the native <select> with aria-hidden; never leave focus on it
    $(document).on('select2:opening select2:open', function (e) {
        var el = e.target;
        if (el && el.blur && document.activeElement === el) {
            el.blur();
        }
        focusOpenSearch();
        setTimeout(focusOpenSearch, 0);
    });

    $(document).on('focusin', 'select.select2-hidden-accessible', function (e) {
        var $sel = $(this);
        var data = $sel.data('select2');
        if (!data) return;
        e.preventDefault();
        this.blur();
        // Do not auto-open Select2 — only move focus away from native <select>
        // so the OS/native dropdown never appears.
    });

    // Label click must NOT open native OS dropdown (or Select2)
    $(document).on('mousedown', 'label[for]', function (e) {
        var id = this.getAttribute('for');
        if (!id) return;
        var el = document.getElementById(id);
        if (!el || el.tagName !== 'SELECT') return;
        if (!$(el).data('select2')) return;
        e.preventDefault();
    });

    // Block native picker if anything still focuses/clicks the hidden select
    $(document).on('mousedown click', 'select.select2-hidden-accessible', function (e) {
        if (!$(this).data('select2')) return;
        e.preventDefault();
        e.stopPropagation();
        this.blur();
    });

    window.CrmSelect2 = {
        init: initAllSelects,
        bind: bindSelect2,
        rebind: function (el) {
            bindSelect2($(el));
        },
        destroy: function (el) {
            destroySelect2($(el));
        }
    };

    $(function () {
        initAllSelects(document);

        // DataTables length dropdown is created after DataTable() init
        if ($.fn.DataTable) {
            $(document).on('init.dt draw.dt', function (e) {
                var $wrap = $(e.target).closest('.dataTables_wrapper');
                if ($wrap.length) {
                    initAllSelects($wrap);
                }
            });
        }

        // Late-added selects (AJAX forms / cascade filters)
        if (window.MutationObserver) {
            var moTimer = null;
            var observer = new MutationObserver(function (mutations) {
                var need = false;
                mutations.forEach(function (m) {
                    if (!m.addedNodes || !m.addedNodes.length) return;
                    for (var i = 0; i < m.addedNodes.length; i++) {
                        var n = m.addedNodes[i];
                        if (n.nodeType !== 1) continue;
                        if (n.tagName === 'SELECT' || (n.querySelector && n.querySelector('select'))) {
                            need = true;
                            break;
                        }
                    }
                });
                if (!need) return;
                clearTimeout(moTimer);
                moTimer = setTimeout(function () {
                    initAllSelects(document);
                }, 50);
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    });
})(window.jQuery);
