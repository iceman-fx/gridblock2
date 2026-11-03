<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Eingabe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */

require_once __DIR__.'/helpers.php';

$v =(array) ($_gb->values ?? []);
$uid = $_gb->uid;
$isNew = !array_key_exists('fields', $v);
$defaults = $isNew ? [
	'mail_body' => '{all-fields}',
	'success_text' => 'Vielen Dank! Ihre Nachricht wurde versendet.',
	'confirm_subject' => 'Ihre Anfrage',
	'confirm_text' => "Hallo {name},\n\nvielen Dank für Ihre Nachricht. Wir melden uns in Kürze bei Ihnen.\n\nIhre Angaben:\n{all-fields}",
] : [];
$val = static fn (string $key, string $default = ''): string => rex_escape((string) ($v[$key] ?? $default));
$confirm = !empty($v['confirm']);

$typeOptions = '';
foreach (GbFormKit::TYPES as $key => $label) {
	$typeOptions .= '<option value="'.$key.'">'.rex_escape($label).'</option>';
}

$groups = [
	'req' => ['text', 'email', 'tel', 'url', 'textarea', 'number', 'date', 'time', 'select', 'radio', 'checkboxes', 'checkbox', 'file'],
	'opts' => ['select', 'radio', 'checkboxes', 'checkbox', 'info'],
	'ph' => ['text', 'email', 'tel', 'url', 'textarea', 'number', 'select'],
	'num' => ['number'],
	'len' => ['text', 'email', 'tel', 'url', 'textarea'],
	'help' => ['text', 'email', 'tel', 'url', 'textarea', 'number', 'date', 'time', 'select', 'radio', 'checkboxes', 'checkbox', 'file'],
	'label' => ['text', 'email', 'tel', 'url', 'textarea', 'number', 'date', 'time', 'select', 'radio', 'checkboxes', 'checkbox', 'file', 'step', 'info'],
	'file' => ['file'],
	'datemode' => ['date'],
];
$css = '.gbf-row [data-gbf-show]{display:none!important}';
foreach ($groups as $group => $types) {
	foreach ($types as $type) {
		$css .= '.gbf-row:has(select[name="type"] option[value="'.$type.'"]:checked) [data-gbf-show~="'.$group.'"]{display:block!important}';
	}
}
?>
<style>
<?php echo $css; ?>
.gbf-groups{margin-top:45px}
.gbf-group{margin:0 0 10px;border:1px solid rgba(128,128,128,.35);border-radius:3px}
.gbf-group>summary{display:flex;align-items:center;gap:10px;padding:9px 14px;font-weight:600;cursor:pointer;list-style:none;background:rgba(128,128,128,.1)}
.gbf-group>summary::-webkit-details-marker{display:none}
.gbf-group>summary::before{content:"";flex:none;width:6px;height:6px;border:solid currentColor;border-width:0 2px 2px 0;transform:rotate(-45deg);transition:transform .15s}
.gbf-group[open]>summary::before{transform:rotate(45deg);margin-top:-3px}
.gbf-group[open]>summary{border-bottom:1px solid rgba(128,128,128,.35)}
.gbf-group-body{padding:14px 14px 0}
.gbf-pair{display:flex;flex-wrap:wrap;gap:0 20px}
.gbf-pair>.rex-form-group{flex:1 1 280px;min-width:0}
.gbf-narrow{display:flex;flex-wrap:wrap;gap:0 15px}
.gbf-narrow>.rex-form-group{flex:1 1 90px;min-width:0}
.gbf-confirm{margin-top:30px}
.gbf-confirm-fields{display:none}
.gbf-confirm:has(input[name="confirm"]:checked) .gbf-confirm-fields{display:block}
.gbf-row:has(select[name="type"] option[value="info"]:checked) textarea[name="options"]::placeholder,.gbf-row:has(select[name="type"] option[value="checkbox"]:checked) textarea[name="options"]::placeholder{color:transparent}
.gbf-row:has(select[name="type"] option[value="checkbox"]:checked) textarea[name="options"]{height:34px;min-height:0;resize:none;overflow:hidden;white-space:nowrap}
.gbf-row .gbf-label-step,.gbf-row .gbf-label-info,.gbf-row .gbf-label-field,.gbf-row .gbf-opt-check,.gbf-row .gbf-opt-info{display:none}
.gbf-row:has(select[name="type"] option[value="checkbox"]:checked) .gbf-opt-check{display:inline}
.gbf-row:has(select[name="type"] option[value="checkbox"]:checked) p.gbf-opt-check{display:block}
.gbf-row:has(select[name="type"] option[value="checkbox"]:checked) .gbf-opt-list{display:none}
.gbf-row:has(select[name="type"] option[value="info"]:checked) .gbf-opt-list{display:none}
.gbf-row:has(select[name="type"] option[value="info"]:checked) .gbf-opt-info{display:inline}
.gbf-row:has(select[name="type"] option[value="info"]:checked) p.gbf-opt-info{display:block}
.gbf-row:has(select[name="type"] option[value="step"]:checked) .gbf-label-step{display:inline}
.gbf-row:has(select[name="type"] option[value="info"]:checked) .gbf-label-info{display:inline}
.gbf-row:has(select[name="type"] option:not([value="step"]):not([value="info"]):checked) .gbf-label-field{display:inline}
</style>
<script>
(function () {
	if (window.gbFormUploadDefault) { return; }
	window.gbFormUploadDefault = true;
	document.addEventListener('change', function (event) {
		var select = event.target;
		if (!select || select.name !== 'type' || select.value !== 'file') { return; }
		var row = select.closest('.gbf-row');
		var size = row ? row.querySelector('input[name="fmaxmb"]') : null;
		var help = row ? row.querySelector('input[name="help"]') : null;
		var fill = function (input, value) {
			if (!input || input.value !== '') { return; }
			input.value = value;
			input.dispatchEvent(new Event('input', { bubbles: true }));
			input.dispatchEvent(new Event('change', { bubbles: true }));
		};
		fill(size, '<?php echo (int) GbFormKit::FILE_DEFAULT_MB; ?>');
		fill(help, 'Erlaubt: {types}, höchstens {max}.');
	});
})();
</script>

<div class="rex-form-group form-group">
	<label>Formularfelder</label>
	<?php
	$rowMarkup = '
		<div class="gbf-row" data-fmrep-title-when="type=step" data-fmrep-title-prefix="-- Schritt: " data-fmrep-title-suffix=" --">
			<div class="rex-form-group form-group">
				<label>Art <span class="text-danger">*</span></label>
				<select name="type" class="form-control" required>'.$typeOptions.'</select>
			</div>
			<div class="rex-form-group form-group" data-gbf-show="datemode">
				<label>Datumsart</label>
				<select name="datemode" class="form-control"><option value="date">Datum (Tag)</option><option value="week">Kalenderwoche</option><option value="month">Monat</option></select>
			</div>
			<div class="rex-form-group form-group" data-gbf-show="req">
				<div class="checkbox"><label><input type="checkbox" name="required" value="checked" {{required|checked}}> Pflichtfeld</label></div>
			</div>
			<div class="rex-form-group form-group" data-gbf-show="label">
				<label><span class="gbf-label-field">Bezeichnung</span><span class="gbf-label-step">Titel des Schritts</span><span class="gbf-label-info">Überschrift <small class="text-muted">(optional, wird fett angezeigt)</small></span></label>
				<input type="text" name="label" value="{{label}}" class="form-control" placeholder="z. B. Ihr Name">
			</div>
			<div class="rex-form-group form-group" data-gbf-show="help">
				<label>Feldname <small class="text-muted">(optional, für Platzhalter in der E-Mail)</small></label>
				<input type="text" name="name" value="{{name}}" class="form-control" placeholder="z. B. vorname">
			</div>
			<div class="rex-form-group form-group" data-gbf-show="opts">
				<label><span class="gbf-opt-list">Auswahl <small class="text-muted">(eine Option pro Zeile)</small></span><span class="gbf-opt-check">Wert in der E-Mail <small class="text-muted">(optional, Standard „Ja“)</small></span><span class="gbf-opt-info">Text <small class="text-muted">(wird unter der Überschrift als Absatz angezeigt)</small></span></label>
				<textarea name="options" rows="4" class="form-control" placeholder="Optionen: Option A&#10;Option B|wert-b">{{options}}</textarea>
				<p class="help-block small gbf-opt-list">Einzelne Textangabe oder im Format: <code>Anzeigetext|Wert</code> (Trennzeichen: senkrechter Strich).</p>
				<p class="help-block small gbf-opt-info">Zeilenumbrüche und Leerzeilen bleiben erhalten (z. B. für Abstände).</p>
				<p class="help-block small gbf-opt-check">Die Bezeichnung (z. B. der lange Datenschutz-Satz) erscheint im Formular. Bei angehakter Checkbox steht in der E-Mail dieser Wert, nur die erste Zeile zählt.</p>
			</div>
			<div class="rex-form-group form-group" data-gbf-show="ph">
				<label>Platzhalter</label>
				<input type="text" name="placeholder" value="{{placeholder}}" class="form-control" placeholder="z. B. Max Mustermann">
			</div>
			<div data-gbf-show="num"><div class="gbf-narrow">
				<div class="rex-form-group form-group"><label>Minimum</label><input type="number" step="any" name="min" value="{{min}}" class="form-control"></div>
				<div class="rex-form-group form-group"><label>Maximum</label><input type="number" step="any" name="max" value="{{max}}" class="form-control"></div>
				<div class="rex-form-group form-group"><label>Schrittweite</label><input type="number" step="any" name="step" value="{{step}}" class="form-control"></div>
			</div></div>
			<div data-gbf-show="len"><div class="gbf-narrow">
				<div class="rex-form-group form-group"><label>Min. Zeichen</label><input type="number" min="1" step="1" name="minlength" value="{{minlength}}" class="form-control"></div>
				<div class="rex-form-group form-group"><label>Max. Zeichen</label><input type="number" min="1" step="1" name="maxlength" value="{{maxlength}}" class="form-control"></div>
			</div></div>
			<div data-gbf-show="file">
				<div class="gbf-pair">
					<div class="rex-form-group form-group"><label>Erlaubte Dateitypen <small class="text-muted">(mit Komma getrennt)</small></label><input type="text" name="fexts" value="{{fexts}}" class="form-control" placeholder="pdf, jpg, jpeg, png"></div>
					<div class="rex-form-group form-group"><label>Höchstgröße in MB</label><input type="number" min="1" max="25" step="1" name="fmaxmb" value="{{fmaxmb}}" class="form-control" placeholder="5"></div>
				</div>
				<p class="help-block small">Mögliche Dateitypen: pdf, jpg, jpeg, png, gif, webp, txt, csv, doc, docx, xls, xlsx, ppt, pptx, odt, ods (leer = pdf, jpg, jpeg, png). Im Hilfetext ersetzen <code>{types}</code> und <code>{max}</code> die Liste der Dateitypen und die Höchstgröße.</p>
			</div>
			<div class="rex-form-group form-group" data-gbf-show="help">
				<label>Hilfetext <small class="text-muted">(kleiner Text unter dem Feld)</small></label>
				<input type="text" name="help" value="{{help}}" class="form-control" placeholder="z. B. Wir antworten innerhalb von 24 Stunden.">
			</div>
		</div>';
	$fieldsValue = $v['fields'] ?? '';
	if ($isNew) {
		$fieldsValue = [];
		foreach ([
			['type' => 'text', 'label' => 'Name', 'name' => 'name', 'required' => 'checked'],
			['type' => 'email', 'label' => 'E-Mail', 'name' => 'email', 'required' => 'checked'],
			['type' => 'textarea', 'label' => 'Nachricht', 'name' => 'nachricht', 'required' => 'checked'],
			['type' => 'checkbox', 'label' => 'Ich habe die Datenschutzerklärung gelesen und bin mit der Verarbeitung meiner Angaben einverstanden.', 'name' => 'datenschutz', 'options' => 'Akzeptiert', 'required' => 'checked'],
		] as $default) {
			$fieldsValue[Gridblock2Builder_Tree::makeRepeaterUid()] = $default;
		}
	}
	echo Gridblock2Builder_Tree::renderRepeater('fields', $fieldsValue, $rowMarkup, [
		'collapsible' => true,
		'titleField' => 'label,options,type',
		'titleFallback' => 'Feld',
		'collapsed' => true,
	]);
	?>
</div>

<div class="gbf-groups">
	<label>Einstellungen</label>
	<details class="gbf-group"<?php echo trim((string) ($v['mailto'] ?? '')) === '' ? ' open' : ''; ?>>
		<summary>E-Mail-Versand</summary>
		<div class="gbf-group-body">
			<div class="gbf-pair">
				<div class="rex-form-group form-group">
					<label for="<?php echo $uid; ?>_mailto">E-Mail an <small class="text-muted">(mehrere mit Komma)</small></label>
					<input type="text" name="mailto" id="<?php echo $uid; ?>_mailto" value="<?php echo $val('mailto'); ?>" class="form-control" placeholder="info@example.com">
				</div>
				<div class="rex-form-group form-group">
					<label for="<?php echo $uid; ?>_subject">Betreff der E-Mail <small class="text-muted">(optional)</small></label>
					<input type="text" name="subject" id="<?php echo $uid; ?>_subject" value="<?php echo $val('subject'); ?>" class="form-control" placeholder="Neue Nachricht von {name}">
				</div>
			</div>

			<div class="rex-form-group form-group">
				<label for="<?php echo $uid; ?>_mail_body">Text der E-Mail an den Empfänger <small class="text-muted">(optional)</small></label>
				<textarea name="mail_body" id="<?php echo $uid; ?>_mail_body" rows="5" class="form-control" placeholder="Leer = alle Angaben als Liste"><?php echo $val('mail_body', $defaults['mail_body'] ?? ''); ?></textarea>
				<p class="help-block small"><b>Platzhalter:</b> <code>{feldname-aus-formularfeld}</code> <code>{all-fields}</code> <code>{site}</code> <code>{date}</code> Gilt auch für Betreff und Bestätigung.</p>
			</div>

			<div class="rex-form-group form-group">
				<label for="<?php echo $uid; ?>_from_name">Absendername <small class="text-muted">(optional)</small></label>
				<input type="text" name="from_name" id="<?php echo $uid; ?>_from_name" value="<?php echo $val('from_name'); ?>" class="form-control" placeholder="z. B. Kontaktformular Musterfirma">
			</div>

			<div class="gbf-confirm">
				<div class="rex-form-group form-group">
					<div class="checkbox"><label><input type="checkbox" name="confirm" value="checked" <?php echo $confirm ? 'checked' : ''; ?>> Bestätigungsmail an den Absender senden </label></div>
				</div>
				<div class="gbf-confirm-fields">
					<div class="rex-form-group form-group">
						<label for="<?php echo $uid; ?>_confirm_to">Empfänger der Bestätigung <small class="text-muted">(optional, Feldplatzhalter)</small></label>
						<input type="text" name="confirm_to" id="<?php echo $uid; ?>_confirm_to" value="<?php echo $val('confirm_to'); ?>" class="form-control" placeholder="Leer lassen für 1. E-Mail-Feld oder {email-platzhalter}">
					</div>
					<div class="rex-form-group form-group">
						<label for="<?php echo $uid; ?>_confirm_subject">Betreff der Bestätigung</label>
						<input type="text" name="confirm_subject" id="<?php echo $uid; ?>_confirm_subject" value="<?php echo $val('confirm_subject', $defaults['confirm_subject'] ?? ''); ?>" class="form-control" placeholder="Ihre Anfrage">
					</div>
					<div class="rex-form-group form-group">
						<label for="<?php echo $uid; ?>_confirm_text">Text der Bestätigung</label>
						<textarea name="confirm_text" id="<?php echo $uid; ?>_confirm_text" rows="4" class="form-control" placeholder="Vielen Dank für Ihre Nachricht. Wir melden uns in Kürze bei Ihnen."><?php echo $val('confirm_text', $defaults['confirm_text'] ?? ''); ?></textarea>
					</div>
				</div>
			</div>
		</div>
	</details>

	<details class="gbf-group">
		<summary>Button &amp; Bestätigung</summary>
		<div class="gbf-group-body">
			<div class="rex-form-group form-group">
				<label for="<?php echo $uid; ?>_submit_label">Beschriftung des Buttons</label>
				<input type="text" name="submit_label" id="<?php echo $uid; ?>_submit_label" value="<?php echo $val('submit_label', 'Absenden'); ?>" class="form-control">
			</div>

			<div class="rex-form-group form-group">
				<label for="<?php echo $uid; ?>_success_text">Text nach dem Absenden</label>
				<textarea name="success_text" id="<?php echo $uid; ?>_success_text" rows="2" class="form-control" placeholder="Vielen Dank! Ihre Nachricht wurde versendet."><?php echo $val('success_text', $defaults['success_text'] ?? ''); ?></textarea>
			</div>

			<div class="rex-form-group form-group">
				<label>Weiterleitung nach dem Absenden <small class="text-muted">(optional, ersetzt den Text)</small></label>
				<?php echo Gridblock2Builder_Tree::renderMultilink('redirect', $v['redirect'] ?? '', ['types' => ['link', 'url']]); ?>
			</div>
		</div>
	</details>

	<details class="gbf-group">
		<summary>Sonstiges</summary>
		<div class="gbf-group-body">
			<div class="rex-form-group form-group">
				<label for="<?php echo $uid; ?>_first_step_title">Titel des ersten Schritts <small class="text-muted">(nur bei Feldern vom Typ „Schritt“)</small></label>
				<input type="text" name="first_step_title" id="<?php echo $uid; ?>_first_step_title" value="<?php echo $val('first_step_title'); ?>" class="form-control" placeholder="Schritt 1">
			</div>

			<div class="rex-form-group form-group">
				<label for="<?php echo $uid; ?>_note">Hinweis unter dem Formular</label>
				<textarea name="note" id="<?php echo $uid; ?>_note" rows="2" class="form-control" placeholder="z. B. Ihre Daten werden vertraulich behandelt."><?php echo $val('note'); ?></textarea>
			</div>
		</div>
	</details>
</div>
