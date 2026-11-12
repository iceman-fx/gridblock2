<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Eingabe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */

require_once __DIR__.'/helpers.php';

$uid = $_gb->uid;
$v = (array) ($_gb->values ?? []);
$val = static fn (string $key, string $default = ''): string => (string) ($v[$key] ?? $default);
$esc = static fn (string $key, string $default = ''): string => rex_escape($val($key, $default));
$sel = static fn (string $current, string $option): string => $current === $option ? ' selected' : '';

$collageJson = (string) json_encode(['images' => GbCollageKit::images($val('collage'))], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$optGreytone = $val('s_greytone') === 'checked';
$optNoLinks = $val('s_nolinks') === 'checked';
$mouseover = GbCollageKit::pick($val('mouseover'), array_keys(GbCollageKit::HOVERS), '');

$ratios = [];
foreach (GbCollageKit::RATIOS as $key => $entry) {
	$ratios[$key] = ['ratio' => $entry[0], 'label' => $entry[1]];
}
$radii = [];
foreach (GbCollageKit::RADII as $key => $entry) {
	$radii[$key] = ['css' => $entry[0], 'label' => $entry[1]];
}
$parallax = [];
foreach (GbCollageKit::PARALLAX as $key => $label) {
	$parallax[] = ['key' => $key, 'label' => $label];
}
$config = [
	'max' => GbCollageKit::MAX_IMAGES,
	'ratios' => $ratios,
	'radii' => $radii,
	'parallax' => $parallax,
	'shadow' => GbCollageKit::SHADOW,
	'mediaBase' => rtrim(rex_url::media(), '/').'/',
	'pickId' => $uid.'_pick',
	'pickArgs' => '&args[types]=gif,jpg,jpeg,png,webp,svg',
];
?>
<style>
.gbcol-root .gbcol-help { margin-bottom: 15px; }
.gbcol-editor { position: relative; width: 100%; min-height: 250px; background: #f5f5f5; border: 1px solid #ccc; margin-bottom: 15px; overflow: hidden; }
.gbcol-item { position: absolute; cursor: move; touch-action: none; user-select: none; }
.gbcol-item:focus-visible { outline: 2px solid #3f9cff; outline-offset: 2px; }
.gbcol-item.is-active { outline: 2px dashed #3f9cff; }
.gbcol-item img { width: 100%; height: 100%; object-fit: cover; display: block; pointer-events: none; border-radius: inherit; }
.gbcol-note { display: inline-block; margin-left: 10px; font-size: 90%; }
.gbcol-settings { margin-top: 15px; }
.gbcol-panel { background: #fff; border: 1px solid #ddd; padding: 15px; display: flex; flex-direction: column; gap: 10px; margin-bottom: 10px; }
.gbcol-panel h4 { margin: 0; word-break: break-all; }
.gbcol-panel-head, .gbcol-panel-options { display: flex; width: 100%; gap: 10px; flex-wrap: wrap; justify-content: space-between; }
.gbcol-panel-options .form-group { flex: 1; min-width: 100px; margin: 0; }
.gbcol-panel-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.gbcol-pick { position: absolute; left: -9999px; top: -9999px; width: 1px; height: 1px; overflow: hidden; }
.gbcol-checks .checkbox { margin-top: 0; margin-bottom: 6px; }
.gbcol-group { margin: 20px 0 10px; border: 1px solid rgba(128,128,128,.35); border-radius: 3px; }
.gbcol-group > summary { display: flex; align-items: center; gap: 10px; padding: 9px 14px; font-weight: 600; cursor: pointer; list-style: none; background: rgba(128,128,128,.1); }
.gbcol-group > summary::-webkit-details-marker { display: none; }
.gbcol-group > summary::before { content: ""; flex: none; width: 6px; height: 6px; border: solid currentColor; border-width: 0 2px 2px 0; transform: rotate(-45deg); transition: transform .15s; }
.gbcol-group[open] > summary::before { transform: rotate(45deg); margin-top: -3px; }
.gbcol-group[open] > summary { border-bottom: 1px solid rgba(128,128,128,.35); }
.gbcol-group-body { padding: 14px 14px 0; }
body.rex-theme-dark .gbcol-editor { background: #2b3440; border-color: #4a5666; }
body.rex-theme-dark .gbcol-panel { background: #333f4d; border-color: #4a5666; }
@media (prefers-color-scheme: dark) {
	body:not(.rex-theme-light) .gbcol-editor { background: #2b3440; border-color: #4a5666; }
	body:not(.rex-theme-light) .gbcol-panel { background: #333f4d; border-color: #4a5666; }
}
</style>

<div class="gbcol-root" data-gbcol-root>
	<script type="application/json" class="gbcol-config"><?php echo json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

	<p class="help-block small gbcol-help">Bilder auswählen und per Ziehen anordnen. Ein Klick auf ein Bild öffnet dessen Einstellungen, mit den Pfeiltasten lässt sich das gewählte Bild verschieben.</p>

	<div class="gbcol-editor" role="group" aria-label="Collage-Vorschau"></div>

	<div>
		<button type="button" class="btn btn-primary gbcol-add">+ Bild hinzufügen</button>
		<span class="gbcol-note text-muted" role="status" aria-live="polite"></span>
	</div>

	<div class="gbcol-settings"></div>

	<div class="gbcol-pick" aria-hidden="true">
		<input type="text" class="gbcol-pick-input" id="REX_MEDIA_<?php echo rex_escape($uid); ?>_pick" tabindex="-1" readonly>
	</div>

	<input type="hidden" class="gbcol-json" name="collage" value="<?php echo rex_escape($collageJson); ?>">

	<details class="gbcol-group">
		<summary>Weitere Optionen</summary>
		<div class="gbcol-group-body">
			<div class="rex-form-group form-group">
				<label for="<?php echo $uid; ?>_mouseover">Maus-Over Effekt</label>
				<select name="mouseover" id="<?php echo $uid; ?>_mouseover" class="form-control">
					<?php foreach (GbCollageKit::HOVERS as $key => $label): ?>
					<option value="<?php echo rex_escape($key); ?>"<?php echo $sel($mouseover, (string) $key); ?>><?php echo rex_escape($label); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="gbcol-checks">
				<div class="checkbox"><label><input type="checkbox" name="s_greytone" value="checked"<?php echo $optGreytone ? ' checked' : ''; ?>> Bilder ausgrauen</label></div>
				<div class="checkbox"><label><input type="checkbox" name="s_nolinks" value="checked"<?php echo $optNoLinks ? ' checked' : ''; ?>> Keine Lightboxansicht (nur Vorschaubilder)</label></div>
			</div>
		</div>
	</details>
</div>

<script>
(function () {
	'use strict';

	if (!window.GbCollageEditor) {
		var inited = new WeakSet();

		var escapeHtml = function (text) {
			return String(text).replace(/[&<>"']/g, function (c) {
				return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
			});
		};

		class Editor {
			constructor(root) {
				this.root = root;
				this.config = JSON.parse(root.querySelector('.gbcol-config').textContent);
				this.input = root.querySelector('.gbcol-json');
				this.editor = root.querySelector('.gbcol-editor');
				this.panel = root.querySelector('.gbcol-settings');
				this.addButton = root.querySelector('.gbcol-add');
				this.note = root.querySelector('.gbcol-note');
				this.mediaInput = root.querySelector('.gbcol-pick-input');
				this.selected = -1;
				this.pickTimer = null;
				this.commitTimer = null;
				this.noteTimer = null;

				try { this.data = JSON.parse(this.input.value); } catch (e) { this.data = null; }
				if (!this.data || !Array.isArray(this.data.images)) { this.data = { images: [] }; }

				this.addButton.addEventListener('click', (e) => { e.preventDefault(); this.addImage(); });
				this.editor.addEventListener('click', (e) => { if (e.target === this.editor) { this.clearSelection(); } });
				this.resizeObserver = new ResizeObserver(() => { this.handleResize(); });
				this.resizeObserver.observe(this.editor);
				this.render();
			}

			alive() { return this.root.isConnected; }

			notify(text) {
				this.note.textContent = text;
				clearTimeout(this.noteTimer);
				if (text) { this.noteTimer = setTimeout(() => { this.note.textContent = ''; }, 4000); }
			}

			ratio(format) {
				var entry = this.config.ratios[format];
				var parts = ((entry && entry.ratio) ? entry.ratio : '3/2').split('/').map(parseFloat);
				return (parts[0] || 1) / (parts[1] || 1);
			}

			src(file) { return this.config.mediaBase + encodeURIComponent(file); }

			saveData() { this.input.value = JSON.stringify(this.data); }

			commit() {
				this.saveData();
				this.input.dispatchEvent(new Event('input', { bubbles: true }));
				this.input.dispatchEvent(new Event('change', { bubbles: true }));
			}

			scheduleCommit() {
				this.saveData();
				clearTimeout(this.commitTimer);
				this.commitTimer = setTimeout(() => { this.commit(); }, 400);
			}

			calculateHeight() {
				var width = this.editor.clientWidth;
				if (!width) { return; }

				var maxPx = 0;
				var hasShadow = false;
				this.data.images.forEach((item) => {
					var bottom = ((parseFloat(item.y) || 0) / 100) * width + (((parseFloat(item.width) || 0) / 100) * width) / this.ratio(item.format);
					if (bottom > maxPx) { maxPx = bottom; }
					if (item.shadow) { hasShadow = true; }
				});
				if (hasShadow) { maxPx += 30; }

				this.editor.style.height = maxPx + 'px';
			}

			applyItem(div, item) {
				var entry = this.config.radii[item.radius || 'keine'];
				div.style.left = item.x + '%';
				div.style.top = (item.y / 100 * this.editor.clientWidth) + 'px';
				div.style.width = item.width + '%';
				div.style.height = 'auto';
				div.style.aspectRatio = ((this.config.ratios[item.format] && this.config.ratios[item.format].ratio) || '3/2').replace('/', ' / ');
				div.style.zIndex = item.z;
				div.style.borderRadius = (entry && entry.css) ? entry.css : '0px';
				div.style.boxShadow = item.shadow ? this.config.shadow : 'none';
			}

			createItem(item, index) {
				var div = document.createElement('div');
				div.className = 'gbcol-item';
				div.dataset.index = index;
				div.tabIndex = 0;
				div.setAttribute('role', 'button');
				div.setAttribute('aria-label', 'Bild ' + (index + 1) + ': ' + item.file + ' - Einstellungen öffnen');
				this.applyItem(div, item);

				var img = document.createElement('img');
				img.alt = '';
				img.src = this.src(item.file);
				div.appendChild(img);

				this.enableDragging(div);
				div.addEventListener('keydown', (e) => { this.handleKey(e, index); });
				return div;
			}

			render() {
				this.editor.innerHTML = '';
				this.data.images.forEach((item, index) => { this.editor.appendChild(this.createItem(item, index)); });
				this.addButton.disabled = this.data.images.length >= this.config.max;
				this.calculateHeight();
				this.saveData();
				if (this.selected >= 0 && this.selected < this.data.images.length) { this.openSettings(this.selected, true); }
				else { this.selected = -1; this.panel.innerHTML = ''; }
			}

			updateItem(index) {
				var div = this.editor.querySelector('.gbcol-item[data-index="' + index + '"]');
				if (!div) { return; }
				var item = this.data.images[index];
				this.applyItem(div, item);
				var img = div.querySelector('img');
				var wanted = this.src(item.file);
				if (img && img.getAttribute('src') !== wanted) { img.src = wanted; }
				this.calculateHeight();
				this.saveData();
			}

			handleKey(e, index) {
				var item = this.data.images[index];
				if (!item) { return; }
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					this.openSettings(index);
					return;
				}
				var step = e.shiftKey ? 5 : 1;
				var dx = 0;
				var dy = 0;
				if (e.key === 'ArrowLeft') { dx = -step; }
				else if (e.key === 'ArrowRight') { dx = step; }
				else if (e.key === 'ArrowUp') { dy = -step; }
				else if (e.key === 'ArrowDown') { dy = step; }
				else { return; }
				e.preventDefault();
				item.x = Math.max(-item.width * 0.5, Math.min(100 - item.width * 0.5, item.x + dx));
				item.y = Math.max(-(item.width / this.ratio(item.format)) * 0.5, item.y + dy);
				this.updateItem(index);
				this.scheduleCommit();
			}

			enableDragging(el) {
				var startX = 0;
				var startY = 0;
				var originX = 0;
				var originY = 0;
				var moved = false;

				el.addEventListener('pointerdown', (e) => {
					if (e.button !== undefined && e.button !== 0) { return; }
					var index = parseInt(el.dataset.index, 10);
					var item = this.data.images[index];
					if (!item) { return; }

					startX = e.clientX;
					startY = e.clientY;
					originX = item.x;
					originY = item.y;
					moved = false;
					try { el.setPointerCapture(e.pointerId); } catch (err) { }

					var move = (ev) => {
						var width = this.editor.clientWidth || 1;
						var dx = ev.clientX - startX;
						var dy = ev.clientY - startY;
						if (Math.abs(dx) + Math.abs(dy) > 3) { moved = true; }
						var height = item.width / this.ratio(item.format);
						item.x = Math.max(-item.width * 0.5, Math.min(100 - item.width * 0.5, originX + dx / width * 100));
						item.y = Math.max(-height * 0.5, originY + dy / width * 100);
						this.updateItem(index);
					};

					var up = () => {
						el.removeEventListener('pointermove', move);
						el.removeEventListener('pointerup', up);
						el.removeEventListener('pointercancel', up);
						if (moved) { this.commit(); } else { this.openSettings(index); }
					};

					el.addEventListener('pointermove', move);
					el.addEventListener('pointerup', up);
					el.addEventListener('pointercancel', up);
				});
			}

			clearSelection() {
				this.selected = -1;
				this.editor.querySelectorAll('.gbcol-item').forEach((el) => { el.classList.remove('is-active'); });
				this.panel.innerHTML = '';
			}

			options(map, current, withSeparatorBefore) {
				return Object.keys(map).map((key) => {
					var label = map[key] && map[key].label ? map[key].label : map[key];
					var sep = (withSeparatorBefore === key) ? '<option value="" disabled>--------</option>' : '';
					return sep + '<option value="' + escapeHtml(key) + '"' + (key === current ? ' selected' : '') + '>' + escapeHtml(label) + '</option>';
				}).join('');
			}

			openSettings(index, keepFocus) {
				this.editor.querySelectorAll('.gbcol-item').forEach((el) => { el.classList.remove('is-active'); });
				var item = this.data.images[index];
				if (!item) { return; }
				this.selected = index;

				var activeEl = this.editor.querySelector('.gbcol-item[data-index="' + index + '"]');
				if (activeEl) { activeEl.classList.add('is-active'); }

				var widthOptions = '';
				for (var w = 10; w <= 100; w += 10) {
					widthOptions += '<option value="' + w + '"' + (w === item.width ? ' selected' : '') + '>' + w + '%</option>';
				}
				var parallaxMap = {};
				this.config.parallax.forEach((entry) => { parallaxMap[entry.key] = entry.label; });
				var id = 'gbcol-' + Math.random().toString(36).slice(2, 8);

				this.panel.innerHTML =
					'<div class="gbcol-panel">'
					+ '<div class="gbcol-panel-head"><h4>Bildeigenschaften: ' + escapeHtml(item.file) + '</h4>'
					+ '<button type="button" class="btn btn-default btn-sm gbcol-swap"><i class="rex-icon fa-exchange" aria-hidden="true"></i> Bild austauschen</button></div>'
					+ '<div class="gbcol-panel-options">'
					+ '<div class="form-group"><label for="' + id + '-z">Z-Index</label><input type="number" min="0" max="999" id="' + id + '-z" class="form-control gbcol-z" value="' + Number(item.z) + '"></div>'
					+ '<div class="form-group"><label for="' + id + '-w">Breite</label><select id="' + id + '-w" class="form-control gbcol-w">' + widthOptions + '</select></div>'
					+ '<div class="form-group"><label for="' + id + '-f">Format</label><select id="' + id + '-f" class="form-control gbcol-f">' + this.options(this.config.ratios, item.format) + '</select></div>'
					+ '<div class="form-group"><label for="' + id + '-r">Eckenrundung</label><select id="' + id + '-r" class="form-control gbcol-r">' + this.options(this.config.radii, item.radius || 'keine') + '</select></div>'
					+ '<div class="form-group"><label for="' + id + '-p">Parallaxeffekt</label><select id="' + id + '-p" class="form-control gbcol-p">' + this.options(parallaxMap, item.parallax || 'none', 'slow-down') + '</select></div>'
					+ '</div>'
					+ '<div class="checkbox"><label><input type="checkbox" class="gbcol-s"' + (item.shadow ? ' checked' : '') + '> Schatten</label></div>'
					+ '<div class="gbcol-panel-actions"><button type="button" class="btn btn-danger gbcol-remove">Bild entfernen</button></div>'
					+ '</div>';

				var bind = (selector, event, handler) => { this.panel.querySelector(selector).addEventListener(event, handler); };

				bind('.gbcol-z', 'change', (e) => { item.z = Math.max(0, Math.min(999, parseInt(e.target.value, 10) || 0)); this.updateItem(index); this.commit(); });
				bind('.gbcol-w', 'change', (e) => { item.width = parseInt(e.target.value, 10) || 50; this.updateItem(index); this.commit(); });
				bind('.gbcol-f', 'change', (e) => { item.format = e.target.value; this.updateItem(index); this.commit(); });
				bind('.gbcol-r', 'change', (e) => { item.radius = e.target.value; this.updateItem(index); this.commit(); });
				bind('.gbcol-p', 'change', (e) => { item.parallax = e.target.value || 'none'; this.commit(); });
				bind('.gbcol-s', 'change', (e) => { item.shadow = e.target.checked ? 1 : 0; this.updateItem(index); this.commit(); });
				bind('.gbcol-swap', 'click', () => {
					this.pick((file) => {
						item.file = file;
						var heading = this.panel.querySelector('h4');
						if (heading) { heading.textContent = 'Bildeigenschaften: ' + file; }
						this.updateItem(index);
						this.commit();
					});
				});
				bind('.gbcol-remove', 'click', () => {
					this.data.images.splice(index, 1);
					this.selected = -1;
					this.render();
					this.commit();
				});

				if (!keepFocus && activeEl) { activeEl.focus({ preventScroll: true }); }
			}

			cancelPick() {
				clearInterval(this.pickTimer);
				this.pickTimer = null;
			}

			pick(callback) {
				this.cancelPick();
				this.mediaInput.value = '';
				var popup = (typeof window.openREXMedia === 'function') ? window.openREXMedia(this.config.pickId, this.config.pickArgs) : null;
				this.pickTimer = setInterval(() => {
					if (!this.alive()) { this.cancelPick(); return; }
					if (this.mediaInput.value !== '') {
						var file = this.mediaInput.value;
						this.cancelPick();
						this.mediaInput.value = '';
						callback(file);
					} else if (popup && popup.closed) {
						this.cancelPick();
					}
				}, 300);
			}

			addImage() {
				if (this.data.images.length >= this.config.max) {
					this.notify('Maximal ' + this.config.max + ' Bilder erlaubt.');
					return;
				}
				this.pick((file) => {
					if (this.data.images.length >= this.config.max) { return; }
					this.data.images.push({
						file: file,
						x: 0,
						y: 0,
						width: 50,
						z: Math.min(999, this.data.images.length + 1),
						radius: 'keine',
						shadow: 0,
						format: '3-2',
						parallax: 'none'
					});
					this.selected = this.data.images.length - 1;
					this.render();
					this.commit();
				});
			}

			handleResize() {
				if (!this.alive()) { return; }
				this.calculateHeight();
				var width = this.editor.clientWidth;
				this.data.images.forEach((item, index) => {
					var div = this.editor.querySelector('.gbcol-item[data-index="' + index + '"]');
					if (div) { div.style.top = (item.y / 100 * width) + 'px'; }
				});
			}
		}

		window.GbCollageEditor = {
			initAll: function () {
				document.querySelectorAll('[data-gbcol-root]').forEach(function (root) {
					if (inited.has(root)) { return; }
					inited.add(root);
					new Editor(root);
				});
			}
		};

		if (window.jQuery) {
			window.jQuery(document).on('rex:ready', function () { window.GbCollageEditor.initAll(); });
		}
	}

	window.GbCollageEditor.initAll();
})();
</script>
