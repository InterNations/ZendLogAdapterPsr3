<?php
namespace InterNations\Component\Logging\Psr3\Tests;

use Monolog\Handler\TestHandler as MonologTestHandler;
use Monolog\Logger as Monolog;
use Psr\Log\LogLevel;
use Zend_Log as ZendLogger;
use PHPUnit\Framework\TestCase;
use stdClass;

class Psr3WriterAdapterTest extends TestCase
{
    /** @var MonologTestHandler */
    private $testHandler;

    public function setUp(): void
    {
        $this->testHandler = new MonologTestHandler();
    }

    public static function provideLogLevel()
    {
        return [
            [ZendLogger::EMERG, LogLevel::EMERGENCY],
            [ZendLogger::ALERT, LogLevel::ALERT],
            [ZendLogger::CRIT, LogLevel::CRITICAL],
            [ZendLogger::ERR, LogLevel::ERROR],
            [ZendLogger::WARN, LogLevel::WARNING],
            [ZendLogger::NOTICE, LogLEvel::NOTICE],
            [ZendLogger::INFO, LogLevel::INFO],
            [ZendLogger::DEBUG, LogLevel::DEBUG],
        ];
    }

    /** @dataProvider provideLogLevel */
    public function testLog($zendLogLevel, $psrLogLevel)
    {
        $logger = ZendLogger::factory(
            [
                [
                    'writerName'      => 'Psr3WriterAdapter',
                    'writerNamespace' => 'InterNations\\Component\\Logging\\Psr3\\',
                    'writerParams'    => [
                        'logger' => new Monolog('test', [$this->testHandler])
                    ]
                ]
            ]
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
            [
                [
                    'writerName'      => 'Psr3WriterAdapter',
                    'writerNamespace' => 'InterNations\\Component\\Logging\\Psr3\\',
                    'writerParams'    => [
                        'logger'                => new Monolog('test', [$this->testHandler]),
                        'includeEventAsContext' => true,
                   ]
                ]
            ]
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
            [
                [
                    'writerName'      => 'Psr3WriterAdapter',
                    'writerNamespace' => 'InterNations\\Component\\Logging\\Psr3\\',
                    'writerParams'    => [
                        'logger'                => new Monolog('test', [$this->testHandler]),
                        'includeEventAsContext' => true,
                    ]
                ]
            ]
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
            [
                [
                    'writerName'      => 'Psr3WriterAdapter',
                    'writerNamespace' => 'InterNations\\Component\\Logging\\Psr3\\',
                    'writerParams'    => [
                        'logger' => new Monolog('test', [$this->testHandler]),
                        'includeEventAsContext' => true,
                        'fallbackLogLevel' => LogLevel::ALERT,
                    ]
                ]
            ]
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
            [
                [
                    'writerName'      => 'Psr3WriterAdapter',
                    'writerNamespace' => 'InterNations\\Component\\Logging\\Psr3\\',
                    'writerParams'    => [
                        'logger'           => new Monolog('test', [$this->testHandler]),
                        'translationTable' => [ZendLogger::DEBUG => LogLevel::EMERGENCY]
                    ]
                ]
            ]
        );

        $logger->log('test', ZendLogger::DEBUG);

        $record = current($this->testHandler->getRecords());

        $this->assertSame('test', $record['message']);
        $this->assertSame('emergency', strtolower($record['level_name']));
    }

    public function testErrorNoLoggerPassed()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Logger needs to implement Psr\Log\LoggerInterface');
        ZendLogger::factory(
            [
                [
                    'writerName'      => 'Psr3WriterAdapter',
                    'writerNamespace' => 'InterNations\\Component\\Logging\\Psr3\\',
                    'writerParams'    =>  [],
                ]
            ]
        );
    }

    public function testErrorInvalidLoggerPassed()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Logger needs to implement Psr\Log\LoggerInterface');
        ZendLogger::factory(
            [
                [
                    'writerName'      => 'Psr3WriterAdapter',
                    'writerNamespace' => 'InterNations\\Component\\Logging\\Psr3\\',
                    'writerParams'    => ['logger' => new stdClass()],
                ]
            ]
        );
    }
}
