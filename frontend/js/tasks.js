
(function($) {

    $.ctasksPluginTemp = (function($) {

        const createTempBtn = $('.ctasks-add-task');
        const tempList = $('.ctasks-temp-list');
        const notBlock = $('.ctasks-not-block');

        let tasksImgDir;
        let teamBackendUrl;
        let crmBackendUrl;
        let temps = {};
        
        const Plugin = function(tasks, projects, funnels, appsRootUrl, backendUrl, is_shop) {

            tasksImgDir = appsRootUrl + 'wa-apps/tasks/img/';
            teamBackendUrl = backendUrl + 'team/';
            crmBackendUrl = backendUrl + 'crm/';
            temps = tasks;

            let show_not = true;

            for (const id in temps) {
                
                this.addTask(temps[id], true);
                show_not = false;
            }

            // Remove temp
            tempList.on('click', '.ctasks-temp-item__remove', event =>
                removeTask($(event.currentTarget).closest('.ctasks-temp-item'))
            );
    
            if (show_not) {
                notBlock.removeClass('hidden');
            }

            if (!projects.length) {
                return notBlock.find('p').text('У вас нет доступа к проектам');
            }

            createTempBtn.removeClass('hidden');

            const editDialog = new $.ctasksPluginTempEdit(projects, funnels, is_shop, crmBackendUrl);

            // Create temp dialog
            createTempBtn.click(() => editDialog.open(null));

            // Edit temp dialog
            tempList.on('click', '.ctasks-temp-item__header-title strong, .ctasks-temp-item__edit', event => 
                editDialog.open(temps[event.currentTarget.closest('.ctasks-temp-item').getAttribute('data-id')])
            );
        };

        Plugin.prototype.addTask = function(task, is_init = false) {
            
            if (!is_init) {
                notBlock.addClass('hidden');
                temps[task.id] = task;
            }

            tempList.append(getTaskHtml(task));
            tempList.find(`.ctasks-temp-item[data-id="${task.id}"] .ctasks-temp-item__footer-actions`).waDropdown({ hover: false });
        }

        Plugin.prototype.updateTask = function(task) {

            temps[task.id] = task;
            tempList.find(`.ctasks-temp-item[data-id="${task.id}"]`).replaceWith(getTaskHtml(task));
            tempList.find(`.ctasks-temp-item[data-id="${task.id}"] .ctasks-temp-item__footer-actions`).waDropdown({ hover: false });
        }

        Plugin.prototype.alertWindow = function(str) {
            $.waDialog({
                html: `
                <div class="dialog">
                    <div class="dialog-background"></div>
                    <div class="dialog-body">
                        <div class="dialog-content">${str}</div>
                        <footer class="dialog-footer">
                            <button class="js-close-dialog button">Ок</button>
                        </footer>
                    </div>
                </div>`
            });
        }

        // HTML созданного шаблона задачи
        function getTaskHtml(task) {

            return `
            <div class="ctasks-temp-item" data-id="${task.id}">
                <img src="${tasksImgDir}tasks.svg">
                <div>
                    <div class="ctasks-temp-item__header">
                        <div class="ctasks-temp-item__header-title">
                            <a href="javascript:void(0);"><strong>${task.name}</strong></a>
                            <span class="ctasks-temp-item__project">${getProjectHtml(task)}</span>
                            <span class="ctasks-temp-item__priority">${getPriorityHtml(task.priority)}</span>
                        </div>
                        <span class="ctasks-temp-item__datetime hint">
                            ${$.ctasksPluginHelper.formatDatetime(task.create_datetime, true)}
                        </span>
                    </div>
                    <div class="ctasks-temp-item__contacts">
                        ${getUserHtml(task.src_user)}
                        <i class="fas fa-arrow-right text-light-gray"></i>
                        ${getUserHtml(task.dest_user)}
                    </div>
                    <div class="ctasks-temp-item__footer">
                        <span class="badge user light-gray ctasks-temp-item__badge">
                            <b>${getWhenFunnel(task)}</b>
                        </span>
                        <div class="dropdown ctasks-temp-item__footer-actions">
                            <span class="dropdown-toggle without-arrow"><i class="fas fa-ellipsis-h text-gray"></i></span>
                            <div class="dropdown-body right">
                                <ul class="menu">
                                    <li>
                                        <a class="ctasks-temp-item__edit">
                                            <i class="fas fa-pen text-blue"></i><span>Редактировать</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="ctasks-temp-item__remove">
                                            <i class="fas fa-trash-alt text-red"></i><span>Удалить</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
        }

        function getPriorityHtml(priority) {

            switch (+priority) {
                case 1: return '<i class="fas fa-exclamation-circle text-orange"></i>';
                case 2: return '<i class="fas fa-exclamation-circle text-red"></i>';
                case 3: return `<i class="ctasks-temp-priority-fire" style="background: url(${tasksImgDir}priority-fire.gif) no-repeat;"></i>`;
            }

            return '';
        }

        function getWhenFunnel(task) {

            if (!+task.funnel_id) {
                return 'Все воронки';
            }

            return task.funnel ? task.funnel.name : getNotTaskParamHtml('Воронка не найдена', false);
        }

        function getProjectHtml(task) {
            return task.project ? `<span class="hint">${task.project.name}</span>` : getNotTaskParamHtml('Проект недоступен', true);
        }

        function getUserHtml(user) {
            return user && user.login ? `
                <a href="${teamBackendUrl}u/${user.login}/" target="_blank">
                    <span class="ctasks-temp-item__contact">
                        <i class="ctasks-temp-item__contact-icon" style="background-image: url(${user.photo});"></i>
                        <span class="ctasks-temp-item__contact-name">${user.name}</span>
                    </span>
                </a>` : getNotTaskParamHtml('Контакт недоступен', true);
        }

        function getNotTaskParamHtml(str, badge) {

            let html = `
            <span class="ctasks-temp-item__not-param" title="Задача не будет создана, пока не отредактируете шаблон.">
                <i class="fas fa-exclamation-triangle text-orange"></i> ${str}
            </span>`;

            return badge ? `<span class="badge user light-gray ctasks-temp-item__badge">${html}</span>` : html;
        }

        function removeTask($taskNode) {
            $.waDialog({
                html: `
                <div class="dialog">
                    <div class="dialog-background"></div>
                    <div class="dialog-body">
                        <div class="dialog-content">Вы уверены, что хотите удалить данный шаблон?</div>
                        <footer class="dialog-footer">
                            <button class="button red ctasks-remove">Удалить</button>
                            <button class="js-close-dialog button light-gray">Отмена</button>
                            <span class="ctasks-load" style="display: none;"><i class="fas fa-spinner wa-animation-spin speed-1000"></i></span>
                        </footer>
                    </div>
                </div>`,
                onOpen: (dialog, dialog_instance) => {
    
                    dialog.on('click', '.ctasks-remove', event => {
    
                        $(event.target).attr('disabled', true);
                        dialog.find('.ctasks-load').show();

                        let id = $taskNode.attr('data-id');
    
                        $.post('?plugin=ctasks&module=tempRemove', { id: id, _csrf: $.ctasksPluginHelper.getCsrf() }, () => {

                            delete temps[id];

                            if (!Object.keys(temps).length) {
                                notBlock.removeClass('hidden');
                            }

                            $taskNode.remove();
                            dialog_instance.close();
                        });
                    });
                }
            });
        }

        return Plugin;
    })($);
})(jQuery);