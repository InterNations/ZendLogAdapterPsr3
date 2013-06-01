<?php
namespace InterNations\Component\Logging\Psr3;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Zend_Log as ZendLog;
use Zend_Log_Writer_Abstract as AbstractZendLogWriter;
use InvalidArgumentException;

class Psr3WriterAdapter extends AbstractZendLogWriter
{
    /**
     * Translation table for translating Zend_Log priorities to PSR-3 log levels
     *
     * @var array
     */
    private $translationTable = array(
        ZendLog::EMERG  => LogLevel::EMERGENCY,
        ZendLog::ALERT  => LogLevel::ALERT,
        ZendLog::CRIT   => LogLevel::CRITICAL,
        ZendLog::ERR    => LogLevel::CRITICAL,
        ZendLog::WARN   => LogLevel::WARNING,
        ZendLog::NOTICE => LogLEvel::NOTICE,
        ZendLog::INFO   => LogLevel::INFO,
        ZendLog::DEBUG  => LogLevel::DEBUG,
    );

    /** @var LoggerInterface */
    private $logger;

    /**
     * Fallback log level if no translation was available (custom priorities e.g.)
     *
     * @var string
     */
    private $fallbackLogLevel = LogLevel::DEBUG;

    /**
     * Should the remaining
     *
     * @var boolean
     */
    private $includeEventAsContext = false;

    public function __construct(
        LoggerInterface $logger,
        array $translationTable = array(),
        $includeEventAsContext = false,
        $fallbackLogLevel = null
    )
    {
        $this->logger = $logger;
        $this->includeEventAsContext = $includeEventAsContext;
        $this->translationTable = array_replace($this->translationTable, $translationTable);
        $this->fallbackLogLevel = $fallbackLogLevel !== null ? $fallbackLogLevel : $this->fallbackLogLevel;
    }

    protected function _write($event)
    {
        $level = $this->translatePriority($event);

        $message = $event['message'];
        $context = array();

        if ($this->includeEventAsContext) {
            unset($event['message']);
            $context = $event;
        }

        $this->logger->log($level, $message, $context);
    }

    private function translatePriority(array $event)
    {
        if (!isset($this->translationTable[$event['priority']])) {
            return $this->fallbackLogLevel;
        }

        return $this->translationTable[$event['priority']];
    }

    public static function factory($config)
    {
        $config = array_replace_recursive(
            array(
                'includeEventAsContext' => false,
                'fallbackLogLevel'      => null,
                'translationTable'      => array(),
            ),
            $config
        );

        if (!isset($config['logger']) || !$config['logger'] instanceof LoggerInterface) {
            throw new InvalidArgumentException('Logger needs to implement Psr\Log\LoggerInterface');
        }

        return new static(
            $config['logger'],
            $config['translationTable'],
            $config['includeEventAsContext'],
            $config['fallbackLogLevel']
        );
    }
}
