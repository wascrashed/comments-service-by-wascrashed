<?php

namespace App\Domain\Comment;

class TreeBuilder
{
    public function buildTree(array $rootRows, array $children, int $page, int $perPage, bool $expand, int $totalRoots): array
    {
        $map = [];

        foreach ($rootRows as $row) {
            $row['children'] = [];
            $map[$row['id']] = $row;
        }

        if ($expand) {
            foreach ($children as $child) {
                $child['children'] = [];
                $map[$child['id']] = $child;
            }

            $roots = [];

            foreach ($map as $id => $node) {
                if ($node['replyto_id'] && isset($map[$node['replyto_id']])) {
                    $map[$node['replyto_id']]['children'][] = &$map[$id];
                } else {
                    $roots[] = &$map[$id];
                }
            }

            return [
                'data' => array_values($roots),
                'meta' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalRoots,
                    'last_page' => (int) ceil($totalRoots / $perPage),
                ],
            ];
        }

        foreach ($map as &$node) {
            $node['children'] = [];
        }
        unset($node);

        return [
            'data' => array_values($map),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalRoots,
                'last_page' => (int) ceil($totalRoots / $perPage),
            ],
        ];
    }
}
