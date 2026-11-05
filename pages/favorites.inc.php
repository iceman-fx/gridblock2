<?php
/*
	Redaxo-Addon Gridblock²
	Verwaltung: Favoriten
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** RexStan: Vars vom Check ausschließen */
/** @var rex_addon $this */
/** @var rex_csrf_token $gbCsrf */
/** @var string $func */
/** @var string $mypage */


if (!Gridblock2Presets::isAdmin()):
	echo rex_view::warning($this->i18n('a2034_fav_admin_forbidden'));
	return;
endif;


Gridblock2Presets::ensureTable();

$filters = Gridblock2Presets::adminFilters([
	'q' => rex_request('q', 'string', ''),
	'domain' => rex_request('domain', 'string', ''),
	'author' => rex_request('author', 'string', ''),
	'kind' => rex_request('kind', 'string', ''),
	'vis' => rex_request('vis', 'string', ''),
	'sort' => rex_request('sort', 'string', 'title'),
]);
$pageNr = max(1, rex_request('p', 'int', 1));
$multisite = Gridblock2Presets::multisiteActive();
$domains = $multisite ? Gridblock2Builder_MultiSite::getDomains() : [];


$favErrorText = function (Gridblock2PresetsException $e): string {
	$map = ['title required' => 'title', 'cross-domain favorites not enabled' => 'crossdomain', 'domain required' => 'domain', 'unknown domain' => 'domain', 'not found' => 'notfound', 'forbidden' => 'forbidden', 'limit reached' => 'limit'];
	return isset($map[$e->getMessage()]) ? $this->i18n('a2034_fav_err_'.$map[$e->getMessage()]) : $this->i18n('a2034_error');
};

if ($func === 'fav_rename'):
	try {
		Gridblock2Presets::adminRename(rex_post('id', 'int', 0), rex_post('title', 'string', ''));
		echo rex_view::info($this->i18n('a2034_fav_admin_renamed'));
	} catch (Gridblock2PresetsException $e) {
		echo rex_view::warning($favErrorText($e));
	}

elseif ($func === 'fav_bulk'):
	$ids = rex_post('ids', 'array', []);
	$bulk = rex_post('bulk', 'string', '');

	try {
		if ($ids === []):
			echo rex_view::warning($this->i18n('a2034_fav_admin_none_selected'));
		elseif ($bulk === 'delete'):
			echo rex_view::info(sprintf($this->i18n('a2034_fav_admin_deleted'), Gridblock2Presets::adminDelete($ids)));
		elseif ($bulk === 'release'):
			echo rex_view::info(sprintf($this->i18n('a2034_fav_admin_released'), Gridblock2Presets::adminRelease($ids)));
		elseif ($bulk === 'assign'):
			echo rex_view::info(sprintf($this->i18n('a2034_fav_admin_assigned'), Gridblock2Presets::adminAssignDomain($ids, rex_post('bulk_domain', 'string', ''))));
		endif;
	} catch (Gridblock2PresetsException $e) {
		echo rex_view::warning($favErrorText($e));
	}
endif;


$result = Gridblock2Presets::adminList($filters, $pageNr);
$authors = Gridblock2Presets::adminAuthors();
$logins = Gridblock2Presets::existingLogins();

$queryBase = ['page' => 'gridblock2/favorites'] + array_filter($filters, static fn (string $value): bool => $value !== '' && $value !== 'title');
$baseUrl = 'index.php?'.http_build_query($queryBase);

$kindLabels = [
	'element' => $this->i18n('a2034_fav_admin_kind_element'),
	'section' => $this->i18n('a2034_fav_admin_kind_section'),
	'columns' => $this->i18n('a2034_fav_admin_kind_columns'),
	'group' => $this->i18n('a2034_fav_admin_kind_group'),
];
$kindIcons = [
	'element' => 'fa-cube',
	'section' => 'fa-window-maximize',
	'columns' => 'fa-table-columns',
	'group' => 'fa-object-group',
];
$sortLabels = [
	'title' => $this->i18n('a2034_fav_admin_sort_title'),
	'new' => $this->i18n('a2034_fav_admin_sort_new'),
	'unused' => $this->i18n('a2034_fav_admin_sort_unused'),
	'used' => $this->i18n('a2034_fav_admin_sort_used'),
];
?>

<style>
.gb-fav-admin td, .gb-fav-admin th { vertical-align: middle !important; }
.gb-fav-admin .table > thead > tr > th { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; opacity: .75; white-space: nowrap; }
.gb-fav-admin .table > tbody > tr > td { padding-top: 12px; padding-bottom: 12px; }
.gb-fav-admin .gb-fav-row > td:first-child { border-left: 3px solid #324050; }
.gb-fav-admin .gb-fav-entry { display: flex; align-items: center; gap: 12px; min-width: 220px; }
.gb-fav-admin .gb-fav-entry-icon { flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 6px; color: #fff; background: #324050; font-size: 15px; }
.gb-fav-admin .gb-fav-entry-text { flex: 1 1 auto; min-width: 0; }
.gb-fav-admin .gb-fav-name { display: block; font-size: 15px; font-weight: 600; line-height: 1.3; overflow-wrap: anywhere; }
.gb-fav-admin .gb-fav-summary { display: block; margin-top: 2px; font-size: 12px; opacity: .7; overflow-wrap: anywhere; }
.gb-fav-admin .gb-fav-rename-toggle { flex: 0 0 auto; }
.gb-fav-admin .gb-fav-kind { white-space: nowrap; }
.gb-fav-admin .gb-fav-kind > i { width: 16px; text-align: center; opacity: .7; }
.gb-fav-admin .gb-fav-title-form { display: flex; gap: 6px; align-items: center; margin: 0; }
.gb-fav-admin .gb-fav-title-form input[type="text"] { min-width: 180px; flex: 1 1 auto; }
.gb-fav-admin .gb-fav-flag { display: inline-block; padding: 2px 8px; font-size: 11px; border-radius: 10px; background: rgba(128, 128, 128, .2); white-space: nowrap; }
.gb-fav-admin .gb-fav-flag-private { color: #8a6d3b; background: #fcf8e3; box-shadow: inset 0 0 0 1px #faebcc; }
.gb-fav-admin .gb-fav-flag-shared { color: #31708f; background: #d9edf7; box-shadow: inset 0 0 0 1px #bce8f1; }
.gb-fav-admin .gb-fav-warn { color: #b94a48; }
.gb-fav-admin .gb-fav-small { font-size: 12px; opacity: .8; }
.gb-fav-filter { display: flex; flex-wrap: wrap; gap: 8px; align-items: flex-end; }
.gb-fav-filter > div { min-width: 150px; }
.gb-fav-bulk { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 12px; }
</style>

<section class="rex-page-section gb-fav-admin">
	<div class="panel panel-default">
		<header class="panel-heading"><div class="panel-title"><?php echo $this->i18n('a2034_fav_admin_head'); ?></div></header>

		<div class="panel-body">
			<p><?php echo $this->i18n('a2034_fav_admin_hint'); ?></p>

			<form method="get" action="index.php" class="gb-fav-filter">
				<input type="hidden" name="page" value="gridblock2/favorites" />
				<div>
					<label for="gb-fav-q"><?php echo $this->i18n('a2034_fav_admin_filter_search'); ?></label>
					<input type="search" class="form-control" id="gb-fav-q" name="q" value="<?php echo rex_escape($filters['q']); ?>" />
				</div>
				<?php if ($multisite): ?>
				<div>
					<label for="gb-fav-domain"><?php echo $this->i18n('a2034_fav_admin_col_domain'); ?></label>
					<select class="form-control" id="gb-fav-domain" name="domain">
						<option value=""><?php echo $this->i18n('a2034_fav_admin_filter_all'); ?></option>
						<option value="global"<?php echo $filters['domain'] === 'global' ? ' selected' : ''; ?>><?php echo $this->i18n('a2034_fav_admin_domain_all'); ?></option>
						<?php foreach ($domains as $domainId => $domainName): ?>
						<option value="<?php echo (int) $domainId; ?>"<?php echo $filters['domain'] === (string) $domainId ? ' selected' : ''; ?>><?php echo rex_escape($domainName); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php endif; ?>
				<div>
					<label for="gb-fav-author"><?php echo $this->i18n('a2034_fav_admin_col_author'); ?></label>
					<select class="form-control" id="gb-fav-author" name="author">
						<option value=""><?php echo $this->i18n('a2034_fav_admin_filter_all'); ?></option>
						<?php foreach ($authors as $author): ?>
						<option value="<?php echo rex_escape($author); ?>"<?php echo $filters['author'] === $author ? ' selected' : ''; ?>><?php echo rex_escape($author); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label for="gb-fav-kind"><?php echo $this->i18n('a2034_fav_admin_col_kind'); ?></label>
					<select class="form-control" id="gb-fav-kind" name="kind">
						<option value=""><?php echo $this->i18n('a2034_fav_admin_filter_all'); ?></option>
						<?php foreach ($kindLabels as $kindKey => $kindLabel): ?>
						<option value="<?php echo rex_escape($kindKey); ?>"<?php echo $filters['kind'] === $kindKey ? ' selected' : ''; ?>><?php echo rex_escape($kindLabel); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label for="gb-fav-vis"><?php echo $this->i18n('a2034_fav_admin_col_visibility'); ?></label>
					<select class="form-control" id="gb-fav-vis" name="vis">
						<option value=""><?php echo $this->i18n('a2034_fav_admin_filter_all'); ?></option>
						<option value="shared"<?php echo $filters['vis'] === 'shared' ? ' selected' : ''; ?>><?php echo $this->i18n('a2034_fav_admin_shared'); ?></option>
						<option value="private"<?php echo $filters['vis'] === 'private' ? ' selected' : ''; ?>><?php echo $this->i18n('a2034_fav_admin_private'); ?></option>
					</select>
				</div>
				<div>
					<label for="gb-fav-sort"><?php echo $this->i18n('a2034_fav_admin_sort'); ?></label>
					<select class="form-control" id="gb-fav-sort" name="sort">
						<?php foreach ($sortLabels as $sortKey => $sortLabel): ?>
						<option value="<?php echo rex_escape($sortKey); ?>"<?php echo $filters['sort'] === $sortKey ? ' selected' : ''; ?>><?php echo rex_escape($sortLabel); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div><button type="submit" class="btn btn-default"><?php echo $this->i18n('a2034_fav_admin_filter_apply'); ?></button></div>
			</form>
		</div>

		<form method="post" action="<?php echo rex_escape($baseUrl.'&p='.(int) $result['page']); ?>">
			<input type="hidden" name="func" value="fav_bulk" />
			<?php echo $gbCsrf->getHiddenField(); ?>

			<table class="table table-striped table-hover">
				<thead>
					<tr>
						<th style="width: 30px;"><input type="checkbox" id="gb-fav-all" aria-label="<?php echo rex_escape($this->i18n('a2034_fav_admin_select_all')); ?>" /></th>
						<th><?php echo $this->i18n('a2034_fav_admin_col_title'); ?></th>
						<th><?php echo $this->i18n('a2034_fav_admin_col_kind'); ?></th>
						<?php if ($multisite): ?><th><?php echo $this->i18n('a2034_fav_admin_col_domain'); ?></th><?php endif; ?>
						<th><?php echo $this->i18n('a2034_fav_admin_col_author'); ?></th>
						<th><?php echo $this->i18n('a2034_fav_admin_col_visibility'); ?></th>
						<th><?php echo $this->i18n('a2034_fav_admin_col_used'); ?></th>
						<th><?php echo $this->i18n('a2034_fav_admin_col_created'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ($result['rows'] === []): ?>
					<tr><td colspan="<?php echo $multisite ? 8 : 7; ?>"><?php echo $this->i18n('a2034_fav_admin_empty'); ?></td></tr>
					<?php endif; ?>
					<?php foreach ($result['rows'] as $row): ?>
					<tr class="gb-fav-row">
						<td><input type="checkbox" name="ids[]" value="<?php echo (int) $row['id']; ?>" class="gb-fav-check" aria-label="<?php echo rex_escape($row['title']); ?>" /></td>
						<td>
							<div class="gb-fav-entry">
								<span class="gb-fav-entry-icon" aria-hidden="true"><i class="rex-icon fa-star"></i></span>
								<span class="gb-fav-entry-text">
									<span class="gb-fav-name"><?php echo rex_escape($row['title']); ?></span>
									<?php if ((string) $row['summary'] !== ''): ?><span class="gb-fav-summary"><?php echo rex_escape((string) $row['summary']); ?></span><?php endif; ?>
								</span>
								<button type="button" class="btn btn-xs btn-default gb-fav-rename-toggle" data-id="<?php echo (int) $row['id']; ?>" data-title="<?php echo rex_escape($row['title']); ?>" title="<?php echo rex_escape($this->i18n('a2034_fav_admin_rename')); ?>" aria-label="<?php echo rex_escape($this->i18n('a2034_fav_admin_rename').': '.$row['title']); ?>"><i class="rex-icon fa-pencil" aria-hidden="true"></i> <?php echo $this->i18n('a2034_fav_admin_rename'); ?></button>
							</div>
						</td>
						<td class="gb-fav-kind"><i class="rex-icon <?php echo rex_escape($kindIcons[$row['kind']] ?? 'fa-cube'); ?>" aria-hidden="true"></i> <?php echo rex_escape($kindLabels[$row['kind']] ?? $row['kind']); ?></td>
						<?php if ($multisite): ?>
						<td>
							<?php if ((int) $row['domain_id'] === 0): ?>
							<?php echo $this->i18n('a2034_fav_admin_domain_all'); ?>
							<?php elseif (isset($domains[(int) $row['domain_id']])): ?>
							<?php echo rex_escape($domains[(int) $row['domain_id']]); ?>
							<?php else: ?>
							<span class="gb-fav-warn"><?php echo $this->i18n('a2034_fav_admin_domain_missing'); ?></span>
							<?php endif; ?>
						</td>
						<?php endif; ?>
						<td>
							<?php echo rex_escape((string) $row['createuser']); ?>
							<?php if (!in_array((string) $row['createuser'], $logins, true)): ?>
							<span class="gb-fav-warn gb-fav-small">(<?php echo $this->i18n('a2034_fav_admin_user_missing'); ?>)</span>
							<?php endif; ?>
						</td>
						<td><span class="gb-fav-flag <?php echo (int) $row['private'] === 1 ? 'gb-fav-flag-private' : 'gb-fav-flag-shared'; ?>"><?php echo (int) $row['private'] === 1 ? $this->i18n('a2034_fav_admin_private') : $this->i18n('a2034_fav_admin_shared'); ?></span></td>
						<td>
							<?php echo (int) $row['used_count']; ?>&times;
							<?php if ($row['last_used']): ?><div class="gb-fav-small"><?php echo rex_escape((string) $row['last_used']); ?></div><?php endif; ?>
						</td>
						<td class="gb-fav-small"><?php echo rex_escape((string) $row['createdate']); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div class="panel-body">
				<div class="gb-fav-bulk">
					<label for="gb-fav-bulk"><?php echo $this->i18n('a2034_fav_admin_bulk'); ?></label>
					<select class="form-control" style="width: auto;" id="gb-fav-bulk" name="bulk">
						<option value="release"><?php echo $this->i18n('a2034_fav_admin_bulk_release'); ?></option>
						<?php if ($multisite): ?><option value="assign"><?php echo $this->i18n('a2034_fav_admin_bulk_assign'); ?></option><?php endif; ?>
						<option value="delete"><?php echo $this->i18n('a2034_fav_admin_bulk_delete'); ?></option>
					</select>
					<?php if ($multisite): ?>
					<select class="form-control" style="width: auto;" name="bulk_domain" aria-label="<?php echo rex_escape($this->i18n('a2034_fav_admin_col_domain')); ?>">
						<?php foreach ($domains as $domainId => $domainName): ?>
						<option value="<?php echo (int) $domainId; ?>"><?php echo rex_escape($domainName); ?></option>
						<?php endforeach; ?>
						<?php if (Gridblock2Presets::crossDomainAllowed()): ?><option value="all"><?php echo $this->i18n('a2034_fav_admin_domain_all'); ?></option><?php endif; ?>
					</select>
					<?php endif; ?>
					<button type="submit" class="btn btn-primary" id="gb-fav-bulk-submit" data-confirm="<?php echo rex_escape($this->i18n('a2034_fav_admin_confirm_delete')); ?>"><?php echo $this->i18n('a2034_fav_admin_bulk_apply'); ?></button>
					<span class="gb-fav-small"><?php echo (int) $result['total']; ?> <?php echo $this->i18n('a2034_fav_admin_total'); ?></span>
				</div>
			</div>
		</form>

		<?php if ($result['pages'] > 1): ?>
		<div class="panel-body">
			<ul class="pagination" style="margin: 0;">
				<?php for ($i = 1; $i <= $result['pages']; $i++): ?>
				<li<?php echo $i === (int) $result['page'] ? ' class="active"' : ''; ?>><a href="<?php echo rex_escape($baseUrl.'&p='.$i); ?>"><?php echo $i; ?></a></li>
				<?php endfor; ?>
			</ul>
		</div>
		<?php endif; ?>
	</div>
</section>

<form method="post" action="<?php echo rex_escape($baseUrl.'&p='.(int) $result['page']); ?>" id="gb-fav-rename-form" hidden>
	<input type="hidden" name="func" value="fav_rename" />
	<input type="hidden" name="id" value="" />
	<input type="hidden" name="title" value="" />
	<?php echo $gbCsrf->getHiddenField(); ?>
</form>

<script>
(function () {
	var all = document.getElementById('gb-fav-all');
	if (all) {
		all.addEventListener('change', function () {
			document.querySelectorAll('.gb-fav-check').forEach(function (box) { box.checked = all.checked; });
		});
	}

	var submit = document.getElementById('gb-fav-bulk-submit');
	var bulk = document.getElementById('gb-fav-bulk');
	if (submit && bulk) {
		submit.addEventListener('click', function (e) {
			if (bulk.value === 'delete' && !window.confirm(submit.getAttribute('data-confirm'))) { e.preventDefault(); }
		});
	}

	var form = document.getElementById('gb-fav-rename-form');
	document.querySelectorAll('.gb-fav-rename-toggle').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var value = window.prompt(btn.getAttribute('data-title'), btn.getAttribute('data-title'));
			if (value === null) { return; }
			value = value.trim();
			if (value === '' || value === btn.getAttribute('data-title')) { return; }
			form.elements.id.value = btn.getAttribute('data-id');
			form.elements.title.value = value;
			form.submit();
		});
	});
})();
</script>
