<?php
/*
	Redaxo-Addon Gridblock²
	API-Anbindung: Favoriten (speichern, auflisten, umbenennen, löschen)
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

class rex_api_gridblock2_favorites extends rex_api_function
{
	protected $published = false;

	protected function requiresCsrfProtection()
	{	return true;
	}

	public function execute()
	{	$status = 200;
		$body = ['ok' => true];

		try {
			if (!Gridblock2Presets::canUse()) {
				throw new Gridblock2PresetsException('forbidden', 403);
			}

			Gridblock2Presets::ensureTable();

			$action = rex_request::request('action', 'string', '');
			$isWrite = in_array($action, ['save', 'rename', 'delete', 'use'], true);
			if (!$isWrite && $action !== 'list') {
				throw new Gridblock2PresetsException('unknown action', 400);
			}
			if ($isWrite && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
				throw new Gridblock2PresetsException('method not allowed', 405);
			}

			$isDemo = Gridblock2Builder_Tree::isDemoVars((array) ($_SESSION['gridRexVars'] ?? []));
			session_write_close();

			switch ($action) {
				case 'list':
					$body['items'] = Gridblock2Presets::listVisible(rex_request::request('q', 'string', ''), $isDemo);
					$body['canSave'] = Gridblock2Presets::canSave();
					break;

				case 'save':
					$privateRaw = rex_post('private', 'string', '');
					$body['item'] = Gridblock2Presets::save(
						rex_post('title', 'string', ''),
						rex_post('kind', 'string', ''),
						rex_post('payload', 'string', ''),
						$privateRaw === '' ? null : $privateRaw === '1',
						rex_post('domain', 'string', 'current'),
						$isDemo
					);
					break;

				case 'rename':
					$body['item'] = Gridblock2Presets::rename(rex_post('id', 'int', 0), rex_post('title', 'string', ''));
					break;

				case 'use':
					$body['favorite'] = Gridblock2Presets::fetchForInsert(rex_post('id', 'int', 0), $isDemo);
					break;

				case 'delete':
					Gridblock2Presets::delete(rex_post('id', 'int', 0));
					break;
			}
		} catch (Gridblock2PresetsException $e) {
			$status = $e->getHttpStatus();
			$body = ['ok' => false, 'error' => $e->getMessage()];
		} catch (Throwable $e) {
			rex_logger::logException($e);
			$status = 500;
			$body = ['ok' => false, 'error' => 'error'];
			if (Gridblock2Presets::isAdmin()) {
				$body['detail'] = mb_substr(get_class($e).': '.$e->getMessage(), 0, 300);
			}
		}

		rex_response::cleanOutputBuffers();
		rex_response::setStatus(self::statusLine($status));
		rex_response::sendJson($body);
		exit;
	}

	private static function statusLine(int $code): string
	{	return match ($code) {
			400 => '400 Bad Request',
			403 => '403 Forbidden',
			404 => '404 Not Found',
			405 => '405 Method Not Allowed',
			409 => '409 Conflict',
			422 => '422 Unprocessable Entity',
			500 => '500 Internal Server Error',
			default => '200 OK',
		};
	}
}
