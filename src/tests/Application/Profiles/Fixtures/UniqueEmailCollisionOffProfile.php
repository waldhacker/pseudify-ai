<?php

declare(strict_types=1);

namespace Waldhacker\Pseudify\Core\Tests\Application\Profiles\Fixtures;

use Waldhacker\Pseudify\Core\Processor\Processing\Pseudonymize\DataManipulatorPreset;
use Waldhacker\Pseudify\Core\Profile\Model\Pseudonymize\Column;
use Waldhacker\Pseudify\Core\Profile\Model\Pseudonymize\Table;
use Waldhacker\Pseudify\Core\Profile\Model\Pseudonymize\TableDefinition;
use Waldhacker\Pseudify\Core\Profile\Pseudonymize\ProfileInterface;

class UniqueEmailCollisionOffProfile implements ProfileInterface
{
    public function getIdentifier(): string
    {
        return 'unique_email_collision_off';
    }

    public function getTableDefinition(): TableDefinition
    {
        $tableDefinition = new TableDefinition('unique_email_collision_off');
        $tableDefinition
            ->addTable(Table::create('wh_unique_email_issue')
                ->addColumn(Column::create('email')
                    ->addDataProcessing(DataManipulatorPreset::scalarData(
                        fakerFormatter: 'constantCollisionEmail',
                    ))
                )
            );

        return $tableDefinition;
    }
}
