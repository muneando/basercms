<?php

/* SVN FILE: $Id$ */
/**
 * FormHelper 拡張クラス
 *
 * PHP versions 5
 *
 * baserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright 2008 - 2013, baserCMS Users Community <http://sites.google.com/site/baserusers/>
 *
 * @copyright		Copyright 2008 - 2013, baserCMS Users Community
 * @link			http://basercms.net baserCMS Project
 * @package			baser.view.helpers
 * @since			baserCMS v 0.1.0
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://basercms.net/license/index.html
 */
/**
 * Include files
 */
App::uses('HtmlHelper', 'View/Helper');
App::uses('FormHelper', 'View/Helper');
App::uses('BcTimeHelper', 'View/Helper');
App::uses('BcTextHelper', 'View/Helper');
App::uses('BcCkeditorHelper', 'View/Helper');

/**
 * FormHelper 拡張クラス
 *
 * @package Web.helpers
 */
class BcFormHelper extends FormHelper {

/**
 * ヘルパー
 *
 * @var array
 * @access public
 */
	public $helpers = array('Html', 'BcTime', 'BcText', 'Js', 'BcCkeditor');

/**
 * sizeCounter用の関数読み込み可否
 * 
 * @var boolean
 * @access public
 */
	public $sizeCounterFunctionLoaded = false;

/**
 * フォームID
 * 
 * @var string
 * @access private
 */
	private $__id = null;

/**
 * 都道府県用のSELECTタグを表示する
 *
 * @param string $fieldName Name attribute of the SELECT
 * @param mixed $selected Selected option
 * @param array $attributes Array of HTML options for the opening SELECT element
 * @return string 都道府県用のSELECTタグ
 * @access public
 */
	public function prefTag($fieldName, $selected = null, $attributes = array()) {

		$options = $this->BcText->prefList();
		$attributes['value'] = $selected;
		$attributes['empty'] = false;
		return $this->select($fieldName, $options, $attributes);
	}

/**
 * Returns a set of SELECT elements for a full datetime setup: day, month and year, and then time.
 *
 * ### Attributes:
 *
 * - `monthNames` If false, 2 digit numbers will be used instead of text.
 *   If a array, the given array will be used.
 * - `minYear` The lowest year to use in the year select
 * - `maxYear` The maximum year to use in the year select
 * - `interval` The interval for the minutes select. Defaults to 1
 * - `separator` The contents of the string between select elements. Defaults to '-'
 * - `empty` - If true, the empty select option is shown. If a string,
 *   that string is displayed as the empty element.
 * - `round` - Set to `up` or `down` if you want to force rounding in either direction. Defaults to null.
 * - `value` | `default` The default value to be used by the input. A value in `$this->data`
 *   matching the field name will override this value. If no default is provided `time()` will be used.
 *
 * @param string $fieldName Prefix name for the SELECT element
 * @param string $dateFormat DMY, MDY, YMD, or null to not generate date inputs.
 * @param string $timeFormat 12, 24, or null to not generate time inputs.
 * @param array|string $attributes array of Attributes
 * @return string Generated set of select boxes for the date and time formats chosen.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::dateTime
 */
	public function dateTime($fieldName, $dateFormat = 'DMY', $timeFormat = '12', $attributes = array()) {
		$attributes += array('empty' => true, 'value' => null);
		$year = $month = $day = $hour = $min = $meridian = null;

		if (empty($attributes['value'])) {
			$attributes = $this->value($attributes, $fieldName);
		}

		if ($attributes['value'] === null && $attributes['empty'] != true) {
			$attributes['value'] = time();
			if (!empty($attributes['maxYear']) && $attributes['maxYear'] < date('Y')) {
				$attributes['value'] = strtotime(date($attributes['maxYear'] . '-m-d'));
			}
		}

		if (!empty($attributes['value'])) {
			list($year, $month, $day, $hour, $min, $meridian) = $this->_getDateTimeValue(
				$attributes['value'],
				$timeFormat
			);
		}

		// >>> CUSTOMIZE MODIFY 2011/01/11 ryuring	日本対応
		/* $defaults = array(
			'minYear' => null, 'maxYear' => null, 'separator' => '-',
			'interval' => 1, 'monthNames' => true, 'round' => null
		); */
		// ---
		$defaults = array(
			'minYear' => null, 'maxYear' => null, 'separator' => ' ',
			'interval' => 1, 'monthNames' => '', 'round' => null
		);
		// <<<

		$attributes = array_merge($defaults, (array)$attributes);
		if (isset($attributes['minuteInterval'])) {
			$attributes['interval'] = $attributes['minuteInterval'];
			unset($attributes['minuteInterval']);
		}
		$minYear = $attributes['minYear'];
		$maxYear = $attributes['maxYear'];
		$separator = $attributes['separator'];
		$interval = $attributes['interval'];
		$monthNames = $attributes['monthNames'];
		$round = $attributes['round'];
		$attributes = array_diff_key($attributes, $defaults);

		if (!empty($interval) && $interval > 1 && !empty($min)) {
			$current = new DateTime();
			if ($year !== null) {
				$current->setDate($year, $month, $day);
			}
			if ($hour !== null) {
				$current->setTime($hour, $min);
			}
			$changeValue = $min * (1 / $interval);
			switch ($round) {
				case 'up':
					$changeValue = ceil($changeValue);
					break;
				case 'down':
					$changeValue = floor($changeValue);
					break;
				default:
					$changeValue = round($changeValue);
			}
			$change = ($changeValue * $interval) - $min;
			$current->modify($change > 0 ? "+$change minutes" : "$change minutes");
			$format = ($timeFormat == 12) ? 'Y m d h i a' : 'Y m d H i a';
			$newTime = explode(' ', $current->format($format));
			list($year, $month, $day, $hour, $min, $meridian) = $newTime;
		}

		$keys = array('Day', 'Month', 'Year', 'Hour', 'Minute', 'Meridian');
		$attrs = array_fill_keys($keys, $attributes);

		$hasId = isset($attributes['id']);
		if ($hasId && is_array($attributes['id'])) {
			// check for missing ones and build selectAttr for each element
			$attributes['id'] += array(
				'month' => '',
				'year' => '',
				'day' => '',
				'hour' => '',
				'minute' => '',
				'meridian' => ''
			);
			foreach ($keys as $key) {
				$attrs[$key]['id'] = $attributes['id'][strtolower($key)];
			}
		}
		if ($hasId && is_string($attributes['id'])) {
			// build out an array version
			foreach ($keys as $key) {
				$attrs[$key]['id'] = $attributes['id'] . $key;
			}
		}

		if (is_array($attributes['empty'])) {
			$attributes['empty'] += array(
				'month' => true,
				'year' => true,
				'day' => true,
				'hour' => true,
				'minute' => true,
				'meridian' => true
			);
			foreach ($keys as $key) {
				$attrs[$key]['empty'] = $attributes['empty'][strtolower($key)];
			}
		}

		$selects = array();
		foreach (preg_split('//', $dateFormat, -1, PREG_SPLIT_NO_EMPTY) as $char) {
			switch ($char) {
				// >>> CUSTOMIZE ADD 2011/01/11 ryuring	和暦対応
				case 'W':
					$selects[] = $this->wyear($fieldName, $minYear, $maxYear, $year, $selectYearAttr, $showEmpty) . "年";
					break;
				// <<<
				case 'Y':
					$attrs['Year']['value'] = $year;
					
					// >>> CUSTOMIZE MODIFY 2011/01/11 ryuring	日本対応
					/* $selects[] = $this->year(
						$fieldName, $minYear, $maxYear, $attrs['Year']
					); */
					// ---
					$suffix = (preg_match('/^W/', $dateFormat)) ? '年' : '';
					$selects[] = $this->year(
							$fieldName, $minYear, $maxYear, $attrs['Year']
						) . $suffix;
					// <<<

					break;
				case 'M':
					$attrs['Month']['value'] = $month;
					$attrs['Month']['monthNames'] = $monthNames;
					
					// >>> CUSTOMIZE MODIFY 2011/01/11 ryuring	日本対応
					/* $selects[] = $this->month($fieldName, $attrs['Month']); */
					// ---
					$suffix = (preg_match('/^W/', $dateFormat)) ? '月' : '';
					$selects[] = $this->month($fieldName, $attrs['Month']) . $suffix;
					// <<<
					
					break;
				case 'D':
					$attrs['Day']['value'] = $day;
					
					// >>> CUSTOMIZE MODIFY 2011/01/11 ryuring	日本対応
					/* $selects[] = $this->day($fieldName, $attrs['Day']); */
					// ---
					$suffix = (preg_match('/^W/', $dateFormat)) ? '日' : '';
					$selects[] = $this->day($fieldName, $attrs['Day']) . $suffix;
					// <<<

					break;
			}
		}
		$opt = implode($separator, $selects);

		$attrs['Minute']['interval'] = $interval;
		switch ($timeFormat) {
			case '24':
				$attrs['Hour']['value'] = $hour;
				$attrs['Minute']['value'] = $min;
				$opt .= $this->hour($fieldName, true, $attrs['Hour']) . ':' .
				$this->minute($fieldName, $attrs['Minute']);
				break;
			case '12':
				$attrs['Hour']['value'] = $hour;
				$attrs['Minute']['value'] = $min;
				$attrs['Meridian']['value'] = $meridian;
				$opt .= $this->hour($fieldName, false, $attrs['Hour']) . ':' .
				$this->minute($fieldName, $attrs['Minute']) . ' ' .
				$this->meridian($fieldName, $attrs['Meridian']);
				break;
		}
		return $opt;
	}

/**
 * 和暦年
 *
 * @param string $fieldName Prefix name for the SELECT element
 * @param integer $minYear First year in sequence
 * @param integer $maxYear Last year in sequence
 * @param string $selected Option which is selected.
 * @param array $attributes Attribute array for the select elements.
 * @param boolean $showEmpty Show/hide the empty select option
 * @return string
 */
	public function wyear($fieldName, $minYear = null, $maxYear = null, $selected = null, $attributes = array(), $showEmpty = true) {

		if ((empty($selected) || $selected === true) && $value = $this->value($fieldName)) {
			if (is_array($value)) {
				extract($value);
				$selected = $year;
			} else {
				if (empty($value)) {
					if (!$showEmpty && !$maxYear) {
						$selected = 'now';
					} elseif (!$showEmpty && $maxYear && !$selected) {
						$selected = $maxYear;
					}
				} else {
					$selected = $value;
				}
			}
		}

		if (strlen($selected) > 4 || $selected === 'now') {
			$wareki = $this->BcTime->convertToWareki(date('Y-m-d', strtotime($selected)));
			$wareki = $this->BcTime->convertToWareki($this->value($fieldName));
			$w = $this->BcTime->wareki($wareki);
			$wyear = $this->BcTime->wyear($wareki);
			$selected = $w . '-' . $wyear;
		} elseif ($selected === false) {
			$selected = null;
		} elseif (strpos($selected, '-') === false) {
			$wareki = $this->BcTime->convertToWareki($this->value($fieldName));
			if ($wareki) {
				$w = $this->BcTime->wareki($wareki);
				$wyear = $this->BcTime->wyear($wareki);
				$selected = $w . '-' . $wyear;
			} else {
				$selected = null;
			}
		}
		$yearOptions = array('min' => $minYear, 'max' => $maxYear);

		return $this->hidden($fieldName . ".wareki", array('value' => true)) .
			$this->select(
				$fieldName . ".year", $this->__generateOptions('wyear', $yearOptions), $selected, $attributes, $showEmpty
		);
	}

/**
 * コントロールソースを取得する
 * Model側でメソッドを用意しておく必要がある
 *
 * @param string $field フィールド名
 * @param array $options
 * @return array コントロールソース
 * @access public
 */
	public function getControlSource($field, $options = array()) {

		$count = preg_match_all('/\./is', $field, $matches);
		if ($count == 1) {
			list($modelName, $field) = explode('.', $field);
		} elseif ($count == 2) {
			list($plugin, $modelName, $field) = explode('.', $field);
			$modelName = $plugin . '.' . $modelName;
		}
		if (empty($modelName)) {
			$modelName = $this->model();
		}
		if (ClassRegistry::isKeySet($modelName)) {
			$model = ClassRegistry::getObject($modelName);
		} else {
			$model = ClassRegistry::init($modelName);
		}
		if ($model) {
			return $model->getControlSource($field, $options);
		} else {
			return false;
		}
	}

/**
 * モデルよりリストを生成する
 *
 * @param string $modelName
 * @param mixed $conditions
 * @param mixed $fields
 * @param mixed $order
 * @return mixed リストまたは、false
 * @access public
 */
	public function generateList($modelName, $conditions = array(), $fields = array(), $order = array()) {

		$model = ClassRegistry::getObject($modelName);

		if ($fields) {
			list($idField, $displayField) = $fields;
		} else {
			$idField = 'id';
			$displayField = $model->getDisplayField();
			$fields = array($idField, $displayField);
		}

		$list = $model->find('all', array('conditions' => $conditions, 'fields' => $fields, 'order' => $order));

		if ($list) {
			return Set::combine($list, "{n}." . $modelName . "." . $idField, "{n}." . $modelName . "." . $displayField);
		} else {
			return null;
		}
	}

/**
 * JsonList
 *
 * @param string $field フィールド文字列
 * @param string $attributes
 * @return array 属性
 * @access public
 */
	public function jsonList($field, $attributes) {

		am(array("imgSrc" => "", "ajaxAddAction" => "", "ajaxDelAction" => ""), $attributes);
		// JsonDb用Hiddenタグ
		$out = $this->hidden('Json.' . $field . '.db');
		// 追加テキストボックス
		$out .= $this->text('Json.' . $field . '.name');
		// 追加ボタン
		$out .= $this->button('追加', array('id' => 'btnAdd' . $field));
		// リスト表示用ビュー
		$out .= '<div id="Json' . $field . 'View"></div>';

		// javascript
		$out .= '<script type="text/javascript"><!--' . "\n" .
			'jQuery(function(){' . "\n" .
			'var json_List = new JsonList({"dbId":"Json' . $field . 'Db","viewId":"JsonTagView","addButtonId":"btnAdd' . $field . '",' . "\n" .
			'"deleteButtonType":"img","deleteButtonSrc":"' . $attributes['imgSrc'] . '","deleteButtonRollOver":true,' . "\n" .
			'"ajaxAddAction":"' . $attributes['ajaxAddAction'] . '",' . "\n" .
			'"ajaxDelAction":"' . $attributes['ajaxDelAction'] . '"});' . "\n" .
			'json_List.loadData();' . "\n" .
			'});' . "\n" .
			'//--></script>';

		return $out;
	}

/**
 * カレンダーコントロール付きのテキストフィールド
 * jquery-ui-1.7.2 必須
 *
 * @param string フィールド文字列
 * @param array HTML属性
 * @return string html
 * @access public
 */
	public function datepicker($fieldName, $attributes = array()) {

		if (!isset($attributes['value'])) {
			$value = $this->value($fieldName);
		} else {
			$value = $attributes['value'];
		}

		if ($value) {
			$value = $this->BcTime->format('Y/m/d', $value);
			if ($value) {
				$attributes['value'] = date('Y/m/d', strtotime($value));
			} else {
				$attributes['value'] = '';
			}
		} else {
			unset($attributes['value']);
		}

		$this->setEntity($fieldName);
		$id = $this->domId($fieldName);

		// テキストボックス
		$input = $this->text($fieldName, $attributes);

		// javascript
		$script = <<< DOC_END
<script type="text/javascript">
<!--
jQuery(function($){
	$("#{$id}").datepicker();
});
//-->
</script>
DOC_END;

		$out = $input . "\n" . $script;
		return $out;
	}

/**
 * 日付カレンダーと時間フィールド
 * 
 * @param string $fieldName
 * @param array $attributes
 * @return string
 * @access public
 */
	public function dateTimePicker($fieldName, $attributes = array()) {

		$this->Html->script('admin/jquery.timepicker', array('inline' => false));
		$this->Html->css('admin/jquery.timepicker', 'stylesheet', array('inline' => false));
		$timeAttributes = array('size' => 8, 'maxlength' => 8);
		if (!isset($attributes['value'])) {
			$value = $this->value($fieldName);
		} else {
			$value = $attributes['value'];
			unset($attributes['value']);
		}
		if ($value && $value != '0000-00-00 00:00:00') {
			$dateValue = date('Y/m/d', strtotime($value));
			$timeValue = date('H:i:s', strtotime($value));
			$attributes['value'] = $dateValue;
			$timeAttributes['value'] = $timeValue;
		}
		$dateTag = $this->datepicker($fieldName . '_date', $attributes);
		$timeTag = $this->text($fieldName . '_time', $timeAttributes);
		$hiddenTag = $this->hidden($fieldName, array('value' => $value));
		$domId = $this->domId();
		$_script = <<< DOC_END
<script type="text/javascript">
$(function(){
   $("#{$domId}Time").timepicker({ 'timeFormat': 'H:i' });
   $("#{$domId}Date").change({$domId}ChangeResultHandler);
   $("#{$domId}Time").change({$domId}ChangeResultHandler);
   function {$domId}ChangeResultHandler(){
		var value = $("#{$domId}Date").val().replace(/\//g, '-');
		if($("#{$domId}Time").val()) {
			value += ' '+$("#{$domId}Time").val();
		}
        $("#{$domId}").val(value);
		if(this.id.replace('{$domId}','') == 'Date') {
			if($("#{$domId}Date").val() && !$("#{$domId}Time").val()) {
				$("#{$domId}Time").val('00:00:00');
			}
		}
   }
});
</script>
DOC_END;
		$script = $this->_View->addScript($_script);
		return $dateTag . $timeTag . $hiddenTag;
	}

/**
 * Generates option lists for common <select /> menus
 *
 * @param string $name
 * @param array $options
 * @return array
 */
	protected function _generateOptions($name, $options = array()) {
		if (!empty($this->options[$name])) {
			return $this->options[$name];
		}
		$data = array();

		switch ($name) {
			case 'minute':
				if (isset($options['interval'])) {
					$interval = $options['interval'];
				} else {
					$interval = 1;
				}
				$i = 0;
				while ($i < 60) {
					$data[sprintf('%02d', $i)] = sprintf('%02d', $i);
					$i += $interval;
				}
				break;
			case 'hour':
				for ($i = 1; $i <= 12; $i++) {
					$data[sprintf('%02d', $i)] = $i;
				}
				break;
			case 'hour24':
				for ($i = 0; $i <= 23; $i++) {
					$data[sprintf('%02d', $i)] = $i;
				}
				break;
			case 'meridian':
				$data = array('am' => 'am', 'pm' => 'pm');
				break;
			case 'day':
				$min = 1;
				$max = 31;

				if (isset($options['min'])) {
					$min = $options['min'];
				}
				if (isset($options['max'])) {
					$max = $options['max'];
				}

				for ($i = $min; $i <= $max; $i++) {
					$data[sprintf('%02d', $i)] = $i;
				}
				break;
			case 'month':
				if ($options['monthNames'] === true) {
					$data['01'] = __d('cake', 'January');
					$data['02'] = __d('cake', 'February');
					$data['03'] = __d('cake', 'March');
					$data['04'] = __d('cake', 'April');
					$data['05'] = __d('cake', 'May');
					$data['06'] = __d('cake', 'June');
					$data['07'] = __d('cake', 'July');
					$data['08'] = __d('cake', 'August');
					$data['09'] = __d('cake', 'September');
					$data['10'] = __d('cake', 'October');
					$data['11'] = __d('cake', 'November');
					$data['12'] = __d('cake', 'December');
				} elseif (is_array($options['monthNames'])) {
					$data = $options['monthNames'];
				} else {
					for ($m = 1; $m <= 12; $m++) {
						$data[sprintf("%02s", $m)] = strftime("%m", mktime(1, 1, 1, $m, 1, 1999));
					}
				}
				break;
			case 'year':
				$current = intval(date('Y'));

				$min = !isset($options['min']) ? $current - 20 : (int)$options['min'];
				$max = !isset($options['max']) ? $current + 20 : (int)$options['max'];

				if ($min > $max) {
					list($min, $max) = array($max, $min);
				}
				if (
					!empty($options['value']) &&
					(int)$options['value'] < $min &&
					(int)$options['value'] > 0
				) {
					$min = (int)$options['value'];
				} elseif (!empty($options['value']) && (int)$options['value'] > $max) {
					$max = (int)$options['value'];
				}

				for ($i = $min; $i <= $max; $i++) {
					$data[$i] = $i;
				}
				if ($options['order'] !== 'asc') {
					$data = array_reverse($data, true);
				}
				break;
			// >>> CUSTOMIZE ADD 2011/01/11 ryuring	和暦対応
			case 'wyear':
				$current = intval(date('Y'));

				if (!isset($options['min'])) {
					$min = $current - 20;
				} else {
					$min = $options['min'];
				}

				if (!isset($options['max'])) {
					$max = $current + 20;
				} else {
					$max = $options['max'];
				}
				if ($min > $max) {
					list($min, $max) = array($max, $min);
				}
				for ($i = $min; $i <= $max; $i++) {
					$wyears = $this->BcTime->convertToWarekiYear($i);
					if ($wyears) {
						foreach ($wyears as $value) {
							list($w, $year) = explode('-', $value);
							$data[$value] = $this->BcTime->nengo($w) . ' ' . $year;
						}
					}
				}
				$data = array_reverse($data, true);
				break;
			// <<<
		}
		$this->_options[$name] = $data;
		return $this->_options[$name];
	}

/**
 * Creates a checkbox input widget.
 * MODIFIED 2008/10/24 egashira
 *          hiddenタグを出力しないオプションを追加
 *
 * @param string $fieldNamem Name of a field, like this "Modelname.fieldname"
 * @param array $options Array of HTML attributes.
 * 		'value' - the value of the checkbox
 * 		'checked' - boolean indicate that this checkbox is checked.
 * @todo Right now, automatically setting the 'checked' value is dependent on whether or not the
 * 		 checkbox is bound to a model.  This should probably be re-evaluated in future versions.
 * @return string An HTML text input element
 * @access public
 */
	public function checkbox($fieldName, $options = array()) {

		// CUSTOMIZE ADD 2011/05/07 ryuring
		// >>> hiddenをデフォルトオプションに追加
		$options = array_merge(array('hidden' => true), $options);
		$hidden = $options['hidden'];
		unset($options['hidden']);
		// <<<

		$options = $this->_initInputField($fieldName, $options);
		$value = current($this->value());

		if (!isset($options['value']) || empty($options['value'])) {
			$options['value'] = 1;
		} elseif (
			(!isset($options['checked']) && !empty($value) && $value === $options['value']) ||
			!empty($options['checked'])
		) {
			$options['checked'] = 'checked';
		}

		// CUSTOMIZE MODIFY 2011/05/07 ryuring
		// >>> hiddenオプションがある場合のみ、hiddenタグを出力
		/* $hiddenOptions = array(
		  'id' => $options['id'] . '_', 'name' => $options['name'],
		  'value' => '0', 'secure' => false
		  );
		  if (isset($options['disabled']) && $options['disabled'] == true) {
		  $hiddenOptions['disabled'] = 'disabled';
		  }
		  $output = $this->hidden($fieldName, $hiddenOptions); */
		// ---
		if ($hidden) {
			$hiddenOptions = array(
				'id' => $options['id'] . '_', 'name' => $options['name'],
				'value' => '0', 'secure' => false
			);
			if (isset($options['disabled']) && $options['disabled'] == true) {
				$hiddenOptions['disabled'] = 'disabled';
			}
			$output = $this->hidden($fieldName, $hiddenOptions);
		} else {
			$output = '';
		}
		// <<<
		// CUSTOMIZE MODIFY 2011/05/07 ryuring
		// >>> label を追加
		/* return $this->output($output . sprintf(
		  $this->Html->_tags['checkbox'],
		  $options['name'],
		  $this->_parseAttributes($options, array('name'), null, ' ')
		  )); */
		// ---
		if (!empty($options['label'])) {
			$label = '&nbsp;' . parent::label($fieldName, $options['label']);
		} else {
			$label = '';
		}
		return $this->output($output . sprintf(
					$this->Html->_tags['checkbox'], $options['name'], $this->_parseAttributes($options, array('name'), null, ' ')
			)) . $label;
		// <<<
	}

/**
 * Returns an array of formatted OPTION/OPTGROUP elements
 *
 * @param array $elements
 * @param array $parents
 * @param boolean $showParents
 * @param array $attributes
 * @return array
 */
	protected function _selectOptions($elements = array(), $parents = array(), $showParents = null, $attributes = array()) {
		$select = array();
		$attributes = array_merge(
			array('escape' => true, 'style' => null, 'value' => null, 'class' => null),
			$attributes
		);
		$selectedIsEmpty = ($attributes['value'] === '' || $attributes['value'] === null);
		$selectedIsArray = is_array($attributes['value']);

		$this->_domIdSuffixes = array();
		foreach ($elements as $name => $title) {
			$htmlOptions = array();
			if (is_array($title) && (!isset($title['name']) || !isset($title['value']))) {
				if (!empty($name)) {
					if ($attributes['style'] === 'checkbox') {
						$select[] = $this->Html->useTag('fieldsetend');
					} else {
						$select[] = $this->Html->useTag('optiongroupend');
					}
					$parents[] = $name;
				}
				$select = array_merge($select, $this->_selectOptions(
					$title, $parents, $showParents, $attributes
				));

				if (!empty($name)) {
					$name = $attributes['escape'] ? h($name) : $name;
					if ($attributes['style'] === 'checkbox') {
						$select[] = $this->Html->useTag('fieldsetstart', $name);
					} else {
						$select[] = $this->Html->useTag('optiongroup', $name, '');
					}
				}
				$name = null;
			} elseif (is_array($title)) {
				$htmlOptions = $title;
				$name = $title['value'];
				$title = $title['name'];
				unset($htmlOptions['name'], $htmlOptions['value']);
			}

			if ($name !== null) {
				$isNumeric = is_numeric($name);
				if (
					(!$selectedIsArray && !$selectedIsEmpty && (string)$attributes['value'] == (string)$name) ||
					($selectedIsArray && in_array((string)$name, $attributes['value'], !$isNumeric))
				) {
					if ($attributes['style'] === 'checkbox') {
						$htmlOptions['checked'] = true;
					} else {
						$htmlOptions['selected'] = 'selected';
					}
				}

				if ($showParents || (!in_array($title, $parents))) {
					$title = ($attributes['escape']) ? h($title) : $title;

					$hasDisabled = !empty($attributes['disabled']);
					if ($hasDisabled) {
						$disabledIsArray = is_array($attributes['disabled']);
						if ($disabledIsArray) {
							$disabledIsNumeric = is_numeric($name);
						}
					}
					if (
						$hasDisabled &&
						$disabledIsArray &&
						in_array((string)$name, $attributes['disabled'], !$disabledIsNumeric)
					) {
						$htmlOptions['disabled'] = 'disabled';
					}
					if ($hasDisabled && !$disabledIsArray && $attributes['style'] === 'checkbox') {
						$htmlOptions['disabled'] = $attributes['disabled'] === true ? 'disabled' : $attributes['disabled'];
					}

					if ($attributes['style'] === 'checkbox') {
						$htmlOptions['value'] = $name;

						$tagName = $attributes['id'] . $this->domIdSuffix($name);
						$htmlOptions['id'] = $tagName;
						$label = array('for' => $tagName);

						if (isset($htmlOptions['checked']) && $htmlOptions['checked'] === true) {
							$label['class'] = 'selected';
						}

						$name = $attributes['name'];

						if (empty($attributes['class'])) {
							$attributes['class'] = 'checkbox';
						} elseif ($attributes['class'] === 'form-error') {
							$attributes['class'] = 'checkbox ' . $attributes['class'];
						}
						$label = $this->label(null, $title, $label);
						$item = $this->Html->useTag('checkboxmultiple', $name, $htmlOptions);
						
						// CUSTOMIZE MODIFY 2014/02/24 ryuring
						// checkboxのdivを外せるオプションを追加
						// >>>
						//$select[] = $this->Html->div($attributes['class'], $item . $label);
						// ---
						if (isset($attributes['div']) && $attributes['div'] === false) {
							$select[] = $item . $label;
						} else {
							$select[] = $this->Html->div($attributes['class'], $item . $label);
						}
						// <<<
						
					} else {
						if ($attributes['escape']) {
							$name = h($name);
						}
						$select[] = $this->Html->useTag('selectoption', $name, $htmlOptions, $title);
					}
				}
			}
		}

		return array_reverse($select, true);
	}

/**
 * 文字列保存用複数選択コントロール
 * 
 * @param string $fieldName
 * @param array $options
 * @param mixed $selected
 * @param array $attributes
 * @param mixed $showEmpty
 * @return string
 * @access public
 */
	public function selectText($fieldName, $options = array(), $selected = null, $attributes = array(), $showEmpty = '') {

		$_attributes = array('separator' => '<br />', 'quotes' => true);
		$attributes = Set::merge($_attributes, $attributes);

		$quotes = $attributes['quotes'];
		unset($attributes['quotes']);

		$_options = $this->_initInputField($fieldName, $options);
		if (empty($attributes['multiple']))
			$attributes['multiple'] = 'checkbox';
		$id = $_options['id'];
		$_id = $_options['id'] . '_';
		$name = $_options['name'];
		$out = '<div id="' . $_id . '">' . $this->select($fieldName . '_', $options, $selected, $attributes, $showEmpty) . '</div>';
		$out .= $this->hidden($fieldName);
		$script = <<< DOC_END
$(document).ready(function() {
    aryValue = $("#{$id}").val().replace(/\'/g,"").split(",");
    for(key in aryValue){
        var value = aryValue[key];
        $("#"+camelize("{$id}_"+value)).attr('checked',true);
    }
    $("#{$_id} input[type=checkbox]").change(function(){
        var aryValue = [];
        $("#{$_id} input[type=checkbox]").each(function(key,value){
            if($(this).attr('checked')){
                aryValue.push("'"+$(this).val()+"'");
            }
        });
        $("#{$id}").val(aryValue.join(','));
    });
});
DOC_END;
		$out .= $this->Js->buffer($script);
		return $out;
	}

/**
 * Creates a hidden input field.
 *
 * @param string $fieldName Name of a field, in the form"Modelname.fieldname"
 * @param array $options Array of HTML attributes.
 * @return string
 * @access public
 */
	public function hidden($fieldName, $options = array()) {

		$secure = true;

		if (isset($options['secure'])) {
			$secure = $options['secure'];
			unset($options['secure']);
		}

		// 2010/07/24 ryuring
		// セキュリティコンポーネントのトークン生成の仕様として、
		// ・hiddenタグ以外はフィールド情報のみ
		// ・hiddenタグはフィールド情報と値
		// をキーとして生成するようになっている。
		// その場合、生成の元のなる値は、multipleを想定されておらず、先頭の値のみとなるが
		// multiple な hiddenタグの場合、送信される値は配列で送信されるので値違いで認証がとおらない。
		// という事で、multiple の場合は、あくまでhiddenタグ以外のようにフィールド情報のみを
		// トークンのキーとする事で認証を通すようにする。
		// >>> ADD
		if (!empty($options['multiple'])) {
			$secure = false;
			$this->_secure(true); //lock
		}
		// <<<

		$options = $this->_initInputField($fieldName, array_merge(
				$options, array('secure' => self::SECURE_SKIP)
		));

		if ($secure && $secure !== self::SECURE_SKIP) {
			$this->_secure(true, null, '' . $options['value']);
		}

		// CUSTOMIZE 2010/07/24 ryuring
		// 配列用のhiddenタグを出力できるオプションを追加
		// CUSTOMIZE 2010/08/01 ryuring
		// class属性を指定できるようにした
		// CUSTOMIZE 2011/03/11 ryuring
		// multiple で送信する値が配列の添字となっていたので配列の値に変更した
		// >>> ADD
		$multiple = false;
		$value = '';
		if (!empty($options['multiple'])) {
			$multiple = true;
			$options['id'] = null;
			if (!isset($options['value'])) {
				$value = $this->value($fieldName);
			} else {
				$value = $options['value'];
			}
			if (is_array($value) && !$value) {
				unset($options['value']);
			}
			unset($options['multiple']);
		}
		// <<<
		// >>> MODIFY
		// return $this->Html->useTag('hidden', $options['name'], array_diff_key($options, array('name' => '')));
		// ---
		if ($multiple && is_array($value)) {
			$out = array();
			foreach ($value as $_value) {
				$options['value'] = $_value;
				$out[] = $this->Html->useTag('hiddenmultiple', $options['name'], array_diff_key($options, array('name' => '')));
			}
			return implode("\n", $out);
		} else {
			return $this->Html->useTag('hidden', $options['name'], array_diff_key($options, array('name' => '')));
		}
		// <<<
	}

/**
 * CKEditorを出力する
 *
 * @param	string	$fieldName
 * @param	array	$options
 * @param	array	$editorOptions
 * @param	array	$styles
 * @return	string
 * @access	public
 */
	public function ckeditor($fieldName, $options = array()) {

		$options = array_merge(array('type' => 'textarea'), $options);
		return $this->BcCkeditor->editor($fieldName, $options);
	}

/**
 * create
 * フック用にラッピング
 * 
 * @param array $model
 * @param array $options
 * @return string
 * @access public
 */
	public function create($model = null, $options = array()) {

		$options = array_merge(array(
			'novalidate' => true
			), $options);

		$this->__id = $this->_getId($model, $options);

		/*** beforeCreate ***/
		$event = $this->dispatchEvent('beforeCreate', array(
			'id' => $this->__id,
			'options' => $options
			), array('class' => 'Form', 'plugin' => ''));
		if ($event !== false) {
			$options = $event->result === true ? $event->data['options'] : $event->result;
		}
		$out = parent::create($model, $options);

		/*** afterCreate ***/
		$event = $this->dispatchEvent('afterCreate', array(
			'id' => $this->__id,
			'out' => $out
			), array('class' => 'Form', 'plugin' => ''));
		if ($event !== false) {
			$out = $event->result === true ? $event->data['out'] : $event->result;
		}

		return $out;
	}

/**
 * end
 * フック用にラッピング
 *
 * @param	array	$options
 * @return	string
 * @access	public
 */
	public function end($options = null) {

		$id = $this->__id;
		$this->__id = null;

		/*** beforeEnd ***/
		$event = $this->dispatchEvent('beforeEnd', array(
			'id' => $id,
			'options' => $options
			), array('class' => 'Form', 'plugin' => ''));
		if ($event !== false) {
			$options = $event->result === true ? $event->data['options'] : $event->result;
		}

		$out = parent::end($options);

		/*** afterEnd ***/
		$event = $this->dispatchEvent('afterEnd', array(
			'id' => $id,
			'out' => $out
			), array('class' => 'Form', 'plugin' => ''));
		if ($event !== false) {
			$out = $event->result === true ? $event->data['out'] : $event->result;
		}

		return $out;
	}

/**
 * Generates a form input element complete with label and wrapper div
 *
 * Options - See each field type method for more information. Any options that are part of
 * $attributes or $options for the different type methods can be included in $options for input().
 *
 * - 'type' - Force the type of widget you want. e.g. ```type => 'select'```
 * - 'label' - control the label
 * - 'div' - control the wrapping div element
 * - 'options' - for widgets that take options e.g. radio, select
 * - 'error' - control the error message that is produced
 *
 * @param string $fieldName This should be "Modelname.fieldname"
 * @param array $options Each type of input takes different options.
 * @return string Completed form widget
 */
	public function input($fieldName, $options = array()) {

		/*** beforeInput ***/
		$event = $this->dispatchEvent('beforeInput', array(
			'fieldName' => $fieldName,
			'options' => $options
			), array('class' => 'Form', 'plugin' => ''));
		if ($event !== false) {
			$options = $event->result === true ? $event->data['options'] : $event->result;
		}

		$type = '';
		if (isset($options['type'])) {
			$type = $options['type'];
		}

		if (!isset($options['div'])) {
			$options['div'] = false;
		}

		if (!isset($options['error'])) {
			$options['error'] = false;
		}

		switch ($type) {
			case 'text':
			default :
				if (!isset($options['label'])) {
					$options['label'] = false;
				}
				break;
			case 'radio':
				if (!isset($options['legend'])) {
					$options['legend'] = false;
				}
				if (!isset($options['separator'])) {
					$options['separator'] = '　';
				}
				break;
		}

		$out = parent::input($fieldName, $options);

		/* カウンター */
		if (!empty($options['counter'])) {
			$domId = $this->domId($fieldName, $options);
			$counter = '<span id="' . $domId . 'Counter' . '" class="size-counter"></span>';
			$script = '$("#' . $domId . '").keyup(countSize);$("#' . $domId . '").keyup();';
			if (!$this->sizeCounterFunctionLoaded) {
				$script .= <<< DOC_END
function countSize() {
	var len = $(this).val().length;
	var maxlen = $(this).attr('maxlength');
	if(!maxlen || maxlen == -1){
		maxlen = '-';
	}
	$("#"+$(this).attr('id')+'Counter').html(len+'/<small>'+maxlen+'</small>');
}
DOC_END;
				$this->sizeCounterFunctionLoaded = true;
			}
			$out = $out . $counter . $this->Html->scriptblock($script);
		}

		/*** afterInput ***/
		$event = $this->dispatchEvent('afterInput', array(
			'fieldName' => $fieldName,
			'out' => $out
			), array('class' => 'Form', 'plugin' => ''));

		if ($event !== false) {
			$out = $event->result === true ? $event->data['out'] : $event->result;
		}

		return $out;
	}

/**
 * フォームのIDを取得する
 * BcForm::create より呼出される事が前提
 * 
 * @param string $model
 * @param array $options
 * @return string
 */
	protected function _getId($model = null, $options = array()) {

		if (!isset($options['id'])) {
			if ($model !== false) {
				$this->setEntity($model, true);
			}
			$domId = isset($options['action']) ? $options['action'] : $this->request['action'];
			$id = $this->domId($domId . 'Form');
		} else {
			$id = $options['id'];
		}

		return $id;
	}

/**
 * エディタを表示する
 * 
 * @param string $fieldName
 * @param array $options
 * @return string
 */
	public function editor($fieldName, $options = array()) {

		$options = array_merge(array(
			'editor' => 'BcCkeditor',
			'style' => 'width:99%;height:540px'
			), $options);
		list($plugin, $editor) = pluginSplit($options['editor']);
		if (!empty($this->_View->{$editor})) {
			return $this->_View->{$editor}->editor($fieldName, $options);
		} elseif ($editor == 'none') {
			$_options = array();
			foreach ($options as $key => $value) {
				if (!preg_match('/^editor/', $key)) {
					$_options[$key] = $value;
				}
			}
			return $this->input($fieldName, array_merge(array('type' => 'textarea'), $_options));
		} else {
			return $this->_View->BcCkeditor->editor($fieldName, $options);
		}
	}

/**
 * 日付タグ
 * 和暦実装
 * TODO 未実装
 */
/* function dateTime($fieldName, $dateFormat = 'DMY', $timeFormat = '12', $selected = null, $attributes = array(), $showEmpty = true) {

	  if($dateFormat == "WYMD"){
	  $this->options['month'] = $this->getWarekiMonthes();
	  $this->options['day'] = $this->getWarekiDays();
	  $this->options['year'] = $this->getWarekiYears($attributes['minYear'],$attributes['maxYear']);
	  $dateFormat = "YMD";

	  }
	  return parent::dateTime($fieldName, $dateFormat, $timeFormat, $selected, $attributes, $showEmpty);

	  } */
}
