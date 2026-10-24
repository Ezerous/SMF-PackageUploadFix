<?php

function fail_test($message)
{
	fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
	exit(1);
}

function get_single_node($parent, $namespace, $name, $context)
{
	$nodes = $parent->getElementsByTagNameNS($namespace, $name);

	if ($nodes->length !== 1)
		fail_test($context . ' must contain exactly one ' . $name . ' element');

	return $nodes->item(0);
}

function token_can_end_expression($id, $value)
{
	if ($id === T_VARIABLE || $id === T_STRING || $id === T_CONSTANT_ENCAPSED_STRING || $id === T_LNUMBER || $id === T_DNUMBER || $id === T_TRUE || $id === T_FALSE || $id === T_NULL)
		return true;

	return $id === null && in_array($value, array(')', ']', '}'), true);
}

function assert_php53_compatible($code, $context)
{
	/*
		PHP 5.3 does not understand short array syntax. Tokenizing the code
		lets us ignore brackets inside strings and comments without evaluating
		package-provided PHP code.
	*/
	$tokens = token_get_all('<?php ' . $code);
	$previous_can_end_expression = false;

	foreach ($tokens as $token)
	{
		if (is_array($token))
		{
			$id = $token[0];
			$value = $token[1];

			if ($id === T_OPEN_TAG || $id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT)
				continue;
		}
		else
		{
			$id = null;
			$value = $token;
		}

		if ($value === '[' && !$previous_can_end_expression)
			fail_test($context . ' uses PHP short array syntax, which is not compatible with PHP 5.3');

		$previous_can_end_expression = token_can_end_expression($id, $value);
	}

	if (strpos($code, '??') !== false || strpos($code, '<=>') !== false)
		fail_test($context . ' uses syntax newer than PHP 5.3');
}

function replace_once($source, $search, $replacement, $context)
{
	if ($search === '')
		fail_test($context . ' has an empty search anchor');

	$count = 0;
	$result = str_replace($search, $replacement, $source, $count);

	if ($count !== 1)
		fail_test($context . ' expected exactly one matching anchor, found ' . $count);

	return $result;
}

function apply_operation($source, $operation, $context)
{
	if ($operation['position'] === 'replace')
		$replacement = $operation['add'];
	elseif ($operation['position'] === 'before')
		$replacement = $operation['add'] . $operation['search'];
	else
		$replacement = $operation['search'] . $operation['add'];

	return replace_once($source, $operation['search'], $replacement, $context);
}

function reverse_operation($source, $operation, $context)
{
	if ($operation['position'] === 'replace')
		$installed = $operation['add'];
	elseif ($operation['position'] === 'before')
		$installed = $operation['add'] . $operation['search'];
	else
		$installed = $operation['search'] . $operation['add'];

	if ($installed === '')
		fail_test($context . ' cannot be reversed because it installs an empty string');

	return replace_once($source, $installed, $operation['search'], $context . ' during reverse');
}

$namespace = 'http://www.simplemachines.org/xml/modification';
$document = new DOMDocument();
$xml_path = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'modification.xml';

$loaded = $document->load($xml_path, LIBXML_NONET);

if (!$loaded)
	fail_test('could not load modification.xml');

$files = $document->getElementsByTagNameNS($namespace, 'file');
if ($files->length === 0)
	fail_test('modification.xml contains no file elements');

$total_operations = 0;

for ($file_index = 0; $file_index < $files->length; $file_index++)
{
	$file = $files->item($file_index);
	$file_name = $file->getAttribute('name');
	if ($file_name === '')
		$file_name = 'file #' . ($file_index + 1);

	$operations = $file->getElementsByTagNameNS($namespace, 'operation');
	if ($operations->length === 0)
		fail_test($file_name . ' contains no modification operations');

	$file_operations = array();
	$fixture = "<?php\n";

	for ($operation_index = 0; $operation_index < $operations->length; $operation_index++)
	{
		$operation_node = $operations->item($operation_index);
		$context = $file_name . ', operation #' . ($operation_index + 1);
		$search_node = get_single_node($operation_node, $namespace, 'search', $context);
		$add_node = get_single_node($operation_node, $namespace, 'add', $context);

		$position = strtolower(trim($search_node->parentNode->getAttribute('position')));
		if ($position === '')
			$position = 'replace';
		if (!in_array($position, array('replace', 'before', 'after'), true))
			fail_test($context . ' uses unsupported search position "' . $position . '"');

		$search = $search_node->textContent;
		$add = $add_node->textContent;
		if ($search === '')
			fail_test($context . ' has an empty search anchor');

		assert_php53_compatible($add, $context . ' add block');

		$file_operations[] = array(
			'position' => $position,
			'search' => $search,
			'add' => $add
		);

		/* Keep every anchor in one synthetic target file for uniqueness checks. */
		$fixture .= '/* __SMF_PACKAGE_VALIDATOR_ANCHOR_' . $operation_index . '__ */' . "\n";
		$fixture .= $search . "\n";
	}

	for ($operation_index = 0; $operation_index < count($file_operations); $operation_index++)
	{
		$operation = $file_operations[$operation_index];
		$context = $file_name . ', operation #' . ($operation_index + 1);
		$count = substr_count($fixture, $operation['search']);

		if ($count !== 1)
			fail_test($context . ' search anchor is not unique, found ' . $count . ' matches');
	}

	$installed = $fixture;
	for ($operation_index = 0; $operation_index < count($file_operations); $operation_index++)
	{
		$context = $file_name . ', operation #' . ($operation_index + 1);
		$installed = apply_operation($installed, $file_operations[$operation_index], $context);
	}

	$restored = $installed;
	for ($operation_index = count($file_operations) - 1; $operation_index >= 0; $operation_index--)
	{
		$context = $file_name . ', operation #' . ($operation_index + 1);
		$restored = reverse_operation($restored, $file_operations[$operation_index], $context);
	}

	if ($restored !== $fixture)
		fail_test($file_name . ' operations are not reversible');

	$total_operations += count($file_operations);
}

echo 'PASS: XML, PHP 5.3 compatibility, ' . $total_operations . ' operations, unique anchors, and reversibility' . PHP_EOL;
