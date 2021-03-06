<?php
namespace GetnetTests\Api\Requests;

use Getnet\Api\Card;
use ReflectionObject;
use Getnet\Api\Seller;
use Getnet\Api\Customer;
use Getnet\Api\TokenCard;
use Getnet\Api\VaultCard;
use Getnet\Api\Environment;
use Getnet\Api\Authentication;
use PHPUnit\Framework\TestCase;
use Getnet\Api\Requests\TokenCardRequest;
use Getnet\Api\Requests\VaultCardRequest;
use Getnet\Api\Exceptions\GetnetException;

class VaultCardRequestTest extends TestCase
{
    private $data;
    private $card;
    private $seller;
    private $customer;
    private $tokenCard;
    private $environment;
    private $authentication;

    protected function setUp(): void
    {
        $this->data = $GLOBALS['configs'];
        $this->environment = new Environment();
        $this->seller = new Seller($this->data['seller']['client_id'], $this->data['seller']['secret_id'], $this->data['seller']['seller_id']);
        $this->authentication = new Authentication($this->seller);
        $this->customer = new Customer();
        $this->customer->setCustomerId($this->data['customer']['customer_id']);
        $this->card = new Card($this->customer);
        $this->card->setCardNumber($this->data['card']['card_number']);
        $this->card->setExpirationMonth($this->data['card']['expiration_month']);
        $this->card->setExpirationYear($this->data['card']['expiration_year']);
        $this->vaultCard = new VaultCard();
    }

    protected function tearDown(): void
    {
        unset($this->data);
        unset($this->card);
        unset($this->seller);
        unset($this->customer);
        unset($this->vaultCard);
        unset($this->environment);
        unset($this->authentication);
    }

    public function testPostVaultCard()
    {
        $testedClass = $this->getMockForAbstractClass(VaultCardRequest::class, [
            $this->vaultCard,
            $this->authentication,
            $this->environment,
        ]);

        $reflector = new ReflectionObject($testedClass);

        $postVaultCard = $reflector->getMethod('postVaultCard');

        $return = $postVaultCard->invokeArgs($testedClass, [
            $this->getTokenCard(),
        ]);

        self::assertNotNull($return);
        self::assertArrayHasKey('card_id', $return);
        self::assertArrayHasKey('number_token', $return);
        self::assertEquals(201, $return['status_code']);
        self::assertEquals(36, strlen($return['card_id']));
        self::assertEquals(128, strlen($return['number_token']));

        return $return['card_id'];
    }

    public function testPostVaultCardNewAccessToken()
    {
        $authentication = new Authentication($this->seller);
        $authentication->setAuthorization([]);

        $testedClass = $this->getMockForAbstractClass(VaultCardRequest::class, [
            $this->vaultCard,
            $authentication,
            $this->environment,
        ]);

        $reflector = new ReflectionObject($testedClass);

        $postVaultCard = $reflector->getMethod('postVaultCard');

        $return = $postVaultCard->invokeArgs($testedClass, [
            $this->getTokenCard(),
        ]);

        self::assertNotNull($return);
        self::assertArrayHasKey('card_id', $return);
        self::assertArrayHasKey('number_token', $return);
        self::assertEquals(201, $return['status_code']);
        self::assertEquals(36, strlen($return['card_id']));
        self::assertEquals(128, strlen($return['number_token']));
    }

    public function testGetVaultCard()
    {
        $testedClass = $this->getMockForAbstractClass(VaultCardRequest::class, [
            $this->vaultCard,
            $this->authentication,
            $this->environment,
        ]);

        $reflector = new ReflectionObject($testedClass);

        $getVaultCard = $reflector->getMethod('getVaultCard');

        $return = $getVaultCard->invokeArgs($testedClass, [
            $this->data['customer']['customer_id']
        ]);

        self::assertNotNull($return);
        self::assertIsArray($return->getCards());
        self::assertCount(1, $return->getCards());
        self::assertInstanceOf(VaultCard::class, $return);
    }

    public function testGetVaultCardStatus()
    {
        $testedClass = $this->getMockForAbstractClass(VaultCardRequest::class, [
            $this->vaultCard,
            $this->authentication,
            $this->environment,
        ]);

        $reflector = new ReflectionObject($testedClass);

        $getVaultCard = $reflector->getMethod('getVaultCard');

        $return = $getVaultCard->invokeArgs($testedClass, [
            $this->data['customer']['customer_id'],
            $this->data['vaultCardRequest']['status']['active'],
        ]);

        self::assertNotNull($return);
        self::assertIsArray($return->getCards());
        self::assertCount(1, $return->getCards());
        self::assertArrayHasKey('status', $return->getCards()[0]);
        self::assertInstanceOf(VaultCard::class, $return);
    }

    public function testGetVaultCardCustomerIdEmpty()
    {
        $this->expectException(GetnetException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Customer ID required.');

        $testedClass = $this->getMockForAbstractClass(VaultCardRequest::class, [
            $this->vaultCard,
            $this->authentication,
            $this->environment,
        ]);

        $reflector = new ReflectionObject($testedClass);

        $getVaultCard = $reflector->getMethod('getVaultCard');

        $getVaultCard->invokeArgs($testedClass, [
            '',
            $this->data['vaultCardRequest']['status']['all'],
        ]);
    }

    /**
     * @depends testPostVaultCard
     */
    public function testGetVaultCardId($cardId)
    {
        $testedClass = $this->getMockForAbstractClass(VaultCardRequest::class, [
            $this->vaultCard,
            $this->authentication,
            $this->environment,
        ]);

        $reflector = new ReflectionObject($testedClass);

        $getVaultCard = $reflector->getMethod('getVaultCard');

        $return = $getVaultCard->invokeArgs($testedClass, [
            '',
            '',
            $cardId,
        ]);

        self::assertNotNull($return);
        self::assertIsArray($return->getCards());
        self::assertEquals($cardId, $return->getCards()['card_id']);
        self::assertInstanceOf(VaultCard::class, $return);
    }

    /**
     * @depends testPostVaultCard
     */
    public function testDeleteVaultCardId($cardId)
    {
        $testedClass = $this->getMockForAbstractClass(VaultCardRequest::class, [
            $this->vaultCard,
            $this->authentication,
            $this->environment,
        ]);

        $reflector = new ReflectionObject($testedClass);

        $deleteVaultCard = $reflector->getMethod('deleteVaultCard');

        $return = $deleteVaultCard->invokeArgs($testedClass, [
            $cardId,
        ]);

        self::assertEquals(204, $return);
    }

    private function getTokenCard()
    {
        $testedClass = $this->getMockForAbstractClass(TokenCardRequest::class, [
            $this->authentication,
            $this->environment,
        ]);

        $reflector = new ReflectionObject($testedClass);

        $method = $reflector->getMethod('getTokenCard');

        $tokenCard = $method->invokeArgs($testedClass, [
            $this->card,
        ]);

        return $tokenCard;
    }
}