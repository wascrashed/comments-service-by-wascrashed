<?php

namespace App\Domain\Comment;

class TreeBuilder
{
    public function buildTree(array $rows, int $page, int $perPage, bool $expand): array
    {
        $map = [];
        $roots = [];

        foreach ($rows as $row) {
            $row['children'] = [];
            $map[$row['id']] = $row;
        }

        foreach ($map as $id => $node) {
            if ($node['replyto_id'] && isset($map[$node['replyto_id']])) {
                $map[$node['replyto_id']]['children'][] = &$map[$id];
            } else {
                $roots[] = &$map[$id];
            }
        }

        if (! $expand) {
            $roots = array_map(fn($node) => $this->shallowNode($node), $roots);
        }

        $offset = ($page - 1) * $perPage;
        return array_slice($roots, $offset, $perPage);
    }

    private function shallowNode(array $node): array
    {
        $node['children'] = [];

        return $node;
    }
}
