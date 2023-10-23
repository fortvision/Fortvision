<?php

namespace Fortvision\Platform\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class HistoryLog
 * @package Fortvision\Platform\Model\ResourceModel
 */
class HistoryLog extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('fortvision_history_log', 'log_id');
    }
}
