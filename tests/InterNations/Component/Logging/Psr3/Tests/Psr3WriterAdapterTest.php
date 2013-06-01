<?php
namespace InterNations\Component\Logging\Psr3\Tests;

use Monolog\Handler\TestHandler as MonologTestHandler;
use Monolog\Logger as Monolog;
use Psr\Log\LogLevel;
use Zend_Log as ZendLogger;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;

class Psr3WriterAdapterTest extends TestCase
{
    /** @var MonologTestHandler */
    private $testHandler;

    public function setUp()
    {
        $this->testHandler = new MonologTestHandler();
    }

    public static function provideLogLevel()
    {
        return array(
            array(ZendLogger::EMERG, LogLevel::EMERGENCY),
            array(ZendLogger::ALERT, LogLevel::ALERT),
            array(ZendLogger::CRIT, LogLevel::CRITICAL),
            array(ZendLogger::ERR, LogLevel::CRITICAL),
            array(ZendLogger::WARN, LogLevel::WARNING),
            array(ZendLogger::NOTICE, LogLEvel::NOTICE),
            array(ZendLogger::INFO, LogLevel::INFO),
            array(ZendLogger::DEBUG, LogLevel::DEBUG),
        );
    }

    /** @dataProvider provideLogLevel */
    public function testLog($zendLogLevel, $psrLogLevel)
    {
        $logger = ZendLogger::factory(
            array(
                array(
                    'writerName'      => 'Psr3WriterAdapter',
                    'writerNamespace' => 'InterNations\\Component\\Logging\\Psr3\\',
                    'writerParams'    => array(
                        'logger' => new Monolog('test', array($this->testHandler))
                    )
                )
            )
        );

        $logger->log('test', $zendLogLevel);

        $record = current($this->testHandler->getRecords());

        $this->assertSame('test', $record['message']);
        $this->assertSame($psrLogLevel, strtolower($record['level_name']));
    }

    /** @dataProvider provideLogLevel */
    public function testLogIncludeContext($zendLogLevel, $psrLogLevel)
    {
        $logger = ZendLogger::factory(
            array(
                array(
                    'writerName'      => 'Psr3WriterAdapter',
                    'writerNamespace' => 'InterNations\\Component\\Logging\\Psr3\\',
                    'writerParams'    => array(
                        'logger'                => new Monolog('test', array($this->testHandler)),
                        'includeEventAsContext' => true,
                    )
                )
            )
        );

        $logger->log('test', $zendLogLevel);

        $record = current($this->testHandler->getRecords());

        $this->assertSame('test', $record['message']);
        $this->assertSame($psrLogLevel, strtolower($record['level_name']));

        $this->assertCount(3, $record['context']);
        $this->assertArrayHasKey('timestamp', $record['context']);
        $this->assertArrayHasKey('priority', $record['context']);
        $this->assertArrayHasKey('priorityName', $record['context']);
    }

    public function testCustomPriorityWithDefaultFallback()
    {
        $logger = ZendLogger::factory(
            array(
                array(
                    'writerName'      => 'Psr3WriterAdapter',
                    'writerNamespace' => 'InterNations\\Component\\Logging\\Psr3\\',
                    'writerParams'    => array(
                        'logger'                => new Monolog('test', array($this->testHandler)),
                        'includeEventAsContext' => true,
                    )
                )
            )
        );
        $logger->addPriority('trace', 8);

        $logger->log('test', 8);

        $record = current($this->testHandler->getRecords());

        $this->assertSame('test', $record['message']);
        $this->assertSame('debug', strtolower($record['level_name']));

        $this->assertCount(3, $record['context']);
        $this->assertArrayHasKey('timestamp', $record['context']);
        $this->assertArrayHasKey('priority', $record['context']);
        $this->assertSame(8, $record['context']['priority']);
        $this->assertArrayHasKey('priorityName', $record['context']);
        $this->assertSame('TRACE', $record['context']['priorityName']);
    }

    public function testCustomPriorityWithCustomFallback()
    {
        $logger = ZendLogger::factory(
            array(
                array(
                    'writerName'      => 'Psr3WriterAdapter',
                    'writerNamespace' => 'InterNations\\Component\\Logging\\Psr3\\',
                    'writerParams'    => array(
                        'logger'                => new Monolog('test', array($this->testHandler)),
                        'includeEventAsContext' => true,
                        'fallbackLogLevel'      => LogLevel::ALERT,
                    )
                )
            )
        );
        $logger->addPriority('trace', 8);

        $logger->log('test', 8);

        $record = current($this->testHandler->getRecords());

        $this->assertSame('test', $record['message']);
        $this->assertSame('alert', strtolower($record['level_name']));

        $this->assertCount(3, $record['context']);
        $this->assertArrayHasKey('timestamp', $record['context']);
        $this->assertArrayHasKey('priority', $record['context']);
        $this->assertSame(8, $record['context']['priority']);
        $this->assertArrayHasKey('priorityName', $record['context']);
        $this->assertSame('TRACE', $record['context']['priorityName']);
    }

    public function testCustomLevelTranslationTable()
    {
        $logger = ZendLogger::factory(
            array(
                array(
                    'writerName'      => 'Psr3WriterAdapter',
                    'writerNamespace' => 'InterNations\\Component\\Logging\\Psr3\\',
                    'writerParams'    => array(
                        'logger'           => new Monolog('test', array($this->testHandler)),
                        'translationTable' => array(ZendLogger::DEBUG => LogLevel::EMERGENCY)
                    )
                )
            )
        );

        $logger->log('test', ZendLogger::DEBUG);

        $record = current($this->testHandler->getRecords());

        $this->assertSame('test', $record['message']);
        $this->assertSame('emergency', strtolower($record['level_name']));
    }

    public function testErrorNoLoggerPassed()
    {
        $this->setExpectedException('InvalidArgumentException', 'Logger needs to implement Psr\Log\LoggerInterface');
        ZendLogger::factory(
            array(
                array(
                    'writerName'      => 'Psr3WriterAdapter',
                    'writerNamespace' => 'InterNations\\Component\\Logging\\Psr3\\',
                    'writerParams'    => array()
                )
            )
        );
    }

    public function testErrorInvalidLoggerPassed()
    {
        $this->setExpectedException('InvalidArgumentException', 'Logger needs to implement Psr\Log\LoggerInterface');
        ZendLogger::factory(
            array(
                array(
                    'writerName'      => 'Psr3WriterAdapter',
                    'writerNamespace' => 'InterNations\\Component\\Logging\\Psr3\\',
                    'writerParams'    => array('logger' => new stdClass())
                )
            )
        );
    }
}
