<?php declare(strict_types = 0);
/*
** Copyright (C) 2001-2025 Zabbix SIA
**
** This program is free software: you can redistribute it and/or modify it under the terms of
** the GNU Affero General Public License as published by the Free Software Foundation, version 3.
**
** This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
** without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
** See the GNU Affero General Public License for more details.
**
** You should have received a copy of the GNU Affero General Public License along with this program.
** If not, see <https://www.gnu.org/licenses/>.
**/
?>


window.module_edit = new class {

	init({rules}) {
		this.overlay = overlays_stack.getById('module.edit');
		this.dialogue = this.overlay.$dialogue[0];
		this.form_element = this.overlay.$dialogue.$body[0].querySelector('form');
		this.form = new CForm(this.form_element, rules);

		const return_url = new URL('zabbix.php', location.href);

		return_url.searchParams.set('action', 'module.list');
		ZABBIX.PopupManager.setReturnUrl(return_url.href);
	}

	submit() {
		const fields = getFormFields(this.form_element);

		this.form.validateSubmit(fields)
			.then((result) => {
				if (!result) {
					return;
				}

				const curl = new Curl('zabbix.php');

				curl.setArgument('action', 'module.update');

				this._post(curl.getUrl(), fields, (response) => {
					if (!response) {
						return;
					}

					overlayDialogueDestroy(this.overlay.dialogueid);

					this.dialogue.dispatchEvent(new CustomEvent('dialogue.submit', {detail: response}));
				});
			})
			.finally(() => this.overlay.unsetLoading());
	}

	_post(url, data, success_callback) {
		fetch(url, {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify(data)
		})
			.then((response) => response.json())
			.then((response) => {
				if ('form_errors' in response) {
					this.form.setErrors(response.form_errors, true, true);
					this.form.renderErrors();

					return false;
				}
				else if ('error' in response) {
					throw {error: response.error};
				}

				return response;
			})
			.then(success_callback)
			.catch((exception) => {
				for (const element of this.form_element.parentNode.children) {
					if (element.matches('.msg-good, .msg-bad, .msg-warning')) {
						element.parentNode.removeChild(element);
					}
				}

				let title, messages;

				if (typeof exception === 'object' && 'error' in exception) {
					title = exception.error.title;
					messages = exception.error.messages;
				}
				else {
					messages = [<?= json_encode(_('Unexpected server error.')) ?>];
				}

				const message_box = makeMessageBox('bad', messages, title)[0];

				this.form_element.parentNode.insertBefore(message_box, this.form_element);
			})
			.finally(() => {
				this.overlay.unsetLoading();
			});
	}
}
