<?php
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


/**
 * @var CPartial $this
 * @var array    $data
 */

if ($data['readonly'] && !$data['macros']) {
	$table = new CObject(_('No macros found.'));
}
else {
	$table = (new CTable())
		->setId('tbl_macros')
		->addClass(ZBX_STYLE_TEXTAREA_FLEXIBLE_CONTAINER)
		->addClass('host-macros-table')
		->setColumns([
			(new CTableColumn(_('Macro')))->addClass('table-col-macro'),
			(new CTableColumn(_('Value')))->addClass('table-col-value'),
			(new CTableColumn(_('Description')))->addClass('table-col-description'),
			$data['readonly'] ? null : (new CTableColumn())->addClass('table-col-action')
		]);

	if ($data['has_inline_validation']) {
		$table
			->setAttribute('data-field-type', 'set')
			->setAttribute('data-field-name', 'macros');
	}

	foreach ($data['macros'] as $i => $macro) {
		$value = array_key_exists('value', $macro) ? $macro['value'] : null;
		$macro_value = (new CMacroValue($macro['type'], 'macros['.$i.']', $value, false))
			->setErrorContainer($data['has_inline_validation'] ? 'macros_'.$i.'_error_container' : null)
			->setReadonly($data['readonly']
				|| !($macro['discovery_state'] & CControllerHostMacrosList::DISCOVERY_STATE_CONVERTING)
			)
			->setErrorLabel(_('Value'));

		$macro_cell = [
			(new CTextAreaFlexible('macros['.$i.'][macro]', $macro['macro']))
				->setErrorContainer($data['has_inline_validation'] ? 'macros_'.$i.'_error_container' : null)
				->setReadonly($data['readonly']
					|| $macro['discovery_state'] != CControllerHostMacrosList::DISCOVERY_STATE_MANUAL
				)
				->addClass('macro')
				->setWidth(ZBX_TEXTAREA_MACRO_WIDTH)
				->setAttribute('placeholder', '{$MACRO}')
				->disableSpellcheck()
				->setErrorLabel($data['has_inline_validation'] ? _('Macro'): null)
		];

		if (!$data['readonly']) {
			$macro_cell[] = new CVar('macros['.$i.'][discovery_state]', $macro['discovery_state']);

			if (array_key_exists('hostmacroid', $macro)) {
				$macro_cell[] = new CVar('macros['.$i.'][hostmacroid]', $macro['hostmacroid']);
			}

			if (array_key_exists('allow_revert', $macro)) {
				$macro_value->addRevertButton();
				$macro_value->setRevertButtonVisibility($macro['type'] != ZBX_MACRO_TYPE_SECRET
					|| array_key_exists('value', $macro)
				);

				$macro_cell[] = new CVar('macros['.$i.'][allow_revert]', '1');
			}

			if ($macro['discovery_state'] != CControllerHostMacrosList::DISCOVERY_STATE_MANUAL) {
				$macro_cell[] = new CVar('macros['.$i.'][original_value]', $macro['original']['value']);
				$macro_cell[] = new CVar('macros['.$i.'][original_description]', $macro['original']['description']);
				$macro_cell[] = new CVar('macros['.$i.'][original_macro_type]', $macro['original']['type']);
			}
		}

		if (!$data['readonly']) {
			$action_column = [];

			if ($macro['discovery_state'] == CControllerHostMacrosList::DISCOVERY_STATE_AUTOMATIC) {
				$action_column[] = (new CButton('macros['.$i.'][change_state]', _x('Change', 'verb')))
					->addClass(ZBX_STYLE_BTN_LINK)
					->addClass('element-table-set-manual');
			}
			elseif ($macro['discovery_state'] == CControllerHostMacrosList::DISCOVERY_STATE_CONVERTING) {
				$action_column[] = (new CButton('macros['.$i.'][change_state]', _('Revert')))
					->addClass(ZBX_STYLE_BTN_LINK)
					->addClass('element-table-set-manual');
			}

			$action_column[] = (new CButton('macros['.$i.'][remove]', _('Remove')))
				->addClass(ZBX_STYLE_BTN_LINK)
				->addClass('element-table-remove');

			if ($macro['discovery_state'] != CControllerHostMacrosList::DISCOVERY_STATE_MANUAL) {
				$action_column[] = (new CSpan(_('(created by host discovery)')))->addClass(ZBX_STYLE_GREY);
			}
		}

		$row = [
			(new CCol($macro_cell))->addClass(ZBX_STYLE_TEXTAREA_FLEXIBLE_PARENT),
			(new CCol($macro_value))
				->addClass(ZBX_STYLE_TEXTAREA_FLEXIBLE_PARENT)
				->addClass(ZBX_STYLE_NOWRAP),
			(new CCol(
				(new CTextAreaFlexible('macros['.$i.'][description]', $macro['description']))
					->setErrorContainer($data['has_inline_validation'] ? 'macros_'.$i.'_error_container' : null)
					->setWidth(ZBX_TEXTAREA_MACRO_VALUE_WIDTH)
					->setMaxlength(DB::getFieldLength('hostmacro', 'description'))
					->setReadonly($data['readonly']
						|| !($macro['discovery_state'] & CControllerHostMacrosList::DISCOVERY_STATE_CONVERTING)
					)
					->setAttribute('placeholder', _('description'))
			))->addClass(ZBX_STYLE_TEXTAREA_FLEXIBLE_PARENT),
			$data['readonly'] ? null : new CCol(new CHorList($action_column))
		];

		$table
			->addRow($row, 'form_row')
			->addRow($data['has_inline_validation']
				? (new CCol())
					->setId('macros_'.$i.'_error_container')
					->setAttribute('colspan', count($row))
					->addClass(ZBX_STYLE_ERROR_CONTAINER)
				: null
			);
	}

	if (!$data['readonly']) {
		$table->setFooter(new CCol(
			(new CButton('macro_add', _('Add')))
				->addClass(ZBX_STYLE_BTN_LINK)
				->addClass('element-table-add')
		));
	}
}

$table->show();

// Initializing input secret and macro value init script separately.
(new CScriptTag("jQuery('.input-secret').inputSecret();"))->show();
(new CScriptTag("jQuery('.macro-input-group').macroValue();"))->show();
