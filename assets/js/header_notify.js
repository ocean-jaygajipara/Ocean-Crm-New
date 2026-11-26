/**
 * Header bell — load / mark notifications
 */
(function () {
    'use strict';

    var wrap = document.getElementById('headerNotify');
    var btn = document.getElementById('headerBellBtn');
    var panel = document.getElementById('headerNotifyPanel');
    var body = document.getElementById('headerNotifyBody');
    var badge = document.getElementById('headerBellBadge');
    var markAllBtn = document.getElementById('headerNotifyMarkAll');
    if (!wrap || !btn || !panel || !body) {
        return;
    }

    var ajaxUrl = wrap.getAttribute('data-ajax-url') || '';

    function closePanel() {
        panel.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
        wrap.classList.remove('is-open');
    }

    function openPanel() {
        panel.hidden = false;
        btn.setAttribute('aria-expanded', 'true');
        wrap.classList.add('is-open');
        refreshList();
    }

    function setBadge(unread) {
        if (!badge) return;
        unread = parseInt(unread, 10) || 0;
        if (unread > 0) {
            badge.hidden = false;
            badge.textContent = unread > 99 ? '99+' : String(unread);
        } else {
            badge.hidden = true;
            badge.textContent = '0';
        }
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderItems(items) {
        if (!items || !items.length) {
            body.innerHTML = '<div class="header-notify-empty">No new notifications</div>';
            return;
        }
        var html = '';
        items.forEach(function (n) {
            var link = n.link || '#';
            var ico = n.type === 'inquiry_added' ? 'fa-clipboard-question' : 'fa-bell';
            var unreadCls = n.is_read ? '' : ' is-unread';
            html += '<a class="header-notify-item' + unreadCls + '" href="' + escapeHtml(link) + '" data-id="' + (n.id || 0) + '">'
                + '<span class="header-notify-ico"><i class="fa-solid ' + ico + '"></i></span>'
                + '<span class="header-notify-meta">'
                + '<strong>' + escapeHtml(n.title) + '</strong>'
                + '<small>' + escapeHtml(n.message) + '</small>'
                + '<small class="header-notify-time">' + escapeHtml(n.time_ago || '') + '</small>'
                + '</span></a>';
        });
        body.innerHTML = html;
    }

    function postAction(action, extra) {
        if (!ajaxUrl) {
            return Promise.reject();
        }
        var fd = new FormData();
        fd.append('action', action);
        if (extra && extra.id) {
            fd.append('id', String(extra.id));
        }
        return fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: fd
        }).then(function (res) {
            return res.json();
        });
    }

    function refreshList() {
        postAction('list').then(function (data) {
            if (!data || !data.ok) return;
            setBadge(data.unread);
            renderItems(data.items || []);
        }).catch(function () { /* silent */ });
    }

    btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (panel.hidden) {
            openPanel();
        } else {
            closePanel();
        }
    });

    document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) {
            closePanel();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closePanel();
        }
    });

    if (markAllBtn) {
        markAllBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            postAction('mark_all').then(function (data) {
                if (!data || !data.ok) return;
                setBadge(data.unread);
                renderItems(data.items || []);
            }).catch(function () { /* silent */ });
        });
    }

    body.addEventListener('click', function (e) {
        var item = e.target.closest('.header-notify-item');
        if (!item) return;
        var id = parseInt(item.getAttribute('data-id') || '0', 10);
        if (id > 0) {
            // mark read; navigation continues via href
            postAction('mark_read', { id: id }).then(function (data) {
                if (data && data.ok) {
                    setBadge(data.unread);
                }
            }).catch(function () { /* silent */ });
        }
    });

    // Soft polling so admin sees new inquiry notifications without refresh
    setInterval(function () {
        if (document.hidden) return;
        postAction('list').then(function (data) {
            if (!data || !data.ok) return;
            setBadge(data.unread);
            if (!panel.hidden) {
                renderItems(data.items || []);
            }
        }).catch(function () { /* silent */ });
    }, 25000);
})();
