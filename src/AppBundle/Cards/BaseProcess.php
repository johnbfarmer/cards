<?php 

namespace AppBundle\Cards;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use AppBundle\Helper\QueryHelper;

class BaseProcess
{
    protected
        $parameters,
        $config,
        $connection,
        $output,
        $data = [],
        $dimension_table = '',
        $errors = [],
        $logger,
        $base_dir = '',
        $data_dir = '';

    public function __construct($parameters)
    {
        $this->parameters = $parameters;
        $this->output = !empty($parameters['output']) ? $parameters['output'] : null;
    }

    protected function execute()
    {
        
    }

    public function showCards($cards, $intro='')
    {
        $str = $intro;
        foreach ($cards as $c) {
            $str .= $c->getDisplay() . ' ';
        }

        $this->writeln("$str\n");
    }

    protected function writeln($msg)
    {
        if ($this->output) {
            $this->output->writeln($msg);
        } else {
            print $msg . "\n";
        }
    }

    protected function exec($sql, $params = [], $log = false, $throwException = false)
    {
        return QueryHelper::exec($sql, $params, $log, $throwException);
    }

    protected function fetch($sql, $params = [], $log = false)
    {
        return QueryHelper::fetch($sql, $params, $log);
    }

    protected function fetchAll($sql, $params = [], $log = false)
    {
        return QueryHelper::fetchAll($sql, $params, $log);
    }

    protected function lastInsertId($connection = null)
    {
        return QueryHelper::lastInsertId($connection);
    }

    protected function getTableName($t, $ticks = true)
    {
        return QueryHelper::getTableName($t, $ticks);
    }

    protected function dropTableIfExists($tables, $log = false)
    {
        return QueryHelper::dropTableIfExists($tables, $log);
    }

    protected function log($msg, $std_out = false, $level = 'info')
    {
        if (empty($this->logger)) {
            return;
        }

        if (is_array($msg)) {
            $msg = json_encode($msg);
        }

        if ($level == 'info') {
            $this->logger->info($msg);
        } else {
            $this->logger->error($msg);
        }

        if ($std_out) {
            print $msg . "\n";
        }
    }

    protected function logError($msg)
    {
        $this->log($msg, true, 'error');
    }

    public function logErrors()
    {
        if (empty($this->errors)) {
            return;
        }
        foreach ($this->errors as $error) {
            $this->logError($error);
        }
    }

    public function hasErrors()
    {
        if (empty($this->errors)) {
            return false;
        }

        return true;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getData()
    {
        return $this->data;
    }

    public static function autoExecute($parameters)
    {
        $class = get_called_class();
        $me = new $class($parameters);
        $me->execute();
        return $me;
    }
}