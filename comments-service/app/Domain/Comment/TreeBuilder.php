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

        // Paginate roots
        $offset = ($page - 1) * $perPage;
        $paginatedRoots = array_slice($roots, $offset, $perPage);

        return [
            'data' => $paginatedRoots,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => count($roots),
            'last_page' => ceil(count($roots) / $perPage),
        ];
    }

    private function shallowNode(array $node): array
    {
        $node['children'] = [];

        return $node;
    }
}
