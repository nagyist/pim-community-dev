<?php
declare(strict_types=1);

namespace Akeneo\Connectivity\Connection\Domain\Apps\Persistence\Query;

use Akeneo\Connectivity\Connection\Domain\Apps\Model\ConnectedApp;

interface FindAllConnectedAppsQueryInterface
{
    /**
     * @return array<ConnectedApp>
     */
    public function execute(): array;
}
