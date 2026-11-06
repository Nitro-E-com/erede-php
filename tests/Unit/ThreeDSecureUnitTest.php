<?php

namespace Rede\Tests\Unit;

use Rede\BillingAddress;
use Rede\Device;
use Rede\Tests\BaseTestCase;
use Rede\ThreeDSecure;
use Rede\Transaction;

class ThreeDSecureUnitTest extends BaseTestCase
{
    public function testShouldSerializeBillingAddress(): void
    {
        $billing = new BillingAddress(
            address: 'Rua Pedro Luiz',
            city: 'Guarulhos',
            postalcode: '07151-385',
            state: 'SP',
            country: 'Brasil',
            emailAddress: 'email@user.com',
            phoneNumber: '(11)91234-5678',
        );

        $serialized = $billing->jsonSerialize();

        $this->assertSame('Rua Pedro Luiz', $serialized['address']);
        $this->assertSame('Guarulhos', $serialized['city']);
        $this->assertSame('07151-385', $serialized['postalcode']);
        $this->assertSame('SP', $serialized['state']);
        $this->assertSame('Brasil', $serialized['country']);
        $this->assertSame('email@user.com', $serialized['emailAddress']);
        $this->assertSame('(11)91234-5678', $serialized['phoneNumber']);
    }

    public function testShouldSerializeThreeDSecureWithBilling(): void
    {
        $billing = new BillingAddress(
            address: 'Rua Pedro Luiz',
            city: 'Guarulhos',
            postalcode: '07151-385',
            state: 'SP',
            country: 'Brasil',
            emailAddress: 'email@user.com',
            phoneNumber: '(11)91234-5678',
        );

        $threeDSecure = new ThreeDSecure(
            new Device(
                colorDepth: 1,
                deviceType3ds: 'BROWSER',
                javaEnabled: false,
                language: 'BR',
                screenHeight: 500,
                screenWidth: 500,
                timeZoneOffset: 3,
            ),
        );
        $threeDSecure->setBilling($billing);

        $serialized = json_decode(json_encode($threeDSecure) ?: '', true);

        $this->assertTrue($serialized['embedded']);
        $this->assertIsArray($serialized['billing']);
        $this->assertSame('Rua Pedro Luiz', $serialized['billing']['address']);
        $this->assertSame('Guarulhos', $serialized['billing']['city']);
        $this->assertSame('07151-385', $serialized['billing']['postalcode']);
        $this->assertSame('SP', $serialized['billing']['state']);
        $this->assertSame('Brasil', $serialized['billing']['country']);
        $this->assertSame('email@user.com', $serialized['billing']['emailAddress']);
        $this->assertSame('(11)91234-5678', $serialized['billing']['phoneNumber']);
    }

    public function testShouldUnserializeThreeDSecureWithBilling(): void
    {
        $json = '
            {
                "embedded": true,
                "onFailure": "decline",
                "userAgent": "SDK/1.0",
                "billing": {
                    "address": "Rua Pedro Luiz",
                    "city": "Guarulhos",
                    "postalcode": "07151-385",
                    "state": "SP",
                    "country": "Brasil",
                    "emailAddress": "email@user.com",
                    "phoneNumber": "(11)91234-5678"
                }
            }
        ';

        $threeDSecure = (new ThreeDSecure())->populate(json_decode($json));

        $this->assertTrue($threeDSecure->isEmbedded());
        $this->assertInstanceOf(BillingAddress::class, $threeDSecure->getBilling());
        $this->assertSame('Rua Pedro Luiz', $threeDSecure->getBilling()->getAddress());
        $this->assertSame('Guarulhos', $threeDSecure->getBilling()->getCity());
        $this->assertSame('07151-385', $threeDSecure->getBilling()->getPostalcode());
        $this->assertSame('SP', $threeDSecure->getBilling()->getState());
        $this->assertSame('Brasil', $threeDSecure->getBilling()->getCountry());
        $this->assertSame('email@user.com', $threeDSecure->getBilling()->getEmailAddress());
        $this->assertSame('(11)91234-5678', $threeDSecure->getBilling()->getPhoneNumber());
    }

    public function testShouldAcceptBillingInTransactionThreeDSecure(): void
    {
        $billing = new BillingAddress(
            address: 'Rua Pedro Luiz',
            city: 'Guarulhos',
            postalcode: '07151-385',
            state: 'SP',
            country: 'Brasil',
            emailAddress: 'email@user.com',
            phoneNumber: '(11)91234-5678',
        );

        $transaction = (new Transaction(25, 'ref-3ds'))
            ->debitCard('5277696455399733', '123', '01', 2030, 'John Snow')
            ->threeDSecure(
                new Device(
                    colorDepth: 1,
                    deviceType3ds: 'BROWSER',
                    javaEnabled: false,
                    language: 'BR',
                    screenHeight: 500,
                    screenWidth: 500,
                    timeZoneOffset: 3,
                ),
                billing: $billing,
            );

        $serialized = json_decode(json_encode($transaction) ?: '', true);

        $this->assertIsArray($serialized['threeDSecure']['billing']);
        $this->assertSame('07151-385', $serialized['threeDSecure']['billing']['postalcode']);
    }

    public function testShouldSerializeResponseModeAndIpAddress(): void
    {
        $transaction = (new Transaction(25, 'ref-3ds'))
            ->debitCard('5277696455399733', '123', '01', 2030, 'John Snow')
            ->threeDSecure(
                new Device(deviceType3ds: 'BROWSER'),
                ThreeDSecure::DECLINE_ON_FAILURE,
                userAgent: 'Browser/1.0',
            );

        $threeDSecure = $transaction->getThreeDSecure();
        $threeDSecure
            ->setIpAddress('192.0.2.1')
            ->setResponseMode('event');

        $serialized = json_decode(json_encode($transaction) ?: '', true);

        $this->assertSame('192.0.2.1', $serialized['threeDSecure']['ipAddress']);
        $this->assertSame('event', $serialized['threeDSecure']['responseMode']);
    }

    public function testShouldNotSerializeResponseModeWhenNotSet(): void
    {
        $threeDSecure = new ThreeDSecure(new Device(deviceType3ds: 'BROWSER'));

        $serialized = $threeDSecure->jsonSerialize();

        $this->assertNull($threeDSecure->getResponseMode());
        $this->assertArrayNotHasKey('responseMode', $serialized);
    }

    public function testShouldUnserializeResponseMode(): void
    {
        $threeDSecure = (new ThreeDSecure())->populate(
            json_decode('{"responseMode": "event", "ipAddress": "192.0.2.1"}')
        );

        $this->assertSame('event', $threeDSecure->getResponseMode());
        $this->assertSame('192.0.2.1', $threeDSecure->getIpAddress());
    }

    public function testShouldDeclareThreeDSecureCallbackUrlKind(): void
    {
        $this->assertSame('threeDSecureCallback', Url::THREE_D_SECURE_CALLBACK);

        $transaction = (new Transaction(25, 'ref-3ds'))
            ->addUrl('https://redirecturl.com/3ds/callback', Url::THREE_D_SECURE_CALLBACK);

        $serialized = json_decode(json_encode($transaction) ?: '', true);

        $this->assertContains(
            ['url' => 'https://redirecturl.com/3ds/callback', 'kind' => 'threeDSecureCallback'],
            $serialized['urls']
        );
    }
}
