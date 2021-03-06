<?php

namespace spec\Pim\Bundle\DataGridBundle\Entity;

use PhpSpec\ObjectBehavior;
use Oro\Bundle\UserBundle\Entity\User;

class DatagridViewSpec extends ObjectBehavior
{
    public function it_stores_the_label_of_the_view(User $owner)
    {
        $this->setLabel('random view');
        $this->getLabel()->shouldReturn('random view');
    }

    public function it_stores_the_owner_of_the_view(User $owner)
    {
        $this->setOwner($owner);
        $this->getOwner()->shouldReturn($owner);
    }

    public function it_stores_the_datagrid_alias()
    {
        $this->setDatagridAlias('foo-grid');
        $this->getDatagridAlias()->shouldReturn('foo-grid');
    }

    public function it_stores_the_displayed_columns()
    {
        $this->setColumns(['foo', 'bar', 'baz']);
        $this->getColumns()->shouldReturn(['foo', 'bar', 'baz']);
    }

    public function it_stores_the_displayed_filters()
    {
        $this->setFilters('sku=1');
        $this->getFilters()->shouldReturn('sku=1');
    }
}
