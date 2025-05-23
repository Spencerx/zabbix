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


class CFilter extends CDiv {

	// Filter form object name and id attribute.
	private const FORM_NAME = 'zbx_filter';

	// Filter form object.
	private $form;

	// Visibility of 'Apply', 'Reset' form buttons. Visibility is set to all tabs.
	private $show_buttons = true;

	/**
	 * Filter reset URL.
	 */
	private $reset_url;

	// Array of filter tab headers. Every header is mapped to it content via href(header) and id(content) attribute.
	protected $headers = [];
	// Array of filter tab content.
	protected $tabs = [];
	// jQuery.tabs initialization options.
	protected $tabs_options = [
		'collapsible' => true,
		'active' => false
	];
	// Profile data associated with filter object.
	protected $idx = null;
	protected $idx2 = 0;

	// Time period Div element.
	private $time_period;

	private $is_hidden = false;

	/**
	 * List of predefined time ranges.
	 */
	protected $time_ranges = [
		[
			['now-2d', 'now'],
			['now-7d', 'now'],
			['now-30d', 'now'],
			['now-3M', 'now'],
			['now-6M', 'now'],
			['now-1y', 'now'],
			['now-2y', 'now']
		],
		[
			['now-1d/d', 'now-1d/d'],
			['now-2d/d', 'now-2d/d'],
			['now-1w/d', 'now-1w/d'],
			['now-1w/w', 'now-1w/w'],
			['now-1M/M', 'now-1M/M'],
			['now-1y/y', 'now-1y/y']
		],
		[
			['now/d', 'now/d'],
			['now/d', 'now'],
			['now/w', 'now/w'],
			['now/w', 'now'],
			['now/M', 'now/M'],
			['now/M', 'now'],
			['now/y', 'now/y'],
			['now/y', 'now']
		],
		[
			['now-5m', 'now'],
			['now-15m', 'now'],
			['now-30m', 'now'],
			['now-1h', 'now'],
			['now-3h', 'now'],
			['now-6h', 'now'],
			['now-12h', 'now'],
			['now-24h', 'now']
		]
	];

	public function __construct() {
		parent::__construct();

		$this
			->setAttribute('data-accessible', 1)
			->addClass(ZBX_STYLE_FILTER_SPACE)
			->setId(uniqid('filter_'));

		$this->form = (new CForm('get'))
			->setAttribute('name', self::FORM_NAME);

		$this->reset_url = new CUrl();
	}

	public function setResetUrl(CUrl $url): self {
		$this->reset_url = (clone $url)
			->setArgument('filter_rst', 1)
			->getUrl();

		return $this;
	}

	/**
	 * Add variable to filter form.
	 *
	 * @param string $name      Variable name.
	 * @param string $value     Variable value.
	 *
	 * @return CFilter
	 */
	public function addVar($name, $value, $id = null) {
		$this->form->addVar($name, $value, $id);

		return $this;
	}

	/**
	 * Hide filter tab buttons. Should be called before addFilterTab.
	 */
	public function hideFilterButtons() {
		$this->show_buttons = false;

		return $this;
	}

	/**
	 * Set profile 'idx' and 'idx2' data.
	 *
	 * @param string $idx     Profile 'idx' string.
	 * @param int    $idx2    Profile 'idx2' identifier, default 0.
	 *
	 * @return CFilter
	 */
	public function setProfile($idx, $idx2 = 0) {
		$this->idx = $idx;
		$this->idx2 = $idx2;

		$this->setAttribute('data-profile-idx', $idx);
		$this->setAttribute('data-profile-idx2', $idx2);

		return $this;
	}

	/**
	 * Adds an item inside the form object.
	 *
	 * @param mixed $item  An item to add inside the form object.
	 *
	 * @return CFilter
	 */
	public function addFormItem($item) {
		$this->form->addItem($item);

		return $this;
	}

	/**
	 * Set active tab.
	 *
	 * @param int $tab  1 based index of active tab. If set to 0 all tabs will be collapsed.
	 *
	 * @return CFilter
	 */
	public function setActiveTab($tab) {
		$this->tabs_options['active'] = $tab > 0 ? $tab - 1 : false;

		return $this;
	}

	/**
	 * Add tab with filter form.
	 *
	 * @param string $header    Tab header title string.
	 * @param array  $columns   Array of filter columns markup.
	 * @param array  $footer    Additional markup objects for filter tab, default null.
	 *
	 * @return CFilter
	 */
	public function addFilterTab($header, $columns, $footer = null) {
		$row = (new CDiv())->addClass(ZBX_STYLE_ROW);
		$body = [];
		$anchor = 'tab_'.count($this->tabs);

		foreach ($columns as $column) {
			$row->addItem((new CDiv($column))->addClass(ZBX_STYLE_CELL));
		}

		$body[] = (new CDiv())
			->addClass(ZBX_STYLE_TABLE)
			->addClass(ZBX_STYLE_FILTER_FORMS)
			->addItem($row);

		if ($this->show_buttons) {
			$body[] = (new CDiv())
				->addClass(ZBX_STYLE_FILTER_FORMS)
				->addItem(
					(new CSubmitButton(_('Apply'), 'filter_set', 1))
						->onClick('javascript: chkbxRange.clearSelectedOnFilterChange();')
				)
				->addItem(
					(new CRedirectButton(_('Reset'), $this->reset_url))
						->addClass(ZBX_STYLE_BTN_ALT)
						->onClick('javascript: chkbxRange.clearSelectedOnFilterChange();')
				);
		}

		if ($footer !== null) {
			$body[] = $footer;
		}

		return $this->addTab(
			(new CLink($header, '#'.$anchor))
				->addClass(ZBX_STYLE_BTN)
				->addClass(ZBX_ICON_FILTER)
				->addClass(ZBX_STYLE_FILTER_TRIGGER),
			(new CDiv($body))
				->addClass(ZBX_STYLE_FILTER_CONTAINER)
				->setId($anchor)
		);
	}

	/**
	 * Add time selector specific tab. Should be called before any tab is added. Adds two tabs:
	 * - time selector range change buttons: back, zoom out, forward.
	 * - time selector range change form with predefined ranges.
	 *
	 * @param string $from        Start date. (can be in relative time format, example: now-1w)
	 * @param string $to          End date. (can be in relative time format, example: now-1w)
	 * @param bool   $visible     Either to make time selector visible or hidden.
	 * @param string $profileidx  Profile key.
	 * @param string $format      Date and time format used in CDateSelector.
	 *
	 * @return CFilter
	 */
	public function addTimeSelector(string $from, string $to, bool $visible = true, string $profileidx = '',
			string $format = ZBX_FULL_DATE_TIME): CFilter {
		$header = relativeDateToText($from, $to);

		if ($visible) {
			$this->addTab(new CDiv([
				(new CButtonIcon(ZBX_ICON_CHEVRON_LEFT))->addClass('js-btn-time-left'),
				(new CSimpleButton(_('Zoom out')))->addClass(ZBX_STYLE_BTN_TIME_ZOOMOUT),
				(new CButtonIcon(ZBX_ICON_CHEVRON_RIGHT))->addClass('js-btn-time-right')
			]), null);

			$predefined_ranges = [];

			foreach ($this->time_ranges as $column_ranges) {
				$column = (new CList())->addClass(ZBX_STYLE_TIME_QUICK);

				foreach ($column_ranges as $range) {
					$label = relativeDateToText($range[0], $range[1]);
					$is_selected = ($header === $label);

					$column->addItem((new CLink($label))
						->setAttribute('data-from', $range[0])
						->setAttribute('data-to', $range[1])
						->setAttribute('data-label', $label)
						->addClass($is_selected ? ZBX_STYLE_SELECTED : null)
					);
				}

				$predefined_ranges[] = (new CDiv($column))->addClass(ZBX_STYLE_CELL);
			}

			$anchor = 'tab_'.count($this->tabs);

			$this->addTab(
				(new CLink($header, '#'.$anchor))
					->addClass(ZBX_STYLE_BTN)
					->addClass(ZBX_ICON_CLOCK)
					->addClass(ZBX_STYLE_BTN_TIME),
				new CDiv()
			);

			$this->time_period = (new CDiv([
				(new CDiv([
					new CList([
						new CLabel(_('From'), 'from'),
						(new CDateSelector('from', $from))->setDateFormat($format)
					]),
					(new CList([(new CListItem(''))->addClass(ZBX_STYLE_RED)]))
						->setAttribute('data-error-for', 'from')
						->addClass(ZBX_STYLE_TIME_INPUT_ERROR)
						->addStyle('display: none'),
					new CList([
						new CLabel(_('To'), 'to'),
						(new CDateSelector('to', $to))->setDateFormat($format)
					]),
					(new CList([(new CListItem(''))->addClass(ZBX_STYLE_RED)]))
						->setAttribute('data-error-for', 'to')
						->addClass(ZBX_STYLE_TIME_INPUT_ERROR)
						->addStyle('display: none'),
					new CList([
						new CButton('apply', _('Apply'))
					])
				]))->addClass(ZBX_STYLE_TIME_INPUT),
				(new CDiv($predefined_ranges))->addClass(ZBX_STYLE_TIME_QUICK_RANGE)
			]))
				->addClass(ZBX_STYLE_FILTER_CONTAINER)
				->addClass(ZBX_STYLE_TIME_SELECTION_CONTAINER)
				->setId($anchor);

			if ($profileidx !== '') {
				$this->form->addItem((new CVar('from',
					CProfile::get($profileidx.'.from', 'now-'.CSettingsHelper::get(CSettingsHelper::PERIOD_DEFAULT))
				))->removeId());
				$this->form->addItem((new CVar('to', CProfile::get($profileidx.'.to', 'now')))->removeId());
			}
		}
		else {
			$this
				->setAttribute('data-accessible', 0)
				->addTab(null, (new CDiv([
					new CVar('from', $from),
					new CVar('to', $to)
				])));
		}

		return $this;
	}

	/**
	 * Disable initial check of time selector parameters. All controls will be initially enabled.
	 *
	 * Required to invoke jQuery.publish('timeselector.update-ui') to update controls according to the time selected.
	 *
	 * @return CFilter
	 */
	public function setManualSetup(): CFilter {
		$this->setAttribute('data-manual-setup', 1);

		return $this;
	}

	/**
	 * Prevent modifying history URL with time selector parameters.
	 *
	 * @return CFilter
	 */
	public function preventHistoryUpdates(): CFilter {
		$this->setAttribute('data-prevent-history-updates', 1);

		return $this;
	}

	/**
	 * Prevent displaying tabs automatically.
	 *
	 * @return CFilter
	 */
	public function setHidden(): CFilter {
		$this->is_hidden = true;

		return $this;
	}

	/**
	 * Add tab.
	 *
	 * @param string|CTag $header  Tab header title string or CTag container.
	 * @param array|CTag  $body    Array of body elements.
	 *
	 * @return CFilter
	 */
	public function addTab($header, $body) {
		$this->headers[] = $header;
		$this->tabs[] = $body;

		return $this;
	}

	/**
	 * Return javascript code for jquery-ui initialization.
	 *
	 * @return string
	 */
	private function getJS() {
		$id = '#'.$this->getId();

		$js = 'const tabs = jQuery("'.$id.'").tabs('.json_encode($this->tabs_options).');';

		if (!$this->is_hidden) {
			$js .= 'tabs.show();';
		}

		// Set the focus to a field with autofocus after the filter becomes visible.
		$js .= 'jQuery("[autofocus=autofocus]", jQuery("'.$id.'")).filter(":visible").focus();';

		if ($this->idx !== null && $this->idx !== '') {
			$js .= 'jQuery("'.$id.'").on("tabsactivate", function(e, ui) {'.
				'var active = ui.newPanel.length ? jQuery(this).tabs("option", "active") + 1 : 0;'.
				'updateUserProfile("'.$this->idx.'.active", active, []);'.

				'if (active) {'.
					'jQuery("[autofocus=autofocus]", ui.newPanel).focus();'.
				'}'.
			'});';
		}

		return $js;
	}

	/**
	 * Render current CFilter object as HTML string.
	 *
	 * @return string
	 */
	public function toString($destroy = true) {
		$headers = (new CList())->addClass(ZBX_STYLE_FILTER_BTN_CONTAINER);
		$headers_cnt = 0;

		if ($this->tabs_options['active'] !== false
				&& !array_key_exists($this->tabs_options['active'], $this->headers)) {
			$this->tabs_options['active'] = 0;
		}

		foreach ($this->headers as $index => $header) {
			if ($header) {
				$headers->addItem($header);
				$headers_cnt++;
			}

			if ($this->tabs[$index] !== null && $index !== $this->tabs_options['active']) {
				$this->tabs[$index]->addStyle('display: none');
			}
		}

		$this
			->addStyle('display:none')
			->form->addItem($this->tabs);

		if ($headers_cnt) {
			$this
				->addItem($headers)
				->setAttribute('aria-label', _('Filter'));
		}

		$this->addItem($this->form);
		$this->addItem($this->time_period);

		return parent::toString($destroy).($headers_cnt ? get_js($this->getJS()) : '');
	}
}
