<?php
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$keyword = trim($_GET['q'] ?? '');
if ($keyword === '') {
    echo json_encode(['error' => 'Parameter q wajib diisi'], JSON_UNESCAPED_UNICODE);
    exit;
}

$networks = searchTags($keyword);

$out = [];
foreach ($networks as $net) {
    $items = [];
    foreach ($net as $n) {
        [$type, $acc] = parseIdentifier($n['identifier']);
        $items[] = [
            'identifier' => $n['identifier'],
            'type'       => $type,
            'type_label' => bankName($type),
            'account_id' => $acc,
            'id_link'    => $n['id_link'],
            'name'       => $n['name'],
            'tag'        => $n['tag'],
            'url'        => $n['url'],
            'created_at' => $n['created_at'],
        ];
    }
    $out[] = ['identities' => $items, 'count' => count($items)];
}

echo json_encode([
    'keyword'  => $keyword,
    'networks' => $out,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
