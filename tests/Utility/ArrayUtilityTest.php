<?php

namespace SourceBroker\DeployerExtendedDatabase\Tests\Utility;

use PHPUnit\Framework\TestCase;
use SourceBroker\DeployerExtendedDatabase\Utility\ArrayUtility;

final class ArrayUtilityTest extends TestCase
{
    /**
     * @dataProvider existingMergeBehaviorProvider
     */
    public function testKeepsExistingMergeBehavior(array $expected, array $base, array $overwrite): void
    {
        self::assertSame($expected, $this->merge($base, $overwrite));
    }

    public function existingMergeBehaviorProvider(): array
    {
        return [
            'list merge and loose duplicate handling' => [
                ['first', 2, 'existing', 'third'],
                ['first', 2, 'existing'],
                ['first', '2', 'third'],
            ],
            'scalar overwrite' => [
                ['post_sql_in' => 'host.sql'],
                ['post_sql_in' => 'global.sql'],
                ['post_sql_in' => 'host.sql'],
            ],
            'recursive config merge' => [
                [
                    'database_default' => [[
                        'ignore_tables_out' => ['sys_log', 'sys_history', 'tx_project'],
                        'post_sql_in' => 'host.sql',
                    ]],
                ],
                [
                    'database_default' => [[
                        'ignore_tables_out' => ['sys_log', 'sys_history'],
                        'post_sql_in' => 'global.sql',
                    ]],
                ],
                [
                    'database_default' => [[
                        'ignore_tables_out' => ['sys_history', 'tx_project'],
                        'post_sql_in' => 'host.sql',
                    ]],
                ],
            ],
            'new branch without unset' => [
                [
                    'database_existing' => [['post_sql_in' => 'existing.sql']],
                    'database_new' => [[
                        'ignore_tables_out' => ['sys_log', 'sys_history'],
                        'post_sql_in' => 'new.sql',
                    ]],
                ],
                ['database_existing' => [['post_sql_in' => 'existing.sql']]],
                [
                    'database_new' => [[
                        'ignore_tables_out' => ['sys_log', 'sys_history'],
                        'post_sql_in' => 'new.sql',
                    ]],
                ],
            ],
        ];
    }

    public function testUnsetRemovesBaseAndNewlyAppendedValues(): void
    {
        self::assertSame(
            ['ignore_tables_out' => ['keep']],
            $this->merge(
                ['ignore_tables_out' => ['keep', 'remove_from_base']],
                [
                    'ignore_tables_out' => [
                        'remove_from_overwrite',
                        '__UNSET' => ['remove_from_base', 'remove_from_overwrite'],
                    ],
                ]
            )
        );
    }

    public function testAppliesUnsetInsideACompletelyNewBranch(): void
    {
        self::assertSame(
            ['database_new' => [['ignore_tables_out' => ['keep']]]],
            $this->merge(
                [],
                [
                    'database_new' => [[
                        'ignore_tables_out' => [
                            'keep',
                            'remove',
                            '__UNSET' => ['remove'],
                        ],
                    ]],
                ]
            )
        );
    }

    public function testUnsetUsesExactValueMatching(): void
    {
        self::assertSame(
            ['values' => [1]],
            $this->merge(
                ['values' => [1, '1']],
                ['values' => ['__UNSET' => ['1']]]
            )
        );
    }

    public function testDoesNotReturnTheSpecialKey(): void
    {
        self::assertSame(
            ['values' => ['keep']],
            $this->merge(
                ['values' => ['keep']],
                ['values' => ['__UNSET' => ['missing']]]
            )
        );
    }

    public function testDoesNotMutateInputs(): void
    {
        $base = ['values' => ['keep', 'remove']];
        $overwrite = ['values' => ['__UNSET' => ['remove']]];
        $baseBefore = $base;
        $overwriteBefore = $overwrite;

        (new ArrayUtility())->arrayMergeRecursiveDistinct($base, $overwrite);

        self::assertSame($baseBefore, $base);
        self::assertSame($overwriteBefore, $overwrite);
    }

    private function merge(array $base, array $overwrite): array
    {
        return (new ArrayUtility())->arrayMergeRecursiveDistinct($base, $overwrite);
    }
}
