<?php

namespace Pim\Bundle\DataGridBundle\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionMediatorInterface;

/**
 * Export action handler
 *
 * @author    Filips Alpe <filips@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ExportMassActionHandler implements MassActionHandlerInterface
{
    /**
     * Return the datagrid QueryBuilder to use for quick export
     *
     * @param MassActionMediatorInterface $mediator
     *
     * @return mixed
     */
    public function handle(MassActionMediatorInterface $mediator)
    {
        $qb = $mediator->getResults()->getSource();

        return $qb->getQuery()->execute();
    }
}
