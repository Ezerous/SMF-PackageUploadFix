<?php

function fail_test($message)
{
	fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
	exit(1);
}

function apply_replacement($source, $search, $replacement)
{
	$count = 0;
	$result = str_replace($search, $replacement, $source, $count);

	if ($count !== 1)
		fail_test('expected exactly one replacement, found ' . $count);

	return $result;
}

$namespace = 'http://www.simplemachines.org/xml/modification';
$document = new DOMDocument();

if (!$document->load(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'modification.xml'))
	fail_test('could not load modification.xml');

$operations = $document->getElementsByTagNameNS($namespace, 'operation');
if ($operations->length !== 2)
	fail_test('expected two modification operations');

$replacements = array();
for ($i = 0; $i < $operations->length; $i++)
{
	$search_nodes = $operations->item($i)->getElementsByTagNameNS($namespace, 'search');
	$add_nodes = $operations->item($i)->getElementsByTagNameNS($namespace, 'add');

	if ($search_nodes->length !== 1 || $add_nodes->length !== 1)
		fail_test('each operation must contain one search and one add element');

	$search = $search_nodes->item(0)->textContent;
	$add = $add_nodes->item(0)->textContent;

	if (preg_match('/[=,]\s*\[/', $add))
		fail_test('replacement contains PHP short array syntax');

	$replacements[] = array('search' => $search, 'add' => $add);
}

eval($replacements[1]['add']);

$original = "<?php\n" . $replacements[0]['search'] . "\n" . $replacements[1]['search'] . "\n";
$installed = apply_replacement($original, $replacements[0]['search'], $replacements[0]['add']);
$installed = apply_replacement($installed, $replacements[1]['search'], $replacements[1]['add']);
$restored = apply_replacement($installed, $replacements[0]['add'], $replacements[0]['search']);
$restored = apply_replacement($restored, $replacements[1]['add'], $replacements[1]['search']);

if ($restored !== $original)
	fail_test('replacement operations are not reversible');

echo 'PASS: XML, PHP compatibility, and replacement reversibility' . PHP_EOL;
