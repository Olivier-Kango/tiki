<?php
namespace Tiki\Tests\Xmlrpc;
use UsersLib;
use PHPUnit\Framework\TestCase;
use PhpXmlRpc\Value as XML_RPC_Value;
use PhpXmlRpc\Request as XML_RPC_Message;
use PhpXmlRpc\Client as XML_RPC_Client;
use PhpXmlRpc\Response as XML_RPC_Response;

class IntervalidateTest extends TestCase
{
    // A mock XML_RPC_Client object
    protected $client;

    // A mock XML_RPC_Message object
    protected $message;

    // A mock XML_RPC_Response object
    protected $response;

    // The intervalidate object to test
    protected $intervalidate;

    // The remote server parameters
    protected $remote;

    // The user credentials
    protected $user;
    protected $pass;

    // The hashkey
    protected $hashkey;

    // The get_info flag
    protected $get_info;

    // Set up the test environment
    protected function setUp(): void
    {
        // Create a mock XML_RPC_Client object
        $this->client = $this->createMock(XML_RPC_Client::class);

        // Create a mock XML_RPC_Message object
        $this->message = $this->createMock(XML_RPC_Message::class);

        // Create a mock XML_RPC_Response object
        $this->response = $this->createMock(XML_RPC_Response::class);

        // Set the remote server parameters
        $this->remote = [
            'host' => 'https://example.com',
            'path' => '/remote.php',
            'port' => 443,
        ];

        // Set the user credentials
        $this->user = 'testuser';
        $this->pass = '123456';

        // Set the hashkey
        $this->hashkey = '5fb167eb27df46e09fdc4a576c0254f0f2053fab';

        // Set the get_info flag
        $this->get_info = false;
    }
    
    // This test method initializes xmlrpc client, send a request to one of the services and check the response
    public function testXmlrpcClient()
    {
        // Initialize an xmlrpc client object
        $protocol = stripos($this->remote['host'], 'https') === 0 ? 'https' : 'http';
        $this->remote['path'] = preg_replace('/^\/?/', '/', $this->remote['path']);
        $this->remote['host'] = parse_url($this->remote['host'], PHP_URL_HOST);
        $client = new XML_RPC_Client($this->remote['path'], $this->remote['host'], $this->remote['port'], $protocol);
        
        // Create an xmlrpc message object with the method name and parameters
        $message = new XML_RPC_Message(
            'intertiki.validate',
            [
                new XML_RPC_Value($this->hashkey, 'string'),
                new XML_RPC_Value($this->user, 'string'),
                new XML_RPC_Value($this->pass, 'string'),
                new XML_RPC_Value($this->get_info, 'boolean'),
                new XML_RPC_Value($this->hashkey, 'string')
            ]
        );
        
        // Send the request to the server and get the response
        $response = $client->send($message);
        
        // Check if the response is valid and has no errors
        $this->assertTrue($response && !$response->faultCode());
    }
}