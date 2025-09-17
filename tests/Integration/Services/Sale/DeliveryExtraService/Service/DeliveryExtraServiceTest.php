<?php

/**
 * This file is part of the bitrix24-php-sdk package.
 *
 * © Sally Fancen <vadimsallee@gmail.com>
 *
 * For the full copyright and license information, please view the MIT-LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bitrix24\SDK\Tests\Integration\Services\Sale\DeliveryExtraService\Service;

use Bitrix24\SDK\Core\Exceptions\BaseException;
use Bitrix24\SDK\Core\Exceptions\TransportException;
use Bitrix24\SDK\Services\Sale\DeliveryExtraService\Service\DeliveryExtraService;
use Bitrix24\SDK\Services\Sale\Delivery\Service\Delivery;
use Bitrix24\SDK\Services\Sale\DeliveryHandler\Service\DeliveryHandler;
use Bitrix24\SDK\Tests\Integration\Fabric;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

/**
 * Class DeliveryExtraServiceTest
 *
 * @package Bitrix24\SDK\Tests\Integration\Services\Sale\DeliveryExtraService\Service
 */
#[CoversMethod(DeliveryExtraService::class,'add')]
#[CoversMethod(DeliveryExtraService::class,'update')]
#[CoversMethod(DeliveryExtraService::class,'get')]
#[CoversMethod(DeliveryExtraService::class,'delete')]
#[\PHPUnit\Framework\Attributes\CoversClass(\Bitrix24\SDK\Services\Sale\DeliveryExtraService\Service\DeliveryExtraService::class)]
class DeliveryExtraServiceTest extends TestCase
{
    protected DeliveryExtraService $deliveryExtraService;
    protected Delivery $deliveryService;
    protected DeliveryHandler $deliveryHandlerService;
    protected ?int $testHandlerId = null;
    protected ?int $testDeliveryId = null;

    protected function setUp(): void
    {
        $this->deliveryExtraService = Fabric::getServiceBuilder()->getSaleScope()->deliveryExtraService();
        $this->deliveryService = Fabric::getServiceBuilder()->getSaleScope()->delivery();
        $this->deliveryHandlerService = Fabric::getServiceBuilder()->getSaleScope()->deliveryHandler();
        
        // Create a test delivery handler and delivery service for our tests
        $this->createTestDeliveryHandler();
        $this->createTestDeliveryService();
    }

    protected function tearDown(): void
    {
        // Clean up test delivery service and handler
        if ($this->testDeliveryId !== null) {
            try {
                $this->deliveryService->delete($this->testDeliveryId);
            } catch (\Exception) {
                // Ignore cleanup errors
            }
        }
        
        if ($this->testHandlerId !== null) {
            try {
                $this->deliveryHandlerService->delete($this->testHandlerId);
            } catch (\Exception) {
                // Ignore cleanup errors
            }
        }
    }

    /**
     * Create a test delivery handler that we can use for delivery service tests
     */
    protected function createTestDeliveryHandler(): void
    {
        $handlerFields = [
            'NAME' => 'Test Delivery Handler for Extra Service',
            'CODE' => 'test_delivery_handler_extra_' . time(),
            'SORT' => 100,
            'DESCRIPTION' => 'Test delivery handler for delivery extra service tests',
            'SETTINGS' => [
                'CALCULATE_URL' => 'https://example.com/calculate',
                'CREATE_DELIVERY_REQUEST_URL' => 'https://example.com/create',
                'CANCEL_DELIVERY_REQUEST_URL' => 'https://example.com/cancel',
                'HAS_CALLBACK_TRACKING_SUPPORT' => 'Y',
                'CONFIG' => [
                    [
                        'TYPE' => 'STRING',
                        'CODE' => 'API_KEY',
                        'NAME' => 'API Key'
                    ]
                ]
            ],
            'PROFILES' => [
                [
                    'NAME' => 'Standard',
                    'CODE' => 'STANDARD',
                    'DESCRIPTION' => 'Standard delivery profile'
                ]
            ]
        ];

        $addedItemResult = $this->deliveryHandlerService->add($handlerFields);
        $this->testHandlerId = $addedItemResult->getId();
    }

    /**
     * Create a test delivery service for extra service tests
     */
    protected function createTestDeliveryService(): void
    {
        // Get the handler list to find our test handler code
        $deliveryHandlersResult = $this->deliveryHandlerService->list();
        $handlers = $deliveryHandlersResult->getDeliveryHandlers();

        $testHandlerCode = null;
        foreach ($handlers as $handler) {
            if ((int)$handler->ID === $this->testHandlerId) {
                $testHandlerCode = $handler->CODE;
                break;
            }
        }

        $deliveryFields = [
            'REST_CODE' => $testHandlerCode,
            'NAME' => 'Test Delivery for Extra Service',
            'CURRENCY' => 'USD',
            'DESCRIPTION' => 'Test delivery service for extra service tests',
            'SORT' => 500,
            'ACTIVE' => 'Y',
            'CONFIG' => [
                [
                    'CODE' => 'API_KEY',
                    'VALUE' => 'test_api_key_123'
                ]
            ]
        ];

        $addedDeliveryResult = $this->deliveryService->add($deliveryFields);
        $this->testDeliveryId = $addedDeliveryResult->getId();
    }

    /**
     * Get sample delivery extra service fields for testing (checkbox type)
     */
    protected function getSampleCheckboxExtraServiceFields(): array
    {
        return [
            'DELIVERY_ID' => $this->testDeliveryId,
            'TYPE' => 'checkbox',
            'NAME' => 'Door Delivery',
            'ACTIVE' => 'Y',
            'CODE' => 'door_delivery_' . time(),
            'SORT' => 100,
            'DESCRIPTION' => 'Door delivery extra service',
            'PRICE' => 99.99
        ];
    }

    /**
     * Get sample delivery extra service fields for testing (enum type)
     */
    protected function getSampleEnumExtraServiceFields(): array
    {
        return [
            'DELIVERY_ID' => $this->testDeliveryId,
            'TYPE' => 'enum',
            'NAME' => 'Cargo Type',
            'ACTIVE' => 'Y',
            'CODE' => 'cargo_type_' . time(),
            'SORT' => 200,
            'DESCRIPTION' => 'Cargo type selection',
            'ITEMS' => [
                [
                    'TITLE' => 'Small Package',
                    'CODE' => 'small_package',
                    'PRICE' => 129.99
                ],
                [
                    'TITLE' => 'Documents',
                    'CODE' => 'documents',
                    'PRICE' => 69.99
                ]
            ]
        ];
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    public function testAddCheckboxType(): void
    {
        $extraServiceFields = $this->getSampleCheckboxExtraServiceFields();

        $addResult = $this->deliveryExtraService->add($extraServiceFields);
        
        $extraServiceId = $addResult->getId();
        self::assertIsInt($extraServiceId);
        self::assertGreaterThan(0, $extraServiceId);

        // Clean up
        $this->deliveryExtraService->delete($extraServiceId);
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    public function testAddEnumType(): void
    {
        $extraServiceFields = $this->getSampleEnumExtraServiceFields();

        $addResult = $this->deliveryExtraService->add($extraServiceFields);
        
        $extraServiceId = $addResult->getId();
        self::assertIsInt($extraServiceId);
        self::assertGreaterThan(0, $extraServiceId);

        // Clean up
        $this->deliveryExtraService->delete($extraServiceId);
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    public function testUpdate(): void
    {
        // Create an extra service first
        $extraServiceFields = $this->getSampleCheckboxExtraServiceFields();
        $addResult = $this->deliveryExtraService->add($extraServiceFields);
        $extraServiceId = $addResult->getId();

        // Update the extra service
        $updateFields = [
            'NAME' => 'Updated Door Delivery',
            'DESCRIPTION' => 'Updated door delivery description',
            'PRICE' => 149.99,
            'ACTIVE' => 'N'
        ];

        $updateResult = $this->deliveryExtraService->update($extraServiceId, $updateFields);
        self::assertTrue($updateResult->isSuccess());

        // Clean up
        $this->deliveryExtraService->delete($extraServiceId);
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    public function testGet(): void
    {
        // Create extra services first
        $checkboxFields = $this->getSampleCheckboxExtraServiceFields();
        $addCheckboxResult = $this->deliveryExtraService->add($checkboxFields);
        $checkboxServiceId = $addCheckboxResult->getId();

        $enumFields = $this->getSampleEnumExtraServiceFields();
        $addEnumResult = $this->deliveryExtraService->add($enumFields);
        $enumServiceId = $addEnumResult->getId();

        // Get extra services for our delivery
        $getResult = $this->deliveryExtraService->get($this->testDeliveryId);

        $extraServices = $getResult->getDeliveryExtraServices();
        self::assertIsArray($extraServices);
        self::assertGreaterThanOrEqual(2, count($extraServices));

        // Verify our services are in the list
        $foundCheckbox = false;
        $foundEnum = false;
        
        foreach ($extraServices as $service) {
            if ((int)$service->ID === $checkboxServiceId) {
                self::assertEquals('checkbox', $service->TYPE);
                self::assertEquals('Door Delivery', $service->NAME);
                self::assertEquals(99.99, (float)$service->PRICE);
                $foundCheckbox = true;
            }
            
            if ((int)$service->ID === $enumServiceId) {
                self::assertEquals('enum', $service->TYPE);
                self::assertEquals('Cargo Type', $service->NAME);
                self::assertIsArray($service->ITEMS);
                self::assertGreaterThanOrEqual(2, count($service->ITEMS));
                $foundEnum = true;
            }
        }

        self::assertTrue($foundCheckbox, 'Checkbox service should be found in the list');
        self::assertTrue($foundEnum, 'Enum service should be found in the list');

        // Clean up
        $this->deliveryExtraService->delete($checkboxServiceId);
        $this->deliveryExtraService->delete($enumServiceId);
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    public function testDelete(): void
    {
        // Create an extra service first
        $extraServiceFields = $this->getSampleCheckboxExtraServiceFields();
        $addResult = $this->deliveryExtraService->add($extraServiceFields);
        $extraServiceId = $addResult->getId();

        // Delete the extra service
        $deleteResult = $this->deliveryExtraService->delete($extraServiceId);
        self::assertTrue($deleteResult->isSuccess());

        // Verify it's deleted by checking the list
        $getResult = $this->deliveryExtraService->get($this->testDeliveryId);
        $extraServices = $getResult->getDeliveryExtraServices();
        
        foreach ($extraServices as $service) {
            self::assertNotEquals($extraServiceId, (int)$service->ID, 'Deleted service should not be in the list');
        }
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    public function testCompleteWorkflow(): void
    {
        // 1. Add an enum type extra service
        $enumFields = $this->getSampleEnumExtraServiceFields();
        $addResult = $this->deliveryExtraService->add($enumFields);
        $extraServiceId = $addResult->getId();

        // 2. Update the extra service
        $updateFields = [
            'NAME' => 'Updated Cargo Type',
            'DESCRIPTION' => 'Updated cargo type description',
            'ITEMS' => [
                [
                    'TITLE' => 'Small Package Updated',
                    'CODE' => 'small_package',
                    'PRICE' => 139.99
                ],
                [
                    'TITLE' => 'Large Package',
                    'CODE' => 'large_package',
                    'PRICE' => 199.99
                ]
            ]
        ];

        $updateResult = $this->deliveryExtraService->update($extraServiceId, $updateFields);
        self::assertTrue($updateResult->isSuccess());

        // 3. Get and verify the updated service
        $getResult = $this->deliveryExtraService->get($this->testDeliveryId);
        $extraServices = $getResult->getDeliveryExtraServices();
        
        $foundService = null;
        foreach ($extraServices as $service) {
            if ((int)$service->ID === $extraServiceId) {
                $foundService = $service;
                break;
            }
        }

        self::assertNotNull($foundService, 'Updated service should be found');
        self::assertEquals('Updated Cargo Type', $foundService->NAME);
        self::assertEquals('Updated cargo type description', $foundService->DESCRIPTION);
        self::assertIsArray($foundService->ITEMS);
        self::assertCount(2, $foundService->ITEMS);

        // 4. Delete the service
        $deleteResult = $this->deliveryExtraService->delete($extraServiceId);
        self::assertTrue($deleteResult->isSuccess());
    }
}