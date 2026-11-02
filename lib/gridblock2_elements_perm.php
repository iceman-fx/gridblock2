<?php
/*
	Redaxo-Addon Gridblock²
	Rollenverwaltung zu Redaxo hinzufügen
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

class rex_gridblock2_elements_perm extends rex_complex_perm
{
    public function hasPerm($key)
    {
        return $this->hasAll() || in_array($key, (array) $this->perms, true);
    }

    public static function getFieldParams()
    {
        $options = Gridblock2Builder_Tree::getPermissionElementOptions();
        if (!$options) {
            return [];
        }

        return [
            'label' => rex_i18n::msg('a2034_perm_elements'),
            'all_label' => rex_i18n::msg('a2034_perm_elements_all'),
            'options' => $options,
        ];
    }

    public static function moveBelowModules()
    {
        $property = new ReflectionProperty(rex_complex_perm::class, 'classes');
        $classes = $property->getValue();
        if (!is_array($classes) || !isset($classes['modules'], $classes['gridblock2_elements'])) {
            return;
        }

        $ordered = [];
        foreach ($classes as $key => $class) {
            if ($key === 'gridblock2_elements') {
                continue;
            }
            $ordered[$key] = $class;
            if ($key === 'modules') {
                $ordered['gridblock2_elements'] = $classes['gridblock2_elements'];
            }
        }
        $property->setValue(null, $ordered);
    }
}
