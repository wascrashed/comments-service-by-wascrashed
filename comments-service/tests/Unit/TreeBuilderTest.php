<?php

namespace Tests\Unit;

use App\Domain\Comment\TreeBuilder;
use PHPUnit\Framework\TestCase;

class TreeBuilderTest extends TestCase
{
    public function test_build_tree_creates_hierarchy(): void
    {
        $rows = [
            ['id' => 1, 'replyto_id' => null, 'path' => '1', 'level' => 1],
            ['id' => 2, 'replyto_id' => 1, 'path' => '1.1', 'level' => 2],
            ['id' => 3, 'replyto_id' => 1, 'path' => '1.2', 'level' => 2],
        ];

        $tree = (new TreeBuilder())->buildTree($rows, 1, 20, true);

        $this->assertCount(1, $tree);
        $this->assertSame(2, count($tree[0]['children']));
        $this->assertSame(1, $tree[0]['children'][0]['replyto_id']);
    }
}
