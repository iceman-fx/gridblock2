<?php
/*
	Redaxo-Addon Gridblock²
	Builder-Engine: Einstellungen aus install/settings.json (nur bei der Erstinstallation)
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

class Gridblock2Builder_SettingsFile
{
	public const FILE = 'install/settings.json';

	private const ENUMS = [
		'modulesmode' => ['allow', 'ignore'],
		'gb_builder_modalstyle' => ['modal', 'sidebar'],
		'gb_builder_preview_mode' => ['iframe', 'shadowdom'],
		'gb_builder_gap_mode' => ['native', 'cs'],
		'gb_builder_slicelist_maxheight' => ['', 'small', 'medium', 'large'],
	];

	//Schluessel, deren Wert als "#id#id#"-Liste gespeichert wird
	private const ID_LISTS = ['modules', 'gb_builder_preview_mode_modules'];

	//Schluessel, die mehrzeiligen Text enthalten (in der Datei auch als Liste von Zeilen moeglich); die Palette darf ein JSON-Objekt sein
	private const LINE_LISTS = [
		'gb_builder_content_stylesheets', 'gb_builder_content_scripts',
		'gb_builder_preview_visibility_fixes', 'gb_builder_module_css_overrides',
	];

	private const INTEGERS = ['gb_builder_maxdepth' => [1, 10], 'gb_builder_modaledit_minwidth' => [200, 2000]];


	/**
	 * Liest die Datei und liefert [Werte, Meldungen]. Es werden nur Schluessel uebernommen, die in $allowedKeys stehen.
	 *
	 * @param array<int, string> $allowedKeys
	 * @return array{values: array<string, string>, notes: array<int, string>}
	 */
	public static function load(string $file, array $allowedKeys): array
	{	$result = ['values' => [], 'notes' => []];
		if (!is_file($file)) {
			return $result;
		}

		$data = json_decode((string) file_get_contents($file), true);
		if (!is_array($data)) {
			$result['notes'][] = basename($file).': keine gueltige JSON-Datei, es wurde nichts uebernommen.';
			return $result;
		}

		foreach ($data as $key => $raw) {
			$key = (string) $key;
			if ($key === '' || $key[0] === '_') {
				continue;
			}
			if (!in_array($key, $allowedKeys, true)) {
				$result['notes'][] = 'Unbekannter Schluessel "'.$key.'" wurde ignoriert.';
				continue;
			}

			$value = self::normalize($key, $raw);
			if ($value === null) {
				$result['notes'][] = 'Ungueltiger Wert fuer "'.$key.'" wurde ignoriert.';
				continue;
			}
			if ($key === 'gb_builder_palette' && $value !== '' && Gridblock2Palette::parse($value)['errors'] !== []) {
				$result['notes'][] = 'Die Palette ("gb_builder_palette") enthaelt Fehler und wurde ignoriert.';
				continue;
			}

			if ($key === 'gb_builder_gap_native_fallback' && Gridblock2Builder_Tree::parseGapNative($value)['errors'] !== []) {
				$result['notes'][] = 'Die Werte fuer den nativen Spalten-Abstand ("gb_builder_gap_native_fallback") enthalten Fehler und wurden ignoriert.';
				continue;
			}

			$result['values'][$key] = $value;
		}

		return $result;
	}


	private static function normalize(string $key, mixed $raw): ?string
	{	if (is_bool($raw)) {
			return $raw ? 'checked' : '';
		}

		if (in_array($key, self::ID_LISTS, true)) {
			if (is_string($raw) && preg_match('/^#(\d+#)*$/', $raw)) {
				return $raw;
			}
			if (!is_array($raw)) {
				return null;
			}
			$ids = [];
			foreach ($raw as $id) {
				if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
					return null;
				}
				$ids[] = (int) $id;
			}
			return '#'.implode('#', $ids).'#';
		}

		if ($key === 'gb_builder_gap_native_fallback' && is_array($raw) && array_is_list($raw)) {
			foreach ($raw as $line) {
				if (!is_scalar($line)) {
					return null;
				}
			}
			return implode("\n", array_map('strval', $raw));
		}

		if ($key === 'gb_builder_palette' || $key === 'gb_builder_gap_native_fallback') {
			if (is_array($raw)) {
				return (string) json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
			}
			return is_string($raw) ? $raw : null;
		}

		if (in_array($key, self::LINE_LISTS, true)) {
			if (is_array($raw)) {
				foreach ($raw as $line) {
					if (!is_scalar($line)) {
						return null;
					}
				}
				return implode("\n", array_map('strval', $raw));
			}
			return is_scalar($raw) ? (string) $raw : null;
		}

		if ($key === 'gb_builder_domains') {
			if (is_array($raw)) {
				foreach ($raw as $id => $entry) {
					if (!preg_match('/^[0-9]+$/', (string) $id) || !is_array($entry)) {
						return null;
					}
				}
				return $raw === [] ? '' : (string) json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}
			return is_string($raw) ? $raw : null;
		}

		if (isset(self::INTEGERS[$key])) {
			if (!is_int($raw) && !(is_string($raw) && ctype_digit($raw))) {
				return null;
			}
			[$min, $max] = self::INTEGERS[$key];
			return (string) max($min, min($max, (int) $raw));
		}

		if (!is_scalar($raw)) {
			return null;
		}
		$value = (string) $raw;
		if (isset(self::ENUMS[$key]) && !in_array($value, self::ENUMS[$key], true)) {
			return null;
		}

		return $value;
	}
}
