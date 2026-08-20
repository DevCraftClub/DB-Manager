/**
 * DB Manager — AJAX UI (настройки Telegram, экспорт/импорт).
 */
(function (global) {
    'use strict';

    if (!global.DevCraft) {
        console.error('[DB Manager] Сначала должен быть загружен DevCraft core.');
        return;
    }

    const DevCraftAjax = global.DevCraft.Ajax;
    const DevCraftMetro = global.DevCraft.Metro;
    const t = function (phrase, params) {
        return global.DevCraft.__(phrase, params || {});
    };

    function moduleMod() {
        return document.body.dataset.mod || 'db_manager';
    }

    function ajaxUrl(method) {
        return DevCraftAjax.url(DevCraftAjax.baseUrl(), {
            mod: moduleMod(),
            controller: 'admin',
            method: method,
        });
    }

    function postAction(method, data) {
        return fetch(ajaxUrl(method), {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                user_hash: DevCraftAjax.getUserHash(),
                data: JSON.stringify(data || {}),
            }).toString(),
        }).then(DevCraftAjax.parseResponse);
    }

    function collectTables() {
        const tables = [];

        document.querySelectorAll('.js-dbm-manager-form input[name="table[]"]:checked').forEach(function (el) {
            tables.push(el.value);
        });

        return { table: tables };
    }

    function initTelegramButton() {
        document.addEventListener('click', function (event) {
            const btn = event.target.closest('.js-dbm-send-tg');

            if (!btn) {
                return;
            }

            const tokenInput = document.querySelector('[name="tg_token"]');
            const chatInput = document.querySelector('[name="tg_chat"]');
            const token = tokenInput ? tokenInput.value : '';
            const chat = chatInput ? chatInput.value : '';

            if (token === '' || chat === '') {
                DevCraftMetro.notify(t('Не заполненные поля!'), t('Поля с токеном и ID чата должны быть заполнены!'), 'warning');
                return;
            }

            postAction('send_message', { bot: token, chat: chat })
                .then(function (payload) {
                    DevCraftAjax.handleNotice(payload);
                })
                .catch(function (err) {
                    DevCraftMetro.notifyError(t('Ошибка'), t('Сеть или сервер недоступен'), err);
                });
        });
    }

    function initManagerPage() {
        const managerForm = document.querySelector('.js-dbm-manager-form');

        if (!managerForm) {
            return;
        }

        managerForm.querySelectorAll('input[data-role="checkbox"]').forEach(function (el) {
            DevCraftMetro.makePlugin(el, 'checkbox');
        });

        document.querySelector('.js-dbm-all-tables')?.addEventListener('change', function () {
            const checked = this.checked;

            document.querySelectorAll('.js-dbm-manager-form input[name="table[]"]').forEach(function (el) {
                el.checked = checked;
            });
        });

        document.querySelector('.js-dbm-export')?.addEventListener('click', function () {
            postAction('export', collectTables())
                .then(function (payload) {
                    DevCraftAjax.handleNotice(payload);

                    if (payload.success) {
                        window.location.reload();
                    }
                })
                .catch(function (err) {
                    DevCraftMetro.notifyError(t('Ошибка'), t('Сеть или сервер недоступен'), err);
                });
        });

        document.querySelectorAll('.js-dbm-delete').forEach(function (btn) {
            btn.addEventListener('click', function () {
                postAction('delete_file', { file_name: btn.dataset.name })
                    .then(function (payload) {
                        DevCraftAjax.handleNotice(payload);

                        if (payload.success) {
                            window.location.reload();
                        }
                    });
            });
        });

        document.querySelectorAll('.js-dbm-import').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!global.confirm(t('Восстановить базу данных из этого файла?'))) {
                    return;
                }

                postAction('import', { file_name: btn.dataset.name })
                    .then(function (payload) {
                        DevCraftAjax.handleNotice(payload);
                    });
            });
        });

        document.querySelectorAll('.js-dbm-download').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const form = document.getElementById('js-dbm-download-form');
                const dataInput = form ? form.querySelector('input[name="data"]') : null;

                if (!form || !dataInput) {
                    return;
                }

                dataInput.value = JSON.stringify({ file_name: btn.dataset.name });
                form.submit();
            });
        });
    }

    function init() {
        initTelegramButton();
        initManagerPage();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window);
