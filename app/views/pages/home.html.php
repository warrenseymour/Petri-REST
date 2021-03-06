<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\core\Libraries;
use lithium\data\Connections;

$this->title('Home');

$self = $this;

$notify = function($status, $message, $solution = null) {
	$html  = "<div class=\"test-result test-result-{$status}\">{$message}</div>";
	$html .= "<div class=\"test-result solution\">{$solution}</div>";
	return $html;
};

$support = function($classes) {
	$result = '<ul class="indicated">';

	foreach ($classes as $class => $enabled) {
		$name = substr($class, strrpos($class, '\\') + 1);
		$url = 'http://lithify.me/docs/' . str_replace('\\', '/', $class);
		$class = $enabled ? 'enabled' : 'disabled';
		$title = $enabled ? "Adapter `{$name}` is enabled." : "Adapter `{$name}` is disabled.";

		$result .= "<li><a href=\"{$url}\" title=\"{$title}\" class=\"{$class}\">{$name}</a></li>";
	}
	$result .= '</ul>';

	return $result;
};

$checks = array(
	'resourcesWritable' => function() use ($notify) {
		if (is_writable($path = Libraries::get(true, 'resources'))) {
			return $notify('success', 'Resources directory is writable.');
		}
		$path = str_replace(dirname(LITHIUM_APP_PATH) . '/', null, $path);
		$solution = null;

		if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
			$solution  = 'To fix this, run the following from the command line: ';
			$solution .= "<code>$ chmod -R 0777 {$path}</code>.";
		} else {
			$path = realpath($path);
			$solution  = 'To fix this, give <code>modify</code> rights to the user ';
			$solution .= "<code>Everyone</code> on directory <code>{$path}</code>.";
		}
		return $notify(
			'fail',
			'Your resource path is not writeable.',
			$solution
		);
	},
	'database' => function() use ($notify) {
		$config = Connections::config();

		if (!empty($config)) {
			return $notify('success', 'Database connection/s configured.');
		}
		return $notify(
			'notice',
			'No database connection defined.',
			"To create a database connection:
			<ol>
				<li>Edit the file <code>config/bootstrap.php</code>.</li>
				<li>
					Uncomment the line having
					<code>require __DIR__ . '/bootstrap/connections.php';</code>.
				</li>
				<li>Edit the file <code>config/bootstrap/connections.php</code>.</li>
			</ol>"
		);
	},
	'magicQuotes' => function() use ($notify) {
		if (get_magic_quotes_gpc() === 0) {
			return;
		}
		return $notify(
			'fail',
			'Magic quotes are enabled in your PHP configuration.',
			'Please set <code>magic_quotes_gpc = Off</code> in your <code>php.ini</code> settings.'
		);
	},
	'registerGlobals' => function() use ($notify) {
		if (!ini_get('register_globals')) {
			return;
		}
		return $notify(
			'fail',
			'Register globals is enabled in your PHP configuration.',
			'Please set <code>register_globals = Off</code> in your <code>php.ini</code> settings.'
		);
	},
	'tests' => function() use ($notify, $self) {
		$tests = $self->html->link('run all tests', array(
			'controller' => 'lithium\test\Controller',
			'args' => 'all'
		));
		$dashboard = $self->html->link('test dashboard', array('controller' => 'lithium\test\Controller'));
		$ticket = $self->html->link('file a ticket', 'https://github.com/warrenseymour/petri-rest/issues');

		return $notify(
			'notice',
			'Run the tests.',
			"Check the builtin {$dashboard} or {$tests} now to ensure Lithium and Petri-REST
			are working as expected. Do not hesitate to {$ticket} in case a test fails."
		);
	},
	'dbSupport' => function() use ($notify, $support) {
		$paths = array('data.source', 'adapter.data.source.database', 'adapter.data.source.http');
		$list = array();

		foreach ($paths as $path) {
			$list = array_merge($list, Libraries::locate($path, null, array('recursive' => false)));
		}
		$list = array_filter($list, function($class) { return method_exists($class, 'enabled'); });
		$map = array_combine($list, array_map(function($c) { return $c::enabled(); }, $list));

		return $notify(
			'notice',
			'Database support',
			$support($map)
		);
	},
	'cacheSupport' => function() use ($notify, $support) {
		$list = Libraries::locate('adapter.storage.cache', null, array('recursive' => false));
		$list = array_filter($list, function($class) { return method_exists($class, 'enabled'); });
		$map = array_combine($list, array_map(function($c) { return $c::enabled(); }, $list));

		return $notify(
			'notice',
			'Cache support',
			$support($map)
		);
	}
);

?>

<h3>Getting Started</h3>
<?php foreach ($checks as $check): ?>
	<?php echo $check(); ?>
<?php endforeach; ?>