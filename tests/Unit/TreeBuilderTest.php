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

        $tree = (new TreeBuilder())->buildTree($rows, [], 1, 20, true, 1);

        $this->assertCount(1, $tree['data']);
        $this->assertSame(2, count($tree['data'][0]['children']));
        $this->assertSame(1, $tree['data'][0]['children'][0]['replyto_id']);
        $this->assertSame(1, $tree['meta']['total']);
    }
}
