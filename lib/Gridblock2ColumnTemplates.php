<?php
/*
	Redaxo-Addon Gridblock²
	Logik der Spaltenvorlagen (Verwendung, Reihenfolge, Aufräumen)
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

class Gridblock2ColumnTemplates
{
	public static function isInUse(int $id): bool
	{	if ($id <= 0) {
			return false;
		}

		$table = rex::getTable('article_slice');

		$db = rex_sql::factory();
		$db->setQuery("SELECT id FROM ".$table." WHERE value17 REGEXP ? LIMIT 1", ['"selectedTemplate": ?"?'.$id.'"?([^0-9]|$)']);
		if ($db->getRows() > 0) {
			return true;
		}

		$db->setQuery("SELECT value16 FROM ".$table." WHERE value16 REGEXP ?", ['"templateId": ?"?'.$id.'"?([^0-9]|$)']);
		foreach ($db as $row) {
			$tree = json_decode((string) $row->getValue('value16'), true);
			if (is_array($tree) && self::treeUsesTemplate($tree, $id)) {
				return true;
			}
		}

		return false;
	}

	private static function treeUsesTemplate(array $node, int $id): bool
	{	$layout = $node['layout'] ?? null;
		if (is_array($layout) && ($layout['mode'] ?? '') === 'template' && (int) ($layout['templateId'] ?? 0) === $id) {
			return true;
		}

		foreach ($node as $child) {
			if (is_array($child) && self::treeUsesTemplate($child, $id)) {
				return true;
			}
		}

		return false;
	}

	public static function placeAt(int $id, int $wantedPrio): void
	{	$ids = self::orderedIds($id);
		$position = ($wantedPrio <= 0 || $wantedPrio > count($ids) + 1) ? count($ids) + 1 : $wantedPrio;
		array_splice($ids, $position - 1, 0, [$id]);
		self::writePrios($ids);
	}

	public static function renumber(): void
	{	self::writePrios(self::orderedIds(0));
	}

	public static function removeResidues(int $id): void
	{	if ($id <= 0) {
			return;
		}

		$addon = rex_addon::get('gridblock2');
		foreach (['contentsettings/templates/template_'.$id, 'synchronizer/templates/template_'.$id] as $path) {
			$dir = $addon->getDataPath($path);
			if (is_dir($dir)) {
				rex_dir::delete($dir);
			}
		}
	}

	private static function orderedIds(int $exceptId): array
	{	$ids = [];
		$db = rex_sql::factory();
		$db->setQuery("SELECT id FROM ".rex::getTable('2034_gridtemplates')." WHERE id <> ? ORDER BY prio ASC, id ASC", [$exceptId]);
		foreach ($db as $row) {
			$ids[] = (int) $row->getValue('id');
		}

		return $ids;
	}

	private static function writePrios(array $ids): void
	{	foreach (array_values($ids) as $index => $id) {
			$db = rex_sql::factory();
			$db->setQuery("UPDATE ".rex::getTable('2034_gridtemplates')." SET prio = ? WHERE id = ?", [$index + 1, (int) $id]);
		}
	}
}
