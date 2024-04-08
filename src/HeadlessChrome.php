<?php

/* Icinga PDF Export | (c) 2018 Icinga GmbH | GPLv2 */

namespace ipl\Pdf;

require_once "DeferredMessage.php";

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use LogicException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use React\ChildProcess\Process;
use React\EventLoop\Loop;
use React\EventLoop\TimerInterface;
use RuntimeException;
use WebSocket\BadOpcodeException;
use WebSocket\Client;
use WebSocket\ConnectionException;

class HeadlessChrome implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Line of stderr output identifying the websocket url
     *
     * First matching group is the used port and the second one the browser id.
     */
    const DEBUG_ADDR_PATTERN = '/DevTools listening on ws:\/\/((?>\d+\.?){4}:\d+)\/devtools\/browser\/([\w-]+)/';

    /** @var string */
    const WAIT_FOR_NETWORK = 'wait-for-network';

    /** @var string Javascript Promise to wait for layout initialization */
    const WAIT_FOR_LAYOUT = <<<JS
new Promise((fulfill, reject) => {
    let timeoutId = setTimeout(() => reject('fail'), 10000);

    if (document.documentElement.dataset.layoutReady === 'yes') {
        clearTimeout(timeoutId);
        fulfill(null);
        return;
    }

    document.addEventListener('layout-ready', e => {
        clearTimeout(timeoutId);
        fulfill(e.detail);
    }, {
        once: true
    });
})
JS;

    /** @var ?string Path to the Chrome binary */
    protected $binary;

    /** @var ?array Host and port to the remote Chrome */
    protected ?array $remote;

    /** @var string The document to print */
    protected string $document;

    /** @var ?string Target Url */
    protected $url;

    /** @var TemporaryDirectory */
    protected TemporaryDirectory $temporaryDirectory;

    /** @var LoggerInterface */
    protected $logger;

    /** @var array */
    protected array $interceptedRequests = [];

    /** @var array */
    protected array $interceptedEvents = [];

    /**
     * Get the path to the Chrome binary
     *
     * @return  string
     */
    public function getBinary(): string
    {
        return $this->binary;
    }

    /**
     * Set the path to the Chrome binary
     *
     * @param string $binary
     *
     * @return  $this
     */
    public function setBinary(string $binary): self
    {
        $this->binary = $binary;

        return $this;
    }

    /**
     * Get host and port combination of the remote chrome
     *
     * @return array
     */
    public function getRemote(): array
    {
        return $this->remote;
    }

    /**
     * Set host and port combination of a remote chrome
     *
     * @param string $host
     * @param int $port
     *
     * @return $this
     */
    public function setRemote(string $host, int $port): self
    {
        $this->remote = [$host, $port];

        return $this;
    }

    /**
     * Get the target Url
     *
     * @return ?string
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Set the target Url
     *
     * @param string $url
     *
     * @return $this
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getTemporaryDirectory(): TemporaryDirectory
    {
        return $this->temporaryDirectory;
    }

    public function setTemporaryDirectory(TemporaryDirectory $temporaryDirectory): self
    {
        $this->temporaryDirectory = $temporaryDirectory;

        return $this;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Render the given argument name-value pairs as shell-escaped string
     *
     * @param array $arguments
     *
     * @return  string
     */
    public static function renderArgumentList(array $arguments): string
    {
        $list = [];

        foreach ($arguments as $name => $value) {
            if ($value !== null) {
                $value = escapeshellarg($value);

                if (!is_int($name)) {
                    if (substr($name, -1) === '=') {
                        $glue = '';
                    } else {
                        $glue = ' ';
                    }

                    $list[] = escapeshellarg($name) . $glue . $value;
                } else {
                    $list[] = $value;
                }
            } else {
                $list[] = escapeshellarg($name);
            }
        }

        return implode(' ', $list);
    }

    /**
     * Export to PDF
     *
     * @param string $html
     * @return string
     * @throws GuzzleException
     * @throws Exception
     */
    public function toPdf(string $html, array $parameters): string
    {
        switch (true) {
            case $this->remote !== null:
                try {
                    $result = $this->jsonVersion($this->remote[0], $this->remote[1]);
                    $parts = explode('/', $result['webSocketDebuggerUrl']);
                    $pdf = $this->printToPDF(
                        $html,
                        join(':', $this->remote),
                        end($parts),
                        $parameters
                    );
                    break;
                } catch (Exception $e) {
                    if ($this->binary === null) {
                        throw $e;
                    } else {
                        $this->logger->warning(
                            'Failed to connect to remote chrome: %s:%d (%s)',
                            [
                                $this->remote[0],
                                $this->remote[1],
                                $e
                            ]
                        );
                    }
                }

            // Fallback to the local binary if a remote chrome is unavailable
            case $this->binary !== null:
                $browserHome = $this->getTemporaryDirectory()->resolvePath('HOME');
                $commandLine = join(' ', [
                    escapeshellarg($this->getBinary()),
                    static::renderArgumentList([
                        '--bwsi',
                        '--headless',
                        '--disable-gpu',
                        '--no-sandbox',
                        '--no-first-run',
                        '--disable-dev-shm-usage',
                        '--remote-debugging-port=0',
                        '--homedir=' => $browserHome,
                        '--user-data-dir=' => $browserHome
                    ])
                ]);

                switch (PHP_OS_FAMILY) {
                    case 'BSD':
                    case 'Darwin':
                    case 'Solaris':
                    case 'Linux':
                        $this->logger->debug('Starting browser process: HOME=%s exec %s', [$browserHome, $commandLine]);
                        $chrome = new Process('exec ' . $commandLine, null, ['HOME' => $browserHome]);
                        break;
                    case 'Windows':
                        $this->logger->debug('Starting browser process: %s', [$commandLine]);
                        $chrome = new Process($commandLine);
                        break;
                    default:
                        throw new RuntimeException(
                            'Unknown OS detected while starting browser process: ' . PHP_OS . '\''
                        );
                }

                $loop = Loop::get();

                $killer = $loop->addTimer(10, function (TimerInterface $timer) use ($chrome) {
                    $chrome->terminate(6); // SIGABRT
                    $this->logger->error(
                        'Terminated browser process after %d seconds elapsed without the expected output',
                        [$timer->getInterval()]
                    );
                });

                $chrome->start($loop);

                $pdf = null;
                $chrome->stderr->on('data', function ($chunk) use ($html, $parameters, &$pdf, $chrome, $loop, $killer) {
                    $this->logger->debug('Caught browser output: %s', [$chunk]);

                    if (preg_match(self::DEBUG_ADDR_PATTERN, trim($chunk), $matches)) {
                        $loop->cancelTimer($killer);

                        try {
                            $pdf = $this->printToPDF(
                                $html,
                                $matches[1],
                                $matches[2],
                                $parameters
                            );
                        } catch (Exception $e) {
                            $this->logger->error('Failed to print PDF. An error occurred: %s', [$e]);
                        }

                        $chrome->terminate();
                    }
                });

                $chrome->on('exit', function ($exitCode, $termSignal) use ($loop, $killer) {
                    $loop->cancelTimer($killer);

                    $this->logger->debug('Browser terminated by signal %d and exited with code %d', [$termSignal, $exitCode]);
                });

                $loop->run();
        }

        if (empty($pdf)) {
            throw new Exception(
                'Received empty response or none at all from browser.'
                . ' Please check the logs for further details.'
            );
        }

        return $pdf;
    }

    /**
     * @throws Exception
     */
    private function printToPDF(string $html, string $socket, string $browserId, array $parameters)
    {
        $browser = new Client(sprintf('ws://%s/devtools/browser/%s', $socket, $browserId));

        // Open new tab, get its id
        $result = $this->communicate($browser, 'Target.createTarget', [
            'url' => 'about:blank'
        ]);
        if (isset($result['targetId'])) {
            $targetId = $result['targetId'];
        } else {
            throw new Exception('Expected target id. Got instead: ' . json_encode($result));
        }

        $page = new Client(sprintf('ws://%s/devtools/page/%s', $socket, $targetId), ['timeout' => 300]);

        // enable various events
        $this->communicate($page, 'Log.enable');
        $this->communicate($page, 'Network.enable');
        $this->communicate($page, 'Page.enable');

        try {
            $this->communicate($page, 'Console.enable');
        } catch (Exception $_) {
            // Deprecated, might fail
        }

        if (($url = $this->getUrl()) !== null) {
            // Navigate to target
            $result = $this->communicate($page, 'Page.navigate', [
                'url' => $url
            ]);
            if (isset($result['frameId'])) {
                $frameId = $result['frameId'];
            } else {
                throw new Exception('Expected navigation frame. Got instead: ' . json_encode($result));
            }

            // wait for page to fully load
            $this->waitFor($page, 'Page.frameStoppedLoading', ['frameId' => $frameId]);
        } elseif ($html !== '') {
            // If there's no url to load transfer the document's content directly
            $this->communicate($page, 'Page.setDocumentContent', [
                'frameId' => $targetId,
                'html' => $html
            ]);

            // wait for page to fully load
            $this->waitFor($page, 'Page.loadEventFired');
        } else {
            throw new LogicException('Nothing to print');
        }

        // Wait for network activity to finish
        $this->waitFor($page, self::WAIT_FOR_NETWORK);

        // Wait for layout to initialize
        if ($html !== '') {
            // Ensure layout scripts work in the same environment as the pdf printing itself
            $this->communicate($page, 'Emulation.setEmulatedMedia', ['media' => 'print']);

            $this->communicate($page, 'Runtime.evaluate', [
                'timeout' => 1000,
                'expression' => 'setTimeout(() => new Layout().apply(), 0)'
            ]);

            $promisedResult = $this->communicate($page, 'Runtime.evaluate', [
                'awaitPromise' => true,
                'returnByValue' => true,
                'timeout' => 1000, // Failsafe, doesn't apply to `await` it seems
                'expression' => static::WAIT_FOR_LAYOUT
            ]);
            if (isset($promisedResult['exceptionDetails'])) {
                if (isset($promisedResult['exceptionDetails']['exception']['description'])) {
                    $this->logger->error(
                        'PDF layout failed to initialize: %s',
                        [$promisedResult['exceptionDetails']['exception']['description']]
                    );
                } else {
                    $this->logger->warning('PDF layout failed to initialize. Pages might look skewed.');
                }
            }

            // Reset media emulation, this may prevent the real media from coming into effect?
            $this->communicate($page, 'Emulation.setEmulatedMedia', ['media' => '']);
        }

        // print pdf
//        try {
            $result = $this->communicate($page, 'Page.printToPDF', array_merge(
                $parameters,
                ['transferMode' => 'ReturnAsBase64', 'printBackground' => true]
            ));
//            echo "No Exception";exit;
//
//        } catch (\Exception $e) {
//            echo "<pre>"; print_r($e); exit;
////            echo "Exception";exit;
//        }


        if (!empty($result['data'])) {
            $pdf = base64_decode($result['data']);
        } else {
            throw new Exception('Expected base64 data. Got instead: ' . json_encode($result));
        }

        // close tab
        $result = $this->communicate($browser, 'Target.closeTarget', [
            'targetId' => $targetId
        ]);
        if (!isset($result['success'])) {
            throw new Exception('Expected close confirmation. Got instead: ' . json_encode($result));
        }

        try {
            $browser->close();
        } catch (ConnectionException $e) {
            // For some reason, the browser doesn't send a response
            $this->logger->debug('Failed to close browser connection: %s', [$e->getMessage()]);
        }

        return $pdf;
    }

    private function renderApiCall(string $method, array $options = null): string
    {
        $data = [
            'id' => time(),
            'method' => $method,
            'params' => $options ?: []
        ];

        return json_encode($data, JSON_FORCE_OBJECT);
    }

    /**
     * @throws Exception
     */
    private function parseApiResponse(string $payload)
    {
        $data = json_decode($payload, true);
        if (isset($data['method']) || isset($data['result'])) {
            return $data;
        } elseif (isset($data['error'])) {
            throw new Exception(sprintf(
                'Error response (%s): %s',
                $data['error']['code'],
                $data['error']['message']
            ));
        } else {
            throw new Exception(sprintf('Unknown response received: %s', $payload));
        }
    }

    private function registerEvent(string $method, array $params): void
    {
        $this->logger->debug(new DeferredMessage($method, $params));

        if ($method === 'Network.requestWillBeSent') {
            $this->interceptedRequests[$params['requestId']] = $params;
        } elseif ($method === 'Network.loadingFinished') {
            unset($this->interceptedRequests[$params['requestId']]);
        } elseif ($method === 'Network.loadingFailed') {
            $requestData = $this->interceptedRequests[$params['requestId']];
            unset($this->interceptedRequests[$params['requestId']]);

            $this->logger->error(
                'Headless Chrome was unable to complete a request to "%s". Error: %s',
                [
                    $requestData['request']['url'],
                    $params['errorText']
                ]
            );
        } else {
            $this->interceptedEvents[] = ['method' => $method, 'params' => $params];
        }
    }

    /**
     * @throws BadOpcodeException
     */
    private function communicate(Client $ws, string $method, array $parameters = null)
    {
        $this->logger->debug('Transmitting CDP call: %s(%s)', [$method, $parameters ? join(',', array_keys($parameters)) : '']);
        $ws->send($this->renderApiCall($method, $parameters));

        do {
            $response = $this->parseApiResponse($ws->receive());
            $gotEvent = isset($response['method']);

            if ($gotEvent) {
                $this->registerEvent($response['method'], $response['params']);
            }
        } while ($gotEvent);

        $this->logger->debug('Received CDP result: %s',
            [
                empty($response['result'])
                    ? 'none'
                    : join(',', array_keys($response['result']))
            ]
        );

        return $response['result'];
    }

    private function waitFor(Client $ws, string $eventName, array $expectedParams = null): ?array
    {
        if ($eventName !== self::WAIT_FOR_NETWORK) {
            $this->logger->debug(
                'Awaiting CDP event: %s(%s)',
                [
                    $eventName,
                    $expectedParams ? join(',', array_keys($expectedParams)) : ''
                ]
            );
        } elseif (empty($this->interceptedRequests)) {
            return null;
        }

        $wait = true;
        $interceptedPos = -1;

        $params = null;
        do {
            if (isset($this->interceptedEvents[++$interceptedPos])) {
                $response = $this->interceptedEvents[$interceptedPos];
                $intercepted = true;
            } else {
                $response = $this->parseApiResponse($ws->receive());
                $intercepted = false;
            }

            if (isset($response['method'])) {
                $method = $response['method'];
                $params = $response['params'];

                if (!$intercepted) {
                    $this->registerEvent($method, $params);
                }

                if ($eventName === self::WAIT_FOR_NETWORK) {
                    $wait = !empty($this->interceptedRequests);
                } elseif ($method === $eventName) {
                    if ($expectedParams !== null) {
                        $diff = array_intersect_assoc($params, $expectedParams);
                        $wait = empty($diff);
                    } else {
                        $wait = false;
                    }
                }

                if (!$wait && $intercepted) {
                    unset($this->interceptedEvents[$interceptedPos]);
                }
            }
        } while ($wait);

        return $params;
    }

    /**
     * Get the major version number of Chrome or false on failure
     *
     * @return  int|false
     *
     * @throws  Exception|GuzzleException
     */
    public function getVersion()
    {
        switch (true) {
            case $this->remote !== null:
                try {
                    $result = $this->jsonVersion($this->remote[0], $this->remote[1]);
                    $version = $result['Browser'];
                    break;
                } catch (Exception $e) {
                    if ($this->binary === null) {
                        throw $e;
                    } else {
                        $this->logger->warning(
                            'Failed to connect to remote chrome: %s:%d (%s)',
                            [
                                $this->remote[0],
                                $this->remote[1],
                                $e
                            ]
                        );
                    }
                }

            // Fallback to the local binary if a remote chrome is unavailable
            case $this->binary !== null:
                $command = new ShellCommand(
                    escapeshellarg($this->getBinary()) . ' HeadlessChrome.php' . static::renderArgumentList(['--version']),
                    false
                );

                $output = $command->execute();

                if ($command->getExitCode() !== 0) {
                    throw new \Exception($output->stderr);
                }

                $version = $output->stdout;
                break;
            default:
                throw new LogicException('Set a binary or remote first');
        }

        if (preg_match('/(\d+)\.[\d.]+/', $version, $match)) {
            return (int)$match[1];
        }

        return false;
    }

    /**
     * Fetch result from the /json/version API endpoint
     *
     * @param string $host
     * @param int $port
     *
     * @return bool|array
     * @throws GuzzleException
     */
    protected function jsonVersion(string $host, int $port)
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', sprintf('http://%s:%s/json/version', $host, $port));

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        return json_decode($response->getBody(), true);
    }
}
