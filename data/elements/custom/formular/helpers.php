<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Hilfsklasse des Custom-Elements "Formular"
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

if (!class_exists('GbFormKit')) {
	class GbFormKit
	{
		public const TYPES = [
			'text' => 'Textfeld',
			'email' => 'E-Mail',
			'tel' => 'Telefon',
			'url' => 'Website',
			'textarea' => 'Mehrzeiliger Text',
			'number' => 'Zahl',
			'date' => 'Datum',
			'time' => 'Uhrzeit',
			'select' => 'Auswahlliste',
			'radio' => 'Einzelauswahl (Radio)',
			'checkboxes' => 'Mehrfachauswahl (Checkboxen)',
			'checkbox' => 'Einzelne Checkbox',
			'file' => 'Datei-Upload (als E-Mail-Anhang)',
			'info' => '-- Infotext (kein Eingabefeld) --',
			'step' => '-- Neuer Schritt (kein Eingabefeld) --',
		];

		//Alle Meldungen des Formulars. Ein Admin ueberschreibt sie in output.php des Elements ($messages, nur die geaenderten Schluessel).
		private const MESSAGES = [
			'csrf_expired' => 'Das Formular ist abgelaufen. Bitte die Seite neu laden und erneut absenden.',
			'spam_label' => 'Bitte nicht ausfüllen',
			'spam_error' => 'Ihre Anfrage wurde als Spam erkannt.',
			'select_empty' => 'Bitte wählen',
			'required' => 'Bitte „{label}“ ausfüllen.',
			'invalid' => 'Bitte bei „{label}“ einen gültigen Wert angeben.',
			'send_failed' => 'Das Formular konnte leider nicht versendet werden. Bitte versuchen Sie es später erneut.',
			'file_intro' => 'Die Datei konnte nicht übernommen werden. Bitte wählen Sie sie erneut aus:',
			'file_required' => 'Bitte bei „{label}“ eine Datei auswählen.',
			'file_too_big' => '„{label}“: Die Datei ist zu groß (höchstens {max}).',
			'file_failed' => '„{label}“: Der Upload ist fehlgeschlagen.',
			'file_empty' => '„{label}“: Die Datei ist leer.',
			'file_type' => '„{label}“: Dieser Dateityp ist nicht erlaubt (erlaubt: {types}).',
			'file_mismatch' => '„{label}“: Der Inhalt der Datei passt nicht zur Endung .{ext}.',
		];

		private static array $messages = self::MESSAGES;

		private const INPUT_TYPES = ['text', 'email', 'tel', 'url', 'textarea', 'number', 'date', 'time', 'select', 'radio', 'checkboxes', 'checkbox', 'file'];

		//Erlaubte Dateiendungen fuer den Upload => zulaessige MIME-Typen (echter Typ per finfo).
		private const FILE_TYPES = [
			'pdf' => ['application/pdf'],
			'jpg' => ['image/jpeg'],
			'jpeg' => ['image/jpeg'],
			'png' => ['image/png'],
			'gif' => ['image/gif'],
			'webp' => ['image/webp'],
			'txt' => ['text/plain'],
			'csv' => ['text/plain', 'text/csv', 'application/csv'],
			'doc' => ['application/msword', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/CDFV2'],
			'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
			'xls' => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/CDFV2'],
			'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
			'ppt' => ['application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/CDFV2'],
			'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
			'odt' => ['application/vnd.oasis.opendocument.text'],
			'ods' => ['application/vnd.oasis.opendocument.spreadsheet'],
		];
		private const FILE_DEFAULT_EXTS = ['pdf', 'jpg', 'jpeg', 'png'];
		public const FILE_DEFAULT_MB = 5;
		private const FILE_MAX_MB = 25;


		public static function isAvailable(): bool
		{	return class_exists('rex_yform') && class_exists('rex_mailer');
		}


		//Normalisiert die im Builder gespeicherten Feldzeilen (JSON des Repeaters) zu einer Liste mit festen Schluesseln.
		public static function parseFields(mixed $json): array
		{	$rows = is_array($json) ? $json : json_decode((string) $json, true);
			if (!is_array($rows)) {
				return [];
			}

			$fields = [];
			$n = 0;
			$used = [];
			foreach ($rows as $row) {
				$row = (array) $row;
				if (!empty($row['disabled'])) {
					continue;
				}

				$type = (string) ($row['type'] ?? 'text');
				if (!isset(self::TYPES[$type])) {
					$type = 'text';
				}

				$field = [
					'type' => $type,
					'label' => trim((string) ($row['label'] ?? '')),
					'required' => !empty($row['required']) && in_array($type, self::INPUT_TYPES, true),
					'options' => [],
					'placeholder' => trim((string) ($row['placeholder'] ?? '')),
					'help' => trim((string) ($row['help'] ?? '')),
					'text' => trim((string) ($row['infotext'] ?? '')),
					'checkvalue' => 'Ja',
					'name' => '',
					'attrs' => [],
				];

				if ($type === 'number') {
					foreach (['min', 'max', 'step'] as $attr) {
						$value = trim((string) ($row[$attr] ?? ''));
						if (preg_match('/^-?\d+(\.\d+)?$/', $value)) {
							$field['attrs'][$attr] = $value;
						}
					}
				}
				if (in_array($type, ['text', 'email', 'tel', 'url', 'textarea'], true)) {
					foreach (['minlength', 'maxlength'] as $attr) {
						$value = trim((string) ($row[$attr] ?? ''));
						if ($value !== '' && ctype_digit($value) && (int) $value > 0) {
							$field['attrs'][$attr] = (string) (int) $value;
						}
					}
				}

				if ($type === 'file') {
					$exts = [];
					foreach (preg_split('/[\s,;]+/', strtolower((string) ($row['fexts'] ?? ''))) ?: [] as $ext) {
						$ext = ltrim($ext, '.');
						if (isset(self::FILE_TYPES[$ext]) && !in_array($ext, $exts, true)) {
							$exts[] = $ext;
						}
					}
					$field['exts'] = $exts !== [] ? $exts : self::FILE_DEFAULT_EXTS;
					$mb = trim((string) ($row['fmaxmb'] ?? ''));
					$field['maxmb'] = ($mb !== '' && ctype_digit($mb) && (int) $mb > 0) ? min((int) $mb, self::FILE_MAX_MB) : self::FILE_DEFAULT_MB;
				}

				if (in_array($type, ['select', 'radio', 'checkboxes'], true)) {
					$seenLabels = [];
					$seenValues = [];
					foreach (preg_split('/\R/', (string) ($row['options'] ?? '')) as $line) {
						$parts = explode('|', trim((string) $line), 2);
						$label = trim($parts[0]);
						$optionValue = isset($parts[1]) && trim($parts[1]) !== '' ? trim($parts[1]) : $label;
						if ($label !== '' && !isset($seenLabels[$label]) && !isset($seenValues[$optionValue])) {
							$seenLabels[$label] = true;
							$seenValues[$optionValue] = true;
							$field['options'][] = ['label' => $label, 'value' => $optionValue];
						}
					}
					if ($field['options'] === []) {
						continue;
					}
				}

				if ($type === 'checkbox') {
					$firstLine = trim((string) strtok((string) ($row['options'] ?? ''), "\r\n"));
					if ($firstLine !== '') {
						$field['checkvalue'] = $firstLine;
					}
				}

				if ($type === 'info' && (string) ($row['options'] ?? '') !== '') {
					$field['text'] = str_replace("\r", '', (string) $row['options']);
				}

				if ($type === 'info' && $field['text'] === '' && $field['label'] === '') {
					continue;
				}
				if (in_array($type, self::INPUT_TYPES, true)) {
					if ($field['label'] === '') {
						$field['label'] = self::TYPES[$type];
					}
					$name = strtolower((string) preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($row['name'] ?? '')));
					if ($name === '' || !preg_match('/^[a-z]/', $name) || in_array($name, ['send', 'submit', 'honeypot'], true) || isset($used[$name])) {
						do {
							$name = 'f'.(++$n);
						} while (isset($used[$name]));
					}
					$used[$name] = true;
					$field['name'] = $name;
				}

				$fields[] = $field;
			}

			return $fields;
		}


		public static function render(array $values, bool $preview = false, array $messages = []): string
		{	if (!self::isAvailable()) {
				return '';
			}
			self::$messages = array_merge(self::MESSAGES, array_filter(array_intersect_key($messages, self::MESSAGES), 'is_string'));

			$fields = self::parseFields($values['fields'] ?? '');
			if (!array_filter($fields, static fn (array $f): bool => $f['name'] !== '')) {
				return '';
			}

			$id = 'gbf'.substr(md5((string) json_encode($values)), 0, 8);
			$hasSteps = (bool) array_filter($fields, static fn (array $f): bool => $f['type'] === 'step');
			$submitLabel = trim((string) ($values['submit_label'] ?? '')) ?: 'Absenden';

			$yform = new rex_yform();
			$yform->setObjectparams('form_name', $id);
			$yform->setObjectparams('form_wrap_id', $id.'-yform');
			$yform->setObjectparams('form_wrap_class', 'yform gb-el-form-yform');
			$yform->setObjectparams('form_class', 'rex-yform gb-el-form-form');
			$yform->setObjectparams('form_anchor', $id);
			$yform->setObjectparams('submit_btn_show', false);
			$yform->setObjectparams('csrf_protection_error_message', self::msg('csrf_expired'));
			if (!$preview && !rex::isBackend()) {
				$yform->setObjectparams('form_action', rex_getUrl(rex_article::getCurrentId(), rex_clang::getCurrentId()));
			} else {
				$yform->setObjectparams('form_action', '#');
			}

			$steps = [];
			if ($hasSteps) {
				$steps[] = trim((string) ($values['first_step_title'] ?? '')) ?: 'Schritt 1';
				$stepNo = 1;
				$yform->setValueField('html', ['gbf_step_1', '', self::stepOpen($stepNo, $steps[0])]);
			}

			foreach ($fields as $field) {
				if ($field['type'] === 'step') {
					$stepNo++; // @phpstan-ignore variable.undefined
					$title = $field['label'] !== '' ? $field['label'] : 'Schritt '.$stepNo;
					$steps[] = $title;
					$yform->setValueField('html', ['gbf_step_'.$stepNo, '', '</fieldset>'.self::stepOpen($stepNo, $title)]);
					continue;
				}
				if ($field['type'] === 'info') {
					$infoHtml = '';
					if ($field['label'] !== '') {
						$infoHtml .= '<strong class="gb-el-form-info-title">'.rex_escape($field['label']).'</strong>';
					}
					if ($field['text'] !== '') {
						$infoHtml .= '<div class="gb-el-form-info-text">'.nl2br(rex_escape($field['text'])).'</div>';
					}
					$spacer = $field['label'] === '' && trim($field['text']) === '';
					$yform->setValueField('html', ['gbf_info_'.md5($field['label'].'|'.$field['text']), '', '<div class="gb-el-form-info'.($spacer ? ' gb-el-form-spacer' : '').'">'.$infoHtml.'</div>']);
					continue;
				}
				self::addField($yform, $field);
			}

			if (class_exists('rex_yform_value_spam_protection')) {
				$yform->setValueField('spam_protection', ['honeypot', self::msg('spam_label'), self::msg('spam_error'), '0']);
			}
			$yform->setValueField('submit', ['gbf_submit', $submitLabel, '', '1']);
			if ($hasSteps) {
				$yform->setValueField('html', ['gbf_step_end', '', '</fieldset>']);
			}

			if (!$preview) {
				$yform->setActionField('callback', [static function ($action) use ($fields, $values): void {
					self::handleSubmit($action, $fields, $values);
				}]);
			}

			$html = $yform->getForm();
			$note = trim((string) ($values['note'] ?? ''));

			$out = '<div class="gb-el-form" id="'.rex_escape($id).'-wrap"'.($hasSteps ? ' data-gb-steps="'.rex_escape((string) json_encode($steps)).'"' : '').'>';
			$out .= $preview ? str_replace('<form ', '<form onsubmit="return false" ', $html) : $html;
			if ($note !== '') {
				$out .= '<p class="gb-el-form-note">'.nl2br(rex_escape($note)).'</p>';
			}
			$out .= '</div>';
			if ($hasSteps && (!$preview || Gridblock2Builder_Preview::isIframeContext())) {
				$out .= self::stepScript();
			}

			return $out;
		}


		private static function stepOpen(int $no, string $title): string
		{	return '<fieldset class="gb-el-form-step" data-gb-step="'.$no.'"><legend class="gb-el-form-step-title">'.rex_escape($title).'</legend>';
		}


		private static function addField(rex_yform $yform, array $field): void
		{	$name = $field['name'];
			$label = $field['label'];
			$required = $field['required'];
			$attributes = [];
			if ($field['placeholder'] !== '' && $field['type'] !== 'select') {
				$attributes['placeholder'] = $field['placeholder'];
			}
			if ($required) {
				$attributes['required'] = 'required';
				$attributes['aria-required'] = 'true';
			}
			$notice = $field['help'];
			$attributes = array_merge($attributes, $field['attrs']);

			switch ($field['type']) {
				case 'text':
				case 'tel':
				case 'url':
				case 'number':
				case 'date':
				case 'time':
					if ($field['type'] !== 'text') {
						$attributes['type'] = $field['type'];
					}
					$yform->setValueField('text', [$name, $label, '', '1', $attributes ? json_encode($attributes) : '', $notice]);
					break;

				case 'email':
					$attributes['type'] = 'email';
					$yform->setValueField('text', [$name, $label, '', '1', json_encode($attributes), $notice]);
					break;

				case 'textarea':
					$yform->setValueField('textarea', [$name, $label, '', '1', $attributes ? json_encode($attributes) : '', $notice]);
					break;

				case 'select':
				case 'radio':
				case 'checkboxes':
					$expanded = $field['type'] === 'select' ? '0' : '1';
					$multiple = $field['type'] === 'checkboxes' ? '1' : '0';
					$choices = [];
					foreach ($field['options'] as $option) {
						$choices[$option['label']] = $option['value'];
					}
					$choiceAttributes = ($field['type'] === 'radio' && $required) ? json_encode(['required' => 'required']) : '';
					$selectAttributes = ($field['type'] === 'select') ? ($required ? json_encode($attributes) : '') : '';
					$grouped = $field['type'] !== 'select';
					if ($grouped) {
						$yform->setValueField('html', ['gbf_'.$name.'_fs_open', '', '<fieldset class="gb-el-form-fieldset"><legend>'.rex_escape($label).'</legend>']);
					}
					$yform->setValueField('choice', [
						$name, $grouped ? '' : $label, json_encode($choices, JSON_UNESCAPED_UNICODE), $expanded, $multiple, '', '', '',
						$field['type'] === 'select' ? ($field['placeholder'] !== '' ? $field['placeholder'] : self::msg('select_empty')) : '',
						'', $selectAttributes, $choiceAttributes, $notice, '1',
					]);
					if ($grouped) {
						$yform->setValueField('html', ['gbf_'.$name.'_fs_close', '', '</fieldset>']);
					}
					break;

				case 'checkbox':
					$yform->setValueField('choice', [
						$name, '', json_encode([$label => $label], JSON_UNESCAPED_UNICODE), '1', '1', '', '', '', '', '', '', '', $notice, '1',
					]);
					break;

				case 'file':
					$yform->setValueField('html', ['gbf_'.$name.'_file', '', self::fileFieldHtml($field)]);
					return;
			}

			if ($required) {
				$yform->setValidateField('empty', [$name, self::msg('required', ['label' => $label])]);
			}
			$validateType = match ($field['type']) {
				'email' => 'email',
				'url' => 'url',
				'number' => 'float',
				default => '',
			};
			if ($validateType !== '') {
				$yform->setValidateField('type', [$name, $validateType, self::msg('invalid', ['label' => $label]), $required ? '0' : '1']);
			}
		}


		private static function msg(string $key, array $vars = []): string
		{	$replace = [];
			foreach ($vars as $name => $value) {
				$replace['{'.$name.'}'] = (string) $value;
			}

			return strtr(self::$messages[$key] ?? self::MESSAGES[$key] ?? '', $replace);
		}


		private static function fileInputName(string $name): string
		{	return 'gbf_file_'.$name;
		}


		//Groesstes erlaubtes Dateigewicht in Bytes: Einstellung des Feldes, nie mehr als das PHP-Limit des Servers.
		private static function maxFileBytes(array $field): int
		{	$limits = [(int) $field['maxmb'] * 1048576];
			foreach (['upload_max_filesize', 'post_max_size'] as $ini) {
				$raw = trim((string) ini_get($ini));
				$number = (float) $raw;
				$unit = strtolower(substr($raw, -1));
				$number *= match ($unit) {
					'g' => 1073741824,
					'm' => 1048576,
					'k' => 1024,
					default => 1,
				};
				if ($number > 0) {
					$limits[] = (int) $number;
				}
			}

			return min($limits);
		}


		private static function fileFieldHtml(array $field): string
		{	$id = 'gbf-file-'.$field['name'];
			$maxMb = max(1, (int) floor(self::maxFileBytes($field) / 1048576));
			$accept = '.'.implode(',.', $field['exts']);
			$hint = strtr($field['help'], ['{types}' => strtoupper(implode(', ', $field['exts'])), '{max}' => $maxMb.' MB']);
			$required = $field['required'] ? ' required aria-required="true"' : '';
			$describedBy = $hint !== '' ? ' aria-describedby="'.rex_escape($id).'-hint"' : '';

			return '<div class="form-group gb-el-form-file"><label class="control-label" for="'.rex_escape($id).'">'.rex_escape($field['label']).'</label>'
				.'<input type="file" class="form-control" id="'.rex_escape($id).'" name="'.rex_escape(self::fileInputName($field['name'])).'" accept="'.rex_escape($accept).'"'.$required.$describedBy.'>'
				.($hint !== '' ? '<p class="help-block small" id="'.rex_escape($id).'-hint">'.rex_escape($hint).'</p>' : '').'</div>';
		}


		//Prueft die hochgeladenen Dateien (Groesse, Endung, echter Dateityp) und liefert [Anhaenge, Dateinamen je Feld, Fehlermeldungen].
		private static function collectUploads(array $fields): array
		{	$attachments = [];
			$names = [];
			$errors = [];
			foreach ($fields as $field) {
				if ($field['type'] !== 'file') {
					continue;
				}
				$label = $field['label'];
				$file = rex_request::files(self::fileInputName($field['name']), '', null);
				if (!is_array($file) || is_array($file['name'] ?? null) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
					if ($field['required']) {
						$errors[] = self::msg('file_required', ['label' => $label]);
					}
					continue;
				}

				$maxBytes = self::maxFileBytes($field);
				$maxText = max(1, (int) floor($maxBytes / 1048576)).' MB';
				$error = (int) $file['error'];
				if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
					$errors[] = self::msg('file_too_big', ['label' => $label, 'max' => $maxText]);
					continue;
				}
				$tmp = (string) ($file['tmp_name'] ?? '');
				if ($error !== UPLOAD_ERR_OK || $tmp === '' || !is_uploaded_file($tmp)) {
					$errors[] = self::msg('file_failed', ['label' => $label]);
					continue;
				}
				$size = (int) filesize($tmp);
				if ($size <= 0) {
					$errors[] = self::msg('file_empty', ['label' => $label]);
					continue;
				}
				if ($size > $maxBytes) {
					$errors[] = self::msg('file_too_big', ['label' => $label, 'max' => $maxText]);
					continue;
				}

				$original = basename(str_replace('\\', '/', (string) $file['name']));
				$ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
				if (!in_array($ext, $field['exts'], true) || !isset(self::FILE_TYPES[$ext])) {
					$errors[] = self::msg('file_type', ['label' => $label, 'types' => strtoupper(implode(', ', $field['exts']))]);
					continue;
				}
				if (function_exists('finfo_open')) {
					$info = finfo_open(FILEINFO_MIME_TYPE);
					$mime = $info ? (string) finfo_file($info, $tmp) : '';
					if ($info) {
						finfo_close($info);
					}
					if ($mime === '' || !in_array($mime, self::FILE_TYPES[$ext], true)) {
						$errors[] = self::msg('file_mismatch', ['label' => $label, 'ext' => $ext]);
						continue;
					}
				}

				$base = trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '_', pathinfo($original, PATHINFO_FILENAME)), '_');
				$safeName = substr($base, 0, 80);
				$safeName = ($safeName !== '' ? $safeName : 'datei').'.'.$ext;
				$attachments[] = [$tmp, $safeName];
				$names[$field['name']] = $safeName.' ('.max(1, (int) round($size / 1024)).' KB)';
			}

			return [$attachments, $names, $errors];
		}


		//Wandelt den abgeschickten Wert eines Feldes in lesbaren Text fuer die E-Mail.
		private static function selectedOptions(array $field, mixed $raw): array
		{	if (is_array($raw)) {
				$raw = implode(',', array_map('strval', $raw));
			}
			$rest = trim((string) $raw);
			$options = $field['options'];
			usort($options, static fn (array $a, array $b): int => strlen($b['value']) <=> strlen($a['value']));
			$selected = [];
			while ($rest !== '') {
				$found = null;
				foreach ($options as $option) {
					$len = strlen($option['value']);
					if (strncmp($rest, $option['value'], $len) === 0 && ($len === strlen($rest) || $rest[$len] === ',')) {
						$found = $option;
						break;
					}
				}
				if ($found === null) {
					$pos = strpos($rest, ',');
					$token = $pos === false ? $rest : substr($rest, 0, $pos);
					$found = ['label' => $token, 'value' => $token];
				}
				$selected[] = $found;
				$rest = ltrim(substr($rest, strlen($found['value'])), ',');
			}

			return $selected;
		}


		//$part: 'label' = angezeigter Text, 'value' = hinterlegter Wert (bei Auswahlfeldern; sonst identisch)
		private static function choiceText(array $field, mixed $raw, string $part): string
		{	if (!in_array($field['type'], ['select', 'radio', 'checkboxes'], true)) {
				return self::valueText($field, $raw);
			}
			$texts = array_map(static fn (array $o): string => $o[$part], self::selectedOptions($field, $raw));

			return $texts === [] ? '-' : implode(', ', $texts);
		}


		private static function valueText(array $field, mixed $value): string
		{	if (is_array($value)) {
				$value = implode(', ', array_map('strval', $value));
			}
			$value = trim((string) $value);

			if ($value === '') {
				return '-';
			}
			if ($field['type'] === 'checkbox') {
				return $field['checkvalue'];
			}
			if ($field['type'] === 'date' && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
				return $m[3].'.'.$m[2].'.'.$m[1];
			}

			return $value;
		}


		private static function summary(array $fields, array $pool): string
		{	$lines = [];
			foreach ($fields as $field) {
				if ($field['type'] === 'step') {
					$lines[] = '';
					$lines[] = '--- '.$field['label'].' ---';
				} elseif ($field['name'] !== '') {
					$lines[] = $field['label'].': '.self::choiceText($field, $pool[$field['name']] ?? '', 'label');
				}
			}

			return trim(implode("\n", $lines));
		}


		private static function handleSubmit(object $action, array $fields, array $values): void
		{	$pool = (array) ($action->params['value_pool']['email'] ?? []);

			[$attachments, $fileNames, $fileErrors] = self::collectUploads($fields);
			if ($fileErrors !== []) {
				$list = '';
				foreach ($fileErrors as $message) {
					$list .= '<li>'.rex_escape($message).'</li>';
				}
				$action->params['output'] .= '<div class="gb-el-form-error alert alert-danger" role="alert"><p>'.rex_escape(self::msg('file_intro')).'</p><ul>'.$list.'</ul></div>';
				$action->params['form_show'] = true;
				return;
			}
			$pool = array_merge($pool, $fileNames);

			$senderEmail = '';
			$confirmTo = trim((string) ($values['confirm_to'] ?? ''), " \t#{}");
			if ($confirmTo !== '') {
				$candidate = trim((string) ($pool[$confirmTo] ?? ''));
				$senderEmail = filter_var($candidate, FILTER_VALIDATE_EMAIL) ? $candidate : '';
			} else {
				foreach ($fields as $field) {
					if ($field['type'] === 'email') {
						$candidate = trim((string) ($pool[$field['name']] ?? ''));
						if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
							$senderEmail = $candidate;
							break;
						}
					}
				}
			}

			$recipients = array_filter(array_map('trim', explode(',', (string) ($values['mailto'] ?? ''))), static fn (string $a): bool => filter_var($a, FILTER_VALIDATE_EMAIL) !== false);
			$summary = self::summary($fields, $pool);
			$host = rex::getServerName() ?: ($_SERVER['HTTP_HOST'] ?? '');
			$subject = trim((string) ($values['subject'] ?? '')) ?: 'Neue Formularanfrage'.($host !== '' ? ' ('.$host.')' : '');

			$page = '';
			try {
				$page = rex_getUrl(rex_article::getCurrentId(), rex_clang::getCurrentId(), [], '&');
				if (!preg_match('#^https?://#', $page)) {
					$page = rtrim(rex::getServer(), '/').'/'.ltrim($page, '/');
				}
			} catch (Throwable $e) {
				$page = '';
			}

			$replacements = self::replacements($fields, $pool, $summary, $page);
			$subject = self::fill($subject, $replacements);
			$fromName = trim((string) ($values['from_name'] ?? ''));
			$bodyTemplate = trim((string) ($values['mail_body'] ?? ''));
			$body = $bodyTemplate !== ''
				? self::fill($bodyTemplate, $replacements)
				: $summary."\n\n--\nGesendet am ".date('d.m.Y H:i').($page !== '' ? "\nSeite: ".$page : '');

			$ok = $recipients !== [];
			if ($ok) {
				$ok = self::send($recipients, $subject, $body, $senderEmail, $fromName, $attachments);
			}

			if (!$ok) {
				$action->params['output'] .= '<div class="gb-el-form-error alert alert-danger" role="alert">'.rex_escape(self::msg('send_failed')).'</div>';
				$action->params['form_show'] = true;
				return;
			}

			if ($senderEmail !== '' && !empty($values['confirm'])) {
				$confirmSubject = self::fill(trim((string) ($values['confirm_subject'] ?? '')) ?: 'Ihre Anfrage', $replacements);
				$confirmText = self::fill(trim((string) ($values['confirm_text'] ?? '')) ?: 'Vielen Dank für Ihre Nachricht. Wir melden uns in Kürze bei Ihnen.', $replacements);
				self::send([$senderEmail], $confirmSubject, $confirmText, '', $fromName);
			}

			$url = self::redirectUrl((string) ($values['redirect'] ?? ''));
			if ($url !== '') {
				rex_response::sendRedirect($url);
			}

			$success = trim((string) ($values['success_text'] ?? '')) ?: 'Vielen Dank! Ihre Nachricht wurde versendet.';
			$action->params['output'] .= '<p class="gb-el-form-success">'.nl2br(rex_escape($success)).'</p>';
		}


		//Platzhalter {feldname} (Wert des Feldes), {feldname_label}, {all-fields}, {site}, {date} fuer Betreff und Texte der E-Mails.
		private static function replacements(array $fields, array $pool, string $summary, string $page): array
		{	$map = ['{all-fields}' => $summary, '{site}' => $page, '{date}' => date('d.m.Y H:i')];
			foreach ($fields as $field) {
				if ($field['name'] !== '') {
					$map['{'.$field['name'].'}'] = self::choiceText($field, $pool[$field['name']] ?? '', 'value');
					$map['{'.$field['name'].'_label}'] = self::choiceText($field, $pool[$field['name']] ?? '', 'label');
				}
			}

			return $map;
		}


		//Ersetzt die Platzhalter (ein Durchlauf, eingesetzte Werte werden nicht erneut ausgewertet); unbekannte bleiben stehen.
		private static function fill(string $template, array $replacements): string
		{	return strtr($template, $replacements);
		}


		private static function send(array $to, string $subject, string $body, string $replyTo, string $fromName = '', array $attachments = []): bool
		{	try {
				$mail = new rex_mailer();
				$mail->isHTML(false);
				$mail->CharSet = 'UTF-8';
				foreach ($to as $address) {
					$mail->addAddress($address);
				}
				if ($replyTo !== '') {
					$mail->addReplyTo($replyTo);
				}
				if ($fromName !== '') {
					$mail->FromName = $fromName;
				}
				$mail->Subject = $subject;
				$mail->Body = $body;
				foreach ($attachments as [$path, $name]) {
					$mail->addAttachment($path, $name);
				}

				return (bool) $mail->send();
			} catch (Throwable $e) {
				return false;
			}
		}


		private static function redirectUrl(string $json): string
		{	$data = json_decode($json, true);
			if (!is_array($data)) {
				return '';
			}
			$value = (string) ($data['value'] ?? '');

			return match ($data['type'] ?? '') {
				'url' => (string) ($data['href'] ?? ''),
				'link' => ((int) $value > 0) ? rex_getUrl((int) $value, rex_clang::getCurrentId(), [], '&') : '',
				default => '',
			};
		}


		private static function stepScript(): string
		{	return <<<'HTML'
	<script>
	(function () {
		document.querySelectorAll('.gb-el-form[data-gb-steps]:not([data-gb-ready])').forEach(function (root) {
			root.setAttribute('data-gb-ready', '1');
			var steps = [].slice.call(root.querySelectorAll('.gb-el-form-step'));
			var titles = [];
			try { titles = JSON.parse(root.getAttribute('data-gb-steps')) || []; } catch (e) {}
			if (steps.length < 2) { return; }
			var progress = document.createElement('p');
			progress.className = 'gb-el-form-progress';
			progress.setAttribute('aria-live', 'polite');
			steps[0].parentNode.insertBefore(progress, steps[0]);
			var current = 0;

			function show(index, focus) {
				current = index;
				steps.forEach(function (step, i) { step.hidden = i !== index; });
				progress.textContent = 'Schritt ' + (index + 1) + ' von ' + steps.length + (titles[index] ? ': ' + titles[index] : '');
				if (focus) {
					var legend = steps[index].querySelector('legend');
					if (legend) { legend.setAttribute('tabindex', '-1'); legend.focus(); }
				}
			}

			function valid(step) {
				var fields = step.querySelectorAll('input, select, textarea');
				for (var i = 0; i < fields.length; i++) {
					if (!fields[i].checkValidity()) { fields[i].reportValidity(); return false; }
				}
				return true;
			}

			function button(label, cls, handler) {
				var b = document.createElement('button');
				b.type = 'button';
				b.className = 'gb-el-form-' + cls + ' btn btn-secondary';
				b.textContent = label;
				b.addEventListener('click', handler);
				return b;
			}

			steps.forEach(function (step, i) {
				var nav = document.createElement('div');
				nav.className = 'gb-el-form-nav';
				if (i > 0) { nav.appendChild(button('Zurück', 'prev', function () { show(i - 1, true); })); }
				if (i < steps.length - 1) {
					nav.appendChild(button('Weiter', 'next', function () { if (valid(step)) { show(i + 1, true); } }));
				}
				var submit = step.querySelector('[type="submit"]');
				var anchor = submit;
				while (anchor && anchor.parentNode !== step) { anchor = anchor.parentNode; }
				if (anchor) { step.insertBefore(nav, anchor); } else { step.appendChild(nav); }
			});

			var first = 0;
			steps.some(function (step, i) {
				if (step.querySelector('.has-error, .is-invalid, [aria-invalid="true"]')) { first = i; return true; }
				return false;
			});
			show(first, false);
		});
	})();
	</script>
	HTML;
		}
	}
}
